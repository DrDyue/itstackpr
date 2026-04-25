<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\WriteoffRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Admina darba virsmas kontrolieris.
 *
 * Šeit tiek sagatavota augšējā statistika, telpu koks un maza ierīču tabula
 * ar statusu priekšskatījumiem un ātrajām pārejām.
 */
class DashboardController extends Controller
{
    /**
     * Parāda darba virsmu vai parasto lietotāju novirza uz viņa ierīcēm.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $user->canManageRequests()) {
            return redirect()->route('devices.index');
        }

        return $this->renderDashboard($request);
    }

    /**
     * Atgriež filtrētu ierīču tabulu priekš dashboard (async).
     */
    public function devices(Request $request): View
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canManageRequests(), 403);

        $filters = $this->dashboardFilters($request);
        $sorting = $this->dashboardSorting($request);
        $viewData = $this->dashboardDevicesData($filters, null, $sorting);

        return view('dashboard.devices-table', [
            'dashboardDevices' => $viewData['dashboardDevices'],
            'dashboardDeviceCount' => $viewData['dashboardDeviceCount'],
            'dashboardDeviceStates' => $viewData['dashboardDeviceStates'],
            'filters' => $filters,
            'sorting' => $sorting,
            'sortOptions' => $this->dashboardSortOptions(),
            'sortDirectionLabels' => $this->sortDirectionLabels(),
        ]);
    }

    /**
     * Kopīga metode dashboard datu sagatavošanai.
     */
    private function dashboardViewData(Request $request, $user, array $filters): array
    {
        $isManager = $user->canManageRequests();
        $hasRooms = $this->featureTableExists('rooms');
        $sorting = $this->dashboardSorting($request);
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;

        $locationRooms = $this->dashboardLocationRooms($hasRooms && $isManager);
        $locationTree = $this->dashboardLocationTree($locationRooms, $filters);

        $pendingRepairRequestCount = $repairRequestQuery
            ? (clone $repairRequestQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count()
            : 0;

        $pendingWriteoffRequestCount = $writeoffRequestQuery
            ? (clone $writeoffRequestQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count()
            : 0;

        return array_merge($this->dashboardDevicesData($filters, $locationRooms, $sorting), [
            'locationTree' => $locationTree,
            'quickActions' => $this->quickActions(
                $pendingRepairRequestCount,
                $pendingWriteoffRequestCount
            ),
            'filters' => $filters,
        ]);
    }

    /**
     * Parāda darba virsmu ar visiem datiem.
     */
    public function renderDashboard(Request $request): View
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canManageRequests(), 403);

        $filters = $this->dashboardFilters($request);
        $sorting = $this->dashboardSorting($request);
        $viewData = $this->dashboardViewData($request, $user, $filters);

        return view('dashboard', array_merge($viewData, [
            'user' => $user,
            'isManager' => true,
            'filters' => $filters,
            'sorting' => $sorting,
            'sortOptions' => $this->dashboardSortOptions(),
            'sortDirectionLabels' => $this->sortDirectionLabels(),
        ]));
    }

    private function dashboardFilters(Request $request): array
    {
        return [
            'floor' => trim((string) $request->query('floor', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
        ];
    }

    private function dashboardSorting(Request $request): array
    {
        $sortOptions = $this->dashboardSortOptions();
        $sort = trim((string) $request->query('sort', 'created_at'));
        $direction = trim((string) $request->query('direction', 'desc'));

        if (! array_key_exists($sort, $sortOptions)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'created_at' ? 'desc' : 'asc';
        }

        if ($sort === 'created_at' && ! $request->has('direction')) {
            $direction = 'desc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $sortOptions[$sort]['label'] ?? 'izveides datuma',
        ];
    }

    private function dashboardLocationRooms(bool $shouldLoad): Collection
    {
        if (! $shouldLoad) {
            return collect();
        }

        return Room::query()
            ->select(['id', 'building_id', 'floor_number', 'room_number', 'room_name', 'department'])
            ->with(['building:id,building_name'])
            ->withCount(['devices'])
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
    }

    private function dashboardLocationTree(Collection $locationRooms, array $filters): Collection
    {
        return $locationRooms
            ->groupBy(fn (Room $room) => (string) ($room->floor_number ?? 0))
            ->sortKeys()
            ->map(function ($rooms, $floorKey) use ($filters) {
                return [
                    'id' => (string) $floorKey,
                    'label' => ((int) $floorKey).'. stāvs',
                    'room_count' => $rooms->count(),
                    'device_count' => (int) $rooms->sum('devices_count'),
                    'rooms' => $rooms->map(function (Room $room) use ($filters) {
                        return [
                            'id' => $room->id,
                            'room_number' => $room->room_number,
                            'room_name' => $room->room_name,
                            'building_name' => $room->building?->building_name,
                            'department' => $room->department,
                            'device_count' => (int) $room->devices_count,
                            'is_active' => (string) $room->id === $filters['room_id'],
                        ];
                    })->values(),
                    'is_active' => (string) $floorKey === $filters['floor'] && $filters['room_id'] === '',
                ];
            })
            ->values();
    }

    private function dashboardDevicesData(array $filters, ?Collection $locationRooms = null, ?array $sorting = null): array
    {
        if (! $this->featureTableExists('devices')) {
            return [
                'dashboardDevices' => collect(),
                'dashboardDeviceCount' => 0,
                'dashboardDeviceStates' => [],
            ];
        }

        $sorting ??= [
            'sort' => 'created_at',
            'direction' => 'desc',
            'label' => 'izveides datuma',
        ];

        $deviceQuery = Device::query()
            ->leftJoin('rooms as sort_rooms', 'sort_rooms.id', '=', 'devices.room_id')
            ->leftJoin('buildings as sort_buildings', 'sort_buildings.id', '=', 'devices.building_id')
            ->leftJoin('users as sort_users', 'sort_users.id', '=', 'devices.assigned_to_id');
        $this->applyDashboardDeviceFilters($deviceQuery, $filters, $locationRooms);
        $this->applyDashboardDeviceSorting($deviceQuery, $sorting);

        $dashboardDevices = $deviceQuery
            ->select([
                'devices.id',
                'devices.code',
                'devices.name',
                'devices.device_type_id',
                'devices.model',
                'devices.status',
                'devices.building_id',
                'devices.room_id',
                'devices.assigned_to_id',
                'devices.serial_number',
                'devices.manufacturer',
                'devices.device_image_url',
                'devices.created_at',
            ])
            ->with([
                'room:id,building_id,room_number,room_name',
                'room.building:id,building_name',
                'building:id,building_name',
                'type:id,type_name',
                'assignedTo:id,full_name,job_title',
                'activeRepair',
                'activeRepair.acceptedBy:id,full_name',
                'activeRepair.request:id,responsible_user_id,reviewed_by_user_id',
                'activeRepair.request.responsibleUser:id,full_name',
                'pendingRepairRequest',
                'pendingRepairRequest.responsibleUser:id,full_name',
                'pendingWriteoffRequest',
                'pendingWriteoffRequest.responsibleUser:id,full_name',
                'pendingTransferRequest',
                'pendingTransferRequest.responsibleUser:id,full_name',
                'pendingTransferRequest.transferTo:id,full_name',
            ])
            ->latest('id')
            ->get();

        $dashboardDeviceStates = $dashboardDevices
            ->mapWithKeys(fn (Device $device) => [
                $device->id => [
                    'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
                    'repairPreview' => $this->repairPreview($device),
                    'pendingRequestBadge' => $this->pendingRequestBadge($device),
                ],
            ])
            ->all();

        return [
            'dashboardDevices' => $dashboardDevices,
            'dashboardDeviceCount' => $dashboardDevices->count(),
            'dashboardDeviceStates' => $dashboardDeviceStates,
        ];
    }

    private function applyDashboardDeviceFilters($deviceQuery, array $filters, ?Collection $locationRooms = null): void
    {
        if ($filters['room_id'] !== '' && ctype_digit($filters['room_id'])) {
            $deviceQuery->where('devices.room_id', (int) $filters['room_id']);
            return;
        }

        if ($filters['floor'] === '' || ! ctype_digit($filters['floor'])) {
            return;
        }

        if ($locationRooms instanceof Collection && $locationRooms->isNotEmpty()) {
            $roomIds = $locationRooms
                ->filter(fn (Room $room) => (int) $room->floor_number === (int) $filters['floor'])
                ->pluck('id')
                ->all();

            if ($roomIds === []) {
                $deviceQuery->whereRaw('1 = 0');
                return;
            }

            $deviceQuery->whereIn('devices.room_id', $roomIds);
            return;
        }

        $deviceQuery->whereHas('room', fn ($roomQuery) => $roomQuery->where('floor_number', (int) $filters['floor']));
    }

    private function applyDashboardDeviceSorting(Builder $query, array $sorting): void
    {
        $direction = ($sorting['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sorting['sort'] ?? 'created_at') {
            'code' => $query
                ->orderByRaw('LOWER(COALESCE(devices.code, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'name' => $query
                ->orderByRaw('LOWER(COALESCE(devices.name, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'location' => $query
                ->orderByRaw('LOWER(COALESCE(sort_buildings.building_name, "")) ' . $direction)
                ->orderBy('sort_rooms.floor_number', $direction)
                ->orderByRaw('LOWER(COALESCE(sort_rooms.room_number, "")) ' . $direction)
                ->orderByRaw('LOWER(COALESCE(sort_rooms.room_name, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'assigned_to' => $query
                ->orderByRaw('LOWER(COALESCE(sort_users.full_name, "")) ' . $direction)
                ->orderBy('devices.id', $direction),
            'status' => $query
                ->orderByRaw($this->dashboardDeviceStatusSortExpression() . ' ' . $direction)
                ->orderBy('devices.id', $direction),
            default => $query
                ->orderBy('devices.created_at', $direction)
                ->orderBy('devices.id', $direction),
        };
    }

    private function dashboardDeviceStatusSortExpression(): string
    {
        return <<<'SQL'
CASE
    WHEN devices.status = 'active' THEN 1
    WHEN devices.status = 'repair' THEN 2
    WHEN devices.status = 'writeoff' THEN 3
    ELSE 4
END
SQL;
    }

    private function dashboardSortOptions(): array
    {
        return [
            'created_at' => ['label' => 'izveides datuma'],
            'code' => ['label' => 'koda'],
            'name' => ['label' => 'ierīces nosaukuma'],
            'location' => ['label' => 'atrašanās vietas'],
            'assigned_to' => ['label' => 'piešķirtā lietotāja'],
            'status' => ['label' => 'statusa'],
        ];
    }

    private function sortDirectionLabels(): array
    {
        return [
            'asc' => 'augošajā secībā',
            'desc' => 'dilstošajā secībā',
        ];
    }

    /**
     * Definē ātrās darbības kartītes darba virsmas augšdaļai.
     */
    private function quickActions(int $pendingRepairRequestCount, int $pendingWriteoffRequestCount): array
    {
        return [
            [
                'label' => 'Jauna ierīce',
                'url' => route('devices.index', ['device_modal' => 'create']),
                'icon' => 'plus',
                'class' => 'btn-create',
                'count' => null,
            ],
            [
                'label' => 'Pievienot remontu',
                'url' => route('repairs.index', ['repair_modal' => 'create']),
                'icon' => 'repair',
                'class' => 'btn-edit',
                'count' => null,
            ],
            [
                'label' => 'Remonta pieteikumi',
                'url' => route('repair-requests.index'),
                'icon' => 'repair-request',
                'class' => 'btn-view',
                'count' => $pendingRepairRequestCount,
            ],
            [
                'label' => 'Norakstīšanas pieteikumi',
                'url' => route('writeoff-requests.index'),
                'icon' => 'writeoff',
                'class' => 'btn-danger',
                'count' => $pendingWriteoffRequestCount,
            ],
        ];
    }

    /**
     * Pārveido remonta tehnisko statusu cilvēkam saprotamā birkā.
     */
    public function repairStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            default => null,
        };
    }

    /**
     * Aprēķina, kādu remonta apakšstatusu rādīt ierīcei dashboardā.
     */
    public function visibleRepairStatusLabel(Device $device): ?string
    {
        if (! $device->activeRepair) {
            return null;
        }

        return $this->repairStatusLabel($device->activeRepair->status) ?: 'Gaida';
    }

    /**
     * Sagatavo remonta hover priekšskatījuma saturu.
     */
    public function repairPreview(Device $device): ?array
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
     * Sagatavo informāciju par gaidošo pieprasījumu birku dashboard tabulā.
     */
    public function pendingRequestBadge(Device $device): ?array
    {
        if ($device->pendingRepairRequest) {
            return [
                'icon' => 'repair-request',
                'label' => 'Apskatīt',
                'detail_label' => 'Remonts',
                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                'url' => $this->requestIndexUrl($device, 'repair', $device->pendingRepairRequest?->id),
                'preview' => $this->pendingRequestPreview('repair', $device->pendingRepairRequest),
            ];
        }

        if ($device->pendingWriteoffRequest) {
            return [
                'icon' => 'writeoff',
                'label' => 'Apskatīt',
                'detail_label' => 'Norakst.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'url' => $this->requestIndexUrl($device, 'writeoff', $device->pendingWriteoffRequest?->id),
                'preview' => $this->pendingRequestPreview('writeoff', $device->pendingWriteoffRequest),
            ];
        }

        if ($device->pendingTransferRequest) {
            return [
                'icon' => 'transfer',
                'label' => 'Apskatīt',
                'detail_label' => 'Nodošana',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'url' => $this->requestIndexUrl($device, 'transfer', $device->pendingTransferRequest?->id),
                'preview' => $this->pendingRequestPreview('transfer', $device->pendingTransferRequest),
            ];
        }

        return null;
    }

    private function requestIndexUrl(Device $device, string $type, ?int $requestId = null): ?string
    {
        $params = [
            'statuses_filter' => 1,
            'status' => ['submitted'],
        ];

        if ($requestId) {
            $params['highlight'] = $device->code ?: $device->name;
            $params['highlight_mode'] = $device->code ? 'exact' : 'contains';
            $params['highlight_id'] = match ($type) {
                'repair' => 'repair-request-' . $requestId,
                'writeoff' => 'writeoff-request-' . $requestId,
                'transfer' => 'device-transfer-' . $requestId,
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

        return $anchor !== '' ? $baseUrl . '#' . $anchor . $requestId : $baseUrl;
    }

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
}
