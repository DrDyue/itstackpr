<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = $this->user();
        abort_unless($user, 403);
        $isManager = $user->canManageRequests();

        $hasDevices = $this->featureTableExists('devices');
        $hasRepairs = $this->featureTableExists('repairs');
        $hasRooms = $this->featureTableExists('rooms');
        $hasAuditLog = $this->featureTableExists('audit_log');

        $deviceQuery = $hasDevices ? Device::query() : null;
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;
        $transferQuery = Schema::hasTable('device_transfers') ? DeviceTransfer::query() : null;
        $repairQuery = $hasRepairs ? Repair::query() : null;

        if (! $isManager) {
            $deviceQuery?->where('assigned_to_id', $user->id);
            $repairRequestQuery?->where('responsible_user_id', $user->id);
            $writeoffRequestQuery?->where('responsible_user_id', $user->id);
            $transferQuery?->where(function ($query) use ($user) {
                $query->where('responsible_user_id', $user->id)
                    ->orWhere('transfered_to_id', $user->id);
            });
            $repairQuery?->where(function ($query) use ($user) {
                $query->where('issue_reported_by', $user->id)
                    ->orWhereHas('device', fn ($deviceBuilder) => $deviceBuilder->where('assigned_to_id', $user->id));
            });
        }

        $activeRepairs = $repairQuery
            ? (clone $repairQuery)
                ->with(['device.building', 'device.room', 'acceptedBy', 'executor'])
                ->whereIn('status', ['waiting', 'in-progress'])
                ->orderByRaw("case when status = 'in-progress' then 0 else 1 end")
                ->orderByDesc('id')
                ->limit(6)
                ->get()
            : collect();

        $locationRooms = $hasRooms && $isManager
            ? Room::query()
                ->with(['building'])
                ->withCount([
                    'devices' => function ($query) use ($user) {
                        if (! $user->canManageRequests()) {
                            $query->where('assigned_to_id', $user->id);
                        }
                    },
                ])
                ->when(
                    ! $user->canManageRequests(),
                    fn ($query) => $query->whereHas('devices', fn ($deviceBuilder) => $deviceBuilder->where('assigned_to_id', $user->id))
                )
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get()
            : collect();

        $recentActivity = $hasAuditLog && $isManager
            ? tap(AuditLog::query()->with('user'), function ($query) use ($user) {
                if (! $user->canManageRequests()) {
                    $query->where('user_id', $user->id);
                }
            })
                ->latest('timestamp')
                ->limit(8)
                ->get()
            : collect();

        $dashboardDevices = $deviceQuery
            ? (clone $deviceQuery)
                ->with(['room.building', 'building', 'type', 'assignedTo', 'activeRepair'])
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        $locationTree = $locationRooms
            ->groupBy(fn (Room $room) => (string) ($room->floor_number ?? 0))
            ->sortKeys()
            ->map(function ($rooms, $floorKey) {
                return [
                    'id' => (string) $floorKey,
                    'label' => ((int) $floorKey) . '. stavs',
                    'room_count' => $rooms->count(),
                    'device_count' => (int) $rooms->sum('devices_count'),
                    'rooms' => $rooms->map(function (Room $room) {
                        return [
                            'id' => $room->id,
                            'room_number' => $room->room_number,
                            'room_name' => $room->room_name,
                            'building_name' => $room->building?->building_name,
                            'department' => $room->department,
                            'device_count' => (int) $room->devices_count,
                        ];
                    })->values(),
                ];
            })
            ->values();

        $pendingRepairRequestCount = $repairRequestQuery
            ? (clone $repairRequestQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count()
            : 0;

        $pendingWriteoffRequestCount = $writeoffRequestQuery
            ? (clone $writeoffRequestQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count()
            : 0;

        $pendingTransferRequestCount = $transferQuery
            ? (clone $transferQuery)
                ->when(
                    ! $user->canManageRequests(),
                    fn ($query) => $query->where('transfered_to_id', $user->id)
                )
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->count()
            : 0;

        $allUserRequests = ! $isManager
            ? $this->recentUserRequests($user, limit: null)
            : collect();

        $recentUserRequests = ! $isManager
            ? $allUserRequests->take(6)->values()
            : collect();

        return view('dashboard', [
            'user' => $user,
            'isManager' => $isManager,
            'dashboardDevices' => $dashboardDevices,
            'locationTree' => $locationTree,
            'activeRepairs' => $activeRepairs,
            'recentActivity' => $recentActivity,
            'recentUserRequests' => $recentUserRequests,
            'userRequestSummary' => ! $isManager ? [
                'total' => $allUserRequests->count(),
                'submitted' => $allUserRequests->where('status', 'submitted')->count(),
                'approved' => $allUserRequests->where('status', 'approved')->count(),
                'rejected' => $allUserRequests->where('status', 'rejected')->count(),
            ] : null,
            'quickActions' => $this->quickActions(
                $user,
                $pendingRepairRequestCount,
                $pendingWriteoffRequestCount,
                $pendingTransferRequestCount
            ),
            'statusLabels' => [
                'waiting' => 'Gaida',
                'in-progress' => 'Procesa',
                'completed' => 'Pabeigts',
                'cancelled' => 'Atcelts',
            ],
        ]);
    }

    private function quickActions(
        User $user,
        int $pendingRepairRequestCount,
        int $pendingWriteoffRequestCount,
        int $pendingTransferRequestCount
    ): array {
        if ($user->canManageRequests()) {
            return [
                [
                    'label' => 'Jauna ierice',
                    'url' => route('devices.create'),
                    'icon' => 'plus',
                    'class' => 'btn-create',
                    'count' => null,
                ],
                [
                    'label' => 'Pievienot remontu',
                    'url' => route('repairs.create'),
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
                    'label' => 'Norakstisanas pieteikumi',
                    'url' => route('writeoff-requests.index'),
                    'icon' => 'writeoff',
                    'class' => 'btn-danger',
                    'count' => $pendingWriteoffRequestCount,
                ],
            ];
        }

        return [
            [
                'label' => 'Izveidot pieteikumu',
                'url' => route('my-requests.create'),
                'icon' => 'plus',
                'class' => 'btn-create',
                'count' => $pendingRepairRequestCount + $pendingWriteoffRequestCount + $pendingTransferRequestCount,
            ],
        ];
    }

    private function recentUserRequests(User $user, ?int $limit = 6): Collection
    {
        $repairRequests = Schema::hasTable('repair_requests')
            ? RepairRequest::query()
                ->with(['device.type', 'reviewedBy', 'repair'])
                ->where('responsible_user_id', $user->id)
                ->get()
                ->map(fn (RepairRequest $request) => [
                    'id' => 'repair-' . $request->id,
                    'type' => 'Remonts',
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'device_name' => $request->device?->name ?: 'Ierice nav atrasta',
                    'device_code' => $request->device?->code ?: 'Bez koda',
                    'device_meta' => collect([$request->device?->manufacturer, $request->device?->model])->filter()->implode(' | '),
                    'device_image_url' => $request->device?->deviceImageThumbUrl(),
                    'summary' => $request->description,
                    'meta' => $request->repair ? 'Saistits remonts #' . $request->repair->id : null,
                ])
            : collect();

        $writeoffRequests = Schema::hasTable('writeoff_requests')
            ? WriteoffRequest::query()
                ->with(['device.type', 'reviewedBy'])
                ->where('responsible_user_id', $user->id)
                ->get()
                ->map(fn (WriteoffRequest $request) => [
                    'id' => 'writeoff-' . $request->id,
                    'type' => 'Norakstisana',
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'device_name' => $request->device?->name ?: 'Ierice nav atrasta',
                    'device_code' => $request->device?->code ?: 'Bez koda',
                    'device_meta' => collect([$request->device?->manufacturer, $request->device?->model])->filter()->implode(' | '),
                    'device_image_url' => $request->device?->deviceImageThumbUrl(),
                    'summary' => $request->reason,
                    'meta' => $request->review_notes,
                ])
            : collect();

        $transfers = Schema::hasTable('device_transfers')
            ? DeviceTransfer::query()
                ->with(['device.type', 'responsibleUser', 'transferTo'])
                ->where(function ($query) use ($user) {
                    $query->where('responsible_user_id', $user->id)
                        ->orWhere('transfered_to_id', $user->id);
                })
                ->get()
                ->map(function (DeviceTransfer $transfer) use ($user) {
                    $incoming = (int) $transfer->transfered_to_id === (int) $user->id
                        && (int) $transfer->responsible_user_id !== (int) $user->id;

                    return [
                        'id' => 'transfer-' . $transfer->id,
                        'type' => 'Nodosana',
                        'status' => $transfer->status,
                        'created_at' => $transfer->created_at,
                        'device_name' => $transfer->device?->name ?: 'Ierice nav atrasta',
                        'device_code' => $transfer->device?->code ?: 'Bez koda',
                        'device_meta' => collect([$transfer->device?->manufacturer, $transfer->device?->model])->filter()->implode(' | '),
                        'device_image_url' => $transfer->device?->deviceImageThumbUrl(),
                        'summary' => $incoming
                            ? 'Tev tiek nodota ierice no ' . ($transfer->responsibleUser?->full_name ?: 'cita lietotaja')
                            : $transfer->transfer_reason,
                        'meta' => $incoming
                            ? 'Gaida tavu lemumu'
                            : ('Sanemejs: ' . ($transfer->transferTo?->full_name ?: '-')),
                    ];
                })
            : collect();

        $items = $repairRequests
            ->concat($writeoffRequests)
            ->concat($transfers)
            ->sortByDesc(fn (array $item) => $item['created_at']?->getTimestamp() ?? 0)
            ->values();

        if ($limit === null) {
            return $items;
        }

        return $items->take($limit)->values();
    }
}
