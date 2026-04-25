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
 * Telpu pārvaldības CRUD kontrolieris. *
 * Apvieno telpu sarakstu ar filtrēšanu pa ēkām, stāviem un atbildīgajiem
 * lietotājiem, kā arī pilno CRUD plūsmu telpu izveide, rediģēšanai un dzēšanai. */
class RoomController extends Controller
{
    /**
     * Parāda telpu sarakstu ar filtriem, kopsavilkumu un meklēšanas opcijām.
     *
     * Filtri ietver: ēka, stāvs, atbildīgais lietotājs un brīvais teksts.
     * Telpu saraksts tiek kārtots pēc ēkas, stāva un telpas numura.
     *
     * Izsaukšana: GET /rooms | Pieejams: administrators, IT vadītājs.
     * Scenārijs: Vadītājs atver "Telpas" sadaļu, lai pārvaldītu telpu konfigurāciju.
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

        AuditTrail::viewed($this->user(), 'Room', null, "Atv\u{0113}rts telpu saraksts.");

        if ($filters['building_id'] !== '' || $filters['floor'] !== '' || $filters['floor_query'] !== '' || $filters['user_id'] !== '') {
            AuditTrail::filter($this->user(), 'Room', [
                "\u{0113}ka" => $filters['building_id'],
                'stāvs' => $filters['floor'] !== '' ? $filters['floor'] : $filters['floor_query'],
                "atbild\u{012B}gais" => $filters['user_id'],
            ], "Filtr\u{0113}ts telpu saraksts.");
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
     * Atrod telpu pēc nosaukuma vai numura aktīvajā filtrētajā sarakstā.
     *
     * Meklēšana ņem vērā aktīvos filtrus, tāpēc rezultāts ir precīzs
     * attiecībā pret pašreiz rādīto telpu kopu. Atgriež lapas numuru un elementu ID.
     *
     * Izsaukšana: GET /rooms/find-by-name | Pieejams: administrators, IT vadītājs.
     * Scenārijs: JavaScript izsauc šo metodi, kad lietotājs raksta mājēšanas lodziņā.
     */
    public function findByName(Request $request): JsonResponse
    {
        $this->requireManager();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($this->user(), 'Room', $search, "Mekl\u{0113}ta telpa p\u{0113}c nosaukuma vai numura: ".$search);

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
     * Saglabā jaunu telpu ar validāciju un audita reģistrāciju.
     *
     * Validē ēkas ID, stāva numuru un unikālos telpas numura datos ēkas kontekstā.
     * Pēc sekmīgas saglabāšanas reģistrē audita žurnālā.
     *
     * Izsaukšana: POST /rooms | Pieejams: administrators, IT vadītājs.
     * Scenārijs: Vadītājs aizpilda "Jauna telpa" formu un klikšķina "Pievienot".
     */
    public function store(Request $request)
    {
        $this->requireManager();

        $room = Room::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $room);

        return redirect()->route('rooms.index')->with('success', "Telpa veiksm\u{012B}gi pievienota");
    }


    /**
     * Atjaunina telpas datus ar pūriņu izsekošanu audita žurnālā.
     *
     * Atjaunina telpas pamatdatus (nosaukums, stāvs, numurs, atbildīgais, nodaļa).
     * Pēc atjauninājuma reģistrē izmaiņas audita žurnālā.
     *
     * Izsaukšana: PUT /rooms/{id} | Pieejams: administrators, IT vadītājs.
     * Scenārijs: Vadītājs atvēr telpas detaļas modāli un rediģē tā laikus.
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
     * Dzēš telpu tikai tad, ja tai nav piesaistītu ierīču.
     *
     * Telpas dzēšana ir bloķēta, ja tai vēl ir ierīces. Dzēšanu var veikt
     * tikai vadītājs, un tā tiek reģistrēta audita žurnālā.
     *
     * Izsaukšana: DELETE /rooms/{id} | Pieejams: administrators, IT vadītājs.
     * Scenārijs: Vadītājs klikšķina uz dzēšanas pogu telpas modālī.
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

        return redirect()->route('rooms.index')->with('success', "Telpa dz\u{0113}sta");
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
            'building_id.required' => "Izv\u{0113}lies \u{0113}ku, kurai telpa pieder.",
            'room_number.required' => 'Norādi telpas numuru.',
            'room_number.unique' => 'Šāds telpas numurs šajā ēkā jau eksistē.',
        ]);

        $data['user_id'] = $data['user_id'] ?: null;
        $data['room_name'] = $data['room_name'] ?: null;
        $data['department'] = $data['department'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        return $data;
    }

}
