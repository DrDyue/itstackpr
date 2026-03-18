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
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = $this->user();
        abort_unless($user, 403);

        $hasDevices = $this->featureTableExists('devices');
        $hasRepairs = $this->featureTableExists('repairs');
        $hasRooms = $this->featureTableExists('rooms');
        $hasAuditLog = $this->featureTableExists('audit_log');

        $deviceQuery = $hasDevices ? Device::query() : null;
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;
        $transferQuery = Schema::hasTable('device_transfers') ? DeviceTransfer::query() : null;
        $repairQuery = $hasRepairs ? Repair::query() : null;

        if (! $user->canManageRequests()) {
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

        $locationRooms = $hasRooms
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

        $recentActivity = $hasAuditLog
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

        return view('dashboard', [
            'user' => $user,
            'dashboardDevices' => $dashboardDevices,
            'locationTree' => $locationTree,
            'activeRepairs' => $activeRepairs,
            'recentActivity' => $recentActivity,
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
                [
                    'label' => 'Parsutisanas pieteikumi',
                    'url' => route('device-transfers.index'),
                    'icon' => 'transfer',
                    'class' => 'btn-search',
                    'count' => $pendingTransferRequestCount,
                ],
            ];
        }

        return [
            [
                'label' => 'Manas ierices',
                'url' => route('devices.index'),
                'icon' => 'device',
                'class' => 'btn-view',
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
            [
                'label' => 'Parsutisanas pieteikumi',
                'url' => route('device-transfers.index'),
                'icon' => 'transfer',
                'class' => 'btn-search',
                'count' => $pendingTransferRequestCount,
            ],
        ];
    }
}
