<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Ko dara: Pārvalda ierīču tipu katalogu.
 *
 * Kā strādā: Nodrošina tipu sarakstu, meklēšanu, izveidi, rediģēšanu un dzēšanu ar piesaistīto ierīču skaita kontroli.
 *
 * Kad pielietojas: Kad administrators uztur ierīču klasifikatoru, ko izmanto ierīču formās un filtros.
 */
class DeviceTypeController extends Controller
{
    // Atļautās ierīču tipu saraksta kārtošanas kolonnas.
    private const SORTABLE_COLUMNS = ['type_name', 'devices_count'];

    /**
     * Ko dara: Parāda ierīču tipu sarakstu ar meklēšanu un kārtošanu.
     *
     * Kā strādā: Administratoram rādāmi visi tipi. Katram tipam tiek uzskaitīts piesaistīto ierīču skaits, kas palīdz novērtēt tā izmantojamību.
     *
     * Kad pielietojas: Izsaukšana: GET /device-types | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs atver sadaļu "Ierīču tipi", lai pārvaldītu tipu sarakstu.
     */
    public function index(Request $request)
    {
        $this->requireManager();

        $sorting = $this->normalizedSorting($request);
        $search = trim((string) $request->query('search', $request->query('q', '')));

        $types = DeviceType::query()
            ->withCount('devices')
            ->when($search !== '', fn ($query) => $query->where('type_name', 'like', "%{$search}%"))
            ->orderBy(
                $sorting['sort'] === 'devices_count' ? 'devices_count' : 'type_name',
                $sorting['direction']
            )
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        AuditTrail::viewed($this->user(), 'DeviceType', null, "Atv\u{0113}rts ier\u{012B}\u{010D}u tipu saraksts.");

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
            'filters' => ['search' => $search],
            'sortOptions' => $this->sortOptions(),
            'selectedModalType' => ctype_digit((string) $request->query('modal_device_type'))
                ? DeviceType::query()->select(['id', 'type_name'])->find((int) $request->query('modal_device_type'))
                : null,
        ]);
    }

    /**
     * Ko dara: Atrod ierīces tipu pēc nosaukuma aktīvajā filtrētajā sarakstā.
     *
     * Kā strādā: Izmantota JavaScript meklēšanas lodziņā, lai iezīmētu atbilstošo rindu tabulā. Atgriež lapu un ieraksta ID priekš ritināšanas.
     *
     * Kad pielietojas: Izsaukšana: GET /device-types/find-by-name | Pieejams: administrators, IT vadītājs. Scenārijs: JavaScript izsauc AJAX pieprasījumu, kad vadītājs raksta meklēšanas lodziņā.
     */
    public function findByName(Request $request): JsonResponse
    {
        $this->requireManager();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($this->user(), 'DeviceType', $search, 'Meklēts ierīces tips pēc nosaukuma: '.$search);

        $sorting = $this->normalizedSorting($request);
        // Sarakstu kārtojam tieši tāpat kā tabulā, jo meklēšanā atrastais indekss
        // vēlāk tiek pārvērsts lapas numurā un rindas highlight identifikatorā.
        $types = DeviceType::query()
            ->withCount('devices')
            ->orderBy(
                $sorting['sort'] === 'devices_count' ? 'devices_count' : 'type_name',
                $sorting['direction']
            )
            ->orderBy('id')
            ->get(['id', 'type_name']);

        $needle = mb_strtolower($search);
        $foundIndex = $types->search(function (DeviceType $type) use ($needle) {
            return str_contains(mb_strtolower(trim((string) $type->type_name)), $needle);
        });

        if ($foundIndex === false) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        return response()->json([
            'found' => true,
            'page' => intdiv((int) $foundIndex, 20) + 1,
            'term' => $search,
            'highlight_id' => 'device-type-'.$types->values()[(int) $foundIndex]->id,
        ]);
    }

    /**
     * Ko dara: Saglabā jaunu ierīces tipu datubāzē.
     *
     * Kā strādā: Validē nosaukumu, pārbauda unikalitāti un reģistrē izveides notikumu auditā.
     *
     * Kad pielietojas: Izsaukšana: POST /device-types | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs aizpilda un iesniedz tipa pievienošanas formu.
     */
    public function store(Request $request)
    {
        $this->requireManager();

        $data = $this->validateTypeName($request);

        $deviceType = DeviceType::create($data);
        AuditTrail::created(auth()->id(), $deviceType);

        return redirect()->route('device-types.index')->with('success', "Ier\u{012B}ces tips veiksm\u{012B}gi pievienots.");
    }

    /**
     * Ko dara: Atjaunina esošā ierīces tipa nosaukumu.
     *
     * Kā strādā: Pirms saglabāšanas salīdzina "pirms" un "pēc" stāvokļus un pieraksta izmaiņas audita žurnālā.
     *
     * Kad pielietojas: Izsaukšana: PUT/PATCH /device-types/{deviceType} | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs rediģē ierīces tipa nosaukumu un saglabā izmaiņas.
     */
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

    /**
     * Ko dara: Dzēš ierīces tipu, ja tam nav piesaistītu ierīču.
     *
     * Kā strādā: Ja tipam vēl ir ierīces, dzēšana tiek noraidīta ar informatīvu kļūdas paziņojumu, lai netiktu sabojāta datu integritāte.
     *
     * Kad pielietojas: Izsaukšana: DELETE /device-types/{deviceType} | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs nospiež dzēšanas pogu tipa rindā un apstiprina darbību.
     */
    public function destroy(DeviceType $deviceType)
    {
        $this->requireManager();

        // Tipu nedrīkst dzēst, ja tas vēl tiek lietots kaut vienā ierīcē,
        // citādi ierīces paliktu bez korekta klasifikatora.
        if ($deviceType->devices()->exists()) {
            return redirect()
                ->route('device-types.index')
                ->with('error', "Ier\u{012B}ces tipu nevar dz\u{0113}st, kam\u{0113}r tam v\u{0113}l ir piesaist\u{012B}tas ier\u{012B}ces.");
        }

        AuditTrail::deleted(auth()->id(), $deviceType);
        $deviceType->delete();

        return redirect()->route('device-types.index')->with('success', "Ier\u{012B}ces tips dz\u{0113}sts.");
    }

    /**
     * Ko dara: Tipa detalizētais skats — pašlaik novirza uz sarakstu.
     *
     * Kā strādā: Šī metode ir saglabāta saderībai ar Laravel resursu maršrutiem. Ja nākotnē tiks veidota atsevišķa tipa lapas, tā tiks implementēta šeit.
     *
     * Kad pielietojas: Kad Laravel resource maršruts izsauc `show`, bet projektā atsevišķa ierīces tipa detaļu lapa nav paredzēta.
     */
    public function show(DeviceType $deviceType)
    {
        $this->requireManager();

        return redirect()->route('device-types.index');
    }

    /**
     * Ko dara: Normalizē kārtošanas parametrus no URL vaicājuma.
     *
     * Kā strādā: Pārbauda, vai pieprasītā kolonna ir atļauto kārtojamo kolonnu sarakstā. Noklusēts kārtojums ir pēc tipa nosaukuma augošā secībā.
     *
     * Kad pielietojas: Ierīču tipu sarakstā un ātrās meklēšanas darbībās, kur jāievēro vienāds kārtojums.
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
     * Ko dara: Atgriež kārtojamo lauku nosaukumu karti Blade skatam un audita paziņojumiem.
     *
     * Kā strādā: Definē, kā tabulas kolonnu tehniskie nosaukumi jāparāda lietotājam un audita tekstos.
     *
     * Kad pielietojas: Ierīču tipu sarakstā, kad jāparāda aktīvā kārtojuma nosaukums vai jāveido audita apraksts.
     */
    private function sortOptions(): array
    {
        return [
            'type_name' => ['label' => 'tipa nosaukuma'],
            'devices_count' => ['label' => "piesaist\u{012B}to ier\u{012B}\u{010D}u skaita"],
        ];
    }

    /**
     * Ko dara: Validē un normalizē ierīces tipa nosaukumu pirms saglabāšanas.
     *
     * Kā strādā: Pārbauda obligāto aizpildījumu, garuma ierobežojumu un unikalitāti (reģistrjutīgi). Metode pieņem esošu tipu, lai izslēgtu to no unikalitātes pārbaudes.
     *
     * Kad pielietojas: Ierīces tipa izveides un labošanas formās pirms datu saglabāšanas.
     */
    private function validateTypeName(Request $request, ?DeviceType $deviceType = null): array
    {
        $data = $this->validateInput($request, [
            'type_name' => ['required', 'string', 'min:2', 'max:30', 'regex:/^[\pL\pN][\pL\pN\s\-\/().]+$/u'],
        ], [
            'type_name.required' => "Ievadi ier\u{012B}ces tipa nosaukumu.",
            'type_name.min' => 'Ierīces tipa nosaukumam jābūt vismaz 2 rakstzīmes garam.',
            'type_name.max' => 'Ierīces tipa nosaukums nedrīkst būt garāks par 30 rakstzīmēm.',
            'type_name.regex' => 'Ierīces tipa nosaukumā izmanto burtus, ciparus, atstarpes un vienkāršas zīmes: -, /, (, ).',
        ]);

        // Pēc validācijas apgriežam atstarpes, lai "Monitors" un " Monitors "
        // sistēmā netiktu uztverti kā dažādi nosaukumi.
        $data['type_name'] = trim($data['type_name']);

        if ($data['type_name'] === '') {
            throw ValidationException::withMessages([
                'type_name' => ["Ievadi ier\u{012B}ces tipa nosaukumu."],
            ]);
        }

        // Unikalitāti pārbaudām reģistrnejutīgi, jo lietotājam "Printeris" un
        // "printeris" ir tas pats tips, kaut arī SQL kolācija to ne vienmēr garantē.
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

    }
