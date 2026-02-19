<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceSetItemController extends Controller
{
    public function index(Request $request)
    {
        $deviceSetId = $request->query('device_set_id');

        $items = DeviceSetItem::with(['deviceSet', 'device'])
            ->when($deviceSetId, fn ($query) => $query->where('device_set_id', $deviceSetId))
            ->orderByDesc('id')
            ->get();

        return view('device_set_items.index', compact('items', 'deviceSetId'));
    }

    public function create(Request $request)
    {
        $selectedDeviceSetId = $request->query('device_set_id');

        return view('device_set_items.create', [
            'deviceSets' => DeviceSet::orderBy('set_name')->get(),
            'devices' => Device::orderBy('name')->get(),
            'selectedDeviceSetId' => $selectedDeviceSetId,
        ]);
    }

    public function store(Request $request)
    {
        DeviceSetItem::create($this->validatedData($request));

        return redirect()->route('device-set-items.index')->with('success', 'Pozicija veiksmigi pievienota komplektam');
    }

    public function edit(DeviceSetItem $deviceSetItem)
    {
        return view('device_set_items.edit', [
            'deviceSetItem' => $deviceSetItem,
            'deviceSets' => DeviceSet::orderBy('set_name')->get(),
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, DeviceSetItem $deviceSetItem)
    {
        $deviceSetItem->update($this->validatedData($request, $deviceSetItem));

        return redirect()->route('device-set-items.index')->with('success', 'Pozicija veiksmigi atjauninata');
    }

    public function destroy(DeviceSetItem $deviceSetItem)
    {
        $deviceSetItem->delete();

        return redirect()->route('device-set-items.index')->with('success', 'Pozicija veiksmigi dzesta no komplekta');
    }

    private function validatedData(Request $request, ?DeviceSetItem $deviceSetItem = null): array
    {
        $uniqueDeviceInSet = Rule::unique('device_set_items', 'device_id')
            ->where(fn ($query) => $query->where('device_set_id', $request->input('device_set_id')));

        if ($deviceSetItem) {
            $uniqueDeviceInSet->ignore($deviceSetItem->id);
        }

        return $request->validate([
            'device_set_id' => ['required', 'exists:device_sets,id'],
            'device_id' => ['required', 'exists:devices,id', $uniqueDeviceInSet],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'role' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
