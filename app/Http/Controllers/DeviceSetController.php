<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use App\Models\Room;
use Illuminate\Http\Request;

class DeviceSetController extends Controller
{
    public function index()
    {
        $sets = DeviceSet::with(['room'])
            ->orderByDesc('id')
            ->get();

        return view('device_sets.index', compact('sets'));
    }

    public function create()
    {
        $rooms = Room::with('building')->orderBy('room_number')->get();
        $statuses = ['draft', 'active', 'returned', 'archived'];

        return view('device_sets.create', compact('rooms', 'statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'set_name' => ['required', 'string', 'max:100'],
            'set_code' => ['required', 'string', 'max:50', 'unique:device_sets,set_code'],
            'status' => ['required', 'in:draft,active,returned,archived'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['room_id'] ?? null) === '') $data['room_id'] = null;

        $userId = auth()->check() ? auth()->id() : null;
        $data['created_by'] = $userId;

        $set = DeviceSet::create($data);

        // audit
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CREATE',
            'entity_type' => 'DeviceSet',
            'entity_id' => (string)$set->id,
            'description' => 'Device set created: ' . $set->set_code . ' (' . $set->set_name . ')',
        ]);

        return redirect()->route('device-sets.edit', $set)->with('success', 'Device set created');
    }

    public function edit(DeviceSet $deviceSet)
    {
        $deviceSet->load(['room', 'items.device']);

        $rooms = Room::with('building')->orderBy('room_number')->get();
        $statuses = ['draft', 'active', 'returned', 'archived'];

        // список устройств для добавления
        $devices = Device::orderBy('name')->get();

        return view('device_sets.edit', [
            'set' => $deviceSet,
            'rooms' => $rooms,
            'statuses' => $statuses,
            'devices' => $devices,
        ]);
    }

    public function update(Request $request, DeviceSet $deviceSet)
    {
        $data = $request->validate([
            'set_name' => ['required', 'string', 'max:100'],
            'set_code' => ['required', 'string', 'max:50', 'unique:device_sets,set_code,' . $deviceSet->id],
            'status' => ['required', 'in:draft,active,returned,archived'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['room_id'] ?? null) === '') $data['room_id'] = null;

        $deviceSet->update($data);

        $userId = auth()->check() ? auth()->id() : null;
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'UPDATE',
            'entity_type' => 'DeviceSet',
            'entity_id' => (string)$deviceSet->id,
            'description' => 'Device set updated: ' . $deviceSet->set_code,
        ]);

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Device set updated');
    }

    public function destroy(DeviceSet $deviceSet)
    {
        $userId = auth()->check() ? auth()->id() : null;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'DELETE',
            'entity_type' => 'DeviceSet',
            'entity_id' => (string)$deviceSet->id,
            'description' => 'Device set deleted: ' . $deviceSet->set_code,
        ]);

        $deviceSet->delete();

        return redirect()->route('device-sets.index')->with('success', 'Device set deleted');
    }

    // ------- ВАЖНОЕ: добавление устройства в комплект -------
    public function addItem(Request $request, DeviceSet $deviceSet)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        // если item уже есть — просто увеличим quantity
        $item = DeviceSetItem::where('device_set_id', $deviceSet->id)
            ->where('device_id', $data['device_id'])
            ->first();

        if ($item) {
            $item->quantity += $data['quantity'];
            $item->save();
        } else {
            DeviceSetItem::create([
                'device_set_id' => $deviceSet->id,
                'device_id' => $data['device_id'],
                'quantity' => $data['quantity'],
            ]);
        }

        $userId = auth()->check() ? auth()->id() : null;
        $device = Device::find($data['device_id']);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CREATE',
            'entity_type' => 'DeviceSetItem',
            'entity_id' => (string)$deviceSet->id,
            'description' => 'Added to set ' . $deviceSet->set_code . ': ' . ($device?->code ?? '') . ' ' . ($device?->name ?? ''),
        ]);

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Item added');
    }

    public function deleteItem(DeviceSet $deviceSet, DeviceSetItem $item)
    {
        // защита: чтобы нельзя было удалить item не из этого комплекта
        if ($item->device_set_id !== $deviceSet->id) {
            abort(404);
        }

        $userId = auth()->check() ? auth()->id() : null;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'DELETE',
            'entity_type' => 'DeviceSetItem',
            'entity_id' => (string)$deviceSet->id,
            'description' => 'Removed item from set ' . $deviceSet->set_code,
        ]);

        $item->delete();

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Item removed');
    }

    public function show(DeviceSet $deviceSet)
    {
        return redirect()->route('device-sets.index');
    }
}
