<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

/**
 * Ierīču tipu vārdnīcas pārvaldība.
 */
class DeviceTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->requireManager();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'sort' => (string) $request->query('sort', 'type_name'),
            'direction' => (string) $request->query('direction', 'asc'),
        ];

        $allowedSorts = ['type_name', 'category'];
        $sort = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : 'type_name';
        $direction = $filters['direction'] === 'desc' ? 'desc' : 'asc';

        $types = DeviceType::query()
            ->withCount('devices')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where('type_name', 'like', '%' . $filters['q'] . '%');
            })
            ->when($filters['category'] !== '', function ($query) use ($filters) {
                $query->where('category', 'like', '%' . $filters['category'] . '%');
            })
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(16)
            ->withQueryString();

        $categoryOptions = DeviceType::query()
            ->selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return view('device_types.index', [
            'types' => $types,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    public function create()
    {
        $this->requireManager();

        return view('device_types.create');
    }

    public function store(Request $request)
    {
        $this->requireManager();

        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30', 'unique:device_types,type_name'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $deviceType = DeviceType::create($data);
        AuditTrail::created(auth()->id(), $deviceType);

        return redirect()->route('device-types.index')->with('success', 'Ierīces tips veiksmīgi pievienots');
    }

    public function edit(DeviceType $deviceType)
    {
        $this->requireManager();

        return view('device_types.edit', ['type' => $deviceType]);
    }

    public function update(Request $request, DeviceType $deviceType)
    {
        $this->requireManager();

        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30', 'unique:device_types,type_name,' . $deviceType->id],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $before = $deviceType->only(['type_name', 'category', 'description']);
        $deviceType->update($data);
        $after = $deviceType->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $deviceType, $before, $after);

        return redirect()->route('device-types.index')->with('success', 'Ierīces tips atjaunināts');
    }

    public function destroy(DeviceType $deviceType)
    {
        $this->requireManager();

        AuditTrail::deleted(auth()->id(), $deviceType);
        $deviceType->delete();
        return redirect()->route('device-types.index')->with('success', 'Ierīces tips dzēsts');
    }

    public function show(DeviceType $deviceType)
    {
        $this->requireManager();

        return redirect()->route('device-types.index');
    }
}
