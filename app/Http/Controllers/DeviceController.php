<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceType;
use App\Models\Room;
use App\Support\DeviceAssetManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    private const STATUSES = ['active', 'reserve', 'broken', 'repair', 'retired', 'kitting'];

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'room' => trim((string) $request->query('room', '')),
            'status' => trim((string) $request->query('status', '')),
            'type' => trim((string) $request->query('type', '')),
        ];

        $devices = Device::query()
            ->with(['type', 'building', 'room'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($deviceQuery) use ($term) {
                    $deviceQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('manufacturer', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($filters['code'] !== '', function ($query) use ($filters) {
                $query->where('code', 'like', '%' . $filters['code'] . '%');
            })
            ->when($filters['room'] !== '', function ($query) use ($filters) {
                $term = $filters['room'];

                $query->whereHas('room', function ($roomQuery) use ($term) {
                    $roomQuery->where('room_number', 'like', "%{$term}%")
                        ->orWhere('room_name', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%");
                });
            })
            ->when(in_array($filters['status'], self::STATUSES, true), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['type'] !== '' && ctype_digit($filters['type']), function ($query) use ($filters) {
                $query->where('device_type_id', (int) $filters['type']);
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $types = DeviceType::query()
            ->withCount('devices')
            ->orderBy('type_name')
            ->get();

        $statusCounts = Device::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusOptions = collect(self::STATUSES)->map(function (string $status) use ($statusCounts) {
            return [
                'value' => $status,
                'label' => $this->statusLabel($status),
                'count' => (int) ($statusCounts[$status] ?? 0),
            ];
        });

        $activeFilterCount = collect($filters)
            ->filter(fn ($value) => $value !== '')
            ->count();

        return view('devices.index', [
            'devices' => $devices,
            'filters' => $filters,
            'types' => $types,
            'statusOptions' => $statusOptions,
            'activeFilterCount' => $activeFilterCount,
        ]);
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
        $this->syncUploads($request, $device);

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

    public function show(Device $device)
    {
        $device->load([
            'type',
            'building',
            'room.building',
            'createdBy.employee',
            'histories.changedBy.employee',
            'sets.room.building',
        ]);

        return view('devices.show', [
            'device' => $device,
            'deviceImageUrl' => $device->deviceImageUrl(),
            'warrantyImageUrl' => $device->warrantyImageUrl(),
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $userId = auth()->id();
        $data = $this->validatedData($request, $device);

        $before = $device->only($this->trackedFields());

        $device->update($data);
        $this->syncUploads($request, $device);

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
        $this->deleteDeviceAssets($device);

        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Ierice dzesta');
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
            'serial_number' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'device_image' => ['nullable', 'image', 'max:' . (int) config('devices.max_upload_kb', 5120)],
            'warranty_image' => ['nullable', 'image', 'max:' . (int) config('devices.max_upload_kb', 5120)],
        ]);

        foreach (['building_id', 'room_id'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        unset($data['device_image'], $data['warranty_image']);

        $data['warranty_photo_name'] = $device?->warranty_photo_name ?? '';
        $data['device_image_url'] = $device?->device_image_url;

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

    private function syncUploads(Request $request, Device $device): void
    {
        $assetManager = app(DeviceAssetManager::class);
        $updates = [];

        if ($request->hasFile('device_image')) {
            $updates['device_image_url'] = $assetManager->storeDeviceImage(
                $request->file('device_image'),
                $device->device_image_url
            );
        }

        if ($request->hasFile('warranty_image')) {
            $updates['warranty_photo_name'] = $assetManager->storeWarrantyImage(
                $request->file('warranty_image'),
                $device->warranty_photo_name
            );
        }

        if ($updates !== []) {
            $device->forceFill($updates)->save();
        }
    }

    private function deleteDeviceAssets(Device $device): void
    {
        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->device_image_url);
        $assetManager->delete($device->warranty_photo_name);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Aktiva',
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'retired' => 'Norakstita',
            'kitting' => 'Komplektacija',
            default => ucfirst($status),
        };
    }
}
