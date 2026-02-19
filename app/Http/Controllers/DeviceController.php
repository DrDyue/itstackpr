<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    private const STATUSES = ['active', 'reserve', 'broken', 'repair', 'retired', 'kitting'];

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
        return view('devices.create', $this->formData());
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        $data = $this->validatedData($request);
        $data['created_by'] = $userId;

        $device = Device::create($data);

        DeviceHistory::create([
            'device_id' => $device->id,
            'action' => 'CREATE',
            'field_changed' => null,
            'old_value' => null,
            'new_value' => 'Ierice veiksmigi pievienota',
            'changed_by' => $userId,
        ]);

        $this->writeAudit($userId, 'CREATE', $device, 'Device created: ' . $device->name, 'info');

        return redirect()->route('devices.index')->with('success', 'Ierice veiksmigi pievienota');
    }

    public function edit(Device $device)
    {
        return view('devices.edit', array_merge(['device' => $device], $this->formData()));
    }

    public function update(Request $request, Device $device)
    {
        $userId = auth()->id();
        $data = $this->validatedData($request, $device);

        $before = $device->only($this->trackedFields());

        $device->update($data);

        $after = $device->fresh()->only(array_keys($before));
        $changedFields = $this->writeHistoryChanges($device->id, $before, $after, $userId);

        $description = 'Device updated: ' . $device->name;
        if ($changedFields !== []) {
            $description .= ' | fields: ' . implode(', ', $changedFields);
        }

        $this->writeAudit($userId, 'UPDATE', $device, $description, 'info');

        return redirect()->route('devices.index')->with('success', 'Ierices dati atjauninati');
    }

    public function destroy(Device $device)
    {
        $userId = auth()->id();
        $id = $device->id;
        $label = trim(($device->code ?? '') . ' ' . ($device->name ?? ''));

        DeviceHistory::create([
            'device_id' => $id,
            'action' => 'DELETE',
            'field_changed' => null,
            'old_value' => null,
            'new_value' => $label,
            'changed_by' => $userId,
        ]);

        $this->writeAudit($userId, 'DELETE', $device, 'Device deleted: ' . $label, 'warning');

        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Ierice dzesta');
    }

    public function show(Device $device)
    {
        return redirect()->route('devices.index');
    }

    private function formData(): array
    {
        return [
            'types' => DeviceType::orderBy('type_name')->get(),
            'buildings' => Building::orderBy('building_name')->get(),
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'statuses' => self::STATUSES,
        ];
    }

    private function validatedData(Request $request, ?Device $device = null): array
    {
        $data = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('devices', 'code')->ignore($device?->id),
            ],
            'name' => ['required', 'string', 'max:200'],
            'device_type_id' => ['required', 'exists:device_types,id'],
            'model' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(self::STATUSES)],
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

        foreach (['building_id', 'room_id'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        // TR: Eski kayitlarla uyum icin bos degeri null yerine bos stringe ceviriyoruz.
        $data['warranty_photo_name'] = $data['warranty_photo_name'] ?? '';

        return $data;
    }

    private function trackedFields(): array
    {
        return [
            'code', 'name', 'device_type_id', 'model', 'status',
            'building_id', 'room_id', 'assigned_to', 'purchase_date',
            'purchase_price', 'warranty_until', 'warranty_photo_name',
            'serial_number', 'manufacturer', 'notes', 'device_image_url',
        ];
    }

    private function writeHistoryChanges(int $deviceId, array $before, array $after, ?int $userId): array
    {
        $changedFields = [];

        foreach ($before as $field => $oldValue) {
            $newValue = $after[$field] ?? null;

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            $changedFields[] = $field;

            DeviceHistory::create([
                'device_id' => $deviceId,
                'action' => 'UPDATE',
                'field_changed' => $field,
                'old_value' => $oldValue === null ? null : (string) $oldValue,
                'new_value' => $newValue === null ? null : (string) $newValue,
                'changed_by' => $userId,
            ]);
        }

        return $changedFields;
    }

    private function writeAudit(?int $userId, string $action, Device $device, string $description, string $severity): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'Device',
            'entity_id' => (string) $device->id,
            'description' => $description,
            'severity' => $severity,
        ]);
    }
}
