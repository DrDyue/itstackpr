<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $this->requireManager();

        $rooms = Room::with(['building', 'user'])
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();

        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        $this->requireManager();

        return view('rooms.create', [
            'buildings' => Building::orderBy('building_name')->get(),
            'users' => User::active()->orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireManager();

        $room = Room::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $room);

        return redirect()->route('rooms.index')->with('success', 'Telpa veiksmigi pievienota');
    }

    public function edit(Room $room)
    {
        $this->requireManager();

        return view('rooms.edit', [
            'room' => $room,
            'buildings' => Building::orderBy('building_name')->get(),
            'users' => User::active()->orderBy('full_name')->get(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $this->requireManager();

        $before = $room->only(['building_id', 'floor_number', 'room_number', 'room_name', 'user_id', 'department', 'notes']);
        $room->update($this->validatedData($request));
        $after = $room->fresh()->only(array_keys($before));

        AuditTrail::updatedFromState(auth()->id(), $room, $before, $after);

        return redirect()->route('rooms.index')->with('success', 'Telpas dati atjauninati');
    }

    public function destroy(Room $room)
    {
        $this->requireManager();

        AuditTrail::deleted(auth()->id(), $room);
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
            'user_id' => ['nullable', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $data['user_id'] = $data['user_id'] ?: null;
        $data['room_name'] = $data['room_name'] ?: null;
        $data['department'] = $data['department'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        return $data;
    }
}
