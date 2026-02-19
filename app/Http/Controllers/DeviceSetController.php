<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceSetController extends Controller
{
    private const STATUSES = ['draft', 'active', 'returned', 'archived'];

    public function index()
    {
        $sets = DeviceSet::with('room')->orderByDesc('id')->get();

        return view('device_sets.index', compact('sets'));
    }

    public function create()
    {
        return view('device_sets.create', [
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        $data = $this->validatedData($request);
        $data['created_by'] = $userId;

        $set = DeviceSet::create($data);

        $this->writeAudit($userId, 'CREATE', $set, 'Device set created: ' . $set->set_name);

        return redirect()->route('device-sets.edit', $set)->with('success', 'Komplekts veiksm\u{12B}gi izveidots.');
    }

    public function edit(DeviceSet $deviceSet)
    {
        $deviceSet->load(['room', 'items.device']);

        return view('device_sets.edit', [
            'set' => $deviceSet,
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'statuses' => self::STATUSES,
            'devices' => Device::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, DeviceSet $deviceSet)
    {
        $userId = auth()->id();
        $deviceSet->update($this->validatedData($request, $deviceSet));

        $this->writeAudit($userId, 'UPDATE', $deviceSet, 'Device set updated: ' . $deviceSet->set_name);

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Komplekts atjaunin\u{101}ts.');
    }

    public function destroy(DeviceSet $deviceSet)
    {
        $userId = auth()->id();

        $this->writeAudit($userId, 'DELETE', $deviceSet, 'Device set deleted: ' . $deviceSet->set_name);

        $deviceSet->delete();

        return redirect()->route('device-sets.index')->with('success', 'Komplekts dz\u{113}sts.');
    }

    public function addItem(Request $request, DeviceSet $deviceSet)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

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

        $device = Device::find($data['device_id']);
        $userId = auth()->id();

        $this->writeAudit(
            $userId,
            'CREATE',
            $deviceSet,
            'Added to set ' . $deviceSet->set_name . ': ' . ($device?->code ?? '') . ' ' . ($device?->name ?? '')
        );

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Poz\u{12B}cija pievienota.');
    }

    public function deleteItem(DeviceSet $deviceSet, DeviceSetItem $item)
    {
        // TR: URL manipulasyonunda farkli bir sete ait kaydin silinmesini engelliyoruz.
        if ($item->device_set_id !== $deviceSet->id) {
            abort(404);
        }

        $userId = auth()->id();

        $this->writeAudit($userId, 'DELETE', $deviceSet, 'Removed item from set ' . $deviceSet->set_name);

        $item->delete();

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Poz\u{12B}cija no\u{146}emta.');
    }

    public function show(DeviceSet $deviceSet)
    {
        return redirect()->route('device-sets.index');
    }

    private function validatedData(Request $request, ?DeviceSet $deviceSet = null): array
    {
        $data = $request->validate([
            'set_name' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['room_id'] ?? null) === '') {
            $data['room_id'] = null;
        }

        // TR: Eski tabloda (name/description) alanlari hala kullaniliyor, bu esleme geriye donuk uyum icin.
        $data['name'] = $data['set_name'];
        $data['description'] = $data['notes'] ?? '';

        return $data;
    }

    private function writeAudit(?int $userId, string $action, DeviceSet $set, string $description): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'DeviceSet',
            'entity_id' => (string) $set->id,
            'description' => $description,
        ]);
    }
}
