<?php

namespace App\Http\Controllers;

use App\Models\Building;
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
        Building::create($this->validatedData($request));

        return redirect()->route('buildings.index')->with('success', 'Eka veiksmigi pievienota');
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $building->update($this->validatedData($request));

        return redirect()->route('buildings.index')->with('success', 'Ekas dati atjauninati');
    }

    public function destroy(Building $building)
    {
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
