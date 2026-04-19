<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\WriteoffRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $isManager = $user->canManageRequests();

        if (! $isManager) {
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

        $filters = [
            'floor' => trim((string) $request->query('floor', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
        ];

        $viewData = $this->dashboardViewData($request, $user, $filters);

        return view('dashboard.devices-table', [
            'dashboardDevices' => $viewData['dashboardDevices'],
            'dashboardDeviceStates' => $viewData['dashboardDeviceStates'],
            'filters' => $filters,
        ]);
    }

    /**
     * Kopīga metode dashboard datu sagatavošanai.
     */
    private function dashboardViewData(Request $request, $user, array $filters): array
    {
        $isManager = $user->canManageRequests();
        $hasDevices = $this->featureTableExists('devices');
        $hasRooms = $this->featureTableExists('rooms');

        $deviceQuery = $hasDevices ? Device::query() : null;
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;

        if ($deviceQuery) {
            $deviceQuery
                ->when(
                    $filters['floor'] !== '' && ctype_digit($filters['floor']),
                    fn ($query) => $query->whereHas('room', fn ($roomQuery) => $roomQuery->where('floor_number', (int) $filters['floor']))
                )
                ->when(
                    $filters['room_id'] !== '' && ctype_digit($filters['room_id']),
                    fn ($query) => $query->where('room_id', (int) $filters['room_id'])
                );
        }

        $locationRooms = $hasRooms && $isManager
            ? Room::query()
                ->select(['id', 'building_id', 'floor_number', 'room_number', 'room_name', 'department'])
                ->with(['building:id,building_name'])
                ->withCount(['devices'])
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get()
            : collect();

        $locationTree = $locationRooms
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

        $pendingRepairRequestCount = $repairRequestQuery
            ? (clone $repairRequestQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count()
            : 0;

        $pendingWriteoffRequestCount = $writeoffRequestQuery
            ? (clone $writeoffRequestQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count()
            : 0;

        $dashboardDevices = $deviceQuery
            ? (clone $deviceQuery)
                ->with([
                    'room:id,building_id,room_number,room_name,floor_number',
                    'room.building:id,building_name',
                    'building:id,building_name',
                    'type:id,type_name',
                    'assignedTo:id,full_name,job_title',
                    'activeRepair.acceptedBy:id,full_name',
                    'activeRepair.request:id,responsible_user_id,reviewed_by_user_id',
                    'activeRepair.request.responsibleUser:id,full_name',
                    'activeRepair.request.reviewedBy:id,full_name',
                    'latestRepair.acceptedBy:id,full_name',
                    'latestRepair.request:id,responsible_user_id,reviewed_by_user_id',
                    'latestRepair.request.responsibleUser:id,full_name',
                    'latestRepair.request.reviewedBy:id,full_name',
                    'pendingRepairRequest.responsibleUser:id,full_name',
                    'pendingWriteoffRequest.responsibleUser:id,full_name',
                    'pendingTransferRequest.responsibleUser:id,full_name',
                    'pendingTransferRequest.transferTo:id,full_name',
                ])
                ->withExists([
                    'repairRequests as has_pending_repair_request' => fn ($query) => $query->where('status', RepairRequest::STATUS_SUBMITTED),
                    'writeoffRequests as has_pending_writeoff_request' => fn ($query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED),
                    'transfers as has_pending_transfer_request' => fn ($query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED),
                ])
                ->latest('id')
                ->paginate(12)
                ->withQueryString()
            : $this->emptyPaginator(12);

        $dashboardDeviceStates = collect($dashboardDevices->items())
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
            'dashboardDeviceStates' => $dashboardDeviceStates,
            'locationTree' => $locationTree,
            'quickActions' => $this->quickActions(
                $pendingRepairRequestCount,
                $pendingWriteoffRequestCount
            ),
            'filters' => $filters,
        ];
    }

    /**
     * Parāda darba virsmu ar visiem datiem.
     */
    public function renderDashboard(Request $request): View
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_unless($user->canManageRequests(), 403);

        $filters = [
            'floor' => trim((string) $request->query('floor', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
        ];

        $viewData = $this->dashboardViewData($request, $user, $filters);

        return view('dashboard', array_merge($viewData, [
            'user' => $user,
            'isManager' => true,
            'filters' => $filters,
        ]));
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
        if ($device->status !== Device::STATUS_REPAIR) {
            return null;
        }

        $label = $this->repairStatusLabel($device->activeRepair?->status)
            ?? $this->repairStatusLabel($device->latestRepair?->status);

        return $label ?: 'Gaida';
    }

    /**
     * Sagatavo remonta hover priekšskatījuma saturu.
     */
    public function repairPreview(Device $device): ?array
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
        if ((bool) ($device->has_pending_repair_request ?? false)) {
            return [
                'icon' => 'repair-request',
                'label' => 'Apskatīt',
                'detail_label' => 'Remonts',
                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                'url' => $this->requestIndexUrl($device, 'repair', $device->pendingRepairRequest?->id),
                'preview' => $this->pendingRequestPreview('repair', $device->pendingRepairRequest),
            ];
        }

        if ((bool) ($device->has_pending_writeoff_request ?? false)) {
            return [
                'icon' => 'writeoff',
                'label' => 'Apskatīt',
                'detail_label' => 'Norakst.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'url' => $this->requestIndexUrl($device, 'writeoff', $device->pendingWriteoffRequest?->id),
                'preview' => $this->pendingRequestPreview('writeoff', $device->pendingWriteoffRequest),
            ];
        }

        if ((bool) ($device->has_pending_transfer_request ?? false)) {
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
