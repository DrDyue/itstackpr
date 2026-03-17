<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use App\Models\DeviceType;
use App\Models\Room;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\DeviceAssetManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    private const STATUSES = ['active', 'reserve', 'broken', 'repair', 'written_off', 'kitting'];

    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'room' => trim((string) $request->query('room', '')),
            'type' => trim((string) $request->query('type', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $devices = $this->visibleDevicesQuery($user)
            ->with(['type', 'building', 'room', 'activeRepair', 'assignedUser'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $deviceQuery) use ($term) {
                    $deviceQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('manufacturer', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%");
                });
            })
            ->when($filters['code'] !== '', fn (Builder $query) => $query->where('code', 'like', '%' . $filters['code'] . '%'))
            ->when($filters['room'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['room'];

                $query->whereHas('room', function (Builder $roomQuery) use ($term) {
                    $roomQuery->where('room_number', 'like', "%{$term}%")
                        ->orWhere('room_name', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%");
                });
            })
            ->when($filters['type'] !== '' && ctype_digit($filters['type']), fn (Builder $query) => $query->where('device_type_id', (int) $filters['type']))
            ->when($filters['status'] !== '' && in_array($filters['status'], self::STATUSES, true), fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('devices.index', [
            'devices' => $devices,
            'filters' => $filters,
            'types' => DeviceType::query()->orderBy('type_name')->get(),
            'statuses' => self::STATUSES,
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $user->canManageRequests(),
        ]);
    }

    public function create()
    {
        $this->requireManager();

        return view('devices.create', $this->formData());
    }

    public function store(Request $request)
    {
        $user = $this->requireManager();

        $device = Device::create(array_merge(
            $this->validatedData($request),
            ['created_by' => $user->id]
        ));

        $this->syncUploads($request, $device);
        $this->removeDeviceImage($request, $device);
        $this->removeWarrantyImage($request, $device);

        AuditTrail::created($user->id, $device);

        return redirect()->route('devices.index')->with('success', 'Ierice veiksmigi pievienota');
    }

    public function edit(Device $device)
    {
        $this->requireManager();

        return view('devices.edit', array_merge(['device' => $device], $this->formData()));
    }

    public function show(Device $device)
    {
        $this->authorizeView($device);

        $device->load([
            'type',
            'building',
            'room.building',
            'createdBy',
            'assignedUser',
            'repairs.assignee',
            'repairRequests.responsibleUser',
            'writeoffRequests.responsibleUser',
            'transfers.responsibleUser',
            'transfers.transferTo',
            'sets.room.building',
        ]);

        return view('devices.show', [
            'device' => $device,
            'deviceImageUrl' => $device->deviceImageUrl(),
            'deviceThumbUrl' => $device->deviceImageThumbUrl(),
            'warrantyImageUrl' => $device->warrantyImageUrl(),
            'statusLabels' => $this->statusLabels(),
            'canManageDevices' => $this->user()?->canManageRequests() ?? false,
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $this->requireManager();

        $before = $device->only($this->trackedFields());

        $device->update($this->validatedData($request, $device));
        $this->syncUploads($request, $device);
        $this->removeDeviceImage($request, $device);
        $this->removeWarrantyImage($request, $device);

        $after = $device->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $device, $before, $after);

        return redirect()->route('devices.index')->with('success', 'Ierices dati atjauninati');
    }

    public function destroy(Device $device)
    {
        $this->requireManager();

        $this->deleteDeviceAssets($device);
        AuditTrail::deleted(auth()->id(), $device, severity: AuditTrail::SEVERITY_WARNING);
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Ierice dzesta');
    }

    public function quickUpdate(Request $request, Device $device)
    {
        $this->requireManager();

        $validated = $request->validate([
            'action' => ['required', Rule::in(['status', 'room', 'set'])],
            'target_status' => ['nullable', Rule::in(self::STATUSES)],
            'target_room_id' => ['nullable', 'exists:rooms,id'],
            'target_set_id' => ['nullable', 'exists:device_sets,id'],
        ]);

        $result = $this->performDeviceAction($device, $validated);

        return redirect()->route('devices.show', $device)->with($result['level'], $result['message']);
    }

    public function bulkUpdate(Request $request)
    {
        $this->requireManager();

        $validated = $request->validate([
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['integer', 'exists:devices,id'],
            'action' => ['required', Rule::in(['status', 'room', 'set'])],
            'target_status' => ['nullable', Rule::in(self::STATUSES)],
            'target_room_id' => ['nullable', 'exists:rooms,id'],
            'target_set_id' => ['nullable', 'exists:device_sets,id'],
        ]);

        $devices = Device::query()->whereIn('id', $validated['device_ids'])->get();
        $processed = 0;
        $messages = [];

        foreach ($devices as $device) {
            $result = $this->performDeviceAction($device, $validated);

            if ($result['level'] === 'success') {
                $processed++;
            } else {
                $messages[] = ($device->code ?: ('ID ' . $device->id)) . ': ' . $result['message'];
            }
        }

        $flash = $processed > 0 ? 'Apstradatas ierices: ' . $processed . '.' : 'Neviena ierice netika apstradata.';
        if ($messages !== []) {
            $flash .= ' ' . implode(' ', array_slice($messages, 0, 3));
        }

        return redirect()->route('devices.index')->with($processed > 0 ? 'success' : 'error', $flash);
    }

    private function visibleDevicesQuery(User $user): Builder
    {
        return Device::query()->when(
            ! $user->canManageRequests(),
            fn (Builder $query) => $query->where('assigned_user_id', $user->id)
        );
    }

    private function authorizeView(Device $device): void
    {
        $user = $this->user();
        abort_unless($user, 403);

        if ($user->canManageRequests()) {
            return;
        }

        abort_unless((int) $device->assigned_user_id === (int) $user->id, 403);
    }

    private function formData(): array
    {
        return [
            'types' => DeviceType::orderBy('type_name')->get(),
            'buildings' => Building::orderBy('building_name')->get(),
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'users' => User::active()->orderBy('full_name')->get(),
            'statuses' => self::STATUSES,
            'statusLabels' => $this->statusLabels(),
        ];
    }

    private function validatedData(Request $request, ?Device $device = null): array
    {
        $data = $request->validate(
            [
                'code' => ['nullable', 'string', 'max:20', Rule::unique('devices', 'code')->ignore($device?->id)],
                'name' => ['required', 'string', 'max:200'],
                'device_type_id' => ['required', 'exists:device_types,id'],
                'model' => ['required', 'string', 'max:100'],
                'status' => ['required', Rule::in(self::STATUSES)],
                'building_id' => ['nullable', 'exists:buildings,id'],
                'room_id' => ['nullable', 'exists:rooms,id'],
                'assigned_user_id' => ['nullable', 'exists:users,id'],
                'purchase_date' => ['required', 'date'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
                'warranty_until' => ['nullable', 'date'],
                'serial_number' => ['nullable', 'string', 'max:100'],
                'manufacturer' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string'],
                'device_image' => ['nullable', 'image', 'max:' . (int) config('devices.max_upload_kb', 5120)],
                'warranty_image' => ['nullable', 'image', 'max:' . (int) config('devices.max_upload_kb', 5120)],
            ]
        );

        foreach (['building_id', 'room_id', 'assigned_user_id'] as $field) {
            $data[$field] = $data[$field] ?: null;
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

        if (($data['status'] ?? null) === 'written_off' && ! empty($data['assigned_user_id'])) {
            throw ValidationException::withMessages([
                'assigned_user_id' => ['Norakstitai iericei nevajag but pieskirtai personai.'],
            ]);
        }

        unset($data['device_image'], $data['warranty_image']);

        $data['warranty_photo_name'] = $device?->warranty_photo_name;
        $data['device_image_url'] = $device?->device_image_url;

        return $data;
    }

    private function trackedFields(): array
    {
        return [
            'code',
            'name',
            'device_type_id',
            'model',
            'status',
            'building_id',
            'room_id',
            'assigned_user_id',
            'purchase_date',
            'purchase_price',
            'warranty_until',
            'warranty_photo_name',
            'serial_number',
            'manufacturer',
            'notes',
            'device_image_url',
        ];
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

    private function removeDeviceImage(Request $request, Device $device): void
    {
        if ($request->hasFile('device_image')) {
            return;
        }

        if (! $request->boolean('remove_device_image') || ! filled($device->device_image_url)) {
            return;
        }

        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->device_image_url);
        $assetManager->delete($assetManager->thumbnailPath($device->device_image_url));
        $device->forceFill(['device_image_url' => null])->save();
    }

    private function removeWarrantyImage(Request $request, Device $device): void
    {
        if ($request->hasFile('warranty_image')) {
            return;
        }

        if (! $request->boolean('remove_warranty_image') || ! filled($device->warranty_photo_name)) {
            return;
        }

        $assetManager = app(DeviceAssetManager::class);
        $assetManager->delete($device->warranty_photo_name);
        $assetManager->delete($assetManager->thumbnailPath($device->warranty_photo_name));
        $device->forceFill(['warranty_photo_name' => null])->save();
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
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'written_off' => 'Norakstita',
            'kitting' => 'Komplektacija',
            default => 'Aktiva',
        };
    }

    private function statusLabels(): array
    {
        return collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
            ->all();
    }

    private function performDeviceAction(Device $device, array $data): array
    {
        return match ($data['action']) {
            'status' => $this->changeDeviceStatus($device, (string) ($data['target_status'] ?? '')),
            'room' => $this->moveDevice($device, $data['target_room_id'] ?? null),
            'set' => $this->attachDeviceToSet($device, $data['target_set_id'] ?? null),
            default => ['level' => 'error', 'message' => 'Neatbalstita darbiba.'],
        };
    }

    private function changeDeviceStatus(Device $device, string $status): array
    {
        if (! in_array($status, self::STATUSES, true)) {
            return ['level' => 'error', 'message' => 'Nav izvelets korekts statuss.'];
        }

        if ($status === 'written_off' && filled($device->assigned_user_id)) {
            return ['level' => 'error', 'message' => 'Norakstitu ierici vispirms atsaisti no personas.'];
        }

        if ($device->status === $status) {
            return ['level' => 'error', 'message' => 'Statuss jau ir iestatits.'];
        }

        $before = ['status' => $device->status];
        $device->forceFill(['status' => $status])->save();
        AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => $status]);

        return ['level' => 'success', 'message' => 'Statuss atjauninats.'];
    }

    private function moveDevice(Device $device, mixed $roomId): array
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

        $before = $device->only(['room_id', 'building_id']);

        $device->forceFill([
            'room_id' => $room->id,
            'building_id' => $room->building_id,
        ])->save();

        AuditTrail::updatedFromState(auth()->id(), $device, $before, [
            'room_id' => $room->id,
            'building_id' => $room->building_id,
        ]);

        return ['level' => 'success', 'message' => 'Ierice parvietota uz citu telpu.'];
    }

    private function attachDeviceToSet(Device $device, mixed $setId): array
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

        $before = ['status' => $device->status];

        if ($device->status !== 'kitting') {
            $device->forceFill(['status' => 'kitting'])->save();
        }

        AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => $device->status]);

        return ['level' => 'success', 'message' => 'Ierice pievienota komplektacijai.'];
    }
}
