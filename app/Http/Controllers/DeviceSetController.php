<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceSet;
use App\Models\DeviceSetItem;
use App\Models\Room;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeviceSetController extends Controller
{
    private const STATUSES = ['draft', 'active', 'returned', 'archived'];

    public function index()
    {
        $this->requireManager();

        $sets = DeviceSet::with('room')->orderByDesc('id')->get();

        return view('device_sets.index', compact('sets'));
    }

    public function create()
    {
        $this->requireManager();

        return view('device_sets.create', [
            'rooms' => Room::with('building')->orderBy('room_number')->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $this->requireManager();

        $userId = auth()->id();
        $data = $this->validatedData($request);
        $data['created_by'] = $userId;

        $set = DeviceSet::create($data);

        $this->writeAudit($userId, 'CREATE', $set, 'Komplekts izveidots: ' . $set->set_name);

        return redirect()->route('device-sets.edit', $set)->with('success', 'Komplekts veiksm\u{12B}gi izveidots.');
    }

    public function edit(DeviceSet $deviceSet)
    {
        $this->requireManager();

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
        $this->requireManager();

        $userId = auth()->id();
        $before = $deviceSet->only(['set_name', 'status', 'room_id', 'assigned_to', 'notes']);
        $deviceSet->update($this->validatedData($request, $deviceSet));
        $after = $deviceSet->fresh()->only(array_keys($before));

        AuditTrail::updatedFromState($userId, $deviceSet, $before, $after);

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Komplekts atjaunin\u{101}ts.');
    }

    public function destroy(DeviceSet $deviceSet)
    {
        $this->requireManager();

        $userId = auth()->id();

        $this->writeAudit($userId, 'DELETE', $deviceSet, 'Komplekts dzests: ' . $deviceSet->set_name);

        $deviceSet->delete();

        return redirect()->route('device-sets.index')->with('success', 'Komplekts dz\u{113}sts.');
    }

    public function addItem(Request $request, DeviceSet $deviceSet)
    {
        $this->requireManager();

        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $userId = auth()->id();
        $device = DB::transaction(function () use ($data, $deviceSet) {
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

            $device = Device::query()->find($data['device_id']);

            if ($device && $device->status !== 'kitting') {
                $device->forceFill(['status' => 'kitting'])->save();
            }

            return $device;
        });

        $this->writeAudit(
            $userId,
            'CREATE',
            $deviceSet,
            'Pievienots komplektam ' . $deviceSet->set_name . ': ' . trim(($device?->code ?? '') . ' ' . ($device?->name ?? ''))
        );

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Poz\u{12B}cija pievienota.');
    }

    public function deleteItem(DeviceSet $deviceSet, DeviceSetItem $item)
    {
        $this->requireManager();

        // TR: URL manipulasyonunda farkli bir sete ait kaydin silinmesini engelliyoruz.
        if ($item->device_set_id !== $deviceSet->id) {
            abort(404);
        }

        $userId = auth()->id();

        $this->writeAudit($userId, 'DELETE', $deviceSet, 'Pozicija iznemta no komplekta ' . $deviceSet->set_name);

        $item->delete();

        return redirect()->route('device-sets.edit', $deviceSet)->with('success', 'Poz\u{12B}cija no\u{146}emta.');
    }

    public function show(DeviceSet $deviceSet)
    {
        $this->requireManager();

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
        AuditTrail::writeForModel($userId, $action, $set, $description);
    }
}
