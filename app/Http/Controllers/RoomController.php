<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $this->requireManager();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'building_id' => trim((string) $request->query('building_id', '')),
            'department' => trim((string) $request->query('department', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'has_devices' => trim((string) $request->query('has_devices', '')),
        ];

        $rooms = Room::query()
            ->with(['building', 'user'])
            ->withCount('devices')
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $searchQuery) use ($term) {
                    $searchQuery->where('room_number', 'like', "%{$term}%")
                        ->orWhere('room_name', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhereHas('building', fn (Builder $buildingQuery) => $buildingQuery->where('building_name', 'like', "%{$term}%"))
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('full_name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['building_id'] !== '' && ctype_digit($filters['building_id']), fn (Builder $query) => $query->where('building_id', (int) $filters['building_id']))
            ->when($filters['department'] !== '', fn (Builder $query) => $query->where('department', $filters['department']))
            ->when($filters['user_id'] !== '' && ctype_digit($filters['user_id']), fn (Builder $query) => $query->where('user_id', (int) $filters['user_id']))
            ->when($filters['has_devices'] === '1', fn (Builder $query) => $query->has('devices'))
            ->when($filters['has_devices'] === '0', fn (Builder $query) => $query->doesntHave('devices'))
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->paginate(20)
            ->withQueryString();

        return view('rooms.index', [
            'rooms' => $rooms,
            'filters' => $filters,
            'buildings' => Building::orderBy('building_name')->get(),
            'departments' => Room::query()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department'),
            'responsibleUsers' => User::active()->orderBy('full_name')->get(),
        ]);
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
        $room->update($this->validatedData($request, $room));
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

    private function validatedData(Request $request, ?Room $room = null): array
    {
        $data = $this->validateInput($request, [
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer', 'min:-10', 'max:200'],
            'room_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'room_number')
                    ->where(fn ($query) => $query->where('building_id', $request->input('building_id')))
                    ->ignore($room?->id),
            ],
            'room_name' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:200'],
        ], [
            'building_id.required' => 'Izvelies eku, kurai telpa pieder.',
            'room_number.required' => 'Noradi telpas numuru.',
        ]);

        $data['user_id'] = $data['user_id'] ?: null;
        $data['room_name'] = $data['room_name'] ?: null;
        $data['department'] = $data['department'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        return $data;
    }
}
