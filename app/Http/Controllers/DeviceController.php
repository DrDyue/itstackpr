<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\DeviceType;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use App\Support\DeviceAssetManager;
use App\Support\RuntimeSchemaBootstrapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;

class DeviceController extends Controller
{
    private const STATUSES = [Device::STATUS_ACTIVE, Device::STATUS_REPAIR, Device::STATUS_WRITEOFF];

    private const REQUEST_TYPES = ['repair', 'writeoff', 'transfer'];

    private const DEFAULT_WAREHOUSE_ROOM_NAME = 'Noliktava';

    private const DEFAULT_WAREHOUSE_ROOM_NUMBER_PREFIX = 'NOL-';

    private const DEFAULT_BUILDING_NAME = 'Ludzes novada pasvaldiba';

    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $canManageDevices = $user->canManageRequests();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'assigned_to_id' => trim((string) $request->query('assigned_to_id', '')),
            'assigned_to_query' => trim((string) $request->query('assigned_to_query', '')),
            'floor' => trim((string) $request->query('floor', '')),
            'floor_query' => trim((string) $request->query('floor_query', '')),
            'room_query' => trim((string) $request->query('room_query', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
            'type' => trim((string) $request->query('type', '')),
            'type_query' => trim((string) $request->query('type_query', '')),
            'statuses' => collect($request->query('status', []))
                ->map(fn (mixed $status) => trim((string) $status))
                ->filter(fn (string $status) => in_array($status, self::STATUSES, true))
                ->unique()
                ->values()
                ->all(),
            'request_types' => collect($request->query('request_type', []))
                ->map(fn (mixed $type) => trim((string) $type))
                ->filter(fn (string $type) => in_array($type, self::REQUEST_TYPES, true))
                ->unique()
                ->values()
                ->all(),
        ];

        $filters['has_status_filter'] = count($filters['statuses']) > 0 && count($filters['statuses']) < count(self::STATUSES);
        $filters['has_request_type_filter'] = count($filters['request_types']) > 0 && count($filters['request_types']) < count(self::REQUEST_TYPES);

        $summaryQuery = $this->visibleDevicesQuery($user);
        $accessibleRooms = $this->accessibleRooms($user);
        $types = DeviceType::query()->orderBy('type_name')->get();
        $selectedRoom = null;
        $selectedType = null;
        $selectedAssignedUser = null;
        if (ctype_digit($filters['room_id'])) {
            $selectedRoom = $accessibleRooms->firstWhere('id', (int) $filters['room_id']);
        }
        if (ctype_digit($filters['type'])) {
            $selectedType = $types->firstWhere('id', (int) $filters['type']);
        }
        if ($canManageDevices && ctype_digit($filters['assigned_to_id'])) {
            $selectedAssignedUser = User::query()->find((int) $filters['assigned_to_id']);
        }

        if ($selectedRoom) {
            $filters['floor'] = (string) $selectedRoom->floor_number;
            $filters['room_query'] = $selectedRoom->room_number.($selectedRoom->room_name ? ' - '.$selectedRoom->room_name : '');
        }
        if ($selectedType) {
            $filters['type_query'] = $selectedType->type_name;
        }
        if ($selectedAssignedUser) {
            $filters['assigned_to_query'] = $selectedAssignedUser->full_name;
        }

        if ($filters['floor'] !== '') {
            $filters['floor_query'] = $filters['floor'].'. stavs';
        }

        $maxFloor = (int) ($accessibleRooms->max('floor_number') ?? 0);
        $floorOptions = $maxFloor > 0 ? range(1, $maxFloor) : [];
        $roomOptions = $accessibleRooms
            ->when(
                $filters['floor'] !== '' && ctype_digit($filters['floor']),
                fn (Collection $rooms) => $rooms->filter(fn (Room $room) => (int) $room->floor_number === (int) $filters['floor'])
            )
            ->values();

        $devices = $this->visibleDevicesQuery($user)
            ->with([
                'type',
                'building',
                'room.building',
                'activeRepair.acceptedBy',
                'activeRepair.request.responsibleUser',
                'activeRepair.request.reviewedBy',
                'latestRepair.acceptedBy',
                'latestRepair.request.responsibleUser',
                'latestRepair.request.reviewedBy',
                'assignedTo',
                'createdBy',
                'pendingRepairRequest.responsibleUser',
                'pendingWriteoffRequest.responsibleUser',
                'pendingTransferRequest.responsibleUser',
                'pendingTransferRequest.transferTo',
            ])
            ->withExists([
                'repairRequests as has_pending_repair_request' => fn (Builder $query) => $query->where('status', RepairRequest::STATUS_SUBMITTED),
                'writeoffRequests as has_pending_writeoff_request' => fn (Builder $query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED),
                'transfers as has_pending_transfer_request' => fn (Builder $query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED),
            ])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $deviceQuery) use ($term) {
                    $deviceQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('manufacturer', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($filters['code'] !== '', fn (Builder $query) => $query->where('code', 'like', '%'.$filters['code'].'%'))
            ->when($selectedAssignedUser instanceof User, fn (Builder $query) => $query->where('assigned_to_id', $selectedAssignedUser->id))
            ->when(! ($selectedAssignedUser instanceof User) && $user->canManageRequests() && $filters['assigned_to_query'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['assigned_to_query'];

                $query->whereHas('assignedTo', fn (Builder $userQuery) => $userQuery->where('full_name', 'like', "%{$term}%"));
            })
            ->when($filters['floor'] !== '' && ctype_digit($filters['floor']), function (Builder $query) use ($filters) {
                $query->whereHas('room', fn (Builder $roomQuery) => $roomQuery->where('floor_number', (int) $filters['floor']));
            })
            ->when($filters['floor'] === '' && $filters['floor_query'] !== '', function (Builder $query) use ($filters) {
                $normalizedFloor = preg_replace('/\D+/', '', $filters['floor_query']);

                if (! is_string($normalizedFloor) || $normalizedFloor === '' || ! ctype_digit($normalizedFloor)) {
                    return;
                }

                $query->whereHas('room', fn (Builder $roomQuery) => $roomQuery->where('floor_number', (int) $normalizedFloor));
            })
            ->when($selectedRoom instanceof Room, fn (Builder $query) => $query->where('room_id', $selectedRoom->id))
            ->when(! ($selectedRoom instanceof Room) && $filters['room_query'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['room_query'];

                $query->whereHas('room', function (Builder $roomQuery) use ($term) {
                    $roomQuery->where('room_number', 'like', "%{$term}%")
                        ->orWhere('room_name', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%");
                });
            })
            ->when($selectedType instanceof DeviceType, fn (Builder $query) => $query->where('device_type_id', $selectedType->id))
            ->when(! ($selectedType instanceof DeviceType) && $filters['type_query'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['type_query'];

                $query->whereHas('type', function (Builder $typeQuery) use ($term) {
                    $typeQuery->where('type_name', 'like', "%{$term}%")
                        ->orWhere('category', 'like', "%{$term}%");
                });
            })
            ->when(
                $filters['has_status_filter'],
                fn (Builder $query) => $query->whereIn('status', $filters['statuses'])
            )
            ->when(
                $filters['has_request_type_filter'],
                function (Builder $query) use ($filters) {
                    $query->where(function (Builder $requestQuery) use ($filters) {
                        foreach (collect($filters['request_types'])->values() as $index => $type) {
                            $method = $index === 0 ? 'whereHas' : 'orWhereHas';

                            match ($type) {
                                'repair' => $requestQuery->{$method}('pendingRepairRequest'),
                                'writeoff' => $requestQuery->{$method}('pendingWriteoffRequest'),
                                'transfer' => $requestQuery->{$method}('pendingTransferRequest'),
                                default => null,
                            };
                        }
                    });
                }
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $deviceStates = $devices->getCollection()
            ->mapWithKeys(function (Device $device) use ($user) {
                $requestAvailability = $this->requestAvailabilityForDevice(
                    $device,
                    (bool) ($device->has_pending_repair_request ?? false),
                    (bool) ($device->has_pending_writeoff_request ?? false),
                    (bool) ($device->has_pending_transfer_request ?? false),
                );

                return [
                    $device->id => [
                        'requestAvailability' => $requestAvailability,
                        'pendingRequestBadge' => $this->pendingRequestBadge(
                            $device,
                            $user->canManageRequests(),
                            (bool) ($device->has_pending_repair_request ?? false),
                            (bool) ($device->has_pending_writeoff_request ?? false),
                            (bool) ($device->has_pending_transfer_request ?? false),
                            $device->pendingRepairRequest,
                            $device->pendingWriteoffRequest,
                            $device->pendingTransferRequest,
                        ),
                        'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
                        'repairPreview' => $this->repairPreview($device),
                    ],
                ];
            })
            ->all();

        return view('devices.index', [
            'devices' => $devices,
            'deviceStates' => $deviceStates,
            'filters' => $filters,
            'deviceSummary' => [
                'total' => (clone $summaryQuery)->count(),
                'active' => (clone $summaryQuery)->where('status', Device::STATUS_ACTIVE)->count(),
                'repair' => (clone $summaryQuery)->where('status', Device::STATUS_REPAIR)->count(),
                'writeoff' => (clone $summaryQuery)->where('status', Device::STATUS_WRITEOFF)->count(),
            ],
            'floorOptions' => $floorOptions,
            'roomOptions' => $roomOptions,
            'selectedRoom' => $selectedRoom,
            'types' => $types,
            'selectedType' => $selectedType,
            'selectedAssignedUser' => $selectedAssignedUser,
            'statuses' => self::STATUSES,
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $canManageDevices,
            'quickRoomOptions' => $canManageDevices ? $this->quickRoomOptions() : collect(),
            'quickAssigneeOptions' => $canManageDevices ? $this->quickAssigneeOptions() : collect(),
        ]);
    }

    public function create()
    {
        $this->requireManager();

        return view('devices.create', $this->formData());
    }

    public function store(Request $request)
    {
        $user = $this->requireManager();

        $device = Device::create(array_merge(
            $this->validatedData($request),
            ['created_by' => $user->id]
        ));

        $this->syncUploads($request, $device);
        $this->removeDeviceImage($request, $device);

        AuditTrail::created($user->id, $device);

        return redirect()->route('devices.index')->with('success', 'Ierice veiksmigi pievienota');
    }

    public function edit(Device $device)
    {
        $this->requireManager();

        return view('devices.edit', array_merge(['device' => $device], $this->formData()));
    }

    public function show(Device $device)
    {
        $this->authorizeView($device);

        $user = $this->user();
        $device->load([
            'type',
            'building',
            'room.building',
            'createdBy',
            'assignedTo',
            'activeRepair',
            'activeRepair.acceptedBy',
            'activeRepair.request.responsibleUser',
            'latestRepair.acceptedBy',
            'latestRepair.request.responsibleUser',
            'repairs.assignee',
            'repairRequests.responsibleUser',
            'repairRequests.reviewedBy',
            'repairRequests.repair.acceptedBy',
            'repairRequests.repair.executor',
            'writeoffRequests.responsibleUser',
            'writeoffRequests.reviewedBy',
            'transfers.responsibleUser',
            'transfers.transferTo',
            'transfers.reviewedBy',
        ]);

        $latestTransferToCurrentUser = $device->transfers
            ->where('status', 'approved')
            ->where('transfered_to_id', $user?->id)
            ->sortByDesc('created_at')
            ->first();

        $roomOptions = Room::query()
            ->with('building')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'value' => (string) $room->id,
                'label' => $room->room_number.($room->room_name ? ' - '.$room->room_name : ''),
                'description' => collect([
                    $room->building?->building_name,
                    $room->floor_number !== null ? $room->floor_number.'. stavs' : null,
                    $room->department,
                ])->filter()->implode(' | '),
                'search' => implode(' ', array_filter([
                    $room->room_number,
                    $room->room_name,
                    $room->building?->building_name,
                    $room->department,
                    $room->floor_number,
                ])),
            ])
            ->values();

        $pendingRepairRequest = $device->repairRequests->firstWhere('status', 'submitted');
        $pendingWriteoffRequest = $device->writeoffRequests->firstWhere('status', 'submitted');
        $pendingTransferRequest = $device->transfers->firstWhere('status', 'submitted');
        $roomUpdateAvailability = $this->userRoomUpdateAvailability($device, $pendingRepairRequest, $pendingWriteoffRequest, $pendingTransferRequest);

        return view('devices.show', [
            'device' => $device,
            'deviceImageUrl' => $device->deviceImageUrl(),
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $this->user()?->canManageRequests() ?? false,
            'originLabel' => $latestTransferToCurrentUser
                ? 'Ierice tev nodota no '.($latestTransferToCurrentUser->responsibleUser?->full_name ?: 'cita lietotaja').'.'
                : 'Ierici tev pieskira administrators.',
            'roomOptions' => $roomOptions,
            'visibleWriteoffRequests' => $device->writeoffRequests,
            'requestAvailability' => $this->requestAvailabilityForDevice(
                $device,
                (bool) $pendingRepairRequest,
                (bool) $pendingWriteoffRequest,
                (bool) $pendingTransferRequest,
            ),
            'roomUpdateAvailability' => $roomUpdateAvailability,
            'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
        ]);
    }

    public function updateUserRoom(Request $request, Device $device): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);
        abort_unless((int) $device->assigned_to_id === (int) $user->id, 403);
        abort_if($device->status === Device::STATUS_WRITEOFF, 403);

        $device->loadMissing(['repairRequests', 'writeoffRequests', 'transfers', 'activeRepair']);
        $pendingRepairRequest = $device->repairRequests->firstWhere('status', 'submitted');
        $pendingWriteoffRequest = $device->writeoffRequests->firstWhere('status', 'submitted');
        $pendingTransferRequest = $device->transfers->firstWhere('status', 'submitted');
        $roomUpdateAvailability = $this->userRoomUpdateAvailability($device, $pendingRepairRequest, $pendingWriteoffRequest, $pendingTransferRequest);

        if (! $roomUpdateAvailability['allowed']) {
            return redirect()->route('devices.show', $device)->with('error', $roomUpdateAvailability['reason']);
        }

        $validated = $this->validateInput($request, [
            'room_id' => ['nullable', 'exists:rooms,id'],
        ], [
            'room_id.exists' => 'Izveleta telpa nav atrasta.',
        ]);

        $roomId = $validated['room_id'] ?? null;
        $room = $roomId ? Room::query()->with('building')->find($roomId) : null;

        $payload = [
            'room_id' => $room?->id,
            'building_id' => $room?->building_id,
        ];

        if ((int) $device->room_id === (int) ($room?->id ?? 0) && (int) $device->building_id === (int) ($room?->building_id ?? 0)) {
            return redirect()->route('devices.show', $device)->with('error', 'Ierice jau atrodas saja telpa.');
        }

        $before = $device->only(['room_id', 'building_id']);
        $this->saveDevicePayload($device, $payload);
        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'room_id' => $device->room_id,
            'building_id' => $device->building_id,
        ]);

        return redirect()->route('devices.show', $device)->with('success', 'Ierices atrasanas vieta atjauninata.');
    }

    public function update(Request $request, Device $device)
    {
        $this->requireManager();

        $before = $device->only($this->trackedFields());

        $device->update($this->validatedData($request, $device));
        $this->syncUploads($request, $device);
        $this->removeDeviceImage($request, $device);

        $after = $device->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $device, $before, $after);

        return redirect()->route('devices.index')->with('success', 'Ierices dati atjauninati');
    }

    public function destroy(Device $device)
    {
        $this->requireManager();

        $this->deleteDeviceAssets($device);
        AuditTrail::deleted(auth()->id(), $device, severity: AuditTrail::SEVERITY_WARNING);
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Ierice dzesta');
    }

    public function quickUpdate(Request $request, Device $device)
    {
        $this->requireManager();

        $validated = $this->validateInput($request, [
            'action' => ['required', Rule::in(['status', 'room', 'assignee'])],
            'target_status' => [Rule::requiredIf(fn () => $request->input('action') === 'status'), Rule::in(self::STATUSES)],
            'target_room_id' => [Rule::requiredIf(fn () => $request->input('action') === 'room'), 'exists:rooms,id'],
            'target_assigned_to_id' => [
                Rule::requiredIf(fn () => $request->input('action') === 'assignee'),
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ], [
            'action.required' => 'Izvelies darbibu, ko veikt ar ierici.',
            'target_status.required' => 'Izvelies jauno ierices statusu.',
            'target_room_id.required' => 'Izvelies telpu, uz kuru parvietot ierici.',
            'target_assigned_to_id.required' => 'Izvelies atbildigo personu.',
        ]);

        $result = $this->performDeviceAction($device, $validated);

        return $this->redirectAfterQuickAction($device, $result['level'], $result['message']);
    }

    public function quickUpdateRedirect(Device $device): RedirectResponse
    {
        $this->requireManager();

        return redirect()
            ->route('devices.show', $device)
            ->with('error', 'So adresi nevar atvert ar GET pieprasijumu. Izmanto darbibu pogas no ierices saraksta.');
    }

    public function bulkUpdate(Request $request)
    {
        $this->requireManager();

        $validated = $this->validateInput($request, [
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['integer', 'exists:devices,id'],
            'action' => ['required', Rule::in(['status', 'room'])],
            'target_status' => [Rule::requiredIf(fn () => $request->input('action') === 'status'), Rule::in(self::STATUSES)],
            'target_room_id' => [Rule::requiredIf(fn () => $request->input('action') === 'room'), 'exists:rooms,id'],
        ], [
            'device_ids.required' => 'Izvelies vismaz vienu ierici.',
            'device_ids.min' => 'Izvelies vismaz vienu ierici.',
            'target_status.required' => 'Masveida statusa mainai izvelies jauno statusu.',
            'target_room_id.required' => 'Masveida parvietosanai izvelies telpu.',
        ]);

        $devices = Device::query()->whereIn('id', $validated['device_ids'])->get();
        $processed = 0;
        $messages = [];

        foreach ($devices as $device) {
            $result = $this->performDeviceAction($device, $validated);

            if ($result['level'] === 'success') {
                $processed++;
            } else {
                $messages[] = ($device->code ?: ('ID '.$device->id)).': '.$result['message'];
            }
        }

        $flash = $processed > 0 ? 'Apstradatas ierices: '.$processed.'.' : 'Neviena ierice netika apstradata.';
        if ($messages !== []) {
            $flash .= ' '.implode(' ', array_slice($messages, 0, 3));
        }

        return redirect()->route('devices.index')->with($processed > 0 ? 'success' : 'error', $flash);
    }

    private function visibleDevicesQuery(User $user): Builder
    {
        return Device::query()->when(
            ! $user->canManageRequests(),
            fn (Builder $query) => $query
                ->where('assigned_to_id', $user->id)
                ->where('status', '!=', Device::STATUS_WRITEOFF)
        );
    }

    private function accessibleRooms(User $user): Collection
    {
        return Room::query()
            ->with('building')
            ->whereHas('devices', function (Builder $query) use ($user) {
                $query->where('status', '!=', Device::STATUS_WRITEOFF);

                if (! $user->canManageRequests()) {
                    $query->where('assigned_to_id', $user->id);
                }
            })
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
    }

    private function authorizeView(Device $device): void
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canViewDevice($device), 403);
    }

    private function formData(): array
    {
        $warehouseRoom = $this->ensureWarehouseRoom();
        $user = $this->user();

        return [
            'types' => DeviceType::orderBy('type_name')->get(),
            'buildings' => Building::orderBy('building_name')->get(),
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'users' => User::active()->orderBy('full_name')->get(),
            'statuses' => self::STATUSES,
            'statusLabels' => $this->statusLabels(),
            'defaultAssignedToId' => $user?->id,
            'defaultRoomId' => $warehouseRoom->id,
            'defaultBuildingId' => $warehouseRoom->building_id,
        ];
    }

    private function validatedData(Request $request, ?Device $device = null): array
    {
        if (! $device) {
            $request->merge([
                'status' => Device::STATUS_ACTIVE,
            ]);
        }

        if (! $device && ! $request->filled('assigned_to_id')) {
            $request->merge([
                'assigned_to_id' => $this->user()?->id,
            ]);
        }

        if (! $device && ! $request->filled('room_id')) {
            $warehouseRoom = $this->ensureWarehouseRoom();

            $request->merge([
                'room_id' => $warehouseRoom->id,
                'building_id' => $warehouseRoom->building_id,
            ]);
        }

        $requiresAssignmentAndRoom = Device::normalizeStatus(
            (string) $request->input('status', $device?->status ?? Device::STATUS_ACTIVE)
        ) !== Device::STATUS_WRITEOFF;

        $data = $this->validateInput(
            $request,
            [
                'code' => ['required', 'string', 'max:20', Rule::unique('devices', 'code')->ignore($device?->id)],
                'name' => ['required', 'string', 'max:200'],
                'device_type_id' => ['required', 'exists:device_types,id'],
                'model' => ['required', 'string', 'max:100'],
                'status' => ['required', Rule::in(self::STATUSES)],
                'building_id' => ['nullable', 'exists:buildings,id'],
                'room_id' => [Rule::requiredIf($requiresAssignmentAndRoom), 'nullable', 'exists:rooms,id'],
                'assigned_to_id' => [Rule::requiredIf($requiresAssignmentAndRoom), 'nullable', 'exists:users,id'],
                'purchase_date' => ['nullable', 'date'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
                'warranty_until' => ['nullable', 'date'],
                'serial_number' => ['nullable', 'string', 'max:100'],
                'manufacturer' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string'],
                'device_image' => ['nullable', 'image', 'max:'.(int) config('devices.max_upload_kb', 5120)],
            ],
            [
                'code.required' => 'Noradi ierices kodu.',
                'name.required' => 'Noradi ierices nosaukumu.',
                'device_type_id.required' => 'Izvelies ierices tipu.',
                'model.required' => 'Noradi ierices modeli.',
                'status.required' => 'Izvelies ierices statusu.',
                'assigned_to_id.required' => 'Izvelies atbildigo personu.',
                'room_id.required' => 'Izvelies telpu.',
                'purchase_price.min' => 'Iegades cenai jabut 0 vai lielakai.',
            ]
        );

        foreach ([
            'building_id',
            'room_id',
            'assigned_to_id',
            'purchase_date',
            'purchase_price',
            'warranty_until',
            'serial_number',
            'manufacturer',
            'notes',
        ] as $field) {
            $data[$field] = $data[$field] ?? null;
        }

        foreach (['building_id', 'room_id', 'assigned_to_id'] as $field) {
            $data[$field] = $data[$field] ?: null;
        }

        if (($data['room_id'] ?? null) !== null) {
            $room = Room::query()->find($data['room_id']);

            if ($room) {
                $data['building_id'] = $room->building_id;
            }
        }

        if (
            ! empty($data['warranty_until'])
            && ! empty($data['purchase_date'])
            && strtotime((string) $data['warranty_until']) < strtotime((string) $data['purchase_date'])
        ) {
            throw ValidationException::withMessages([
                'warranty_until' => ['Garantijas datums nevar but agraks par pirkuma datumu.'],
            ]);
        }

        if (($data['status'] ?? null) === Device::STATUS_WRITEOFF) {
            $data = array_merge($data, $this->writeoffWarehousePayload());
        }

        if ($device && $device->status === Device::STATUS_WRITEOFF) {
            $data['status'] = Device::STATUS_WRITEOFF;
            $data = array_merge($data, $this->writeoffWarehousePayload());
        }

        unset($data['device_image']);

        $data['device_image_url'] = $device?->device_image_url;

        return $data;
    }

    private function ensureWarehouseRoom(): Room
    {
        $warehouseRoom = Room::query()
            ->with('building')
            ->get()
            ->first(function (Room $room) {
                return $this->isWarehouseLabel($room->room_name)
                    || $this->isWarehouseLabel($room->room_number)
                    || $this->isWarehouseLabel($room->notes);
            });

        if ($warehouseRoom) {
            return $warehouseRoom;
        }

        $building = $this->preferredWarehouseBuilding();

        return Room::query()->create([
            'building_id' => $building->id,
            'floor_number' => 1,
            'room_number' => $this->nextWarehouseRoomNumber($building->id),
            'room_name' => self::DEFAULT_WAREHOUSE_ROOM_NAME,
            'user_id' => $this->user()?->id,
            'department' => 'Inventars',
            'notes' => 'Automatiski izveidota nokluseta noliktavas telpa.',
        ])->load('building');
    }

    private function writeoffWarehousePayload(): array
    {
        $warehouseRoom = $this->ensureWarehouseRoom();

        return [
            'assigned_to_id' => null,
            'building_id' => $warehouseRoom->building_id,
            'room_id' => $warehouseRoom->id,
        ];
    }

    private function preferredWarehouseBuilding(): Building
    {
        $preferredBuilding = Building::query()
            ->orderBy('building_name')
            ->get()
            ->first(fn (Building $building) => $this->matchesPreferredBuildingName($building->building_name));

        if ($preferredBuilding) {
            return $preferredBuilding;
        }

        $existingBuilding = Building::query()->orderBy('building_name')->first();

        if ($existingBuilding) {
            return $existingBuilding;
        }

        return Building::query()->create([
            'building_name' => self::DEFAULT_BUILDING_NAME,
            'city' => 'Ludza',
            'total_floors' => 1,
            'notes' => 'Automatiski izveidota nokluseta eka noliktavas telpai.',
        ]);
    }

    private function nextWarehouseRoomNumber(int $buildingId): string
    {
        $existingNumbers = Room::query()
            ->where('building_id', $buildingId)
            ->pluck('room_number')
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->all();

        $sequence = 1;

        do {
            $candidate = self::DEFAULT_WAREHOUSE_ROOM_NUMBER_PREFIX.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (in_array($candidate, $existingNumbers, true));

        return $candidate;
    }

    private function isWarehouseLabel(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'noliktav');
    }

    private function matchesPreferredBuildingName(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'ludz');
    }

    private function trackedFields(): array
    {
        return [
            'code',
            'name',
            'device_type_id',
            'model',
            'status',
            'building_id',
            'room_id',
            'assigned_to_id',
            'purchase_date',
            'purchase_price',
            'warranty_until',
            'serial_number',
            'manufacturer',
            'notes',
            'device_image_url',
        ];
    }

    private function syncUploads(Request $request, Device $device): void
    {
        $assetManager = app(DeviceAssetManager::class);
        $updates = [];

        if ($request->hasFile('device_image')) {
            $updates['device_image_url'] = $assetManager->storeDeviceImage(
                $request->file('device_image'),
                $device->device_image_url
            );
        }

        if ($updates !== []) {
            $device->forceFill($updates)->save();
        }
    }

    private function removeDeviceImage(Request $request, Device $device): void
    {
        if ($request->hasFile('device_image')) {
            return;
        }

        if (! $request->boolean('remove_device_image') || ! filled($device->device_image_url)) {
            return;
        }

        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->device_image_url);
        $assetManager->delete($assetManager->thumbnailPath($device->device_image_url));
        $device->forceFill(['device_image_url' => null])->save();
    }

    private function deleteDeviceAssets(Device $device): void
    {
        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->device_image_url);
        $assetManager->delete($assetManager->thumbnailPath($device->device_image_url));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Device::STATUS_REPAIR => 'Remonta',
            Device::STATUS_WRITEOFF => 'Norakstita',
            default => 'Aktiva',
        };
    }

    private function statusLabels(): array
    {
        return collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
            ->all();
    }

    private function repairStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            default => null,
        };
    }

    private function visibleRepairStatusLabel(Device $device): ?string
    {
        if ($device->status !== Device::STATUS_REPAIR) {
            return null;
        }

        $label = $this->repairStatusLabel($device->activeRepair?->status)
            ?? $this->repairStatusLabel($device->latestRepair?->status);

        return $label ?: 'Gaida';
    }

    private function repairPreview(Device $device): ?array
    {
        if ($device->status !== Device::STATUS_REPAIR) {
            return null;
        }

        $repair = $device->activeRepair ?? $device->latestRepair;

        if (! $repair) {
            return null;
        }

        return [
            'title' => 'Remonta ieraksts',
            'status' => $this->repairStatusLabel($repair->status) ?: 'Gaida',
            'type' => $repair->repair_type === 'external' ? 'Arejais' : 'Ieksejais',
            'approved_by' => $repair->approval_actor_name
                ?: $repair->request?->responsibleUser?->full_name
                ?: '-',
            'created_at' => $repair->created_at?->format('d.m.Y H:i') ?: '-',
            'description' => $repair->description ?: 'Apraksts nav pievienots.',
        ];
    }

    private function repairReasonText(Device $device): string
    {
        $repairStatusLabel = $this->repairStatusLabel($device->activeRepair?->status);

        if ($repairStatusLabel) {
            return 'Ierice sobrid ir remonta ar statusu "'.$repairStatusLabel.'".';
        }

        return 'Ierice sobrid ir remonta.';
    }

    private function requestAvailabilityForDevice(
        Device $device,
        bool $hasPendingRepairRequest,
        bool $hasPendingWriteoffRequest,
        bool $hasPendingTransferRequest
    ): array {
        if ($device->status === Device::STATUS_WRITEOFF) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => 'Ierice ir norakstita, tapec jaunus pieteikumus veidot nevar.',
            ];
        }

        if ($device->status === Device::STATUS_REPAIR) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => $this->repairReasonText($device),
            ];
        }

        if ($hasPendingRepairRequest) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => 'Sai iericei jau ir gaidoss remonta pieteikums.',
            ];
        }

        if ($hasPendingWriteoffRequest) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => 'Sai iericei jau ir gaidoss norakstisanas pieteikums.',
            ];
        }

        if ($hasPendingTransferRequest) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => 'Sai iericei jau ir gaidoss nodosanas pieteikums.',
            ];
        }

        return [
            'repair' => true,
            'writeoff' => true,
            'transfer' => true,
            'can_create_any' => true,
            'reason' => null,
        ];
    }

    private function pendingRequestBadge(
        Device $device,
        bool $canManageRequests,
        bool $hasPendingRepairRequest,
        bool $hasPendingWriteoffRequest,
        bool $hasPendingTransferRequest,
        mixed $pendingRepairRequest = null,
        mixed $pendingWriteoffRequest = null,
        mixed $pendingTransferRequest = null,
    ): ?array {
        if ($hasPendingRepairRequest) {
            return [
                'icon' => 'repair-request',
                'label' => 'Gaida remonta pieteikumu',
                'short_label' => 'Pieprasijums',
                'detail_label' => 'Remonts',
                'class' => 'border-sky-200 bg-sky-50 text-sky-700',
                'url' => $this->requestIndexUrl($device, $canManageRequests, 'repair', $pendingRepairRequest?->id),
                'preview' => $this->pendingRequestPreview('repair', $pendingRepairRequest),
            ];
        }

        if ($hasPendingWriteoffRequest) {
            return [
                'icon' => 'writeoff',
                'label' => 'Gaida norakstisanas pieteikumu',
                'short_label' => 'Pieprasijums',
                'detail_label' => 'Norakst.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'url' => $this->requestIndexUrl($device, $canManageRequests, 'writeoff', $pendingWriteoffRequest?->id),
                'preview' => $this->pendingRequestPreview('writeoff', $pendingWriteoffRequest),
            ];
        }

        if ($hasPendingTransferRequest) {
            return [
                'icon' => 'transfer',
                'label' => 'Gaida nodosanas pieteikumu',
                'short_label' => 'Pieprasijums',
                'detail_label' => 'Nodosana',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'url' => $this->requestIndexUrl($device, $canManageRequests, 'transfer', $pendingTransferRequest?->id),
                'preview' => $this->pendingRequestPreview('transfer', $pendingTransferRequest),
            ];
        }

        return null;
    }

    private function requestIndexUrl(Device $device, bool $canManageRequests, string $type, ?int $requestId = null): ?string
    {
        $params = [
            'q' => $device->code ?: $device->name,
            'statuses_filter' => 1,
            'status' => ['submitted'],
        ];

        $baseUrl = match ($type) {
            'repair' => Route::has('repair-requests.index') ? route('repair-requests.index', $params) : null,
            'writeoff' => Route::has('writeoff-requests.index') ? route('writeoff-requests.index', $params) : null,
            'transfer' => Route::has('device-transfers.index') ? route('device-transfers.index', $params) : null,
            default => null,
        };

        if (! $baseUrl || ! $requestId) {
            return $baseUrl;
        }

        $anchor = match ($type) {
            'repair' => 'repair-request-',
            'writeoff' => 'writeoff-request-',
            'transfer' => 'device-transfer-',
            default => '',
        };

        return $anchor !== '' ? $baseUrl.'#'.$anchor.$requestId : $baseUrl;
    }

    private function pendingRequestPreview(string $type, mixed $request): ?array
    {
        if (! $request) {
            return null;
        }

        return match ($type) {
            'repair' => [
                'type_label' => 'Remonta pieprasijums',
                'meta_label' => 'Apraksts',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->description ?: 'Apraksts nav pievienots.',
                'recipient' => null,
            ],
            'writeoff' => [
                'type_label' => 'Norakstisanas pieprasijums',
                'meta_label' => 'Iemesls',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->reason ?: 'Iemesls nav pievienots.',
                'recipient' => null,
            ],
            'transfer' => [
                'type_label' => 'Nodosanas pieprasijums',
                'meta_label' => 'Iemesls',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->transfer_reason ?: 'Iemesls nav pievienots.',
                'recipient' => $request->transferTo?->full_name ?: null,
            ],
            default => null,
        };
    }

    private function performDeviceAction(Device $device, array $data): array
    {
        return match ($data['action']) {
            'status' => $this->changeDeviceStatus($device, (string) ($data['target_status'] ?? '')),
            'room' => $this->moveDevice($device, $data['target_room_id'] ?? null),
            'assignee' => $this->reassignDevice($device, $data['target_assigned_to_id'] ?? null),
            default => ['level' => 'error', 'message' => 'Neatbalstita darbiba.'],
        };
    }

    private function changeDeviceStatus(Device $device, string $status): array
    {
        if (! in_array($status, self::STATUSES, true)) {
            return ['level' => 'error', 'message' => 'Nav izvelets korekts statuss.'];
        }

        if ($status === Device::STATUS_REPAIR && $device->status !== Device::STATUS_ACTIVE) {
            return ['level' => 'error', 'message' => 'Remonta ierakstu var izveidot tikai aktivai iericei.'];
        }

        if ($status === Device::STATUS_REPAIR && $device->repairs()->whereIn('status', ['waiting', 'in-progress'])->exists()) {
            return ['level' => 'error', 'message' => 'Sai iericei jau ir aktivs remonta ieraksts.'];
        }

        if ($status === Device::STATUS_WRITEOFF && $device->status !== Device::STATUS_ACTIVE) {
            return ['level' => 'error', 'message' => 'Norakstit var tikai aktivu ierici.'];
        }

        if ($status === Device::STATUS_WRITEOFF && ($device->status === Device::STATUS_REPAIR || $device->activeRepair()->exists())) {
            return ['level' => 'error', 'message' => 'Ierici nevar norakstit, kamer tai ir aktivs remonta process.'];
        }

        if ($device->status === $status) {
            return ['level' => 'error', 'message' => 'Statuss jau ir iestatits.'];
        }

        if ($status === Device::STATUS_REPAIR) {
            $before = [
                'status' => $device->status,
            ];

            $repair = null;

            DB::transaction(function () use ($device, &$repair, $before) {
                $repair = $this->createRepairRecord([
                    'device_id' => $device->id,
                    'issue_reported_by' => null,
                    'accepted_by' => auth()->id(),
                    'description' => 'Ierice nodota remonta no iericu saraksta.',
                    'status' => 'waiting',
                    'repair_type' => 'internal',
                    'priority' => 'medium',
                    'start_date' => null,
                    'end_date' => null,
                    'cost' => null,
                    'vendor_name' => null,
                    'vendor_contact' => null,
                    'invoice_number' => null,
                    'request_id' => null,
                ]);

                $this->saveDevicePayload($device, ['status' => Device::STATUS_REPAIR]);
                AuditTrail::created(auth()->id(), $repair);
                AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => $device->status]);
            });

            return ['level' => 'success', 'message' => 'Ierice nodota remonta. Izveidots remonta ieraksts #'.$repair->id.'.'];
        }

        $before = [
            'status' => $device->status,
            'assigned_to_id' => $device->assigned_to_id,
            'building_id' => $device->building_id,
            'room_id' => $device->room_id,
        ];

        $payload = ['status' => $status];
        if ($status === Device::STATUS_WRITEOFF) {
            $payload = array_merge($payload, $this->writeoffWarehousePayload());
        }

        $this->saveDevicePayload($device, $payload);
        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'status' => $device->status,
            'assigned_to_id' => $device->assigned_to_id,
            'building_id' => $device->building_id,
            'room_id' => $device->room_id,
        ]);

        return ['level' => 'success', 'message' => 'Statuss atjauninats.'];
    }

    private function moveDevice(Device $device, mixed $roomId): array
    {
        if ($blockedReason = $this->quickRelationEditBlockedReason($device)) {
            return ['level' => 'error', 'message' => $blockedReason];
        }

        if (! $roomId) {
            return ['level' => 'error', 'message' => 'Nav izveleta telpa.'];
        }

        $room = Room::query()->with('building')->find($roomId);
        if (! $room) {
            return ['level' => 'error', 'message' => 'Telpa nav atrasta.'];
        }

        if ((int) $device->room_id === (int) $room->id) {
            return ['level' => 'error', 'message' => 'Ierice jau atrodas saja telpa.'];
        }

        $before = $device->only(['room_id', 'building_id']);

        $this->saveDevicePayload($device, [
            'room_id' => $room->id,
            'building_id' => $room->building_id,
        ]);

        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'room_id' => $room->id,
            'building_id' => $room->building_id,
        ]);

        return ['level' => 'success', 'message' => 'Ierice parvietota uz citu telpu.'];
    }

    private function reassignDevice(Device $device, mixed $assignedToId): array
    {
        if ($blockedReason = $this->quickRelationEditBlockedReason($device)) {
            return ['level' => 'error', 'message' => $blockedReason];
        }

        if (! $assignedToId) {
            return ['level' => 'error', 'message' => 'Nav izveleta atbildiga persona.'];
        }

        $assignee = User::query()
            ->active()
            ->find($assignedToId);

        if (! $assignee) {
            return ['level' => 'error', 'message' => 'Atbildiga persona nav atrasta.'];
        }

        if ((int) $device->assigned_to_id === (int) $assignee->id) {
            return ['level' => 'error', 'message' => 'Ierice jau ir pieskirta sajai personai.'];
        }

        $before = $device->only(['assigned_to_id']);

        $this->saveDevicePayload($device, [
            'assigned_to_id' => $assignee->id,
        ]);

        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'assigned_to_id' => $assignee->id,
        ]);

        return ['level' => 'success', 'message' => 'Atbildiga persona atjauninata.'];
    }

    private function quickRelationEditBlockedReason(Device $device): ?string
    {
        if ($device->status === Device::STATUS_WRITEOFF) {
            return 'Norakstitai iericei vairs nevar mainit telpu vai atbildigo personu.';
        }

        if ($device->status === Device::STATUS_REPAIR) {
            $repairStatusLabel = $this->visibleRepairStatusLabel($device);

            return 'Remonta iericei nevar mainit telpu vai atbildigo personu'.($repairStatusLabel ? ' ar statusu "'.$repairStatusLabel.'".' : '.');
        }

        return null;
    }

    private function quickRoomOptions(): Collection
    {
        return Room::query()
            ->with('building')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'value' => (string) $room->id,
                'label' => $room->room_number.($room->room_name ? ' - '.$room->room_name : ''),
                'description' => implode(' | ', array_filter([
                    $room->building?->building_name,
                    $room->floor_number ? $room->floor_number.'. stavs' : null,
                    $room->department,
                ])),
                'search' => implode(' ', array_filter([
                    $room->room_number,
                    $room->room_name,
                    $room->department,
                    $room->building?->building_name,
                    $room->floor_number,
                ])),
            ])
            ->values();
    }

    private function quickAssigneeOptions(): Collection
    {
        return User::query()
            ->active()
            ->orderBy('full_name')
            ->get()
            ->map(fn (User $managedUser) => [
                'value' => (string) $managedUser->id,
                'label' => $managedUser->full_name,
                'description' => implode(' | ', array_filter([
                    $managedUser->job_title,
                    $managedUser->email,
                ])),
                'search' => implode(' ', array_filter([
                    $managedUser->full_name,
                    $managedUser->job_title,
                    $managedUser->email,
                ])),
            ])
            ->values();
    }

    private function userRoomUpdateAvailability(Device $device, mixed $pendingRepairRequest, mixed $pendingWriteoffRequest, mixed $pendingTransferRequest): array
    {
        if ($device->status === Device::STATUS_REPAIR) {
            $repairStatusLabel = $this->repairStatusLabel($device->activeRepair?->status);

            return [
                'allowed' => false,
                'reason' => 'Ierices atrasanas vietu nevar mainit, kamer ierice ir remonta'.($repairStatusLabel ? ' ar statusu "'.$repairStatusLabel.'".' : '.'),
            ];
        }

        if ($pendingRepairRequest) {
            return [
                'allowed' => false,
                'reason' => 'Ierices atrasanas vietu nevar mainit, jo sai iericei ir gaidoss remonta pieteikums.',
            ];
        }

        if ($pendingWriteoffRequest) {
            return [
                'allowed' => false,
                'reason' => 'Ierices atrasanas vietu nevar mainit, jo sai iericei ir gaidoss norakstisanas pieteikums.',
            ];
        }

        if ($pendingTransferRequest) {
            return [
                'allowed' => false,
                'reason' => 'Ierices atrasanas vietu nevar mainit, jo sai iericei ir gaidoss nodosanas pieteikums.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    private function redirectAfterQuickAction(Device $device, string $level, string $message): RedirectResponse
    {
        $previousUrl = url()->previous();
        $previousPath = is_string($previousUrl) ? (parse_url($previousUrl, PHP_URL_PATH) ?: '') : '';

        if (is_string($previousUrl) && $previousUrl !== '' && ! str_contains($previousPath, '/quick-update')) {
            return redirect()->to($previousUrl)->with($level, $message);
        }

        return redirect()->route('devices.show', $device)->with($level, $message);
    }

    private function saveDevicePayload(Device $device, array $payload): void
    {
        try {
            $device->forceFill($payload)->save();
        } catch (QueryException $exception) {
            if (! $this->isLegacyStatusEnumMismatch($exception)) {
                throw $exception;
            }

            app(RuntimeSchemaBootstrapper::class)->ensure();
            $device->refresh();
            $device->forceFill($payload)->save();
        }
    }

    private function isLegacyStatusEnumMismatch(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'data truncated for column')
            && str_contains($message, "'status'");
    }
}
