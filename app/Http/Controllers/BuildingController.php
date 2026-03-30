<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Ēku pārvaldības CRUD kontrolieris.
 */
class BuildingController extends Controller
{
    private const NOTES_DEFAULT = '';

    /**
     * Parāda ēku sarakstu ar filtriem.
     */
    public function index(Request $request)
    {
        $this->requireManager();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'city' => trim((string) $request->query('city', '')),
        ];

        $buildings = Building::query()
            ->withCount(['rooms', 'devices'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $searchQuery) use ($term) {
                    $searchQuery->where('building_name', 'like', "%{$term}%")
                        ->orWhere('address', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%");
                });
            })
            ->when($filters['city'] !== '', fn (Builder $query) => $query->where('city', $filters['city']))
            ->orderBy('building_name')
            ->paginate(20)
            ->withQueryString();

        return view('buildings.index', [
            'buildings' => $buildings,
            'filters' => $filters,
            'cities' => Building::query()
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),
        ]);
    }

    /**
     * Parāda jaunas ēkas izveides formu.
     */
    public function create()
    {
        $this->requireManager();

        return view('buildings.create');
    }

    /**
     * Saglabā jaunu ēkas ierakstu.
     */
    public function store(Request $request)
    {
        $this->requireManager();

        $building = Building::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $building);

        return redirect()->route('buildings.index')->with('success', 'Ēka veiksmīgi pievienota');
    }

    /**
     * Parāda ēkas rediģēšanas formu.
     */
    public function edit(Building $building)
    {
        $this->requireManager();

        return view('buildings.edit', compact('building'));
    }

    /**
     * Atjaunina ēkas datus.
     */
    public function update(Request $request, Building $building)
    {
        $this->requireManager();

        $before = $building->only(['building_name', 'address', 'city', 'total_floors', 'notes']);
        $building->update($this->validatedData($request));
        $after = $building->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $building, $before, $after);

        return redirect()->route('buildings.index')->with('success', 'Ēkas dati atjaunināti');
    }

    /**
     * Dzēš ēku tikai tad, ja tai vairs nav piesaistītu telpu un ierīču.
     */
    public function destroy(Building $building)
    {
        $this->requireManager();

        $roomsCount = $building->rooms()->count();
        $devicesCount = $building->devices()->count();

        if ($roomsCount > 0 || $devicesCount > 0) {
            $parts = [];

            if ($roomsCount > 0) {
                $parts[] = "ēka joprojām satur {$roomsCount} telpu" . ($roomsCount === 1 ? '' : 's');
            }

            if ($devicesCount > 0) {
                $parts[] = "tai piesaistītas {$devicesCount} ierīce" . ($devicesCount === 1 ? '' : 's');
            }

            return redirect()
                ->route('buildings.index')
                ->with('error', 'Ēku nevar dzēst, jo ' . implode(' un ', $parts) . '. Vispirms pārvieto vai dzēs piesaistītas telpas un ierīces, tad mēģiniet vēlreiz.');
        }

        AuditTrail::deleted(auth()->id(), $building);
        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Ēka dzēsta');
    }

    /**
     * Vecais show ceļš tiek novirzīts atpakaļ uz sarakstu.
     */
    public function show(Building $building)
    {
        $this->requireManager();

        return redirect()->route('buildings.index');
    }

    private function validatedData(Request $request): array
    {
        $data = $this->validateInput($request, [
            'building_name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string', 'max:200'],
        ], [
            'building_name.required' => 'Norādi ēkas nosaukumu.',
        ]);

        $data['notes'] = $data['notes'] ?? self::NOTES_DEFAULT;

        return $data;
    }
}
