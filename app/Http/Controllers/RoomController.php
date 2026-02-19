<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Employee;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with(['building', 'employee'])
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();

        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('rooms.create', [
            'buildings' => Building::orderBy('building_name')->get(),
            'employees' => Employee::orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Room::create($this->validatedData($request));

        return redirect()->route('rooms.index')->with('success', 'Telpa veiksmigi pievienota');
    }

    public function edit(Room $room)
    {
        return view('rooms.edit', [
            'room' => $room,
            'buildings' => Building::orderBy('building_name')->get(),
            'employees' => Employee::orderBy('full_name')->get(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validatedData($request));

        return redirect()->route('rooms.index')->with('success', 'Telpas dati atjauninati');
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Telpa dzesta');
    }

    public function show(Room $room)
    {
        return redirect()->route('rooms.index');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer', 'min:-10', 'max:200'],
            'room_number' => ['required', 'string', 'max:20'],
            'room_name' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        if (($data['employee_id'] ?? null) === '') {
            $data['employee_id'] = null;
        }

        return $data;
    }
}
