<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use Illuminate\Http\Request;

class DeviceTypeController extends Controller
{
    public function index()
    {
        $types = DeviceType::orderBy('type_name')->get();
        return view('device_types.index', compact('types'));
    }

    public function create()
    {
        return view('device_types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30', 'unique:device_types,type_name'],
            'category' => ['required', 'string', 'max:50'],
            'icon_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'expected_lifetime_years' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        DeviceType::create($data);

        return redirect()->route('device-types.index')->with('success', 'Ierices tips veiksmigi pievienots');
    }

    public function edit(DeviceType $deviceType)
    {
        return view('device_types.edit', ['type' => $deviceType]);
    }

    public function update(Request $request, DeviceType $deviceType)
    {
        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30', 'unique:device_types,type_name,' . $deviceType->id],
            'category' => ['required', 'string', 'max:50'],
            'icon_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'expected_lifetime_years' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $deviceType->update($data);

        return redirect()->route('device-types.index')->with('success', 'Ierices tips atjauninats');
    }

    public function destroy(DeviceType $deviceType)
    {
        $deviceType->delete();
        return redirect()->route('device-types.index')->with('success', 'Ierices tips dzests');
    }

    public function show(DeviceType $deviceType)
    {
        return redirect()->route('device-types.index');
    }
}
