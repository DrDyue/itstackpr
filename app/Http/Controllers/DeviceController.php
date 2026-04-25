<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasRepairStatusLabels;
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
use App\Support\WarehouseConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Galvenais inventāra kontrolieris.
 *
 * Šī klase apvieno ierīču sarakstu, filtrus, pilno CRUD plūsmu,
 * ātrās darbības un statusu/pieprasījumu priekšskatījumu sagatavošanu.
 */
class DeviceController extends Controller
{
    use HasRepairStatusLabels;

    private const STATUSES = [Device::STATUS_ACTIVE, Device::STATUS_REPAIR, Device::STATUS_WRITEOFF];

    private const SORTABLE_COLUMNS = ['code', 'serial_number', 'name', 'location', 'created_at', 'assigned_to', 'status'];

    private const USER_VISIBLE_STATUSES = [Device::STATUS_ACTIVE, Device::STATUS_REPAIR];

    private const USER_SORTABLE_COLUMNS = ['code', 'serial_number', 'name', 'location', 'status'];

    /**
     * Parāda ierīču sarakstu ar lomām atkarīgu filtrēšanu un statusu palīgdatiem.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->devicesIndexViewData($request, $user);

        AuditTrail::viewed($user, 'Device', null, 'Atvērts ierīču saraksts.');
        $this->auditDeviceListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);

        return view('devices.index', $viewData);
    }

    /**
     * Atgriež filtrētu ierīču tabulu (async).
     */
    public function table(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->devicesIndexViewData($request, $user);
        $this->auditDeviceListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);
        return view('devices.index-table', [
            'devices' => $viewData['devices'],
            'deviceStates' => $viewData['deviceStates'],
            'sorting' => $viewData['sorting'],
            'sortOptions' => $viewData['sortOptions'],
            'statusLabels' => $viewData['statusLabels'],
            'canManageDevices' => $viewData['canManageDevices'],
            'quickRoomSelectOptions' => $viewData['quickRoomOptions'],
            'quickAssigneeOptions' => $viewData['quickAssigneeOptions'],
            'types' => $viewData['types'] ?? collect(),
            'buildings' => $viewData['buildings'] ?? collect(),
            'rooms' => $viewData['rooms'] ?? collect(),
            'users' => $viewData['users'] ?? collect(),
            'statuses' => $viewData['statuses'] ?? [],
            'defaultAssignedToId' => $viewData['defaultAssignedToId'] ?? null,
            'defaultRoomId' => $viewData['defaultRoomId'] ?? null,
            'defaultBuildingId' => $viewData['defaultBuildingId'] ?? null,
        ]);
    }

    /**
     * Atrod ierīci pēc koda un atgriež informāciju par lapu kurā tā atrodas.
     */
    public function findByCode(Request $request): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));

        if (empty($code)) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'Device', $code, 'Meklēta ierīce pēc koda: '.$code);

        $canManageDevices = $user->canManageRequests();
        $filters = $this->normalizedIndexFilters($request, $user);
        $sorting = $this->normalizedDeviceSorting($request, $canManageDevices);
        $accessibleRooms = $this->accessibleRooms($user);
        $types = DeviceType::query()
            ->select(['id', 'type_name'])
            ->orderBy('type_name')
            ->get();
        $assignableUsers = $canManageDevices
            ? User::query()
                ->active()
                ->select(['id', 'full_name', 'job_title', 'email'])
                ->orderBy('full_name')
                ->get()
            : collect();

        $selectedRoom = ctype_digit($filters['room_id'])
            ? $accessibleRooms->firstWhere('id', (int) $filters['room_id'])
            : null;
        $selectedType = ctype_digit($filters['type'])
            ? $types->firstWhere('id', (int) $filters['type'])
            : null;
        $selectedAssignedUser = $canManageDevices && ctype_digit($filters['assigned_to_id'])
            ? $assignableUsers->firstWhere('id', (int) $filters['assigned_to_id'])
            : null;

        $devicesQuery = $this->visibleDevicesQuery($user)->select('devices.id', 'devices.code');
        $this->applyDeviceIndexFilters($devicesQuery, $filters, $selectedAssignedUser, $selectedRoom, $selectedType);
        $this->applyDeviceIndexSorting($devicesQuery, $sorting);

        $allDevices = $devicesQuery->get();

        $foundDevice = null;
        $foundIndex = null;
        $searchCode = mb_strtolower($code);

        foreach ($allDevices as $index => $device) {
            $deviceCode = mb_strtolower(trim((string) ($device->code ?? '')));
            if ($deviceCode === $searchCode) {
                $foundDevice = $device;
                $foundIndex = $index;
                break;
            }
        }

        if (!$foundDevice || $foundIndex === null) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        return response()->json([
            'found' => true,
            'page' => 1,
            'device_id' => $foundDevice->id,
            'device_code' => $foundDevice->code,
            'term' => $code,
            'highlight_id' => 'device-'.$foundDevice->id,
        ]);
    }

    /**
     * Sagatavo visus datus ierīču saraksta lapai.
     *
     * Šī metode centralizē filtru normalizēšanu, ierīču atlasi, kārtošanu
     * un arī papildu palīgdatus, ko izmanto Blade skats.
     */
    private function devicesIndexViewData(Request $request, User $user): array
    {
        $canManageDevices = $user->canManageRequests();
        $filters = $this->normalizedIndexFilters($request, $user);
        $sorting = $this->normalizedDeviceSorting($request, $canManageDevices);

        $summaryQuery = $this->visibleDevicesQuery($user);
        $accessibleRooms = $this->accessibleRooms($user);
        $types = DeviceType::query()->orderBy('type_name')->get();
        $assignableUsers = $canManageDevices
            ? User::query()->active()->orderBy('full_name')->get()
            : collect();

        $selectedRoom = ctype_digit($filters['room_id'])
            ? $accessibleRooms->firstWhere('id', (int) $filters['room_id'])
            : null;
        $selectedType = ctype_digit($filters['type'])
            ? $types->firstWhere('id', (int) $filters['type'])
            : null;
        $selectedAssignedUser = $canManageDevices && ctype_digit($filters['assigned_to_id'])
            ? $assignableUsers->firstWhere('id', (int) $filters['assigned_to_id'])
            : null;

        if ($selectedRoom) {
            $filters['floor'] = (string) $selectedRoom->floor_number;
            $filters['floor_query'] = $selectedRoom->floor_number . '. stāvs';
            $filters['room_query'] = $selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : '');
        }

        if ($selectedType) {
            $filters['type_query'] = $selectedType->type_name;
        }

        if ($selectedAssignedUser) {
            $filters['assigned_to_query'] = $selectedAssignedUser->full_name;
        } elseif ($canManageDevices) {
            $filters['assigned_to_query'] = '';
        }

        $maxFloor = (int) ($accessibleRooms->max('floor_number') ?? 0);
        $floorOptions = $maxFloor > 0 ? range(1, $maxFloor) : [];
        $roomOptions = $accessibleRooms
            ->when(
                $filters['floor'] !== '' && ctype_digit($filters['floor']),
                fn (Collection $rooms) => $rooms->filter(
                    fn (Room $room) => (int) $room->floor_number === (int) $filters['floor']
                )
            )
            ->values();

        $devicesQuery = $this->visibleDevicesQuery($user)
            ->select('devices.*')
            ->leftJoin('rooms as sort_rooms', 'sort_rooms.id', '=', 'devices.room_id')
            ->leftJoin('buildings as sort_buildings', 'sort_buildings.id', '=', 'devices.building_id')
            ->leftJoin('users as sort_users', 'sort_users.id', '=', 'devices.assigned_to_id')
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
            ->selectSub(
                DB::table('repairs')
                    ->select('status')
                    ->whereColumn('repairs.device_id', 'devices.id')
                    ->whereIn('status', ['waiting', 'in-progress'])
                    ->orderByDesc('id')
                    ->limit(1),
                'sort_repair_progress'
            );

        $this->applyDeviceIndexFilters(
            $devicesQuery,
            $filters,
            $selectedAssignedUser,
            $selectedRoom,
            $selectedType
        );

        $this->applyDeviceIndexSorting($devicesQuery, $sorting);

        $devices = $devicesQuery->get();

        $deviceStates = $devices
            ->mapWithKeys(function (Device $device) use ($user) {
                $pendingRepairRequest = $device->pendingRepairRequest;
                $pendingWriteoffRequest = $device->pendingWriteoffRequest;
                $pendingTransferRequest = $device->pendingTransferRequest;
                $hasPendingRepairRequest = (bool) $pendingRepairRequest;
                $hasPendingWriteoffRequest = (bool) $pendingWriteoffRequest;
                $hasPendingTransferRequest = (bool) $pendingTransferRequest;
                $requestAvailability = $this->requestAvailabilityForDevice(
                    $device,
                    $hasPendingRepairRequest,
                    $hasPendingWriteoffRequest,
                    $hasPendingTransferRequest,
                );

                return [
                    $device->id => [
                        'requestAvailability' => $requestAvailability,
                        'roomUpdateAvailability' => $this->userRoomUpdateAvailability(
                            $device,
                            $pendingRepairRequest,
                            $pendingWriteoffRequest,
                            $pendingTransferRequest,
                        ),
                        'pendingRequestBadge' => $this->pendingRequestBadge(
                            $device,
                            $user->canManageRequests(),
                            $hasPendingRepairRequest,
                            $hasPendingWriteoffRequest,
                            $hasPendingTransferRequest,
                            $pendingRepairRequest,
                            $pendingWriteoffRequest,
                            $pendingTransferRequest,
                        ),
                        'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
                        'repairPreview' => $this->repairPreview($device),
                    ],
                ];
            })
            ->all();

        return array_merge([
            'devices' => $devices,
            'deviceStates' => $deviceStates,
            'filters' => $filters,
            'sorting' => $sorting,
            'deviceSummary' => [
                'total' => (clone $summaryQuery)->count(),
                'active' => (clone $summaryQuery)->where('status', Device::STATUS_ACTIVE)->count(),
                'repair' => (clone $summaryQuery)->where('status', Device::STATUS_REPAIR)->count(),
                'writeoff' => (clone $summaryQuery)->where('status', Device::STATUS_WRITEOFF)->count(),
                'active_requests' => $this->activeDeviceRequestFilterQuery(clone $summaryQuery)->count(),
            ],
            'floorOptions' => $floorOptions,
            'roomOptions' => $roomOptions,
            'selectedRoom' => $selectedRoom,
            'types' => $types,
            'selectedType' => $selectedType,
            'selectedAssignedUser' => $selectedAssignedUser,
            'assignableUsers' => $assignableUsers,
            'filterStatuses' => $this->availableStatuses($user),
            'statuses' => $canManageDevices ? self::STATUSES : self::USER_VISIBLE_STATUSES,
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $canManageDevices,
            'quickRoomOptions' => $canManageDevices ? $this->quickRoomOptions() : collect(),
            'userRoomOptions' => $this->roomSelectOptions($accessibleRooms),
            'quickAssigneeOptions' => $canManageDevices ? $this->quickAssigneeOptions() : collect(),
            'sortOptions' => $this->deviceSortOptions($canManageDevices),
            'deviceModalQuery' => (string) $request->query('device_modal', ''),
            'deviceModalDeviceId' => ctype_digit((string) $request->query('modal_device'))
                ? (int) $request->query('modal_device')
                : null,
            'selectedModalDevice' => ctype_digit((string) $request->query('modal_device'))
                ? Device::query()
                    ->with([
                        'activeRepair',
                        'pendingRepairRequest',
                        'pendingWriteoffRequest',
                        'pendingTransferRequest',
                    ])
                    ->find((int) $request->query('modal_device'))
                : null,
        ], $canManageDevices ? $this->formData() : []);
    }

    /**
     * Normalizē visus ierīču saraksta filtrus vienotā formā.
     */
    private function normalizedIndexFilters(Request $request, User $user): array
    {
        $availableStatuses = $this->availableStatuses($user);
        $statuses = collect($request->query('status', []))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        return [
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
            'statuses' => $statuses,
            'has_status_filter' => count($statuses) > 0 && count($statuses) < count($availableStatuses),
            'active_requests' => $request->boolean('active_requests'),
        ];
    }

    /**
     * Sagatavo drošu kārtošanas konfigurāciju no query string.
     */
    private function normalizedDeviceSorting(Request $request, bool $canManageDevices): array
    {
        $sortOptions = $this->deviceSortOptions($canManageDevices);
        $sortableColumns = array_keys($sortOptions);
        $sort = trim((string) $request->query('sort', 'created_at'));
        $direction = trim((string) $request->query('direction', 'desc'));

        if (! in_array($sort, $sortableColumns, true)) {
            $sort = $canManageDevices ? 'created_at' : 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        if (! $canManageDevices && $sort !== 'status') {
            $direction = 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $sortOptions[$sort]['label'] ?? ($canManageDevices ? 'Izveidots' : 'Nosaukums'),
            'direction_label' => $direction === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā',
        ];
    }

    /**
     * Uzliek atlasītajam ierīču saraksta query visus filtru nosacījumus.
     */
    private function applyDeviceIndexFilters(
        Builder $query,
        array $filters,
        ?User $selectedAssignedUser,
        ?Room $selectedRoom,
        ?DeviceType $selectedType
    ): void {
        $query
            ->when($filters['q'] !== '', function (Builder $deviceQuery) use ($filters) {
                $term = $filters['q'];

                $deviceQuery->where(function (Builder $nestedQuery) use ($term) {
                    $nestedQuery->where('devices.name', 'like', "%{$term}%")
                        ->orWhere('devices.serial_number', 'like', "%{$term}%")
                        ->orWhere('devices.manufacturer', 'like', "%{$term}%")
                        ->orWhere('devices.model', 'like', "%{$term}%");
                });
            })
            ->when($selectedAssignedUser instanceof User, fn (Builder $deviceQuery) => $deviceQuery->where('devices.assigned_to_id', $selectedAssignedUser->id))
            ->when($filters['floor'] !== '' && ctype_digit($filters['floor']), function (Builder $deviceQuery) use ($filters) {
                $deviceQuery->whereHas('room', fn (Builder $roomQuery) => $roomQuery->where('floor_number', (int) $filters['floor']));
            })
            ->when($filters['floor'] === '' && $filters['floor_query'] !== '', function (Builder $deviceQuery) use ($filters) {
                $normalizedFloor = preg_replace('/\D+/', '', $filters['floor_query']);

                if (! is_string($normalizedFloor) || $normalizedFloor === '' || ! ctype_digit($normalizedFloor)) {
                    return;
                }

                $deviceQuery->whereHas('room', fn (Builder $roomQuery) => $roomQuery->where('floor_number', (int) $normalizedFloor));
            })
            ->when($selectedRoom instanceof Room, fn (Builder $deviceQuery) => $deviceQuery->where('devices.room_id', $selectedRoom->id))
            ->when(! ($selectedRoom instanceof Room) && $filters['room_query'] !== '', function (Builder $deviceQuery) use ($filters) {
                $term = $filters['room_query'];

                $deviceQuery->whereHas('room', function (Builder $roomQuery) use ($term) {
                    $roomQuery->where('room_number', 'like', "%{$term}%")
                        ->orWhere('room_name', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%");
                });
            })
            ->when($selectedType instanceof DeviceType, fn (Builder $deviceQuery) => $deviceQuery->where('devices.device_type_id', $selectedType->id))
            ->when(! ($selectedType instanceof DeviceType) && $filters['type_query'] !== '', function (Builder $deviceQuery) use ($filters) {
                $term = $filters['type_query'];

                $deviceQuery->whereHas('type', function (Builder $typeQuery) use ($term) {
                    $typeQuery->where('type_name', 'like', "%{$term}%");
                });
            })
            ->when(
                $filters['has_status_filter'],
                fn (Builder $deviceQuery) => $deviceQuery->whereIn('devices.status', $filters['statuses'])
            )
            ->when(
                $filters['active_requests'],
                fn (Builder $deviceQuery) => $this->activeDeviceRequestFilterQuery($deviceQuery)
            );
    }

    /**
     * Pielieto filtrēšanas nosacījumu ierīcēm ar aktīviem (iesniegtiem) pieprasījumiem.
     *
     * Atlasa ierīces, kurām ir vismaz viens gaidošs remonta vai norakstīšanas pieteikums.
     * Izmantota gan kopsavilkuma skaitļu aprēķinā, gan filtra aktivizēšanas gadījumā.
     */
    private function activeDeviceRequestFilterQuery(Builder $query): Builder
    {
        return $query->where(function (Builder $requestQuery) {
            $requestQuery
                ->whereHas('repairRequests', fn (Builder $repairRequestQuery) => $repairRequestQuery->where('status', RepairRequest::STATUS_SUBMITTED))
                ->orWhereHas('writeoffRequests', fn (Builder $writeoffRequestQuery) => $writeoffRequestQuery->where('status', WriteoffRequest::STATUS_SUBMITTED));
        });
    }

    /**
     * Uzliek query vajadzīgo kārtošanas kārtību.
     */
    private function applyDeviceIndexSorting(Builder $query, array $sorting): void
    {
        $direction = $sorting['direction'] === 'asc' ? 'asc' : 'desc';

        match ($sorting['sort']) {
            'code' => $query
                ->orderByRaw('LOWER(COALESCE(devices.code, \'\')) ' . $direction)
                ->orderBy('devices.id'),
            'serial_number' => $query
                ->orderByRaw('LOWER(COALESCE(devices.serial_number, \'\')) ' . $direction)
                ->orderBy('devices.id'),
            'name' => $query
                ->orderByRaw('LOWER(COALESCE(devices.name, \'\')) ' . $direction)
                ->orderBy('devices.id'),
            'location' => $query
                ->orderByRaw('LOWER(COALESCE(sort_buildings.building_name, \'\')) ' . $direction)
                ->orderBy('sort_rooms.floor_number', $direction)
                ->orderByRaw('LOWER(COALESCE(sort_rooms.room_number, \'\')) ' . $direction)
                ->orderByRaw('LOWER(COALESCE(sort_rooms.room_name, \'\')) ' . $direction)
                ->orderBy('devices.id'),
            'assigned_to' => $query
                ->orderByRaw('LOWER(COALESCE(sort_users.full_name, \'\')) ' . $direction)
                ->orderBy('devices.id'),
            'status' => $query
                ->orderByRaw($this->deviceStatusSortExpression() . ' ' . $direction)
                ->orderBy('devices.id', $direction),
            default => $query
                ->orderBy('devices.created_at', $direction)
                ->orderBy('devices.id', $direction),
        };
    }

    /**
     * Atgriež statusa kolonnas prioritāšu secību SQL CASE formā.
     */
    private function deviceStatusSortExpression(): string
    {
        return <<<'SQL'
CASE
    WHEN devices.status = 'active' AND (
        COALESCE(has_pending_repair_request, 0) = 1
        OR COALESCE(has_pending_writeoff_request, 0) = 1
        OR COALESCE(has_pending_transfer_request, 0) = 1
    ) THEN 1
    WHEN devices.status = 'active' THEN 2
    WHEN devices.status = 'repair' AND sort_repair_progress = 'waiting' THEN 3
    WHEN devices.status = 'repair' AND sort_repair_progress = 'in-progress' THEN 4
    WHEN devices.status = 'writeoff' THEN 5
    ELSE 6
END
SQL;
    }

    /**
     * Apraksta, kādi lauki lietotājam ir kārtojami un kā saucas paziņojumos.
     */
    private function deviceSortOptions(bool $canManageDevices = true): array
    {
        $options = [
            'code' => ['label' => 'koda'],
            'serial_number' => ['label' => 'sērijas numura'],
            'name' => ['label' => 'nosaukuma'],
            'location' => ['label' => 'atrašanās vietas'],
            'created_at' => ['label' => 'izveides datuma'],
            'assigned_to' => ['label' => 'piešķirtās personas'],
            'status' => ['label' => 'statusa'],
        ];

        if ($canManageDevices) {
            return $options;
        }

        return array_intersect_key($options, array_flip(self::USER_SORTABLE_COLUMNS));
    }

    /**
     * Atgriež ierīces statusu sarakstu, ko drīkst redzēt konkrētais lietotājs.
     *
     * Parasts lietotājs redz tikai aktīvās un remontā esošās ierīces.
     * Administrators redz visus statusus, izņemot gadījumu, ja ir aktivizēts
     * preferenču iestatījums par norakstīto ierīču slēpšanu.
     */
    private function availableStatuses(User $user): array
    {
        if (! $user->canManageRequests()) {
            return self::USER_VISIBLE_STATUSES;
        }

        return $user->prefersHiddenWrittenOffDevices()
            ? self::USER_VISIBLE_STATUSES
            : self::STATUSES;
    }

    /**
     * Reģistrē filtrēšanas un kārtošanas darbības ierīču sarakstā audita žurnālā.
     *
     * Tiek reģistrēts tikai tad, ja filtra vai kārtošanas vērtības atšķiras
     * no noklusētajām, lai neaizpildītu žurnālu ar nevajadzīgiem ierakstiem.
     */
    private function auditDeviceListInteractions(Request $request, User $user, array $filters, array $sorting): void
    {
        $filterPayload = array_filter([
            'teksts' => $filters['q'] ?? '',
            'piešķirtais lietotājs' => $filters['assigned_to_query'] ?? '',
            'stāvs' => ($filters['floor_query'] ?? '') !== '' ? ($filters['floor_query'] ?? '') : ($filters['floor'] ?? ''),
            'telpa' => $filters['room_query'] ?? '',
            'ierīces tips' => $filters['type_query'] ?? '',
            'statusi' => $filters['has_status_filter'] ? ($filters['statuses'] ?? []) : [],
            'aktīvie pieteikumi' => ($filters['active_requests'] ?? false) ? 'jā' : '',
        ], fn (mixed $value) => $value !== null && $value !== '' && $value !== []);

        if ($filterPayload !== []) {
            AuditTrail::filter(
                $user,
                'Device',
                $filterPayload,
                'Filtrēts ierīču saraksts: '.implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
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
                'Device',
                $sorting['label'] ?? 'izveides datuma',
                $sorting['direction'] ?? 'desc',
                'Kārtots ierīču saraksts pēc '.($sorting['label'] ?? 'izveides datuma').' '.(($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }
    }

    /**
     * Saglabā jaunu ierīci sistēmā.
     */
    public function store(Request $request)
    {
        $user = $this->requireManager();

        $device = new Device();
        $this->saveDevicePayload($device, array_merge(
            $this->validatedData($request),
            ['created_by' => $user->id]
        ));

        $this->syncUploads($request, $device);
        $this->removeDeviceImage($request, $device);

        AuditTrail::created($user->id, $device);

        return redirect()->route('devices.index')->with('success', 'Ierīce veiksmīgi pievienota');
    }

    /**
     * Parāda detalizētu ierīces kartīti.
     */
    public function show(Device $device)
    {
        $this->authorizeView($device);

        $user = $this->user();
        AuditTrail::viewed($user, 'Device', (string) $device->id, 'Atvērta ierīces karte: '.AuditTrail::labelFor($device));
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
            'repairs.acceptedBy',
            'repairs.executor',
            'repairs.request.responsibleUser',
            'repairs.request.reviewedBy',
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
                    $room->floor_number !== null ? $room->floor_number.'. stāvs' : null,
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

        $roomOptions = $this->roomSelectOptions(
            Room::query()
                ->with('building')
                ->orderBy('building_id')
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get()
        );

        $pendingRepairRequest = $device->repairRequests->firstWhere('status', 'submitted');
        $pendingWriteoffRequest = $device->writeoffRequests->firstWhere('status', 'submitted');
        $pendingTransferRequest = $device->transfers->firstWhere('status', 'submitted');
        $roomUpdateAvailability = $this->userRoomUpdateAvailability($device, $pendingRepairRequest, $pendingWriteoffRequest, $pendingTransferRequest);

        return view('devices.show', [
            'device' => $device,
            'deviceImageUrl' => $device->deviceImageUrl(),
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $this->user()?->canManageRequests() ?? false,
            'visibleRepairRequests' => $device->repairRequests->sortByDesc('created_at')->values(),
            'visibleRepairs' => $device->repairs->sortByDesc('created_at')->values(),
            'originLabel' => $latestTransferToCurrentUser
                ? 'Ierīce tev nodota no '.($latestTransferToCurrentUser->responsibleUser?->full_name ?: 'cita lietotāja').'.'
                : 'Ierīci tev piešķīra administrators.',
            'latestTransferToCurrentUser' => $latestTransferToCurrentUser,
            'roomOptions' => $roomOptions,
            'visibleWriteoffRequests' => $device->writeoffRequests->sortByDesc('created_at')->values(),
            'visibleTransfers' => $device->transfers->sortByDesc('created_at')->values(),
            'requestAvailability' => $this->requestAvailabilityForDevice(
                $device,
                (bool) $pendingRepairRequest,
                (bool) $pendingWriteoffRequest,
                (bool) $pendingTransferRequest,
            ),
            'roomUpdateAvailability' => $roomUpdateAvailability,
            'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
            'repairStatusDescription' => $this->repairStatusDescription($device),
        ]);
    }

    /**
     * Lietotāja skatā atļauj nomainīt tikai telpu savai ierīcei.
     */
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
            return $this->redirectAfterQuickAction($device, 'error', $roomUpdateAvailability['reason']);
        }

        $validated = $this->validateInput($request, [
            'room_id' => ['nullable', 'exists:rooms,id'],
        ], [
            'room_id.exists' => 'Izvēlētā telpa nav atrasta.',
        ]);

        $roomId = $validated['room_id'] ?? null;
        $room = $roomId ? Room::query()->with('building')->find($roomId) : null;

        $payload = [
            'room_id' => $room?->id,
            'building_id' => $room?->building_id,
        ];

        if ((int) $device->room_id === (int) ($room?->id ?? 0) && (int) $device->building_id === (int) ($room?->building_id ?? 0)) {
            return $this->redirectAfterQuickAction($device, 'error', 'Ierīce jau atrodas šajā telpā.');
        }

        $before = $device->only(['room_id', 'building_id']);
        $this->saveDevicePayload($device, $payload);
        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'room_id' => $device->room_id,
            'building_id' => $device->building_id,
        ]);

        return $this->redirectAfterQuickAction($device, 'success', 'Ierīces atrašanās vieta atjaunināta.');
    }

    /**
     * Atjaunina esošās ierīces datus, ievērojot aktīvo pieprasījumu bloķēšanu.
     *
     * Pirms saglabāšanas pārbauda, vai ierīcei nav aktīvu pieteikumu, kas
     * aizliedz rediģēšanu. Saglabā attēla izmaiņas un reģistrē visas
     * lauku izmaiņas audita žurnālā, salīdzinot "pirms" un "pēc" stāvokļus.
     */
    public function update(Request $request, Device $device)
    {
        $this->requireManager();

        if ($blockedReason = $this->activeRequestEditBlockedReason($device)) {
            return redirect()->route('devices.index')->with('error', $blockedReason);
        }

        $before = $device->only($this->trackedFields());

        $this->saveDevicePayload($device, $this->validatedData($request, $device));
        $this->syncUploads($request, $device);
        $this->removeDeviceImage($request, $device);

        $after = $device->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $device, $before, $after);

        return redirect()->route('devices.index')->with('success', 'Ierīces dati atjaunināti');
    }

    /**
     * Dzēš ierīces ierakstu.
     */
    public function destroy(Device $device)
    {
        $this->requireManager();

        $this->deleteDeviceAssets($device);
        AuditTrail::deleted(auth()->id(), $device, severity: AuditTrail::SEVERITY_WARNING);
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Ierīce dzēsta');
    }

    /**
     * Ātrā atjaunošana no tabulas dropdown darbībām.
     */
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
            'action.required' => 'Izvēlies darbību, ko veikt ar ierīci.',
            'target_status.required' => 'Izvēlies jauno ierīces statusu.',
            'target_room_id.required' => 'Izvēlies telpu, uz kuru pārvietot ierīci.',
            'target_assigned_to_id.required' => 'Izvēlies atbildīgo personu.',
        ]);

        if (($validated['action'] ?? null) === 'status' && ($validated['target_status'] ?? null) === Device::STATUS_REPAIR) {
            return redirect()
                ->route('repairs.index', [
                    'repair_modal' => 'create',
                    'device_id' => $device->id,
                ])
                ->with('success', 'Atvērta remonta forma izvēlētajai ierīcei.');
        }

        $result = $this->performDeviceAction($device, $validated);

        return $this->redirectAfterQuickAction($device, $result['level'], $result['message']);
    }

    /**
     * Pāradresē no vecā ātrās rediģēšanas ceļa uz pilno ierīces skatu.
     */
    public function quickUpdateRedirect(Device $device): RedirectResponse
    {
        $this->requireManager();

        return redirect()
            ->route('devices.show', $device)
            ->with('error', 'Šo adresi nevar atvērt ar GET pieprasījumu. Izmanto darbību pogas no ierīces saraksta.');
    }

    /**
     * Veic masveida darbību ar vairākām ierīcēm vienlaikus (statuss vai telpa).
     *
     * Apstrādā katru ierīci atsevišķi, uzskaita veiksmīgi apstrādātās un
     * apkopo kļūdu ziņojumus par neizdevušajām. Rezultāts tiek attēlots
     * kā vienots paziņojums par apstrādāto ierīču skaitu.
     */
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
            'device_ids.required' => 'Izvēlies vismaz vienu ierīci.',
            'device_ids.min' => 'Izvēlies vismaz vienu ierīci.',
            'target_status.required' => 'Masveida statusa mainai izvēlies jauno statusu.',
            'target_room_id.required' => 'Masveida pārvietošanai izvēlies telpu.',
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

        $flash = $processed > 0 ? 'Apstrādātās ierīces: '.$processed.'.' : 'Neviena ierīce netika apstrādāta.';
        if ($messages !== []) {
            $flash .= ' '.implode(' ', array_slice($messages, 0, 3));
        }

        return redirect()->route('devices.index')->with($processed > 0 ? 'success' : 'error', $flash);
    }

    /**
     * Atgriež bāzes vaicājumu ar ierīcēm, kuras drīkst redzēt konkrētais lietotājs.
     *
     * Parasts lietotājs redz tikai savas ierīces (izņemot norakstītās).
     * Administrators redz visas ierīces, bet var izvēlēties slēpt norakstītās
     * atbilstoši saviem preferenču iestatījumiem.
     */
    private function visibleDevicesQuery(User $user): Builder
    {
        return Device::query()
            ->when(
                ! $user->canManageRequests(),
                fn (Builder $query) => $query
                    ->where('assigned_to_id', $user->id)
                    ->where('status', '!=', Device::STATUS_WRITEOFF)
            )
            ->when(
                $user->canManageRequests() && $user->prefersHiddenWrittenOffDevices(),
                fn (Builder $query) => $query->where('status', '!=', Device::STATUS_WRITEOFF)
            );
    }

    /**
     * Atgriež telpu kolekciju, kurās atrodas lietotājam redzamās ierīces.
     *
     * Izmantota filtra izvēlnē — parādīt tikai tās telpas, kurās ir
     * šim lietotājam pieejamas ierīces. Administrators redz visas telpas
     * (izņemot norakstīto slēpšanas gadījumu).
     */
    private function accessibleRooms(User $user): Collection
    {
        return Room::query()
            ->select(['id', 'building_id', 'floor_number', 'room_number', 'room_name', 'department'])
            ->with('building:id,building_name')
            ->whereHas('devices', function (Builder $query) use ($user) {
                if (! $user->canManageRequests() || $user->prefersHiddenWrittenOffDevices()) {
                    $query->where('status', '!=', Device::STATUS_WRITEOFF);
                }

                if (! $user->canManageRequests()) {
                    $query->where('assigned_to_id', $user->id);
                }
            })
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
    }

    /**
     * Pārbauda, vai aktīvais lietotājs drīkst apskatīt konkrēto ierīci.
     *
     * Izmanto modeļa metodi `canViewDevice`, lai noteiktu piekļuves tiesības.
     * Ja lietotājs nav autentificēts vai nav tiesīgs, tiek atgriezta kļūda 403.
     */
    private function authorizeView(Device $device): void
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canViewDevice($device), 403);
    }

    /**
     * Sagatavo visus palīgdatus ierīces izveides un rediģēšanas formai.
     *
     * Iekļauj tipu, ēku, telpu un lietotāju sarakstus, kā arī noklusētās
     * vērtības jaunas ierīces veidlapai — noklusēto telpu un atbildīgo personu.
     */
    private function formData(): array
    {
        $warehouseRoom = $this->ensureWarehouseRoom();
        $defaultResponsibleUserId = $this->defaultResponsibleUserId();

        return [
            'types' => DeviceType::query()->select(['id', 'type_name'])->orderBy('type_name')->get(),
            'buildings' => Building::query()->select(['id', 'building_name'])->orderBy('building_name')->get(),
            'rooms' => Room::query()->select(['id', 'building_id', 'room_number', 'room_name'])->with('building:id,building_name')->orderBy('room_number')->get(),
            'users' => User::query()->active()->select(['id', 'full_name', 'job_title', 'email'])->orderBy('full_name')->get(),
            'statuses' => self::STATUSES,
            'statusLabels' => $this->statusLabels(),
            'defaultAssignedToId' => $defaultResponsibleUserId,
            'defaultRoomId' => $warehouseRoom->id,
            'defaultBuildingId' => $warehouseRoom->building_id,
        ];
    }

    /**
     * Validē un normalizē ierīces ievaddatus pirms saglabāšanas.
     *
     * Pārbauda obligātos laukus, koda unikalitāti, garantijas datuma loģiku.
     * Ja ierīcei statuss ir "norakstīta", tiek automātiski pievienots noliktavas
     * telpas un atbildīgās personas nulle. Statusu maiņu var bloķēt aktīvs
     * remonta vai pieprasījuma ieraksts.
     */
    private function validatedData(Request $request, ?Device $device = null): array
    {
        if (! $device) {
            $request->merge([
                'status' => Device::STATUS_ACTIVE,
            ]);
        }

        $normalizedStatus = Device::normalizeStatus(
            (string) $request->input('status', $device?->status ?? Device::STATUS_ACTIVE)
        );
        $requiresAssignmentAndRoom = $normalizedStatus !== Device::STATUS_WRITEOFF;

        if ($requiresAssignmentAndRoom) {
            if (! $request->filled('assigned_to_id')) {
                $request->merge([
                    'assigned_to_id' => $this->fallbackResponsibleUserId($device),
                ]);
            }

            if (! $request->filled('room_id')) {
                $warehouseRoom = $this->ensureWarehouseRoom();

                $request->merge([
                    'room_id' => $warehouseRoom->id,
                    'building_id' => $warehouseRoom->building_id,
                ]);
            }
        }

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
                'code.required' => 'Norādi ierīces kodu.',
                'name.required' => 'Norādi ierīces nosaukumu.',
                'device_type_id.required' => 'Izvēlies ierīces tipu.',
                'model.required' => 'Norādi ierīces modeli.',
                'status.required' => 'Izvēlies ierīces statusu.',
                'assigned_to_id.required' => 'Izvēlies atbildīgo personu.',
                'room_id.required' => 'Izvēlies telpu.',
                'purchase_price.min' => 'Iegādes cenai jābūt 0 vai lielākai.',
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
                'warranty_until' => ['Garantijas datums nevar būt agraks par pirkuma datumu.'],
            ]);
        }

        if (($data['status'] ?? null) === Device::STATUS_WRITEOFF) {
            $data = array_merge($data, $this->writeoffWarehousePayload());
        }

        if (
            $device
            && ($data['status'] ?? $device->status) !== $device->status
            && ($blockedReason = $this->deviceStatusEditBlockedReason($device))
        ) {
            throw ValidationException::withMessages([
                'status' => [$blockedReason],
            ]);
        }

        if ($device && $device->status === Device::STATUS_WRITEOFF) {
            $data['status'] = Device::STATUS_WRITEOFF;
            $data = array_merge($data, $this->writeoffWarehousePayload());
        }

        unset($data['device_image']);

        $data['device_image_url'] = $device?->device_image_url;

        return $data;
    }

    /**
     * Atgriež pašreizējā administratora ID, ja tas ir aktīvs lietotājs.
     *
     * Izmantota kā noklusētā atbildīgā persona jaunu ierīču veidlapā.
     * Ja administrators nav aktīvs sistēmā, atgriež null.
     */
    private function defaultResponsibleUserId(): ?int
    {
        $userId = $this->user()?->id;

        if (! $userId) {
            return null;
        }

        return User::query()
            ->active()
            ->whereKey($userId)
            ->exists()
            ? (int) $userId
            : null;
    }

    /**
     * Atgriež pirmo derīgo atbildīgās personas ID, pārbaudot vairākus kandidātus pēc kārtas.
     *
     * Prioritātes secība: ierīces pašreizējais atbildīgais → pašreizējais administrators →
     * ierīces izveidotājs. Tiek atgriezts pirmais kandidāts, kas pastāv aktīvo
     * lietotāju sarakstā. Ja neviens neatbilst, atgriež null.
     */
    private function fallbackResponsibleUserId(?Device $device = null): ?int
    {
        foreach ([
            $device?->assigned_to_id,
            $this->defaultResponsibleUserId(),
            $device?->created_by,
        ] as $candidateId) {
            if (! $candidateId) {
                continue;
            }

            $activeUser = User::query()
                ->active()
                ->select('id')
                ->find($candidateId);

            if ($activeUser) {
                return (int) $activeUser->id;
            }
        }

        return null;
    }

    /**
     * Atrod vai izveido sistēmas noliktavas telpu.
     *
     * Meklē telpu, kuras nosaukumā vai numurā ir vārds "noliktav".
     * Ja tāda nav, izveido jaunu telpu piemērotā ēkā ar automātiski
     * ģenerētu numuru un standarta noliktavas nosaukumu no WarehouseConfig.
     */
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
            'room_name' => WarehouseConfig::DEFAULT_ROOM_NAME,
            'user_id' => $this->user()?->id,
            'department' => 'Inventārs',
            'notes' => 'Automātiski izveidota noklusētā noliktavas telpa.',
        ])->load('building');
    }

    /**
     * Atgriež ierīces lauku vērtības, ko piemērot norakstīšanas gadījumā.
     *
     * Norakstīta ierīce tiek automātiski pārvietota uz noliktavas telpu
     * un atbrīvota no atbildīgās personas (assigned_to_id kļūst null).
     */
    private function writeoffWarehousePayload(): array
    {
        $warehouseRoom = $this->ensureWarehouseRoom();

        return [
            'assigned_to_id' => null,
            'building_id' => $warehouseRoom->building_id,
            'room_id' => $warehouseRoom->id,
        ];
    }

    /**
     * Atrod vispiemērotāko ēku noliktavas telpas izveidei.
     *
     * Priekšroka tiek dota ēkām ar "ludz" nosaukumā. Ja tādas nav,
     * tiek izmantota pirmā ēka alfabētiskā secībā. Ja sistēmā vēl nav
     * nevienas ēkas, tā tiek automātiski izveidota ar noklusēto nosaukumu.
     */
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

    /**
     * Ģenerē nākamo pieejamo noliktavas telpas numuru norādītajā ēkā.
     *
     * Pārbauda esošos telpu numurus un atrod pirmo neaizņemto numuru
     * pēc kārtas, izmantojot noliktavas prefiksu un trīsciparu formatējumu.
     */
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

    /**
     * Pārbauda, vai teksta virkne norāda uz noliktavas telpu.
     *
     * Meklē vārda sakni "noliktav" (reģistrjutīgi) telpas nosaukumā,
     * numurā vai piezīmēs, lai identificētu esošo noliktavas telpu.
     */
    private function isWarehouseLabel(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'noliktav');
    }

    /**
     * Pārbauda, vai ēkas nosaukums atbilst noliktavas vēlamajai atrašanās vietai.
     *
     * Meklē vārdu "ludz" ēkas nosaukumā (reģistrjutīgi), jo sistēma ir
     * paredzēta lietošanai Ludzā. Tiek izmantota, izvēloties piemērotāko ēku.
     */
    private function matchesPreferredBuildingName(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'ludz');
    }

    /**
     * Atgriež to ierīces lauku sarakstu, kuru izmaiņas tiek reģistrētas auditā.
     *
     * Tiek izmantots pirms un pēc atjaunināšanas, lai salīdzinātu stāvokļus
     * un ierakstītu tikai faktiskās izmaiņas audita žurnālā.
     */
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

    /**
     * Apstrādā ierīces attēla augšupielādi, ja tāda ir pievienota pieprasījumam.
     *
     * Izmanto DeviceAssetManager, lai saglabātu attēlu un, ja nepieciešams,
     * aizstātu iepriekšējo. Ja attēls nav pievienots, metode neko nedara.
     */
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

    /**
     * Dzēš ierīces attēlu, ja pieprasījumā ir atzīmēts attēla noņemšanas karogs.
     *
     * Ja pieprasījumā vienlaikus ir pievienots jauns attēls, noņemšana netiek
     * veikta. Attēls tiek dzēsts gan no failu sistēmas, gan no modeļa lauka.
     */
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

    /**
     * Dzēš visus ar ierīci saistītos failu aktīvus pirms ierīces dzēšanas.
     *
     * Tiek dzēsts gan pilnā izmēra attēls, gan miniatūra (thumbnail).
     * Jāizsauc pirms `$device->delete()`, lai nepaliktu bāreņfaili.
     */
    private function deleteDeviceAssets(Device $device): void
    {
        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->device_image_url);
        $assetManager->delete($assetManager->thumbnailPath($device->device_image_url));
    }

    /**
     * Atgriež cilvēkam saprotamu ierīces statusa nosaukumu latviešu valodā.
     */
    private function statusLabel(string $status): string
    {
        return match ($status) {
            Device::STATUS_REPAIR => 'Remonta',
            Device::STATUS_WRITEOFF => 'Norakstīta',
            default => 'Aktīva',
        };
    }

    /**
     * Atgriež visu ierīces statusu cilvēkam saprotamo nosaukumu karti (statuss → nosaukums).
     */
    private function statusLabels(): array
    {
        return collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
            ->all();
    }



    /**
     * Atgriež aktīvā remonta statusa etiķeti, ja ierīcei notiek remonts.
     *
     * Ja ierīcei nav aktīva remonta, atgriež null. Ja remonta statuss nav
     * atpazīts, atgriež virkni "Gaida" kā noklusēto vērtību.
     */
    private function visibleRepairStatusLabel(Device $device): ?string
    {
        if (! $device->activeRepair) {
            return null;
        }

        return $this->repairStatusLabel($device->activeRepair->status) ?: 'Gaida';
    }

    /**
     * Sagatavo aktīvā remonta kopsavilkuma datus rādīšanai ierīces kartītē.
     *
     * Atgriež masīvu ar remonta veidu, statusu, apstiprinātāju un aprakstu.
     * Ja ierīcei nav aktīva remonta, atgriež null.
     */
    private function repairPreview(Device $device): ?array
    {
        if (! $device->activeRepair) {
            return null;
        }

        $repair = $device->activeRepair;

        return [
            'title' => 'Remonta ieraksts',
            'status' => $this->repairStatusLabel($repair->status) ?: 'Gaida',
            'type' => $repair->repair_type === 'external' ? 'Ārējais' : 'Iekšējais',
            'approved_by' => $repair->approval_actor_name
                ?: $repair->request?->responsibleUser?->full_name
                ?: '-',
            'created_at' => $repair->created_at?->format('d.m.Y H:i') ?: '-',
            'description' => $repair->description ?: 'Apraksts nav pievienots.',
        ];
    }

    /**
     * Atgriež tekstu, kas skaidro, kāpēc ierīce nevar pieņemt jaunu pieprasījumu.
     *
     * Iekļauj aktīvā remonta statusa nosaukumu, ja tāds ir noteikts.
     */
    private function repairReasonText(Device $device): string
    {
        $repairStatusLabel = $this->repairStatusLabel($device->activeRepair?->status);

        if ($repairStatusLabel) {
            return 'Ierīce šobrīd ir remontā ar statusu "'.$repairStatusLabel.'".';
        }

        return 'Ierīce šobrīd ir remonta.';
    }

    /**
     * Atgriež lietotājam saprotamu remonta statusa paskaidrojumu.
     *
     * Katram remonta statusa vērtībai atbilst atsevišķs teksts, kas skaidro,
     * ko šis statuss nozīmē praksē. Ja ierīcei nav aktīva remonta, atgriež null.
     */
    private function repairStatusDescription(Device $device): ?string
    {
        if (! $device->activeRepair) {
            return null;
        }

        return match ($device->activeRepair->status) {
            'waiting' => 'Gaida nozīmē, ka remonta ieraksts jau ir izveidots, bet pats remontdarbs vēl nav uzsākts.',
            'in-progress' => 'Procesā nozīmē, ka ierīce šobrīd tiek remontēta un darbs vēl nav pabeigts.',
            'completed' => 'Pabeigts nozīmē, ka remonta darbs ir noslēgts un ieraksts saglabāts vēsturē.',
            'cancelled' => 'Atcelts nozīmē, ka remonta process tika pārtraukts un netika pabeigts.',
            default => 'Šis ir ierīces remonta statuss, kas parāda, kurā posmā atrodas remonta darbs.',
        };
    }

    /**
     * Nosaka, kādus pieprasījumu veidus var izveidot konkrētajai ierīcei.
     *
     * Atgriež masīvu ar pieejamības karodziņiem katram pieprasījuma veidam
     * un iemeslu, ja kāds no tiem nav pieejams. Ierīce bloķē visus pieprasījumus,
     * ja tai ir aktīvs remonts, norakstīts statuss vai gaidošs pieteikums.
     */
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
                'reason' => 'Ierīce ir norakstīta, tāpēc jaunus pieteikumus veidot nevar.',
            ];
        }

        if ($device->activeRepair) {
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
                'reason' => 'Šai ierīcei jau ir gaidošs remonta pieteikums.',
            ];
        }

        if ($hasPendingWriteoffRequest) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => 'Šai ierīcei jau ir gaidošs norakstīšanas pieteikums.',
            ];
        }

        if ($hasPendingTransferRequest) {
            return [
                'repair' => false,
                'writeoff' => false,
                'transfer' => false,
                'can_create_any' => false,
                'reason' => 'Šai ierīcei jau ir gaidošs nodošanas pieteikums.',
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

    /**
     * Sagatavo vizuālā statusa žetona (badge) datus gaidošajam pieprasījumam.
     *
     * Atgriež masīvu ar ikonu, etiķeti, krāsas klasi un saiti uz pieprasījumu sarakstu.
     * Prioritāte: remonta pieprasījums → norakstīšanas pieprasījums → nodošanas pieprasījums.
     * Ja nav gaidošu pieprasījumu, atgriež null.
     */
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
                'short_label' => 'Pieprasījums',
                'detail_label' => 'Remonts',
                'class' => 'border-amber-200 bg-amber-50 text-amber-800',
                'url' => $this->requestIndexUrl($device, $canManageRequests, 'repair', $pendingRepairRequest?->id),
                'preview' => $this->pendingRequestPreview('repair', $pendingRepairRequest),
            ];
        }

        if ($hasPendingWriteoffRequest) {
            return [
                'icon' => 'writeoff',
                'label' => 'Gaida norakstīšanas pieteikumu',
                'short_label' => 'Pieprasījums',
                'detail_label' => 'Norakst.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'url' => $this->requestIndexUrl($device, $canManageRequests, 'writeoff', $pendingWriteoffRequest?->id),
                'preview' => $this->pendingRequestPreview('writeoff', $pendingWriteoffRequest),
            ];
        }

        if ($hasPendingTransferRequest) {
            return [
                'icon' => 'transfer',
                'label' => 'Gaida nodošanas pieteikumu',
                'short_label' => 'Pieprasījums',
                'detail_label' => 'Nodošana',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'url' => $this->requestIndexUrl($device, $canManageRequests, 'transfer', $pendingTransferRequest?->id),
                'preview' => $this->pendingRequestPreview('transfer', $pendingTransferRequest),
            ];
        }

        return null;
    }

    /**
     * Izveido saiti uz pieprasījumu sarakstu ar filtriem un, ja iespējams, ar enkuru uz konkrētu ierakstu.
     *
     * Ja pieprasījuma ID ir zināms, tiek pievienots ierīces kods kā meklēšanas termins
     * un enkurs (#repair-request-X), lai pārlūks ritinātu pie konkrētā ieraksta.
     */
    private function requestIndexUrl(Device $device, bool $canManageRequests, string $type, ?int $requestId = null): ?string
    {
        $params = [
            'statuses_filter' => 1,
            'status' => ['submitted'],
        ];

        if ($requestId) {
            $params['highlight'] = $device->code ?: $device->name;
            $params['highlight_mode'] = $device->code ? 'exact' : 'contains';
            $params['highlight_id'] = match ($type) {
                'repair' => 'repair-request-'.$requestId,
                'writeoff' => 'writeoff-request-'.$requestId,
                'transfer' => 'device-transfer-'.$requestId,
                default => null,
            };
        }

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

    /**
     * Sagatavo gaidošā pieprasījuma priekšskatījuma datus uznirstošajam logam.
     *
     * Atgriež strukturētu masīvu ar iesniedzēja vārdu, iesniegšanas laiku
     * un pieprasījuma saturu (apraksts vai iemesls). Ja pieprasījums nav nodots,
     * atgriež null. Nodošanas pieprasījumam papildu tiek iekļauts saņēmēja vārds.
     */
    private function pendingRequestPreview(string $type, mixed $request): ?array
    {
        if (! $request) {
            return null;
        }

        return match ($type) {
            'repair' => [
                'type_label' => 'Remonta pieprasījums',
                'meta_label' => 'Apraksts',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->description ?: 'Apraksts nav pievienots.',
                'recipient' => null,
            ],
            'writeoff' => [
                'type_label' => 'Norakstīšanas pieprasījums',
                'meta_label' => 'Iemesls',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->reason ?: 'Iemesls nav pievienots.',
                'recipient' => null,
            ],
            'transfer' => [
                'type_label' => 'Nodošanas pieprasījums',
                'meta_label' => 'Iemesls',
                'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                'summary' => $request->transfer_reason ?: 'Iemesls nav pievienots.',
                'recipient' => $request->transferTo?->full_name ?: null,
            ],
            default => null,
        };
    }

    /**
     * Izpilda ierīces ātrās darbības atbilstoši norādītajam darbības tipam.
     *
     * Darbības tipi: `status` — maina statusu, `room` — pārvietot telpu,
     * `assignee` — mainīt atbildīgo personu. Atgriež rezultāta masīvu
     * ar `level` (success/error) un `message` laukiem.
     */
    private function performDeviceAction(Device $device, array $data): array
    {
        return match ($data['action']) {
            'status' => $this->changeDeviceStatus($device, (string) ($data['target_status'] ?? '')),
            'room' => $this->moveDevice($device, $data['target_room_id'] ?? null),
            'assignee' => $this->reassignDevice($device, $data['target_assigned_to_id'] ?? null),
            default => ['level' => 'error', 'message' => 'Neatbalstīta darbība.'],
        };
    }

    /**
     * Maina ierīces statusu, veicot visas nepieciešamās pārbaudes.
     *
     * Remonta statusa gadījumā tiek automātiski izveidots jauns remonta ieraksts
     * datubāzes transakcijā. Norakstīšanas gadījumā ierīce tiek pārvietota uz
     * noliktavas telpu. Visas izmaiņas tiek reģistrētas audita žurnālā.
     */
    private function changeDeviceStatus(Device $device, string $status): array
    {
        if (! in_array($status, self::STATUSES, true)) {
            return ['level' => 'error', 'message' => 'Nav izvēlēts korekts statuss.'];
        }

        if ($blockedReason = $this->deviceStatusEditBlockedReason($device)) {
            return ['level' => 'error', 'message' => $blockedReason];
        }

        if ($status === Device::STATUS_REPAIR && $device->status !== Device::STATUS_ACTIVE) {
            return ['level' => 'error', 'message' => 'Remonta ierakstu var izveidot tikai aktīvai ierīcei.'];
        }

        if ($status === Device::STATUS_REPAIR && $device->repairs()->whereIn('status', ['waiting', 'in-progress'])->exists()) {
            return ['level' => 'error', 'message' => 'Šai ierīcei jau ir aktīvs remonta ieraksts.'];
        }

        if ($status === Device::STATUS_WRITEOFF && $device->status !== Device::STATUS_ACTIVE) {
            return ['level' => 'error', 'message' => 'Norakstīt var tikai aktīvu ierīci.'];
        }

        if ($status === Device::STATUS_WRITEOFF && ($device->status === Device::STATUS_REPAIR || $device->activeRepair()->exists())) {
            return ['level' => 'error', 'message' => 'Ierīci nevar norakstīt, kamēr tai ir aktīvs remonta process.'];
        }

        if ($device->status === $status) {
            return ['level' => 'error', 'message' => 'Statuss jau ir iestatīts.'];
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
                    'description' => 'Ierīce nodota remontā no ierīču saraksta.',
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

            return ['level' => 'success', 'message' => 'Ierīce nodota remontā. Izveidots remonta ieraksts #'.$repair->id.'.'];
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

        return ['level' => 'success', 'message' => 'Statuss atjaunināts.'];
    }

    /**
     * Pārvieto ierīci uz citu telpu, validējot visus bloķēšanas nosacījumus.
     *
     * Pārbauda, vai ierīcei nav aktīvu pieprasījumu vai remonta, un vai telpa
     * nav tā pati. Saglabā izmaiņas un reģistrē tās audita žurnālā.
     */
    private function moveDevice(Device $device, mixed $roomId): array
    {
        if ($blockedReason = $this->quickRelationEditBlockedReason($device)) {
            return ['level' => 'error', 'message' => $blockedReason];
        }

        if (! $roomId) {
            return ['level' => 'error', 'message' => 'Nav izvēlēta telpa.'];
        }

        $room = Room::query()->with('building')->find($roomId);
        if (! $room) {
            return ['level' => 'error', 'message' => 'Telpa nav atrasta.'];
        }

        if ((int) $device->room_id === (int) $room->id) {
            return ['level' => 'error', 'message' => 'Ierīce jau atrodas šajā telpā.'];
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

        return ['level' => 'success', 'message' => 'Ierīce pārvietota uz citu telpu.'];
    }

    /**
     * Maina ierīces atbildīgo personu, validējot bloķēšanas nosacījumus.
     *
     * Pārbauda, vai jaunais atbildīgais pastāv aktīvo lietotāju sarakstā
     * un vai ierīce vēl nav piešķirta tam pašam lietotājam.
     */
    private function reassignDevice(Device $device, mixed $assignedToId): array
    {
        if ($blockedReason = $this->quickRelationEditBlockedReason($device)) {
            return ['level' => 'error', 'message' => $blockedReason];
        }

        if (! $assignedToId) {
            return ['level' => 'error', 'message' => 'Nav izvēlēta atbildīgā persona.'];
        }

        $assignee = User::query()
            ->active()
            ->find($assignedToId);

        if (! $assignee) {
            return ['level' => 'error', 'message' => 'Atbildīgā persona nav atrasta.'];
        }

        if ((int) $device->assigned_to_id === (int) $assignee->id) {
            return ['level' => 'error', 'message' => 'Ierīce jau ir piešķirta šai personai.'];
        }

        $before = $device->only(['assigned_to_id']);

        $this->saveDevicePayload($device, [
            'assigned_to_id' => $assignee->id,
        ]);

        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'assigned_to_id' => $assignee->id,
        ]);

        return ['level' => 'success', 'message' => 'Atbildīgā persona atjaunināta.'];
    }

    /**
     * Pārbauda, vai ierīces telpas vai atbildīgās personas maiņa ir bloķēta.
     *
     * Apvieno aktīvo pieprasījumu pārbaudi ar statusa pārbaudi — remontā esošai
     * vai norakstītai ierīcei šādas izmaiņas nav atļautas. Atgriež iemesla
     * tekstu vai null, ja maiņa ir atļauta.
     */
    private function quickRelationEditBlockedReason(Device $device): ?string
    {
        if ($blockedReason = $this->activeRequestEditBlockedReason($device)) {
            return $blockedReason;
        }

        if ($device->status === Device::STATUS_WRITEOFF) {
            return 'Norakstītai ierīcei vairs nevar mainīt telpu vai atbildīgo personu.';
        }

        if ($device->status === Device::STATUS_REPAIR) {
            $repairStatusLabel = $this->visibleRepairStatusLabel($device);

            return 'Remonta ierīcei nevar mainīt telpu vai atbildīgo personu'.($repairStatusLabel ? ' ar statusu "'.$repairStatusLabel.'".' : '.');
        }

        return null;
    }

    /**
     * Pārbauda, vai ierīcei ir aktīvs pieprasījums, kas bloķē rediģēšanu.
     *
     * Pārbauda, vai pastāv iesniegts remonta, norakstīšanas vai nodošanas
     * pieprasījums. Ja pastāv, atgriež skaidrojošu kļūdas tekstu,
     * pretējā gadījumā atgriež null.
     */
    private function activeRequestEditBlockedReason(Device $device): ?string
    {
        if ($device->repairRequests()->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            return 'Šai ierīcei ir aktīvs remonta pieteikums. Vispirms jāatrisina pieteikums.';
        }

        if ($device->writeoffRequests()->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            return 'Šai ierīcei ir aktīvs norakstīšanas pieteikums. Vispirms jāatrisina pieteikums.';
        }

        if ($device->transfers()->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            return 'Šai ierīcei ir aktīvs nodošanas pieteikums. Vispirms jāatrisina pieteikums.';
        }

        return null;
    }

    /**
     * Pārbauda, vai ierīces statusa maiņa ir bloķēta.
     *
     * Statusu nevar mainīt, ja ierīcei notiek aktīvs remonts vai tai ir
     * gaidošs remonta, norakstīšanas vai nodošanas pieprasījums.
     * Atgriež bloķēšanas iemeslu vai null, ja maiņa ir atļauta.
     */
    private function deviceStatusEditBlockedReason(Device $device): ?string
    {
        if ($device->status === Device::STATUS_REPAIR || $device->activeRepair()->exists()) {
            $repairStatusLabel = $this->visibleRepairStatusLabel($device);

            return 'IerÄ«ces statusu nevar mainÄ«t, kamÄ“r tai notiek remonts'.($repairStatusLabel ? ' ar statusu "'.$repairStatusLabel.'".' : '.');
        }

        if ($device->repairRequests()->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            return 'IerÄ«ces statusu nevar mainÄ«t, kamÄ“r tai ir aktÄ«vs remonta pieprasÄ«jums.';
        }

        if ($device->writeoffRequests()->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            return 'IerÄ«ces statusu nevar mainÄ«t, kamÄ“r tai ir aktÄ«vs norakstÄ«Åanas pieprasÄ«jums.';
        }

        if ($device->transfers()->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            return 'IerÄ«ces statusu nevar mainÄ«t, kamÄ“r tai ir aktÄ«vs nodoÅanas pieprasÄ«jums.';
        }

        return null;
    }

    /**
     * Formatē telpu kolekciju kā opciju masīvu lietojamai izvēlnei.
     *
     * Katrai telpai tiek sagatavots `value`, `label`, `group`, `description`
     * un `search` lauks, kas ļauj JavaScript pusē filtrēt un meklēt opcijās.
     */
    private function roomSelectOptions(Collection $rooms): Collection
    {
        return $rooms
            ->map(fn (Room $room) => [
                'value' => (string) $room->id,
                'label' => $room->room_number.($room->room_name ? ' - '.$room->room_name : ''),
                'group' => collect([
                    $room->building?->building_name,
                    $room->floor_number !== null ? $room->floor_number.'. stāvs' : null,
                ])->filter()->implode(' | '),
                'description' => collect([
                    $room->department,
                    $room->building?->building_name,
                ])->filter()->implode(' | '),
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

    /**
     * Atgriež visu telpu sarakstu formatētu ātrajai izvēlnei ierīču tabulā.
     *
     * Atšķirībā no `roomSelectOptions`, šī metode ielādē telpas no datubāzes
     * tieši, nefiltrējot pēc lietotāja, jo to izmanto tikai administratori.
     */
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
                    $room->floor_number ? $room->floor_number.'. stāvs' : null,
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

    /**
     * Atgriež aktīvo lietotāju sarakstu formatētu ātrajai ierīces piešķiršanas izvēlnei.
     *
     * Katram lietotājam tiek sagatavots `value`, `label`, `description` un `search`
     * lauks, kas ietver vārdu, amatu un e-pastu meklēšanas vajadzībām.
     */
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

    /**
     * Pārbauda, vai parastais lietotājs drīkst atjaunināt savas ierīces atrašanās vietu.
     *
     * Telpu maiņa nav atļauta, ja ierīce ir remontā vai tai ir gaidošs pieteikums.
     * Atgriež masīvu ar `allowed` (bool) un `reason` (string|null) laukiem.
     */
    private function userRoomUpdateAvailability(Device $device, mixed $pendingRepairRequest, mixed $pendingWriteoffRequest, mixed $pendingTransferRequest): array
    {
        if ($device->status === Device::STATUS_REPAIR) {
            $repairStatusLabel = $this->repairStatusLabel($device->activeRepair?->status);

            return [
                'allowed' => false,
                'reason' => 'Ierīces atrašanās vietu nevar mainīt, kamēr ierīce ir remontā'.($repairStatusLabel ? ' ar statusu "'.$repairStatusLabel.'".' : '.'),
            ];
        }

        if ($pendingRepairRequest) {
            return [
                'allowed' => false,
                'reason' => 'Ierīces atrašanās vietu nevar mainīt, jo šai ierīcei ir gaidošs remonta pieteikums.',
            ];
        }

        if ($pendingWriteoffRequest) {
            return [
                'allowed' => false,
                'reason' => 'Ierīces atrašanās vietu nevar mainīt, jo šai ierīcei ir gaidošs norakstīšanas pieteikums.',
            ];
        }

        if ($pendingTransferRequest) {
            return [
                'allowed' => false,
                'reason' => 'Ierīces atrašanās vietu nevar mainīt, jo šai ierīcei ir gaidošs nodošanas pieteikums.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Novirza lietotāju pēc ātrās darbības uz iepriekšējo lapu vai ierīces skatu.
     *
     * Ja iepriekšējā lapa nav ātrās atjaunināšanas ceļš, tiek atgriezts uz to.
     * Citādi — uz ierīces detalizēto karti. Piešķir sesijas ziņojumu ar darbības rezultātu.
     */
    private function redirectAfterQuickAction(Device $device, string $level, string $message): RedirectResponse
    {
        $previousUrl = url()->previous();
        $previousPath = is_string($previousUrl) ? (parse_url($previousUrl, PHP_URL_PATH) ?: '') : '';

        if (is_string($previousUrl) && $previousUrl !== '' && ! str_contains($previousPath, '/quick-update')) {
            return redirect()->to($previousUrl)->with($level, $message);
        }

        return redirect()->route('devices.show', $device)->with($level, $message);
    }

    /**
     * Saglabā ierīces datus, automātiski apstrādājot novecojušas shēmas kļūdas.
     *
     * Ja datubāze atgriež kļūdu par neatbilstošu kolonnu (piemēram, vecā ENUM vērtība
     * vai NOT NULL datuma lauks), tiek izsaukts RuntimeSchemaBootstrapper un
     * saglabāšana tiek atkārtota. Citas kļūdas tiek tūlīt pārraidītas tālāk.
     */
    private function saveDevicePayload(Device $device, array $payload): void
    {
        try {
            $device->forceFill($payload)->save();
        } catch (QueryException $exception) {
            if (! $this->isRecoverableLegacyDeviceSchemaMismatch($exception)) {
                throw $exception;
            }

            $this->repairLegacyDeviceSchemaMismatch($exception);
            if ($device->exists) {
                $device->refresh();
            }
            $device->forceFill($payload)->save();
        }
    }

    /**
     * Pārbauda, vai datubāzes kļūda ir atgūstama vecās shēmas neatbilstība.
     *
     * Atgriež true, ja kļūda ir vai nu ENUM vērtības neatbilstība, vai datuma
     * kolonnas NOT NULL ierobežojuma pārkāpums, kurus var automātiski novērst.
     */
    private function isRecoverableLegacyDeviceSchemaMismatch(QueryException $exception): bool
    {
        return $this->isLegacyStatusEnumMismatch($exception)
            || $this->isLegacyNullableDeviceDateMismatch($exception);
    }

    /**
     * Automātiski novērš novecojušas shēmas kļūdu, palaižot RuntimeSchemaBootstrapper.
     *
     * Ja kļūda saistīta ar NOT NULL datuma kolonnu, tiek papildu izpildīts
     * ALTER TABLE, lai kolonnas pieņemtu null vērtības. Katra labojuma fakts
     * tiek ierakstīts sistēmas žurnālā un sesijā rāda brīdinājumu.
     */
    private function repairLegacyDeviceSchemaMismatch(QueryException $exception): void
    {
        app(RuntimeSchemaBootstrapper::class)->ensure();

        if ($this->isLegacyNullableDeviceDateMismatch($exception)) {
            $this->ensureLegacyDeviceDateColumnsAllowNull();
        }

        $warningMessage = 'Sistēma automātiski pielāgoja novecojušu datubāzes shēmu, lai šo ierīces saglabāšanu varētu pabeigt korekti.';
        Log::warning('Legacy devices schema mismatch repaired automatically.', [
            'user_id' => $this->user()?->id,
            'driver' => DB::getDriverName(),
            'error' => $exception->getMessage(),
        ]);
        session()->flash('warning', $warningMessage);
    }

    /**
     * Pārbauda, vai kļūda rodas no vecā ENUM vērtības neatbilstības statusu kolonnā.
     *
     * Vecās datubāzes shēmās statusa kolonna var būt ENUM, kas neatbalsta
     * jaunās vērtības. Šī metode identificē šo konkrēto kļūdu pēc ziņojuma satura.
     */
    private function isLegacyStatusEnumMismatch(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'data truncated for column')
            && str_contains($message, "'status'");
    }

    /**
     * Pārbauda, vai kļūda rodas no vecās shēmas datuma kolonnas NOT NULL ierobežojuma.
     *
     * Vecās shēmās `purchase_date` un `warranty_until` var būt NOT NULL kolonnas,
     * kas rada kļūdu, mēģinot saglabāt null vērtību.
     */
    private function isLegacyNullableDeviceDateMismatch(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, "column 'purchase_date' cannot be null")
            || str_contains($message, "column 'warranty_until' cannot be null");
    }

    /**
     * Veic ALTER TABLE, lai `purchase_date` un `warranty_until` kolonnas atļautu NULL.
     *
     * Darbība tiek izpildīta tikai MySQL datubāzēs un tikai tad, ja kolonnas pastāv.
     * Šis ir vienreizējs labojums instalācijām ar novecojušu migrāciju stāvokli.
     */
    private function ensureLegacyDeviceDateColumnsAllowNull(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('devices')) {
            return;
        }

        if (Schema::hasColumn('devices', 'purchase_date')) {
            DB::statement('ALTER TABLE devices MODIFY purchase_date DATE NULL');
        }

        if (Schema::hasColumn('devices', 'warranty_until')) {
            DB::statement('ALTER TABLE devices MODIFY warranty_until DATE NULL');
        }
    }
}
