<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\Room;
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
        $hasBuildings = $this->featureTableExists('buildings');
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

        $totalDevices = $deviceQuery ? (clone $deviceQuery)->count() : 0;
        $activeDevices = $deviceQuery ? (clone $deviceQuery)->where('status', Device::STATUS_ACTIVE)->count() : 0;
        $inRepairDevices = $deviceQuery ? (clone $deviceQuery)->where('status', Device::STATUS_REPAIR)->count() : 0;
        $writtenOffDevices = $deviceQuery ? (clone $deviceQuery)->where('status', Device::STATUS_WRITEOFF)->count() : 0;

        $activeRepairs = $repairQuery
            ? (clone $repairQuery)
                ->with(['device.building', 'device.room', 'acceptedBy'])
                ->whereIn('status', ['waiting', 'in-progress'])
                ->orderByRaw("case when status = 'in-progress' then 0 else 1 end")
                ->orderByDesc('id')
                ->limit(6)
                ->get()
            : collect();

        $recentDevices = $deviceQuery
            ? (clone $deviceQuery)
                ->with(['room', 'building', 'type', 'assignedTo'])
                ->latest('created_at')
                ->limit(5)
                ->get()
            : collect();

        $recentActivity = $hasAuditLog
            ? AuditLog::query()
                ->with('user')
                ->latest('timestamp')
                ->limit(8)
                ->get()
            : collect();

        $buildings = $hasBuildings && $hasRooms && $hasDevices
            ? Building::query()
                ->withCount(['rooms', 'devices'])
                ->with([
                    'rooms' => function ($query) {
                        $query->with(['user'])
                            ->withCount('devices')
                            ->orderBy('floor_number')
                            ->orderBy('room_number');
                    },
                ])
                ->orderBy('building_name')
                ->get()
            : collect();

        $buildingTree = $buildings->map(function (Building $building) {
            $floors = $building->rooms
                ->groupBy(fn ($room) => (string) ($room->floor_number ?? 0))
                ->sortKeys()
                ->map(function ($rooms, $floorKey) {
                    return [
                        'floor_label' => ((int) $floorKey) . '. stavs',
                        'device_count' => (int) $rooms->sum('devices_count'),
                        'room_count' => $rooms->count(),
                        'rooms' => $rooms->values(),
                    ];
                })
                ->values();

            return [
                'building' => $building,
                'floor_count' => $floors->count(),
                'device_count' => (int) $building->devices_count,
                'rooms_count' => (int) $building->rooms_count,
                'floors' => $floors,
            ];
        });

        return view('dashboard', [
            'user' => $user,
            'totalDevices' => $totalDevices,
            'activeDevices' => $activeDevices,
            'writtenOffDevices' => $writtenOffDevices,
            'inRepairDevices' => $inRepairDevices,
            'totalRooms' => $hasRooms ? Room::count() : 0,
            'mappedRooms' => $hasRooms && $hasDevices ? Room::has('devices')->count() : 0,
            'activeRepairsCount' => $repairQuery ? (clone $repairQuery)->whereIn('status', ['waiting', 'in-progress'])->count() : 0,
            'waitingRepairsCount' => $repairQuery ? (clone $repairQuery)->where('status', 'waiting')->count() : 0,
            'inProgressRepairsCount' => $repairQuery ? (clone $repairQuery)->where('status', 'in-progress')->count() : 0,
            'completedRepairsThisMonth' => $repairQuery ? (clone $repairQuery)->where('status', 'completed')->where('end_date', '>=', now()->startOfMonth())->count() : 0,
            'pendingRepairRequests' => $repairRequestQuery ? (clone $repairRequestQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count() : 0,
            'pendingWriteoffRequests' => $writeoffRequestQuery ? (clone $writeoffRequestQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count() : 0,
            'pendingTransfers' => $transferQuery ? (clone $transferQuery)->where('status', DeviceTransfer::STATUS_SUBMITTED)->count() : 0,
            'averageRepairCost' => (float) ($repairQuery ? ((clone $repairQuery)->whereNotNull('cost')->avg('cost') ?? 0) : 0),
            'latestInventoryAt' => $deviceQuery ? (clone $deviceQuery)->max('created_at') : null,
            'buildingTree' => $buildingTree,
            'activeRepairs' => $activeRepairs,
            'recentDevices' => $recentDevices,
            'recentActivity' => $recentActivity,
            'statusLabels' => [
                'waiting' => 'Gaida',
                'in-progress' => 'Procesa',
                'completed' => 'Pabeigts',
                'cancelled' => 'Atcelts',
            ],
        ]);
    }
}
