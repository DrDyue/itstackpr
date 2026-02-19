<?php

namespace App\Http\Controllers;

use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceSetItemController extends Controller
{
    public function index(Request $request)
    {
        $deviceSetId = $request->query('device_set_id');

        $items = DeviceSetItem::with(['deviceSet', 'device'])
            ->when($deviceSetId, function ($query) use ($deviceSetId) {
                $query->where('device_set_id', $deviceSetId);
            })
            ->orderByDesc('id')
            ->get();

        return view('device_set_items.index', compact('items', 'deviceSetId'));
    }

    public function create()
    {
        $deviceSets = DeviceSet::orderBy('name')->get();
        $devices = Device::orderBy('name')->get();

        return view('device_set_items.create', compact('deviceSets', 'devices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_set_id' => ['required', 'exists:device_sets,id'],
            'device_id' => ['required', 'exists:devices,id', 'unique:device_set_items,device_id,NULL,id,device_set_id,' . $request->device_set_id],
            'role' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        DeviceSetItem::create($data);

        return redirect()->route('device-set-items.index')->with('success', 'Item added to set successfully');
    }

    public function edit(DeviceSetItem $deviceSetItem)
    {
        $deviceSets = DeviceSet::orderBy('name')->get();
        $devices = Device::orderBy('name')->get();

        return view('device_set_items.edit', compact('deviceSetItem', 'deviceSets', 'devices'));
    }

    public function update(Request $request, DeviceSetItem $deviceSetItem)
    {
        $data = $request->validate([
            'device_set_id' => ['required', 'exists:device_sets,id'],
            'device_id' => ['required', 'exists:devices,id'],
            'role' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $deviceSetItem->update($data);

        return redirect()->route('device-set-items.index')->with('success', 'Item updated successfully');
    }

    public function destroy(DeviceSetItem $deviceSetItem)
    {
        $deviceSetItem->delete();

        return redirect()->route('device-set-items.index')->with('success', 'Item removed from set successfully');
    }
}
