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
        $buildings = Building::orderBy('building_name')->get();
        $employees = Employee::orderBy('full_name')->get();

        return view('rooms.create', compact('buildings', 'employees'));
    }

    public function store(Request $request)
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

        // Если прислали пустую строку — превращаем в null
        if (($data['employee_id'] ?? null) === '') {
            $data['employee_id'] = null;
        }

        Room::create($data);

        return redirect()->route('rooms.index')->with('success', 'Room created');
    }

    public function edit(Room $room)
    {
        $buildings = Building::orderBy('building_name')->get();
        $employees = Employee::orderBy('full_name')->get();

        return view('rooms.edit', compact('room', 'buildings', 'employees'));
    }

    public function update(Request $request, Room $room)
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

        $room->update($data);

        return redirect()->route('rooms.index')->with('success', 'Room updated');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'Room deleted');
    }

    public function show(Room $room)
    {
        return redirect()->route('rooms.index');
    }
}
