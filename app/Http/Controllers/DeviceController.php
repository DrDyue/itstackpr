<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use App\Models\DeviceType;
use App\Models\Room;
use App\Support\DeviceAssetManager;
use App\Support\DeviceImageAutoFetcher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            'type' => trim((string) $request->query('type', '')),
        ];

        $devices = Device::query()
            ->with(['type', 'building', 'room', 'activeRepair'])
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

        $categories = DeviceType::query()
            ->selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('total', 'category');

        $activeFilterCount = collect($filters)
            ->filter(fn ($value) => $value !== '')
            ->count();

        return view('devices.index', [
            'devices' => $devices,
            'filters' => $filters,
            'types' => $types,
            'activeFilterCount' => $activeFilterCount,
            'statusLabels' => $this->statusLabels(),
            'categoryOptions' => $categories,
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
        $this->populateAutoImage($request, $device);

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
            'deviceThumbUrl' => $device->deviceImageThumbUrl(),
            'warrantyImageUrl' => $device->warrantyImageUrl(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $userId = auth()->id();
        $data = $this->validatedData($request, $device);

        $before = $device->only($this->trackedFields());

        $device->update($data);
        $this->syncUploads($request, $device);
        $this->populateAutoImage($request, $device->fresh());

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

    public function quickUpdate(Request $request, Device $device)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['status', 'room', 'set'])],
            'target_status' => ['nullable', Rule::in(self::STATUSES)],
            'target_room_id' => ['nullable', 'exists:rooms,id'],
            'target_set_id' => ['nullable', 'exists:device_sets,id'],
        ]);

        $result = $this->performDeviceAction($device, $validated, auth()->id());

        return redirect()
            ->route('devices.show', $device)
            ->with($result['level'], $result['message']);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['integer', 'exists:devices,id'],
            'action' => ['required', Rule::in(['status', 'room', 'set'])],
            'target_status' => ['nullable', Rule::in(self::STATUSES)],
            'target_room_id' => ['nullable', 'exists:rooms,id'],
            'target_set_id' => ['nullable', 'exists:device_sets,id'],
        ]);

        $devices = Device::query()
            ->whereIn('id', $validated['device_ids'])
            ->get();

        $processed = 0;
        $messages = [];

        foreach ($devices as $device) {
            $result = $this->performDeviceAction($device, $validated, auth()->id());
            if ($result['level'] === 'success') {
                $processed++;
            } else {
                $messages[] = ($device->code ?: ('ID ' . $device->id)) . ': ' . $result['message'];
            }
        }

        $flash = $processed > 0
            ? 'Apstradatas ierices: ' . $processed . '.'
            : 'Neviena ierice netika apstradata.';

        if ($messages !== []) {
            $flash .= ' ' . implode(' ', array_slice($messages, 0, 3));
        }

        return redirect()
            ->route('devices.index')
            ->with($processed > 0 ? 'success' : 'error', $flash);
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

        if (($data['room_id'] ?? null) !== null) {
            $room = Room::query()->find($data['room_id']);

            if ($room && ($data['building_id'] ?? null) === null) {
                $data['building_id'] = $room->building_id;
            }

            if ($room && ($data['building_id'] ?? null) !== null && (int) $room->building_id !== (int) $data['building_id']) {
                throw ValidationException::withMessages([
                    'room_id' => ['Izveleta telpa nepieder noraditajai ekai.'],
                ]);
            }
        }

        if (
            ! empty($data['warranty_until'])
            && ! empty($data['purchase_date'])
            && strtotime((string) $data['warranty_until']) < strtotime((string) $data['purchase_date'])
        ) {
            throw ValidationException::withMessages([
                'warranty_until' => ['Garantijas datums nevar but agraks par pirkuma datumu.'],
            ]);
        }

        if (($data['status'] ?? null) === 'retired' && ! empty($data['assigned_to'])) {
            throw ValidationException::withMessages([
                'assigned_to' => ['Norakstitai iericei nevajag but pieskirtai personai.'],
            ]);
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

    private function populateAutoImage(Request $request, Device $device): void
    {
        if ($request->hasFile('device_image') || filled($device->device_image_url)) {
            return;
        }

        $device->loadMissing('type');

        app(DeviceImageAutoFetcher::class)->populate($device);
    }

    private function deleteDeviceAssets(Device $device): void
    {
        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->device_image_url);
        $assetManager->delete($assetManager->thumbnailPath($device->device_image_url));
        $assetManager->delete($device->warranty_photo_name);
        $assetManager->delete($assetManager->thumbnailPath($device->warranty_photo_name));
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

    private function statusLabels(): array
    {
        return collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
            ->all();
    }

    private function performDeviceAction(Device $device, array $data, ?int $userId): array
    {
        return match ($data['action']) {
            'status' => $this->changeDeviceStatus($device, (string) ($data['target_status'] ?? ''), $userId),
            'room' => $this->moveDevice($device, $data['target_room_id'] ?? null, $userId),
            'set' => $this->attachDeviceToSet($device, $data['target_set_id'] ?? null, $userId),
            default => ['level' => 'error', 'message' => 'Neatbalstita darbiba.'],
        };
    }

    private function changeDeviceStatus(Device $device, string $status, ?int $userId): array
    {
        if (! in_array($status, self::STATUSES, true)) {
            return ['level' => 'error', 'message' => 'Nav izvelets korekts statuss.'];
        }

        if ($status === 'retired' && filled($device->assigned_to)) {
            return ['level' => 'error', 'message' => 'Norakstitu ierici vispirms atsaisti no personas.'];
        }

        if ($device->status === $status) {
            return ['level' => 'error', 'message' => 'Statuss jau ir iestatits.'];
        }

        $oldStatus = $device->status;
        $device->forceFill(['status' => $status])->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action' => 'STATUS_CHANGE',
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $status,
            'changed_by' => $userId,
        ]);

        $this->writeAudit($userId, 'UPDATE', $device, 'Device status changed: ' . $device->name . ' | ' . $oldStatus . ' -> ' . $status, 'info');

        return ['level' => 'success', 'message' => 'Statuss atjauninats.'];
    }

    private function moveDevice(Device $device, mixed $roomId, ?int $userId): array
    {
        if (! $roomId) {
            return ['level' => 'error', 'message' => 'Nav izveleta telpa.'];
        }

        $room = Room::query()->with('building')->find($roomId);
        if (! $room) {
            return ['level' => 'error', 'message' => 'Telpa nav atrasta.'];
        }

        if ((int) $device->room_id === (int) $room->id) {
            return ['level' => 'error', 'message' => 'Ierice jau atrodas saja telpa.'];
        }

        $oldValue = $device->room?->room_number ?: '-';

        $device->forceFill([
            'room_id' => $room->id,
            'building_id' => $room->building_id,
        ])->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action' => 'MOVE',
            'field_changed' => 'room_id',
            'old_value' => $oldValue,
            'new_value' => $room->room_number,
            'changed_by' => $userId,
        ]);

        $this->writeAudit($userId, 'UPDATE', $device, 'Device moved: ' . $device->name . ' -> room ' . $room->room_number, 'info');

        return ['level' => 'success', 'message' => 'Ierice parvietota uz citu telpu.'];
    }

    private function attachDeviceToSet(Device $device, mixed $setId, ?int $userId): array
    {
        if (! $setId) {
            return ['level' => 'error', 'message' => 'Nav izveleta komplektacija.'];
        }

        $set = DeviceSet::query()->find($setId);
        if (! $set) {
            return ['level' => 'error', 'message' => 'Komplektacija nav atrasta.'];
        }

        $existing = DeviceSetItem::query()
            ->where('device_set_id', $set->id)
            ->where('device_id', $device->id)
            ->first();

        if ($existing) {
            return ['level' => 'error', 'message' => 'Ierice jau ir saja komplektacija.'];
        }

        DeviceSetItem::create([
            'device_set_id' => $set->id,
            'device_id' => $device->id,
        ]);

        if ($device->status !== 'kitting') {
            $device->forceFill(['status' => 'kitting'])->save();
        }

        DeviceHistory::create([
            'device_id' => $device->id,
            'action' => 'SET_ATTACH',
            'field_changed' => 'device_set_id',
            'old_value' => null,
            'new_value' => $set->set_name,
            'changed_by' => $userId,
        ]);

        $this->writeAudit($userId, 'UPDATE', $device, 'Device added to set: ' . $device->name . ' -> ' . $set->set_name, 'info');

        return ['level' => 'success', 'message' => 'Ierice pievienota komplektacijai.'];
    }
}
