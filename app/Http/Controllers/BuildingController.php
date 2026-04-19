<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ēku pārvaldības CRUD kontrolieris.
 */
class BuildingController extends Controller
{
    private const NOTES_DEFAULT = '';
    private const SORTABLE_COLUMNS = [
        'building_name' => 'building_name',
        'address' => 'address',
        'total_floors' => 'total_floors',
        'created_at' => 'created_at',
    ];

    /**
     * Parāda ēku sarakstu ar filtriem.
     */
    public function index(Request $request)
    {
        $this->requireManager();

        $filters = [
            'search' => trim((string) $request->query('search', $request->query('q', ''))),
            'total_floors' => trim((string) $request->query('total_floors', '')),
        ];
        $sorting = $this->resolveSorting($request);

        $buildingsQuery = Building::query()
            ->select(['id', 'building_name', 'address', 'city', 'total_floors', 'notes', 'created_at'])
            ->withCount(['rooms', 'devices'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];

                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('building_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($filters['total_floors'] !== '' && ctype_digit($filters['total_floors']), fn (Builder $query) => $query->where('total_floors', (int) $filters['total_floors']));

        $this->applySorting($buildingsQuery, $sorting);

        $buildings = $buildingsQuery
            ->paginate(20)
            ->withQueryString();

        AuditTrail::viewed($this->user(), 'Building', null, 'AtvÄ“rts Ä“ku saraksts.');

        if ($filters['search'] !== '' || $filters['total_floors'] !== '') {
            AuditTrail::filter($this->user(), 'Building', [
                'teksts' => $filters['search'],
                'stāvu skaits' => $filters['total_floors'],
            ], 'FiltrÄ“ts Ä“ku saraksts.');
        }

        if (($sorting['sort'] ?? 'building_name') !== 'building_name' || ($sorting['direction'] ?? 'asc') !== 'asc' || $request->has('sort')) {
            AuditTrail::sort(
                $this->user(),
                'Building',
                $this->sortOptions()[$sorting['sort']]['label'] ?? 'nosaukuma',
                $sorting['direction'] ?? 'asc',
                'Kārtots ēku saraksts pēc '.($this->sortOptions()[$sorting['sort']]['label'] ?? 'nosaukuma').' '.(($sorting['direction'] ?? 'asc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }

        return view('buildings.index', [
            'buildings' => $buildings,
            'filters' => $filters,
            'sorting' => $sorting,
            'sortOptions' => [
                'building_name' => ['label' => 'nosaukuma'],
                'address' => ['label' => 'adreses'],
                'total_floors' => ['label' => 'stāvu skaita'],
                'created_at' => ['label' => 'izveides datuma'],
            ],
            'selectedModalBuilding' => ctype_digit((string) $request->query('modal_building'))
                ? Building::query()->select(['id', 'building_name', 'address', 'city', 'total_floors', 'notes'])->find((int) $request->query('modal_building'))
                : null,
        ]);
    }

    /**
     * Atrod ēku pēc nosaukuma aktīvajā filtrētajā sarakstā.
     */
    public function findByName(Request $request): JsonResponse
    {
        $this->requireManager();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($this->user(), 'Building', $search, 'MeklÄ“ta Ä“ka pÄ“c nosaukuma vai adreses: '.$search);

        $filters = [
            'total_floors' => trim((string) $request->query('total_floors', '')),
        ];
        $sorting = $this->resolveSorting($request);

        $buildingsQuery = Building::query()
            ->when($filters['total_floors'] !== '' && ctype_digit($filters['total_floors']), fn (Builder $query) => $query->where('total_floors', (int) $filters['total_floors']))
            ->select(['id', 'building_name', 'address']);

        $this->applySorting($buildingsQuery, $sorting);

        $buildings = $buildingsQuery->get();

        $needle = mb_strtolower($search);
        $foundIndex = $buildings->search(function (Building $building) use ($needle) {
            $searchValue = mb_strtolower(trim(implode(' ', array_filter([
                $building->building_name,
                $building->address,
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
            'highlight_id' => 'building-'.$buildings->values()[(int) $foundIndex]->id,
        ]);
    }


    /**
     * Saglabā jaunu ēkas ierakstu.
     */
    public function store(Request $request)
    {
        $this->requireManager();

        $building = Building::create($this->validatedData($request));
        AuditTrail::created(auth()->id(), $building);

        return redirect()->route('buildings.index')->with('success', 'Ä’ka veiksmÄ«gi pievienota');
    }


    /**
     * Atjaunina Ä“kas datus.
     */
    public function update(Request $request, Building $building)
    {
        $this->requireManager();

        $before = $building->only(['building_name', 'address', 'city', 'total_floors', 'notes']);
        $building->update($this->validatedData($request, $building));
        $after = $building->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $building, $before, $after);

        return redirect()->route('buildings.index')->with('success', 'Ēkas dati atjaunināti');
    }

    /**
     * Dzēš ēku tikai tad, ja tai vairs nav piesaistītu telpu un ierīču.
     */
    public function destroy(Building $building)
    {
        $this->requireManager();

        $roomsCount = $building->rooms()->count();
        $devicesCount = $building->devices()->count();

        if ($roomsCount > 0 || $devicesCount > 0) {
            $parts = [];

            if ($roomsCount > 0) {
                $parts[] = "Ēka joprojām satur {$roomsCount} telpu" . ($roomsCount === 1 ? '' : 's');
            }

            if ($devicesCount > 0) {
                $parts[] = "tai piesaistÄ«tas {$devicesCount} ierÄ«ce" . ($devicesCount === 1 ? '' : 's');
            }

            return redirect()
                ->route('buildings.index')
                ->with('error', 'Ēku nevar dzēst, jo ' . implode(' un ', $parts) . '. Vispirms pārvieto vai dzēs piesaistītas telpas un ierīces, tad mēģiniet vēlreiz.');
        }

        AuditTrail::deleted(auth()->id(), $building);
        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Ä’ka dzÄ“sta');
    }


    private function validatedData(Request $request, ?Building $building = null): array
    {
        $data = $this->validateInput($request, [
            'building_name' => [
                'bail',
                'required',
                'string',
                'max:100',
                Rule::unique('buildings', 'building_name')->ignore($building?->id),
            ],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string', 'max:200'],
        ], [
            'building_name.required' => 'Norādi ēkas nosaukumu.',
            'building_name.unique' => 'Ēka ar šādu nosaukumu jau eksistē.',
        ]);

        $data['notes'] = $data['notes'] ?? self::NOTES_DEFAULT;

        return $data;
    }

    private function resolveSorting(Request $request): array
    {
        $sort = trim((string) $request->query('sort', 'building_name'));
        $direction = trim((string) $request->query('direction', 'asc'));

        if (! array_key_exists($sort, self::SORTABLE_COLUMNS)) {
            $sort = 'building_name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function sortOptions(): array
    {
        return [
            'building_name' => ['label' => 'nosaukuma'],
            'address' => ['label' => 'adreses'],
            'total_floors' => ['label' => 'stāvu skaita'],
            'created_at' => ['label' => 'izveides datuma'],
        ];
    }

    private function applySorting(Builder $query, array $sorting): void
    {
        $column = self::SORTABLE_COLUMNS[$sorting['sort']] ?? self::SORTABLE_COLUMNS['building_name'];
        $direction = $sorting['direction'] ?? 'asc';

        $query->orderBy($column, $direction);

        if ($column !== 'building_name') {
            $query->orderBy('building_name');
        }
    }

}
