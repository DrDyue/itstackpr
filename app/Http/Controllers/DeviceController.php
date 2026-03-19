<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Device;
use App\Models\Repair;
use App\Models\DeviceType;
use App\Models\Room;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\DeviceAssetManager;
use App\Support\RuntimeSchemaBootstrapper;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    private const STATUSES = [Device::STATUS_ACTIVE, Device::STATUS_REPAIR, Device::STATUS_WRITEOFF];

    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

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
        ];

        $filters['has_status_filter'] = count($filters['statuses']) > 0 && count($filters['statuses']) < count(self::STATUSES);

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
        if ($user->canManageRequests() && ctype_digit($filters['assigned_to_id'])) {
            $selectedAssignedUser = User::query()->find((int) $filters['assigned_to_id']);
        }

        if ($selectedRoom) {
            $filters['floor'] = (string) $selectedRoom->floor_number;
            $filters['room_query'] = $selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : '');
        }
        if ($selectedType) {
            $filters['type_query'] = $selectedType->type_name;
        }
        if ($selectedAssignedUser) {
            $filters['assigned_to_query'] = $selectedAssignedUser->full_name;
        }

        if ($filters['floor'] !== '') {
            $filters['floor_query'] = $filters['floor'] . '. stavs';
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
            ->with(['type', 'building', 'room.building', 'activeRepair', 'assignedTo', 'createdBy'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $deviceQuery) use ($term) {
                    $deviceQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('manufacturer', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($filters['code'] !== '', fn (Builder $query) => $query->where('code', 'like', '%' . $filters['code'] . '%'))
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
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('devices.index', [
            'devices' => $devices,
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
            'canManageDevices' => $user->canManageRequests(),
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
                'label' => $room->room_number . ($room->room_name ? ' - ' . $room->room_name : ''),
                'description' => collect([
                    $room->building?->building_name,
                    $room->floor_number !== null ? $room->floor_number . '. stavs' : null,
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

        return view('devices.show', [
            'device' => $device,
            'deviceImageUrl' => $device->deviceImageUrl(),
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $this->user()?->canManageRequests() ?? false,
            'originLabel' => $latestTransferToCurrentUser
                ? 'Ierice tev nodota no ' . ($latestTransferToCurrentUser->responsibleUser?->full_name ?: 'cita lietotaja') . '.'
                : 'Ierici tev pieskira administrators.',
            'roomOptions' => $roomOptions,
            'visibleWriteoffRequests' => ($this->user()?->canManageRequests() ?? false)
                ? $device->writeoffRequests
                : $device->writeoffRequests->where('status', 'rejected')->values(),
            'requestAvailability' => [
                'repair' => ! $pendingWriteoffRequest && ! $pendingTransferRequest && $device->status !== Device::STATUS_REPAIR,
                'writeoff' => ! $pendingRepairRequest && ! $pendingTransferRequest && $device->status !== Device::STATUS_REPAIR,
                'transfer' => ! $pendingRepairRequest && ! $pendingWriteoffRequest && $device->status !== Device::STATUS_REPAIR,
                'reason' => $device->status === Device::STATUS_REPAIR
                    ? 'Ierice sobrid ir remonta ar statusu "' . $this->repairStatusLabel($device->activeRepair?->status) . '".'
                    : ($pendingRepairRequest
                        ? 'Sai iericei jau ir gaidoss remonta pieteikums.'
                        : ($pendingWriteoffRequest
                            ? 'Sai iericei jau ir gaidoss norakstisanas pieteikums.'
                            : ($pendingTransferRequest ? 'Sai iericei jau ir gaidoss nodosanas pieteikums.' : null))),
            ],
            'repairStatusLabel' => $this->repairStatusLabel($device->activeRepair?->status),
        ]);
    }

    public function updateUserRoom(Request $request, Device $device): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);
        abort_unless((int) $device->assigned_to_id === (int) $user->id, 403);
        abort_if($device->status === Device::STATUS_WRITEOFF, 403);

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
            'action' => ['required', Rule::in(['status', 'room'])],
            'target_status' => [Rule::requiredIf(fn () => $request->input('action') === 'status'), Rule::in(self::STATUSES)],
            'target_room_id' => [Rule::requiredIf(fn () => $request->input('action') === 'room'), 'exists:rooms,id'],
        ], [
            'action.required' => 'Izvelies darbibu, ko veikt ar ierici.',
            'target_status.required' => 'Izvelies jauno ierices statusu.',
            'target_room_id.required' => 'Izvelies telpu, uz kuru parvietot ierici.',
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
                $messages[] = ($device->code ?: ('ID ' . $device->id)) . ': ' . $result['message'];
            }
        }

        $flash = $processed > 0 ? 'Apstradatas ierices: ' . $processed . '.' : 'Neviena ierice netika apstradata.';
        if ($messages !== []) {
            $flash .= ' ' . implode(' ', array_slice($messages, 0, 3));
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
        return [
            'types' => DeviceType::orderBy('type_name')->get(),
            'buildings' => Building::orderBy('building_name')->get(),
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'users' => User::active()->orderBy('full_name')->get(),
            'statuses' => self::STATUSES,
            'statusLabels' => $this->statusLabels(),
        ];
    }

    private function validatedData(Request $request, ?Device $device = null): array
    {
        $data = $this->validateInput(
            $request,
            [
                'code' => ['required', 'string', 'max:20', Rule::unique('devices', 'code')->ignore($device?->id)],
                'name' => ['required', 'string', 'max:200'],
                'device_type_id' => ['required', 'exists:device_types,id'],
                'model' => ['required', 'string', 'max:100'],
                'status' => ['required', Rule::in(self::STATUSES)],
                'building_id' => ['nullable', 'exists:buildings,id'],
                'room_id' => ['nullable', 'exists:rooms,id'],
                'assigned_to_id' => ['nullable', 'exists:users,id'],
                'purchase_date' => ['nullable', 'date'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
                'warranty_until' => ['nullable', 'date'],
                'serial_number' => ['nullable', 'string', 'max:100'],
                'manufacturer' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string'],
                'device_image' => ['nullable', 'image', 'max:' . (int) config('devices.max_upload_kb', 5120)],
            ],
            [
                'code.required' => 'Noradi ierices kodu.',
                'name.required' => 'Noradi ierices nosaukumu.',
                'device_type_id.required' => 'Izvelies ierices tipu.',
                'model.required' => 'Noradi ierices modeli.',
                'status.required' => 'Izvelies ierices statusu.',
                'purchase_price.min' => 'Iegades cenai jabut 0 vai lielakai.',
            ]
        );

        foreach (['building_id', 'room_id', 'assigned_to_id'] as $field) {
            $data[$field] = $data[$field] ?: null;
        }

        $data['purchase_date'] = $data['purchase_date'] ?: null;

        if (($data['room_id'] ?? null) !== null) {
            $room = Room::query()->find($data['room_id']);

            if ($room && ($data['building_id'] ?? null) === null) {
                $data['building_id'] = $room->building_id;
            }

            if ($room && ($data['building_id'] ?? null) !== null && (int) $room->building_id !== (int) $data['building_id']) {
                throw ValidationException::withMessages([
                    'room_id' => ['Izveleta telpa nepieder noraditajai ekai.'],
                ]);
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

        if (($data['status'] ?? null) === Device::STATUS_WRITEOFF && ! empty($data['assigned_to_id'])) {
            $data['assigned_to_id'] = null;
        }

        if (($data['status'] ?? null) === Device::STATUS_WRITEOFF) {
            $data['room_id'] = null;
            $data['building_id'] = null;
        }

        if ($device && $device->status === Device::STATUS_WRITEOFF) {
            $data['status'] = Device::STATUS_WRITEOFF;
            $data['assigned_to_id'] = null;
            $data['room_id'] = null;
            $data['building_id'] = null;
        }

        unset($data['device_image']);

        $data['device_image_url'] = $device?->device_image_url;

        return $data;
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

    private function repairStatusLabel(?string $status): string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            default => 'Remonta',
        };
    }

    private function performDeviceAction(Device $device, array $data): array
    {
        return match ($data['action']) {
            'status' => $this->changeDeviceStatus($device, (string) ($data['target_status'] ?? '')),
            'room' => $this->moveDevice($device, $data['target_room_id'] ?? null),
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
                $repair = Repair::create([
                    'device_id' => $device->id,
                    'issue_reported_by' => $device->assigned_to_id,
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

            return ['level' => 'success', 'message' => 'Ierice nodota remonta. Izveidots remonta ieraksts #' . $repair->id . '.'];
        }

        $before = [
            'status' => $device->status,
            'assigned_to_id' => $device->assigned_to_id,
            'building_id' => $device->building_id,
            'room_id' => $device->room_id,
        ];

        $payload = ['status' => $status];
        if ($status === Device::STATUS_WRITEOFF) {
            $payload['assigned_to_id'] = null;
            $payload['building_id'] = null;
            $payload['room_id'] = null;
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
        if ($device->status === Device::STATUS_WRITEOFF) {
            return ['level' => 'error', 'message' => 'Norakstitu ierici vairs nevar pieskirt telpai.'];
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
