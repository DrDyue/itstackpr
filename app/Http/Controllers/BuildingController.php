<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    private const NOTES_DEFAULT = '';

    public function index()
    {
        $buildings = Building::orderBy('building_name')->get();

        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        return view('buildings.create');
    }

    public function store(Request $request)
    {
        $building = Building::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $building);

        return redirect()->route('buildings.index')->with('success', 'Eka veiksmigi pievienota');
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $before = $building->only(['building_name', 'address', 'city', 'total_floors', 'notes']);
        $building->update($this->validatedData($request));
        $after = $building->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $building, $before, $after);

        return redirect()->route('buildings.index')->with('success', 'Ekas dati atjauninati');
    }

    public function destroy(Building $building)
    {
        AuditTrail::deleted(auth()->id(), $building);
        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Eka dzesta');
    }

    public function show(Building $building)
    {
        return redirect()->route('buildings.index');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'building_name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $data['notes'] = $data['notes'] ?? self::NOTES_DEFAULT;

        return $data;
    }
}
