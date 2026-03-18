<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\WriteoffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->user();
        abort_unless($user, 403);

        $hasDevices = $this->featureTableExists('devices');
        $hasRepairs = $this->featureTableExists('repairs');
        $hasRooms = $this->featureTableExists('rooms');
        $hasAuditLog = $this->featureTableExists('audit_log');
        $selectedFloor = trim((string) $request->query('floor', ''));
        $selectedRoomId = trim((string) $request->query('room', ''));

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
                ->with(['device.building', 'device.room', 'acceptedBy', 'reporter'])
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

        $availableFloorIds = $locationRooms
            ->map(fn (Room $room) => (string) ($room->floor_number ?? ''))
            ->filter(fn (string $floor) => $floor !== '')
            ->unique()
            ->values();

        $selectedRoom = ctype_digit($selectedRoomId)
            ? $locationRooms->firstWhere('id', (int) $selectedRoomId)
            : null;

        if ($selectedRoom instanceof Room) {
            $selectedFloor = (string) $selectedRoom->floor_number;
        } elseif (! $availableFloorIds->contains($selectedFloor)) {
            $selectedFloor = '';
        }

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
                ->when(
                    $selectedRoom instanceof Room,
                    fn ($query) => $query->where('room_id', $selectedRoom->id)
                )
                ->when(
                    ! ($selectedRoom instanceof Room) && $selectedFloor !== '',
                    fn ($query) => $query->whereHas('room', fn ($roomQuery) => $roomQuery->where('floor_number', (int) $selectedFloor))
                )
                ->orderBy('building_id')
                ->orderBy('room_id')
                ->orderBy('name')
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

        return view('dashboard', [
            'user' => $user,
            'dashboardDevices' => $dashboardDevices,
            'locationTree' => $locationTree,
            'selectedFloor' => $selectedFloor,
            'selectedRoom' => $selectedRoom,
            'activeRepairs' => $activeRepairs,
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
