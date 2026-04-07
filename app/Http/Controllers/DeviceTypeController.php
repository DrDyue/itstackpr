<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        AuditTrail::viewed($this->user(), 'DeviceType', null, 'Atvērts ierīču tipu saraksts.');

        if (($sorting['sort'] ?? 'type_name') !== 'type_name' || ($sorting['direction'] ?? 'asc') !== 'asc' || $request->has('sort')) {
            AuditTrail::sort(
                $this->user(),
                'DeviceType',
                $this->sortOptions()[$sorting['sort']]['label'] ?? 'tipa nosaukuma',
                $sorting['direction'] ?? 'asc',
                'Kārtots ierīču tipu saraksts pēc '.($this->sortOptions()[$sorting['sort']]['label'] ?? 'tipa nosaukuma').' '.(($sorting['direction'] ?? 'asc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }

        return view('device_types.index', [
            'types' => $types,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'deviceTypeModal' => $this->deviceTypeModalState($request, $types),
        ]);
    }

    public function create(Request $request)
    {
        $this->requireManager();
        AuditTrail::viewed($this->user(), 'DeviceType', null, 'Atvērts ierīces tipa pievienošanas modālis.');

        return redirect()->to(route('device-types.index').'?'.http_build_query([
            'modal' => 'create',
        ]));
    }

    public function store(Request $request)
    {
        $this->requireManager();

        $data = $this->validateTypeName($request);

        $deviceType = DeviceType::create($data);
        AuditTrail::created(auth()->id(), $deviceType);

        return redirect()->route('device-types.index')->with('success', 'Ierīces tips veiksmīgi pievienots.');
    }

    public function edit(DeviceType $deviceType)
    {
        $this->requireManager();
        AuditTrail::viewed($this->user(), 'DeviceType', (string) $deviceType->id, 'Atvērts ierīces tipa labošanas modālis: '.AuditTrail::labelFor($deviceType));

        return redirect()->to(route('device-types.index').'?'.http_build_query([
            'modal' => 'edit',
            'device_type' => $deviceType->id,
        ]));
    }

    public function update(Request $request, DeviceType $deviceType)
    {
        $this->requireManager();

        $data = $this->validateTypeName($request, $deviceType);

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

    private function validateTypeName(Request $request, ?DeviceType $deviceType = null): array
    {
        $data = $request->validate([
            'type_name' => ['required', 'string', 'max:30'],
        ], [
            'type_name.required' => 'Ievadi ierīces tipa nosaukumu.',
            'type_name.max' => 'Ierīces tipa nosaukums nedrīkst būt garāks par 30 rakstzīmēm.',
        ]);

        $data['type_name'] = trim($data['type_name']);

        if ($data['type_name'] === '') {
            throw ValidationException::withMessages([
                'type_name' => ['Ievadi ierīces tipa nosaukumu.'],
            ]);
        }

        $exists = DeviceType::query()
            ->when($deviceType, fn ($query) => $query->whereKeyNot($deviceType->id))
            ->whereRaw('LOWER(type_name) = ?', [mb_strtolower($data['type_name'])])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'type_name' => ['Ierīces tips ar šādu nosaukumu jau eksistē.'],
            ]);
        }

        return $data;
    }

    private function deviceTypeModalState(Request $request, $types): array
    {
        $mode = (string) (old('_device_type_modal_mode') ?? session('device_type_modal', ''));
        $id = (string) (old('_device_type_modal_id') ?? session('device_type_modal_id', ''));

        if (! in_array($mode, ['create', 'edit'], true)) {
            $mode = '';
        }

        $selectedType = null;

        if ($mode === 'edit' && $id !== '') {
            $normalizedId = (int) $id;
            $selectedType = $types->getCollection()->firstWhere('id', $normalizedId)
                ?? DeviceType::query()->select(['id', 'type_name'])->find($normalizedId);

            if (! $selectedType) {
                $mode = '';
                $id = '';
            }
        }

        return [
            'mode' => $mode,
            'id' => $id,
            'type' => $selectedType,
        ];
    }
}
