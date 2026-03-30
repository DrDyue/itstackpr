<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

/**
 * Ierīču tipu vārdnīcas pārvaldība.
 *
 * Šis kontrolieris apkalpo vienkāršotu tipu struktūru, kur katram tipam
 * paliek tikai nosaukums un saistīto ierīču skaits.
 */
class DeviceTypeController extends Controller
{
    private const SORTABLE_COLUMNS = ['type_name', 'devices_count'];

    public function index(Request $request)
    {
        $this->requireManager();

        $sorting = $this->normalizedSorting($request);

        $types = DeviceType::query()
            ->withCount('devices')
            ->orderBy(
                $sorting['sort'] === 'devices_count' ? 'devices_count' : 'type_name',
                $sorting['direction']
            )
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('device_types.index', [
            'types' => $types,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
        ]);
    }

    public function create()
    {
        $this->requireManager();

        return view('device_types.create');
    }

    public function store(Request $request)
    {
        $this->requireManager();

        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30', 'unique:device_types,type_name'],
        ]);

        $deviceType = DeviceType::create($data);
        AuditTrail::created(auth()->id(), $deviceType);

        return redirect()->route('device-types.index')->with('success', 'Ierīces tips veiksmīgi pievienots.');
    }

    public function edit(DeviceType $deviceType)
    {
        $this->requireManager();

        return view('device_types.edit', ['type' => $deviceType]);
    }

    public function update(Request $request, DeviceType $deviceType)
    {
        $this->requireManager();

        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30', 'unique:device_types,type_name,' . $deviceType->id],
        ]);

        $before = $deviceType->only(['type_name']);
        $deviceType->update($data);
        $after = $deviceType->fresh()->only(['type_name']);
        AuditTrail::updatedFromState(auth()->id(), $deviceType, $before, $after);

        return redirect()->route('device-types.index')->with('success', 'Ierīces tips atjaunināts.');
    }

    public function destroy(DeviceType $deviceType)
    {
        $this->requireManager();

        if ($deviceType->devices()->exists()) {
            return redirect()
                ->route('device-types.index')
                ->with('error', 'Ierīces tipu nevar dzēst, kamēr tam vēl ir piesaistītas ierīces.');
        }

        AuditTrail::deleted(auth()->id(), $deviceType);
        $deviceType->delete();

        return redirect()->route('device-types.index')->with('success', 'Ierīces tips dzēsts.');
    }

    public function show(DeviceType $deviceType)
    {
        $this->requireManager();

        return redirect()->route('device-types.index');
    }

    /**
     * Normalizē drošu kārtošanas konfigurāciju.
     */
    private function normalizedSorting(Request $request): array
    {
        $sort = trim((string) $request->query('sort', 'type_name'));
        $direction = trim((string) $request->query('direction', 'asc'));

        if (! in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'type_name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'devices_count' ? 'desc' : 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $this->sortOptions()[$sort]['label'] ?? 'tipa nosaukuma',
        ];
    }

    /**
     * Lietotāja paziņojumiem un kolonnu galvām izmantojamās kārtošanas etiķetes.
     */
    private function sortOptions(): array
    {
        return [
            'type_name' => ['label' => 'tipa nosaukuma'],
            'devices_count' => ['label' => 'piesaistīto ierīču skaita'],
        ];
    }
}
