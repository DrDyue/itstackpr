<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceHistory;
use Illuminate\Http\Request;

class DeviceHistoryController extends Controller
{
    // /device-history  (показывает историю всех устройств)
    public function index()
    {
        $history = DeviceHistory::with('device')
            ->orderByDesc('timestamp')
            ->limit(300)
            ->get();

        return view('device_history.index', compact('history'));
    }

    // /devices/{device}/history  (история одного устройства)
    public function device(Device $device)
    {
        $history = DeviceHistory::where('device_id', $device->id)
            ->orderByDesc('timestamp')
            ->get();

        return view('device_history.device', compact('device', 'history'));
    }
}
