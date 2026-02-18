<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceType;
use App\Models\Room;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        $devices = Device::with(['type', 'building', 'room'])
            ->when($q, function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('serial_number', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->get();

        return view('devices.index', compact('devices', 'q'));
    }

    public function create()
    {
        $types = DeviceType::orderBy('type_name')->get();
        $buildings = Building::orderBy('building_name')->get();
        $rooms = Room::with('building')->orderBy('room_number')->get();

        $statuses = ['active', 'reserve', 'broken', 'repair', 'retired', 'kitting'];

        return view('devices.create', compact('types', 'buildings', 'rooms', 'statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'unique:devices,code'],
            'name' => ['required', 'string', 'max:200'],
            'device_type_id' => ['required', 'exists:device_types,id'],
            'model' => ['required', 'string', 'max:100'],
            'status_id' => ['required', 'in:active,reserve,broken,repair,retired,kitting'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'],
            'warranty_photo_name' => ['nullable', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'device_image_url' => ['nullable', 'string'],
        ]);

        foreach (['building_id', 'room_id'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        $userId = auth()->check() ? auth()->id() : null;
        $data['created_by'] = $userId;

        $device = Device::create($data);

        // device_history: CREATE
        DeviceHistory::create([
            'device_id' => $device->id,
            'action' => 'CREATE',
            'field_changed' => null,
            'old_value' => null,
            'new_value' => 'Device created',
            'changed_by' => $userId,
        ]);

        // audit_log: CREATE
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CREATE',
            'entity_type' => 'Device',
            'entity_id' => (string)$device->id,
            'description' => 'Device created: ' . $device->name,
        ]);

        return redirect()->route('devices.index')->with('success', 'Device created');
    }

    public function edit(Device $device)
    {
        $types = DeviceType::orderBy('type_name')->get();
        $buildings = Building::orderBy('building_name')->get();
        $rooms = Room::with('building')->orderBy('room_number')->get();

        $statuses = ['active', 'reserve', 'broken', 'repair', 'retired', 'kitting'];

        return view('devices.edit', compact('device', 'types', 'buildings', 'rooms', 'statuses'));
    }

    public function update(Request $request, Device $device)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'unique:devices,code,' . $device->id],
            'name' => ['required', 'string', 'max:200'],
            'device_type_id' => ['required', 'exists:device_types,id'],
            'model' => ['required', 'string', 'max:100'],
            'status_id' => ['required', 'in:active,reserve,broken,repair,retired,kitting'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'],
            'warranty_photo_name' => ['nullable', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'device_image_url' => ['nullable', 'string'],
        ]);

        foreach (['building_id', 'room_id'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        $userId = auth()->check() ? auth()->id() : null;

        // BEFORE
        $before = $device->only([
            'code', 'name', 'device_type_id', 'model', 'status_id',
            'building_id', 'room_id', 'assigned_to',
            'purchase_date', 'purchase_price', 'warranty_until',
            'warranty_photo_name', 'serial_number', 'manufacturer',
            'notes', 'device_image_url'
        ]);

        // UPDATE
        $device->update($data);

        // AFTER
        $after = $device->fresh()->only(array_keys($before));

        $changedFields = [];

        foreach ($before as $field => $oldValue) {
            $newValue = $after[$field] ?? null;

            if ((string)$oldValue === (string)$newValue) {
                continue;
            }

            $changedFields[] = $field;

            DeviceHistory::create([
                'device_id' => $device->id,
                'action' => 'UPDATE',
                'field_changed' => $field,
                'old_value' => $oldValue === null ? null : (string)$oldValue,
                'new_value' => $newValue === null ? null : (string)$newValue,
                'changed_by' => $userId,
            ]);
        }

        // audit_log: UPDATE (одна запись, со списком полей)
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'UPDATE',
            'entity_type' => 'Device',
            'entity_id' => (string)$device->id,
            'description' => 'Device updated: ' . $device->name . (count($changedFields) ? ' | fields: ' . implode(', ', $changedFields) : ''),
        ]);

        return redirect()->route('devices.index')->with('success', 'Device updated');
    }

    public function destroy(Device $device)
    {
        $userId = auth()->check() ? auth()->id() : null;

        $id = $device->id;
        $code = $device->code;
        $name = $device->name;

        // device_history: DELETE (пишем ДО удаления)
        DeviceHistory::create([
            'device_id' => $id,
            'action' => 'DELETE',
            'field_changed' => null,
            'old_value' => null,
            'new_value' => trim(($code ?? '') . ' ' . ($name ?? '')),
            'changed_by' => $userId,
        ]);

        // audit_log: DELETE (пишем ДО удаления)
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'DELETE',
            'entity_type' => 'Device',
            'entity_id' => (string)$id,
            'description' => 'Device deleted: ' . trim(($code ?? '') . ' ' . ($name ?? '')),
        ]);

        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted');
    }

    public function show(Device $device)
    {
        return redirect()->route('devices.index');
    }
}
