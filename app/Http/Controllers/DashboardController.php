<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\WriteoffRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);
        $isManager = $user->canManageRequests();

        if (! $isManager) {
            return redirect()->route('devices.index');
        }

        $filters = [
            'floor' => trim((string) $request->query('floor', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
        ];

        $hasDevices = $this->featureTableExists('devices');
        $hasRepairs = $this->featureTableExists('repairs');
        $hasRooms = $this->featureTableExists('rooms');
        $hasAuditLog = $this->featureTableExists('audit_log');

        $deviceQuery = $hasDevices ? Device::query() : null;
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;
        $repairQuery = $hasRepairs ? Repair::query() : null;

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
                ->withCount(['devices'])
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get()
            : collect();

        $recentActivity = $hasAuditLog && $isManager
            ? AuditLog::query()
                ->with('user')
                ->latest('timestamp')
                ->limit(8)
                ->get()
            : collect();

        $locationTree = $locationRooms
            ->groupBy(fn (Room $room) => (string) ($room->floor_number ?? 0))
            ->sortKeys()
            ->map(function ($rooms, $floorKey) use ($filters) {
                return [
                    'id' => (string) $floorKey,
                    'label' => ((int) $floorKey).'. stavs',
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
                ->with(['room.building', 'building', 'type', 'assignedTo', 'activeRepair'])
                ->latest('id')
                ->paginate(12)
                ->withQueryString()
            : $this->emptyPaginator(12);

        return view('dashboard', [
            'user' => $user,
            'isManager' => $isManager,
            'dashboardDevices' => $dashboardDevices,
            'locationTree' => $locationTree,
            'activeRepairs' => $activeRepairs,
            'recentActivity' => $recentActivity,
            'quickActions' => $this->quickActions(
                $pendingRepairRequestCount,
                $pendingWriteoffRequestCount
            ),
            'filters' => $filters,
            'statusLabels' => [
                'waiting' => 'Gaida',
                'in-progress' => 'Procesa',
                'completed' => 'Pabeigts',
                'cancelled' => 'Atcelts',
            ],
        ]);
    }

    private function quickActions(int $pendingRepairRequestCount, int $pendingWriteoffRequestCount): array
    {
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
}
