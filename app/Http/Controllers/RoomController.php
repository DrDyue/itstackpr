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
 * Telpu pÄrvaldÄ«bas CRUD kontrolieris.
 */
class RoomController extends Controller
{
    /**
     * ParÄda telpu sarakstu ar filtriem un kopsavilkumu.
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

        AuditTrail::viewed($this->user(), 'Room', null, 'AtvÄ“rts telpu saraksts.');

        if ($filters['building_id'] !== '' || $filters['floor'] !== '' || $filters['floor_query'] !== '' || $filters['user_id'] !== '') {
            AuditTrail::filter($this->user(), 'Room', [
                'Ä“ka' => $filters['building_id'],
                'stÄvs' => $filters['floor'] !== '' ? $filters['floor'] : $filters['floor_query'],
                'atbildÄ«gais' => $filters['user_id'],
            ], 'FiltrÄ“ts telpu saraksts.');
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
            'selectedModalRoom' => ctype_digit((string) $request->query('modal_room'))
                ? Room::query()->select(['id', 'building_id', 'floor_number', 'room_number', 'room_name', 'department', 'user_id', 'notes'])->find((int) $request->query('modal_room'))
                : null,
        ]);
    }

    /**
     * Atrod telpu pÄ“c nosaukuma vai numura aktÄ«vajÄ filtrÄ“tajÄ sarakstÄ.
     */
    public function findByName(Request $request): JsonResponse
    {
        $this->requireManager();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($this->user(), 'Room', $search, 'MeklÄ“ta telpa pÄ“c nosaukuma vai numura: '.$search);

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
     * SaglabÄ jaunu telpu.
     */
    public function store(Request $request)
    {
        $this->requireManager();

        $room = Room::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $room);

        return redirect()->route('rooms.index')->with('success', 'Telpa veiksmÄ«gi pievienota');
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

        return redirect()->route('rooms.index')->with('success', 'Telpas dati atjauninÄti');
    }

    /**
     * DzÄ“Å telpu tikai tad, ja tai vairs nav piesaistÄ«tu ierÄ«Ä¨u.
     */
    public function destroy(Room $room)
    {
        $this->requireManager();

        $devicesCount = $room->devices()->count();

        if ($devicesCount > 0) {
            return redirect()
                ->route('rooms.index')
                ->with('error', 'Telpu nevar dzÄ“st, jo tai piesaistÄ«tas ' . $devicesCount . ' ierÄ«ce' . ($devicesCount === 1 ? '' : 's') . '. Vispirms pÄrvieto vai atsien ierÄ«ces no ÅÄ« ieraksta, tad mÄ“Ä£iniet vÄ“lreiz.');
        }

        AuditTrail::deleted(auth()->id(), $room);
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Telpa dzÄ“sta');
    }


    private function validatedData(Request $request, ?Room $room = null): array
    {
        $data = $this->validateInput($request, [
            'building_id' => ['bail', 'required', 'exists:buildings,id'],
            'floor_number' => ['bail', 'required', 'integer', 'min:-10', 'max:200'],
            'room_number' => [
                'bail',
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
            'building_id.required' => 'IzvÄ“lies Ä“ku, kurai telpa pieder.',
            'room_number.required' => 'NorÄdi telpas numuru.',
            'room_number.unique' => 'Å Äds telpas numurs ÅajÄ Ä“kÄ jau eksistÄ“.',
        ]);

        $data['user_id'] = $data['user_id'] ?: null;
        $data['room_name'] = $data['room_name'] ?: null;
        $data['department'] = $data['department'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        return $data;
    }

}
