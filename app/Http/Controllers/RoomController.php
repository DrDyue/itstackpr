<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Telpu pārvaldības CRUD kontrolieris.
 */
class RoomController extends Controller
{
    /**
     * Parāda telpu sarakstu ar filtriem un kopsavilkumu.
     */
    public function index(Request $request)
    {
        $this->requireManager();

        $filters = [
            'search' => trim((string) $request->query('search', $request->query('q', ''))),
            'building_id' => trim((string) $request->query('building_id', '')),
            'floor' => trim((string) $request->query('floor', '')),
            'floor_query' => trim((string) $request->query('floor_query', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
        ];

        $rooms = Room::query()
            ->select(['id', 'building_id', 'floor_number', 'room_number', 'room_name', 'department', 'user_id', 'notes'])
            ->with([
                'building:id,building_name',
                'user:id,full_name',
            ])
            ->withCount('devices')
            ->when($filters['building_id'] !== '' && ctype_digit($filters['building_id']), fn (Builder $query) => $query->where('building_id', (int) $filters['building_id']))
            ->when($filters['floor'] !== '' && is_numeric($filters['floor']), fn (Builder $query) => $query->where('floor_number', (int) $filters['floor']))
            ->when($filters['floor'] === '' && $filters['floor_query'] !== '', function (Builder $query) use ($filters) {
                if (is_numeric($filters['floor_query'])) {
                    $query->where('floor_number', (int) $filters['floor_query']);
                }
            })
            ->when($filters['user_id'] !== '' && ctype_digit($filters['user_id']), fn (Builder $query) => $query->where('user_id', (int) $filters['user_id']))
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->paginate(20)
            ->withQueryString();

        AuditTrail::viewed($this->user(), 'Room', null, 'Atvērts telpu saraksts.');

        if ($filters['building_id'] !== '' || $filters['floor'] !== '' || $filters['floor_query'] !== '' || $filters['user_id'] !== '') {
            AuditTrail::filter($this->user(), 'Room', [
                'ēka' => $filters['building_id'],
                'stāvs' => $filters['floor'] !== '' ? $filters['floor'] : $filters['floor_query'],
                'atbildīgais' => $filters['user_id'],
            ], 'Filtrēts telpu saraksts.');
        }

        return view('rooms.index', [
            'rooms' => $rooms,
            'roomSummary' => [
                'total' => Room::query()->count(),
            ],
            'filters' => $filters,
            'buildings' => Building::query()
                ->select(['id', 'building_name', 'city', 'address'])
                ->orderBy('building_name')
                ->get(),
            'floors' => Room::query()
                ->select('floor_number')
                ->distinct()
                ->orderBy('floor_number')
                ->pluck('floor_number')
                ->values(),
            'responsibleUsers' => User::query()
                ->active()
                ->select(['id', 'full_name', 'job_title', 'email'])
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    /**
     * Atrod telpu pēc nosaukuma vai numura aktīvajā filtrētajā sarakstā.
     */
    public function findByName(Request $request): JsonResponse
    {
        $this->requireManager();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($this->user(), 'Room', $search, 'Meklēta telpa pēc nosaukuma vai numura: '.$search);

        $filters = [
            'building_id' => trim((string) $request->query('building_id', '')),
            'floor' => trim((string) $request->query('floor', '')),
            'floor_query' => trim((string) $request->query('floor_query', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
        ];

        $rooms = Room::query()
            ->when($filters['building_id'] !== '' && ctype_digit($filters['building_id']), fn (Builder $query) => $query->where('building_id', (int) $filters['building_id']))
            ->when($filters['floor'] !== '' && is_numeric($filters['floor']), fn (Builder $query) => $query->where('floor_number', (int) $filters['floor']))
            ->when($filters['floor'] === '' && $filters['floor_query'] !== '', function (Builder $query) use ($filters) {
                if (is_numeric($filters['floor_query'])) {
                    $query->where('floor_number', (int) $filters['floor_query']);
                }
            })
            ->when($filters['user_id'] !== '' && ctype_digit($filters['user_id']), fn (Builder $query) => $query->where('user_id', (int) $filters['user_id']))
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'room_name']);

        $needle = mb_strtolower($search);
        $foundIndex = $rooms->search(function (Room $room) use ($needle) {
            $searchValue = mb_strtolower(trim(implode(' ', array_filter([
                $room->room_number,
                $room->room_name,
            ]))));

            return str_contains($searchValue, $needle);
        });

        if ($foundIndex === false) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        return response()->json([
            'found' => true,
            'page' => intdiv((int) $foundIndex, 20) + 1,
            'term' => $search,
            'highlight_id' => 'room-'.$rooms->values()[(int) $foundIndex]->id,
        ]);
    }

    /**
     * Parāda jaunas telpas izveides formu.
     */
    public function create()
    {
        $this->requireManager();
        AuditTrail::viewed($this->user(), 'Room', null, 'Atvērta telpas izveides forma.');

        return view('rooms.create', [
            'buildings' => Building::query()
                ->select(['id', 'building_name'])
                ->orderBy('building_name')
                ->get(),
            'users' => User::query()
                ->active()
                ->select(['id', 'full_name', 'job_title', 'email'])
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    /**
     * Saglabā jaunu telpu.
     */
    public function store(Request $request)
    {
        $this->requireManager();

        $room = Room::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $room);

        return redirect()->route('rooms.index')->with('success', 'Telpa veiksmīgi pievienota');
    }

    /**
     * Parāda telpas rediģēšanas formu.
     */
    public function edit(Room $room)
    {
        $this->requireManager();
        AuditTrail::viewed($this->user(), 'Room', (string) $room->id, 'Atvērta telpas labošanas forma: '.AuditTrail::labelFor($room));

        return view('rooms.edit', [
            'room' => $room,
            'buildings' => Building::query()
                ->select(['id', 'building_name'])
                ->orderBy('building_name')
                ->get(),
            'users' => User::query()
                ->active()
                ->select(['id', 'full_name', 'job_title', 'email'])
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    /**
     * Atjaunina telpas datus.
     */
    public function update(Request $request, Room $room)
    {
        $this->requireManager();

        $before = $room->only(['building_id', 'floor_number', 'room_number', 'room_name', 'user_id', 'department', 'notes']);
        $room->update($this->validatedData($request, $room));
        $after = $room->fresh()->only(array_keys($before));

        AuditTrail::updatedFromState(auth()->id(), $room, $before, $after);

        return redirect()->route('rooms.index')->with('success', 'Telpas dati atjaunināti');
    }

    /**
     * Dzēš telpu tikai tad, ja tai vairs nav piesaistītu ierīču.
     */
    public function destroy(Room $room)
    {
        $this->requireManager();

        $devicesCount = $room->devices()->count();

        if ($devicesCount > 0) {
            return redirect()
                ->route('rooms.index')
                ->with('error', 'Telpu nevar dzēst, jo tai piesaistītas ' . $devicesCount . ' ierīce' . ($devicesCount === 1 ? '' : 's') . '. Vispirms pārvieto vai atsien ierīces no šī ieraksta, tad mēģiniet vēlreiz.');
        }

        AuditTrail::deleted(auth()->id(), $room);
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Telpa dzēsta');
    }

    /**
     * Vecais show ceļš tiek novirzīts uz telpu sarakstu.
     */
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
            'building_id.required' => 'Izvēlies ēku, kurai telpa pieder.',
            'room_number.required' => 'Norādi telpas numuru.',
        ]);

        $data['user_id'] = $data['user_id'] ?: null;
        $data['room_name'] = $data['room_name'] ?: null;
        $data['department'] = $data['department'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        return $data;
    }
}
