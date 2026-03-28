<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WriteoffRequestController extends Controller
{
    private const DEFAULT_WAREHOUSE_ROOM_NAME = 'Noliktava';

    private const DEFAULT_WAREHOUSE_ROOM_NUMBER_PREFIX = 'NOL-';

    private const DEFAULT_BUILDING_NAME = 'Ludzes novada pasvaldiba';

    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $availableStatuses = [
            WriteoffRequest::STATUS_SUBMITTED,
            WriteoffRequest::STATUS_APPROVED,
            WriteoffRequest::STATUS_REJECTED,
        ];
        $statusFilterTouched = $request->has('statuses_filter');
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : $availableStatuses))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'statuses' => $selectedStatuses === [] ? $availableStatuses : $selectedStatuses,
            'has_status_filter' => true,
        ];

        if (! $this->featureTableExists('writeoff_requests')) {
            return view('writeoff_requests.index', [
                'requests' => $this->emptyPaginator(),
                'requestSummary' => [
                    'total' => 0,
                    'submitted' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'canReview' => $user->canManageRequests(),
                'featureMessage' => 'Tabula writeoff_requests sobrid nav pieejama.',
            ]);
        }

        $baseQuery = WriteoffRequest::query()
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $requests = (clone $baseQuery)
            ->with(['device.assignedTo', 'responsibleUser', 'reviewedBy'])
            ->whereIn('status', $filters['statuses'] === [] ? ['__none__'] : $filters['statuses'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $builder) use ($term) {
                    $builder->where('reason', 'like', "%{$term}%")
                        ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('writeoff_requests.index', [
            'requests' => $requests,
            'requestSummary' => [
                'total' => (clone $baseQuery)->count(),
                'submitted' => (clone $baseQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count(),
                'approved' => (clone $baseQuery)->where('status', WriteoffRequest::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', WriteoffRequest::STATUS_REJECTED)->count(),
            ],
            'filters' => $filters,
            'statuses' => $availableStatuses,
            'statusLabels' => $this->requestStatusLabels(),
            'canReview' => $user->canManageRequests(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return view('writeoff_requests.create', [
                'devices' => collect(),
                'deviceOptions' => collect(),
                'featureMessage' => 'Tabula writeoff_requests sobrid nav pieejama.',
            ]);
        }

        $devices = $this->availableDevicesForUser($user)->get();
        $selectedDeviceId = (string) $request->query('device_id', '');
        $selectedDevice = ctype_digit($selectedDeviceId)
            ? $devices->firstWhere('id', (int) $selectedDeviceId)
            : null;

        return view('writeoff_requests.create', [
            'devices' => $devices,
            'deviceOptions' => $this->deviceOptions($devices),
            'selectedDeviceId' => $selectedDevice?->id ? (string) $selectedDevice->id : '',
            'selectedDeviceLabel' => $selectedDevice
                ? $selectedDevice->name.' ('.($selectedDevice->code ?: 'bez koda').')'
                : '',
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return redirect()->route('writeoff-requests.index')->with('error', 'Norakstisanas pieteikumus sobrid nevar saglabat, jo tabula writeoff_requests nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'reason' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvelies ierici, kuru velies norakstit.',
            'reason.required' => 'Apraksti norakstisanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt norakstisanu tikai savai piesaistitai iericei.'],
            ]);
        }

        $this->ensureDeviceCanAcceptWriteoffRequest($device);

        $writeoffRequest = WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => $validated['reason'],
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $writeoffRequest);

        return redirect()->route('writeoff-requests.index')->with('success', 'Norakstisanas pieteikums nosutits izskatisanai');
    }

    public function review(Request $request, WriteoffRequest $writeoffRequest)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('writeoff_requests')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Norakstisanas pieteikumu tabula sobrid nav pieejama.'], 503);
            }

            return back()->with('error', 'Norakstisanas pieteikumu tabula sobrid nav pieejama.');
        }

        if ($writeoffRequest->status !== WriteoffRequest::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sis pieteikums jau ir izskatits.'], 409);
            }

            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED])],
        ], [
            'status.required' => 'Izvelies lemumu norakstisanas pieteikumam.',
        ]);

        $before = $writeoffRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $writeoffRequest, $manager) {
            $writeoffRequest->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $manager->id,
                'review_notes' => null,
            ]);

            if ($validated['status'] !== WriteoffRequest::STATUS_APPROVED) {
                return;
            }

            $device = $writeoffRequest->device()->lockForUpdate()->first();

            if (! $device) {
                throw ValidationException::withMessages([
                    'status' => ['Ierice norakstisanai vairs nav atrasta.'],
                ]);
            }

            if ($device->status !== Device::STATUS_ACTIVE || $device->activeRepair()->exists()) {
                throw ValidationException::withMessages([
                    'status' => ['Norakstit var tikai aktivu ierici bez aktiva remonta procesa.'],
                ]);
            }

            $device->forceFill(array_merge(
                ['status' => Device::STATUS_WRITEOFF],
                $this->writeoffWarehousePayload($manager->id)
            ))->save();
        });

        $after = $writeoffRequest->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($manager->id, $writeoffRequest, $before, $after);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Norakstisanas pieteikums izskatits',
                'status' => $validated['status'],
                'request_id' => $writeoffRequest->id,
            ]);
        }

        return back()->with('success', 'Norakstisanas pieteikums izskatits');
    }

    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->where('assigned_to_id', $user->id)
            ->where('status', Device::STATUS_ACTIVE)
            ->with(['type', 'building', 'room', 'activeRepair'])
            ->orderBy('name');
    }

    private function ensureDeviceCanAcceptWriteoffRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau notiek remonts ('.$this->repairStatusLabel($device->activeRepair?->status).'), tapec norakstisanas pieteikumu veidot nevar.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums, tapec norakstisanas pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums, tapec norakstisanas pieteikumu veidot nevar.'],
            ]);
        }
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

    private function deviceOptions($devices)
    {
        return collect($devices)->map(function (Device $device) {
            $description = collect([
                $device->type?->type_name,
                collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                $device->room?->room_number ? 'telpa ' . $device->room->room_number : null,
                $device->building?->building_name,
            ])->filter()->implode(' | ');

            return [
                'value' => (string) $device->id,
                'label' => $device->name . ' (' . ($device->code ?: 'bez koda') . ')',
                'description' => $description,
                'search' => implode(' ', array_filter([
                    $device->name,
                    $device->code,
                    $device->type?->type_name,
                    $device->manufacturer,
                    $device->model,
                    $device->room?->room_number,
                    $device->room?->room_name,
                    $device->building?->building_name,
                ])),
            ];
        })->values();
    }

    private function writeoffWarehousePayload(?int $preferredUserId = null): array
    {
        $warehouseRoom = $this->ensureWarehouseRoom($preferredUserId);

        return [
            'assigned_to_id' => null,
            'building_id' => $warehouseRoom->building_id,
            'room_id' => $warehouseRoom->id,
        ];
    }

    private function ensureWarehouseRoom(?int $preferredUserId = null): Room
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
            'user_id' => $preferredUserId,
            'department' => 'Inventars',
            'notes' => 'Automatiski izveidota nokluseta noliktavas telpa.',
        ])->load('building');
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
}
