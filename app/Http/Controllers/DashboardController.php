<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Device;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        try {
            $totalDevices = Device::count();
            $activeDevices = Device::where('status', 'active')->count();
            $brokenDevices = Device::where('status', 'broken')->count();
            $inRepairDevices = Device::where('status', 'repair')->count();
            $newThisMonth = Device::where('created_at', '>=', now()->startOfMonth())->count();
            $withoutRoom = Device::whereNull('room_id')->count();

            $buildings = Building::withCount('rooms')->orderBy('building_name')->get();

            $hotDevices = Device::with(['room', 'type'])
                ->whereIn('status', ['broken', 'repair'])
                ->latest('updated_at')
                ->limit(6)
                ->get();

            $recentDevices = Device::with(['room', 'type'])
                ->latest('created_at')
                ->limit(8)
                ->get();
        } catch (\Throwable $e) {
            $totalDevices = 0;
            $activeDevices = 0;
            $brokenDevices = 0;
            $inRepairDevices = 0;
            $newThisMonth = 0;
            $withoutRoom = 0;
            $buildings = collect();
            $hotDevices = collect();
            $recentDevices = collect();
        }

        return view('dashboard', compact(
            'totalDevices',
            'activeDevices',
            'brokenDevices',
            'inRepairDevices',
            'newThisMonth',
            'withoutRoom',
            'buildings',
            'hotDevices',
            'recentDevices'
        ));
    }
}
