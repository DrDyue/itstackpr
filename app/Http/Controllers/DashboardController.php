<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\Repair;
use App\Models\Room;
use App\Support\DatabaseBackupService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backupService
    ) {
    }

    public function index(): View
    {
        try {
            $totalDevices = Device::count();
            $activeDevices = Device::where('status', 'active')->count();
            $reserveDevices = Device::where('status', 'reserve')->count();
            $brokenDevices = Device::where('status', 'broken')->count();
            $inRepairDevices = Device::where('status', 'repair')->count();
            $newThisMonth = Device::where('created_at', '>=', now()->startOfMonth())->count();
            $withoutRoom = Device::whereNull('room_id')->count();
            $totalRooms = Room::count();
            $mappedRooms = Room::has('devices')->count();
            $activeRepairsCount = Repair::whereIn('status', ['waiting', 'in-progress'])->count();
            $completedRepairsThisMonth = Repair::where('status', 'completed')
                ->where('actual_completion', '>=', now()->startOfMonth())
                ->count();
            $averageRepairCost = (float) Repair::whereNotNull('cost')->avg('cost');
            $latestInventoryAt = Device::max('created_at');

            $buildings = Building::query()
                ->withCount(['rooms', 'devices'])
                ->with([
                    'rooms' => function ($query) {
                        $query->with(['employee'])
                            ->withCount('devices')
                            ->orderBy('floor_number')
                            ->orderBy('room_number');
                    },
                ])
                ->orderBy('building_name')
                ->get();

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

            $activeRepairs = Repair::with(['device.building', 'device.room', 'assignee.employee'])
                ->whereIn('status', ['waiting', 'in-progress'])
                ->orderByRaw("case when status = 'in-progress' then 0 else 1 end")
                ->orderByDesc('id')
                ->limit(6)
                ->get();

            $recentDevices = Device::with(['room', 'building', 'type'])
                ->latest('created_at')
                ->limit(6)
                ->get();

            $recentActivity = AuditLog::with('user.employee')
                ->latest('timestamp')
                ->limit(8)
                ->get();

            $allBackups = $this->backupService->allBackups();
            $backupSettings = $this->backupService->getSettings();
            $latestBackup = $allBackups->first();
            $currentBackup = $allBackups->first(fn ($backup) => $backup->is_current);
            $nextBackupRun = $this->backupService->nextRunAt($backupSettings, CarbonImmutable::now());
            $backupSummary = [
                'count' => $allBackups->count(),
                'latest' => $latestBackup,
                'current' => $currentBackup,
                'enabled' => (bool) $backupSettings->enabled,
                'next_run_at' => $nextBackupRun,
            ];
        } catch (\Throwable $e) {
            $totalDevices = 0;
            $activeDevices = 0;
            $reserveDevices = 0;
            $brokenDevices = 0;
            $inRepairDevices = 0;
            $newThisMonth = 0;
            $withoutRoom = 0;
            $totalRooms = 0;
            $mappedRooms = 0;
            $activeRepairsCount = 0;
            $completedRepairsThisMonth = 0;
            $averageRepairCost = 0;
            $latestInventoryAt = null;
            $buildings = collect();
            $buildingTree = collect();
            $activeRepairs = collect();
            $recentDevices = collect();
            $recentActivity = collect();
            $backupSummary = [
                'count' => 0,
                'latest' => null,
                'current' => null,
                'enabled' => false,
                'next_run_at' => null,
            ];
        }

        return view('dashboard', compact(
            'totalDevices',
            'activeDevices',
            'reserveDevices',
            'brokenDevices',
            'inRepairDevices',
            'newThisMonth',
            'withoutRoom',
            'totalRooms',
            'mappedRooms',
            'activeRepairsCount',
            'completedRepairsThisMonth',
            'averageRepairCost',
            'latestInventoryAt',
            'buildings',
            'buildingTree',
            'activeRepairs',
            'recentDevices',
            'recentActivity',
            'backupSummary'
        ));
    }
}
