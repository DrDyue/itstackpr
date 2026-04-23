<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasRepairStatusLabels;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use App\Support\WarehouseConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Lietotāju norakstīšanas pieteikumu plūsma.
 */
class WriteoffRequestController extends Controller
{
    use HasRepairStatusLabels;



    private const SORTABLE_COLUMNS = ['code', 'name', 'requester', 'created_at', 'status'];

    /**
     * Parāda norakstīšanas pieteikumu sarakstu.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->writeoffRequestsViewData($request, $user);

        AuditTrail::viewed($user, 'WriteoffRequest', null, 'Atvērts norakstīšanas pieteikumu saraksts.');
        $this->auditWriteoffRequestListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);

        return view('writeoff_requests.index', $viewData);
    }

    /**
     * Atgriež filtrētu norakstīšanas pieteikumu tabulu (async).
     */
    public function table(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->writeoffRequestsViewData($request, $user);
        $this->auditWriteoffRequestListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);
        return view('writeoff_requests.index-table', [
            'requests' => $viewData['requests'],
            'canReview' => $viewData['canReview'],
            'sorting' => $viewData['sorting'],
            'sortOptions' => $viewData['sortOptions'],
            'statusLabels' => $viewData['statusLabels'],
            'sortDirectionLabels' => $viewData['sortDirectionLabels'],
        ]);
    }

    /**
     * Kopīga metode norakstīšanas pieteikumu datu sagatavošanai.
     */
    private function writeoffRequestsViewData(Request $request, $user): array
    {
        $canReview = $user->canManageRequests();
        $availableStatuses = [
            WriteoffRequest::STATUS_SUBMITTED,
            WriteoffRequest::STATUS_APPROVED,
            WriteoffRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        if (! $this->featureTableExists('writeoff_requests')) {
            return [
                'requests' => collect(),
                'requestSummary' => [
                    'total' => 0,
                    'submitted' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'canReview' => $canReview,
                'sorting' => $sorting,
                'sortOptions' => $this->sortOptions(),
                'deviceOptions' => collect(),
                'createDeviceOptions' => collect(),
                'requesterOptions' => collect(),
                'selectedEditableRequest' => null,
                'featureMessage' => 'Tabula writeoff_requests šobrīd nav pieejama.',
                'sortDirectionLabels' => ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'],
            ];
        }

        $baseQuery = WriteoffRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $deviceOptions = $this->writeoffDeviceOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id', 'code']))
                ->with(['device.type'])
                ->get()
        );

        $requesterOptions = $this->writeoffRequesterOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id', 'code']))
                ->with('responsibleUser')
                ->get()
        );

        $createDeviceOptions = ! $canReview
            ? $this->deviceOptions($this->availableDevicesForUser($user)->get())
            : collect();

        $requestsQuery = (clone $baseQuery)
            ->with(['device.type', 'responsibleUser', 'reviewedBy'])
            ->select('writeoff_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();

        return [
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
            'canReview' => $canReview,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'deviceOptions' => $deviceOptions,
            'createDeviceOptions' => $createDeviceOptions,
            'requesterOptions' => $requesterOptions,
            'selectedEditableRequest' => ! $canReview && ctype_digit((string) $request->query('modal_request'))
                ? WriteoffRequest::query()
                    ->with('device')
                    ->whereKey((int) $request->query('modal_request'))
                    ->where('responsible_user_id', $user->id)
                    ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                    ->first()
                : null,
            'sortDirectionLabels' => ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'],
        ];
    }

    /**
     * Atrod norakstīšanas pieteikumu pēc saistītās ierīces koda filtrētajā sarakstā.
     */
    public function findByCode(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'WriteoffRequest', $code, 'Meklēts norakstīšanas pieteikums pēc ierīces koda: '.$code);

        $canReview = $user->canManageRequests();
        $availableStatuses = [
            WriteoffRequest::STATUS_SUBMITTED,
            WriteoffRequest::STATUS_APPROVED,
            WriteoffRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        $baseQuery = WriteoffRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $requestsQuery = (clone $baseQuery)
            ->with('device:id,code')
            ->select('writeoff_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();
        $needle = mb_strtolower($code);
        $foundIndex = null;

        foreach ($requests as $index => $writeoffRequest) {
            $deviceCode = mb_strtolower(trim((string) ($writeoffRequest->device?->code ?? '')));
            if ($deviceCode === $needle) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === null) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        return response()->json([
            'found' => true,
            'page' => 1,
            'term' => $code,
            'highlight_id' => 'writeoff-request-'.$requests->values()[$foundIndex]->id,
        ]);
    }


    /**
     * Saglabā jaunu norakstīšanas pieteikumu.
     */
    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return redirect()->route('writeoff-requests.index')->with('error', 'Norakstīšanas pieteikumus šobrīd nevar saglabāt, jo tabula writeoff_requests nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'reason' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvēlies ierīci, kuru vēlies norakstīt.',
            'reason.required' => 'Apraksti norakstīšanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt norakstīšanu tikai savai piesaistītai ierīcei.'],
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
        AuditTrail::submit($user->id, $writeoffRequest, 'Iesniegts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));

        return redirect()->route('writeoff-requests.index')->with('success', 'Norakstīšanas pieteikums nosūtīts izskatīšanai');
    }

    /**
     * Administratora lēmums par norakstīšanas pieprasījumu.
     */
    public function review(Request $request, WriteoffRequest $writeoffRequest)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('writeoff_requests')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Norakstīšanas pieteikumu tabula šobrīd nav pieejama.'], 503);
            }

            return back()->with('error', 'Norakstīšanas pieteikumu tabula šobrīd nav pieejama.');
        }

        if ($writeoffRequest->status !== WriteoffRequest::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Šis pieteikums jau ir izskatīts.'], 409);
            }

            return back()->with('error', 'Šis pieteikums jau ir izskatīts.');
        }

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED])],
        ], [
            'status.required' => 'Izvēlies lēmumu norakstīšanas pieteikumam.',
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
                    'status' => ['Ierīce norakstīšanai vairs nav atrasta.'],
                ]);
            }

            if ($device->status !== Device::STATUS_ACTIVE || $device->activeRepair()->exists()) {
                throw ValidationException::withMessages([
                    'status' => ['Norakstīt var tikai aktīvu ierīci bez aktīva remonta procesā.'],
                ]);
            }

            $device->forceFill(array_merge(
                ['status' => Device::STATUS_WRITEOFF],
                $this->writeoffWarehousePayload($manager->id)
            ))->save();
        });

        $after = $writeoffRequest->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($manager->id, $writeoffRequest, $before, $after);
        if ($validated['status'] === WriteoffRequest::STATUS_APPROVED) {
            AuditTrail::approve($manager->id, $writeoffRequest, 'Apstiprināts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));
        } else {
            AuditTrail::reject($manager->id, $writeoffRequest, null, 'Noraidīts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Norakstīšanas pieteikums izskatīts',
                'status' => $validated['status'],
                'request_id' => $writeoffRequest->id,
            ]);
        }

        return back()->with('success', 'Norakstīšanas pieteikums izskatīts');
    }

    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->where('assigned_to_id', $user->id)
            ->where('status', Device::STATUS_ACTIVE)
            ->whereDoesntHave('repairs', fn (Builder $query) => $query->whereIn('status', ['waiting', 'in-progress']))
            ->whereDoesntHave('repairRequests', fn (Builder $query) => $query->where('status', RepairRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('writeoffRequests', fn (Builder $query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('transfers', fn (Builder $query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED))
            ->with(['type', 'building', 'room', 'activeRepair'])
            ->orderBy('name');
    }

    private function ensureDeviceCanAcceptWriteoffRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau notiek remonts ('.$this->repairStatusLabel($device->activeRepair?->status).'), tāpēc norakstīšanas pieteikumu veidot nevar.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums, tāpēc norakstīšanas pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums, tāpēc norakstīšanas pieteikumu veidot nevar.'],
            ]);
        }
    }

    private function deviceOptions($devices)
    {
        return collect($devices)->map(function (Device $device) {
            $description = collect([
                $device->type?->type_name,
                collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                $device->room?->room_number ? 'telpa '.$device->room->room_number : null,
                $device->building?->building_name,
            ])->filter()->implode(' | ');

            return [
                'value' => (string) $device->id,
                'label' => $device->name.' ('.($device->code ?: 'bez koda').')',
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
            'room_name' => WarehouseConfig::DEFAULT_ROOM_NAME,
            'user_id' => $preferredUserId,
            'department' => 'Inventārs',
            'notes' => 'Automātiski izveidota noklusētā noliktavas telpa.',
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
            'building_name' => WarehouseConfig::DEFAULT_BUILDING_NAME,
            'city' => 'Ludza',
            'total_floors' => 1,
            'notes' => 'Automātiski izveidota noklusētā ēka noliktavas telpai.',
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
            $candidate = WarehouseConfig::DEFAULT_ROOM_NUMBER_PREFIX.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
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

    /**
     * Sakārto saraksta filtru stāvokli, ieskaitot admina noklusēto "iesniegts".
     */
    private function normalizedIndexFilters(Request $request, array $availableStatuses, bool $canReview): array
    {
        $statusFilterTouched = $request->has('statuses_filter');
        $filtersCleared = $request->boolean('clear');
        $hasOtherFilters = $request->filled('q')
            || $request->filled('code')
            || $request->filled('device_id')
            || $request->filled('requester_id')
            || $request->filled('date_from')
            || $request->filled('date_to');
        $defaultStatuses = $canReview && ! $filtersCleared && ! $hasOtherFilters ? [WriteoffRequest::STATUS_SUBMITTED] : [];
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : $defaultStatuses))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        return [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'device_id' => ctype_digit((string) $request->query('device_id', '')) ? (int) $request->query('device_id') : null,
            'device_query' => trim((string) $request->query('device_query', '')),
            'requester_id' => ctype_digit((string) $request->query('requester_id', '')) ? (int) $request->query('requester_id') : null,
            'requester_query' => trim((string) $request->query('requester_query', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'statuses' => $selectedStatuses,
            'status_filter_touched' => $statusFilterTouched,
        ];
    }

    /**
     * Pielieto meklēšanu un filtrus pieteikumu vaicājumam.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        $skipLookup = array_flip($skip);

        if (! isset($skipLookup['code']) && $filters['code'] !== '') {
            $query->whereHas('device', function (Builder $deviceQuery) use ($filters) {
                $deviceQuery->where('code', $filters['code']);
            });
        }

        if (! isset($skipLookup['q']) && $filters['q'] !== '') {
            $term = $filters['q'];

            $query->whereHas('device', function (Builder $deviceQuery) use ($term) {
                $deviceQuery->where(function (Builder $q) use ($term) {
                    $q->where('code', 'like', "%{$term}%")
                      ->orWhere('serial_number', 'like', "%{$term}%")
                      ->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('manufacturer', 'like', "%{$term}%")
                      ->orWhere('model', 'like', "%{$term}%");
                });
            });
        }

        if (! isset($skipLookup['device_id']) && filled($filters['device_id'])) {
            $query->where('writeoff_requests.device_id', $filters['device_id']);
        }

        if (! isset($skipLookup['requester_id']) && filled($filters['requester_id'])) {
            $query->where('writeoff_requests.responsible_user_id', $filters['requester_id']);
        }

        if (! isset($skipLookup['date_from']) && filled($filters['date_from'])) {
            $query->whereDate('writeoff_requests.created_at', '>=', $filters['date_from']);
        }

        if (! isset($skipLookup['date_to']) && filled($filters['date_to'])) {
            $query->whereDate('writeoff_requests.created_at', '<=', $filters['date_to']);
        }

        if (! isset($skipLookup['statuses'])) {
            $selectedStatuses = $filters['statuses'] ?? [];

            if ($selectedStatuses !== [] && count($selectedStatuses) < 3) {
                $query->whereIn('writeoff_requests.status', $selectedStatuses);
            }
        }

        return $query;
    }

    /**
     * Pielieto drošu kārtošanu pēc atļautajām kolonnām.
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        $query
            ->leftJoin('devices as sortable_devices', 'writeoff_requests.device_id', '=', 'sortable_devices.id')
            ->leftJoin('users as sortable_requesters', 'writeoff_requests.responsible_user_id', '=', 'sortable_requesters.id');

        switch ($sorting['sort']) {
            case 'code':
                $query->orderByRaw('LOWER(COALESCE(sortable_devices.code, "")) '.$sorting['direction']);
                break;
            case 'name':
                $query->orderByRaw('LOWER(COALESCE(sortable_devices.name, "")) '.$sorting['direction']);
                break;
            case 'requester':
                $query->orderByRaw('LOWER(COALESCE(sortable_requesters.full_name, "")) '.$sorting['direction']);
                break;
            case 'status':
                $query->orderByRaw("
                    CASE writeoff_requests.status
                        WHEN 'submitted' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                        ELSE 4
                    END {$sorting['direction']}
                ");
                break;
            case 'created_at':
            default:
                $query->orderBy('writeoff_requests.created_at', $sorting['direction']);
                break;
        }

        $query->orderBy('writeoff_requests.id', $sorting['direction'] === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Normalizē kārtošanas parametrus tabulas galvenei un toast paziņojumiem.
     */
    private function normalizedSorting(Request $request): array
    {
        $sort = trim((string) $request->query('sort', 'created_at'));
        $direction = trim((string) $request->query('direction', 'desc'));

        if (! in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'created_at' ? 'desc' : 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $this->sortOptions()[$sort]['label'] ?? 'iesniegšanas datuma',
        ];
    }

    /**
     * Lietotāja paziņojumiem izmantojamās kārtošanas etiķetes.
     */
    private function sortOptions(): array
    {
        return [
            'code' => ['label' => 'koda'],
            'name' => ['label' => 'nosaukuma'],
            'requester' => ['label' => 'pieteicēja'],
            'created_at' => ['label' => 'iesniegšanas datuma'],
            'status' => ['label' => 'statusa'],
        ];
    }

    private function auditWriteoffRequestListInteractions(Request $request, User $user, array $filters, array $sorting): void
    {
        $filterPayload = array_filter([
            'teksts' => $filters['q'] ?? '',
            'ierīce' => $filters['device_query'] ?? '',
            'pieteicējs' => $filters['requester_query'] ?? '',
            'no datuma' => $filters['date_from'] ?? '',
            'līdz datumam' => $filters['date_to'] ?? '',
            'statusi' => count($filters['statuses'] ?? []) > 0 && count($filters['statuses'] ?? []) < 3 ? ($filters['statuses'] ?? []) : [],
        ], fn (mixed $value) => $value !== null && $value !== '' && $value !== []);

        if ($filterPayload !== []) {
            AuditTrail::filter(
                $user,
                'WriteoffRequest',
                $filterPayload,
                'Filtrēti norakstīšanas pieteikumi: '.implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
                    if (is_array($value)) {
                        return $label.': '.implode(', ', $value);
                    }

                    return $label.': '.$value;
                })->all())
            );
        }

        if (($sorting['sort'] ?? 'created_at') !== 'created_at' || ($sorting['direction'] ?? 'desc') !== 'desc' || $request->has('sort')) {
            AuditTrail::sort(
                $user,
                'WriteoffRequest',
                $sorting['label'] ?? 'iesniegšanas datuma',
                $sorting['direction'] ?? 'desc',
                'Kārtoti norakstīšanas pieteikumi pēc '.($sorting['label'] ?? 'iesniegšanas datuma').' '.(($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }
    }

    /**
     * Sagatavo ierīču dropdown opcijas norakstīšanas pieteikumu filtram.
     */
    private function writeoffDeviceOptions($requests)
    {
        return collect($requests)
            ->pluck('device')
            ->filter()
            ->unique('id')
            ->sortBy(fn (Device $device) => mb_strtolower($device->name.' '.$device->code))
            ->values()
            ->map(function (Device $device) {
                return [
                    'value' => (string) $device->id,
                    'label' => $device->name.' ('.($device->code ?: 'bez koda').')',
                    'description' => collect([
                        $device->type?->type_name,
                        collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                    ])->filter()->implode(' | '),
                    'search' => implode(' ', array_filter([
                        $device->name,
                        $device->code,
                        $device->serial_number,
                        $device->manufacturer,
                        $device->model,
                        $device->type?->type_name,
                    ])),
                ];
            });
    }

    /**
     * Sagatavo pieteicēju dropdown opcijas norakstīšanas pieteikumu filtram.
     */
    private function writeoffRequesterOptions($requests)
    {
        return collect($requests)
            ->pluck('responsibleUser')
            ->filter()
            ->unique('id')
            ->sortBy(fn (User $requester) => mb_strtolower($requester->full_name))
            ->values()
            ->map(fn (User $requester) => [
                'value' => (string) $requester->id,
                'label' => $requester->full_name,
                'description' => implode(' | ', array_filter([
                    $requester->job_title,
                    $requester->email,
                ])),
                'search' => implode(' ', array_filter([
                    $requester->full_name,
                    $requester->job_title,
                    $requester->email,
                ])),
            ]);
    }
}
