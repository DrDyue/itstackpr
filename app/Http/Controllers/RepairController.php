<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use App\Support\UserNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Ko dara: Pārvalda faktiskos remonta ierakstus.
 *
 * Kā strādā: Nodrošina remontu sarakstu, filtrēšanu, izveidi, rediģēšanu, dzēšanu, statusu pārejas un ierīces statusa sinhronizāciju.
 *
 * Kad pielietojas: Kad administrators vai IT vadītājs apstrādā jau izveidotu remonta darbu.
 */
class RepairController extends Controller
{
    // Remonta dzīves ciklā atļautie statusi.
    private const STATUSES = ['waiting', 'in-progress', 'completed', 'cancelled'];
    // Remonta tipi, ko var izvēlēties remonta formā.
    private const TYPES = ['internal', 'external'];
    // Remonta prioritātes, ko izmanto filtrēšanai, kārtošanai un formas izvēlnēm.
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    // Atļautās remontu saraksta kārtošanas kolonnas.
    private const SORTABLE_COLUMNS = ['code', 'name', 'assigned', 'location', 'status', 'priority', 'repair_type', 'cost', 'start_date', 'end_date'];

    /**
     * Ko dara: Parāda remontu sarakstu vienotā tabulā ar filtriem, kārtošanu un statusu kopsavilkumu.
     *
     * Kā strādā: Administrators redz visus remontus ar filtrēšanu pēc statusa, prioritātes, tipa un ierīces. Parasts lietotājs redz tikai remontus, ko viņš ir piesaistījis vai par kuriem ziņojis.
     *
     * Kad pielietojas: Izsaukšana: GET /repairs | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs navigē uz "Remonti" sadaļu sānjoslā vai atgriežas no remonta detaļu skata.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        // Ātrā meklēšana atkārto saraksta lomu nosacījumus, filtrus un kārtošanu,
        // lai nevarētu atrast remontu ārpus pašreiz redzamās datu kopas.
        $canManageRepairs = $user->canManageRequests();
        $filters = $this->normalizedIndexFilters($request, $canManageRepairs);
        $sorting = $this->normalizedSorting($request);

        if (! $this->featureTableExists('repairs')) {
            return view('repairs.index', [
                'repairs' => collect(),
                'repairSummary' => [
                    'total' => 0,
                    'waiting' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                ],
                'prioritySummary' => [
                    'low' => 0,
                    'medium' => 0,
                    'high' => 0,
                    'critical' => 0,
                ],
                'typeSummary' => [
                    'internal' => 0,
                    'external' => 0,
                    'total' => 0,
                ],
                'filters' => $filters,
                'statuses' => ['waiting', 'in-progress', 'completed', 'cancelled'],
                'priorities' => self::PRIORITIES,
                'statusLabels' => $this->statusLabels(),
                'priorityLabels' => $this->priorityLabels(),
                'typeLabels' => $this->typeLabels(),
                'canManageRepairs' => $canManageRepairs,
                'sorting' => $sorting,
                'sortOptions' => $this->sortOptions(),
                'deviceOptions' => collect(),
                'createDeviceOptions' => collect(),
                'requesterOptions' => collect(),
                'featureMessage' => 'Tabula repairs šobrīd nav pieejama.',
            ]);
        }

        // Bāzes vaicājums satur tikai tos remontus, ko konkrētais lietotājs drīkst redzēt.
        // Ja administrators ieslēdz "mani remonti", tas papildus sašaurinās uz viņa pieņemtajiem remontiem.
        $baseQuery = $this->visibleRepairsQuery($user)
            ->when($filters['mine'] && $canManageRepairs, fn (Builder $query) => $query->where('repairs.accepted_by', $user->id));

        // Filtru dropdown opcijas tiek iegūtas no tās pašas redzamās datu kopas,
        // izlaižot konkrēto filtru, lai lietotājs varētu pārslēgt izvēli bez opciju pazušanas.
        $deviceOptions = $this->repairDeviceOptions(
            clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id'])
        );

        $requesterOptions = $this->repairRequesterOptions(
            clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id'])
        );
        $createDeviceOptions = $canManageRepairs
            ? $this->deviceOptions($this->availableDevicesForCreate()->get())
            : collect();

        $repairsQuery = (clone $baseQuery)
            ->with([
                'device.assignedTo',
                'device.building',
                'device.room',
                'device.type',
                'reporter',
                'acceptedBy',
                'request.responsibleUser',
                'request.reviewedBy',
            ])
            ->select('repairs.*');

        $this->applyIndexFilters($repairsQuery, $filters);
        $this->applySorting($repairsQuery, $sorting);

        $repairs = $repairsQuery->get();
        // Kopsavilkuma kartītes izmanto bāzes vaicājumu, nevis jau filtrēto sarakstu,
        // lai rādītu kopējo remontu stāvokli lietotājam pieejamajā apjomā.
        $repairSummary = $this->repairSummary($baseQuery);
        $prioritySummary = $this->prioritySummary($baseQuery);
        $typeSummary = $this->typeSummary($baseQuery);

        AuditTrail::viewed($user, 'Repair', null, 'Atvērts remontu saraksts.');
        $this->auditRepairListInteractions($request, $user, $filters, $sorting);

        return view('repairs.index', [
            'repairs' => $repairs,
            'repairSummary' => $repairSummary,
            'prioritySummary' => $prioritySummary,
            'typeSummary' => $typeSummary,
            'filters' => $filters,
            'statuses' => ['waiting', 'in-progress', 'completed', 'cancelled'],
            'priorities' => self::PRIORITIES,
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
            'canManageRepairs' => $canManageRepairs,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'deviceOptions' => $deviceOptions,
            'createDeviceOptions' => $createDeviceOptions,
            'requesterOptions' => $requesterOptions,
            'selectedModalRepair' => ctype_digit((string) $request->query('modal_repair'))
                ? Repair::query()
                    ->with([
                        'device.assignedTo',
                        'device.building',
                        'device.room',
                        'device.type',
                        'reporter',
                        'acceptedBy',
                        'request.responsibleUser',
                        'request.reviewedBy',
                    ])
                    ->find((int) $request->query('modal_repair'))
                : null,
        ]);
    }

    /**
     * Ko dara: Atrod remonta ierakstu pēc saistītās ierīces koda un atgriež lapu, kur tas atrodas.
     *
     * Kā strādā: Meklēšana ņem vērā aktīvos filtrus un kārtošanu, tāpēc rezultāts ir precīzs attiecībā pret pašreiz rādīto datu kopu. Rezultāts satur lapas numuru un elementa ID.
     *
     * Kad pielietojas: Izsaukšana: GET /repairs/find-by-code | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: JavaScript izsauc šo metodi, kad lietotājs raksta ierīces kodu ātrajā meklēšanā.
     */
    public function findByCode(Request $request): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'Repair', $code, 'Meklēts remonta ieraksts pēc ierīces koda: ' . $code);

        $canManageRepairs = $user->canManageRequests();
        $filters = $this->normalizedIndexFilters($request, $canManageRepairs);
        $sorting = $this->normalizedSorting($request);

        $baseQuery = $this->visibleRepairsQuery($user)
            ->when($filters['mine'] && $canManageRepairs, fn (Builder $query) => $query->where('repairs.accepted_by', $user->id));

        // Pievienojam ierīces kodu kā atsevišķu atlasītu lauku, jo meklēšana
        // notiek pēc saistītās ierīces, nevis remonta ieraksta kolonnas.
        $searchQuery = (clone $baseQuery)
            ->leftJoin('devices as repair_search_device', 'repair_search_device.id', '=', 'repairs.device_id')
            ->select([
                'repairs.id',
                'repair_search_device.code as device_code',
            ]);

        $this->applyIndexFilters($searchQuery, $filters);
        $this->applySorting($searchQuery, $sorting);

        // Precīzs normalizēta koda salīdzinājums novērš daļējas sakritības, kas
        // varētu izcelt nepareizu remonta rindu.
        $repair = $searchQuery->first(function ($repair) use ($code) {
            return mb_strtolower(trim((string) ($repair->device_code ?? ''))) === mb_strtolower($code);
        });

        if ($repair) {
            return response()->json([
                'found' => true,
                'page' => 1,
                'repair_id' => $repair->id,
                'term' => $code,
                'highlight_id' => 'repair-'.$repair->id,
            ]);
        }

        return response()->json(['found' => false, 'page' => 1]);
    }


    /**
     * Ko dara: Saglabā jaunu remonta ierakstu ar automātisku ierīces statusa sinhronizāciju.
     *
     * Kā strādā: Remonta ieraksts tiek izveidots ar statsu "waiting" (gaidošs) un pārvaldnieka ID. Pēc saglabāšanas tiek pārbaudīts un atjaunināts saistītās ierīces statuss.
     *
     * Kad pielietojas: Izsaukšana: POST /repairs | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs aizpilda remonta formu un klikšķina "Pievienot remontu".
     */
    public function store(Request $request)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Remontus šobrīd nevar saglabāt, jo tabula repairs nav pieejama.');
        }

        $validated = $this->validatedData($request);
        $validated['accepted_by'] = $manager->id;

        $repair = $this->createRepairRecord($validated);
        $repair->load(['device', 'executor', 'acceptedBy', 'request.responsibleUser', 'request.reviewedBy']);

        $this->syncDeviceStatus($repair);
        AuditTrail::created($manager->id, $repair);

        return redirect()->route('repairs.index')->with('success', 'Remonts veiksmīgi pievienots');
    }


    /**
     * Ko dara: Atjaunina esošu remonta ierakstu ar pūriņu izsekošanu audita žurnālā.
     *
     * Kā strādā: Atjaunina remonta detaļas (apraksts, tips, prioritāte, izmaksas, piegādātāja info). Pēc atjauninājuma tiek pārbaudīts ierīces statuss un reģistrētas izmaiņas audita žurnālā.
     *
     * Kad pielietojas: Izsaukšana: PUT /repairs/{id} | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs atvēr remonta detaļas modāli un rediģē tā laikus.
     */
    public function update(Request $request, Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Tabula repairs šobrīd nav pieejama.');
        }

        $before = $repair->only([
            'device_id',
            'issue_reported_by',
            'accepted_by',
            'description',
            'status',
            'repair_type',
            'priority',
            'start_date',
            'end_date',
            'cost',
            'vendor_name',
            'vendor_contact',
            'invoice_number',
            'request_id',
        ]);

        $repair->update($this->validatedData($request, $repair));
        $repair->load(['device', 'executor', 'acceptedBy', 'request.responsibleUser', 'request.reviewedBy']);
        $this->syncDeviceStatus($repair, $before['status'] ?? null);

        $after = $repair->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $repair, $before, $after);

        return redirect()->route('repairs.index')->with('success', 'Remonts atjaunināts');
    }

    /**
     * Ko dara: Dzēš remonta ierakstu un atjauno ierīces iepriekšējo statusu.
     *
     * Kā strādā: Dzēšanu var veikt tikai vadītājs. Pēc dzēšanas tiek reģistrēts audita žurnālā un atjaunināts saistītās ierīces statuss (parasti atgriežas uz "Active").
     *
     * Kad pielietojas: Izsaukšana: DELETE /repairs/{id} | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs klikšķina uz dzēšanas pogu remonta detaļu modālī.
     */
    public function destroy(Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Tabula repairs šobrīd nav pieejama.');
        }

        $previousStatus = $repair->status;
        AuditTrail::deleted(auth()->id(), $repair);
        $repair->delete();
        $this->restoreDeviceAfterRepairRemoval($repair->device_id, $previousStatus, null);

        return redirect()->route('repairs.index')->with('success', 'Remonts dzēsts');
    }

    /**
     * Ko dara: Pārveido remontu starp atļautajiem statusiem (waiting → in-progress → completed → cancelled).
     *
     * Kā strādā: Validē mērķa statusu un pārbauda obligātos laukus (piemēram, ārējam remontam nepieciešami piegādātāja dati). Atjaunina remontu un sinhronizē ierīces statusu.
     *
     * Kad pielietojas: Izsaukšana: POST /repairs/{id}/transition | Pieejams: administrators, IT vadītājs. Scenārijs: Vadītājs klikšķina uz statusa mainīšanas pogu remonta kartītē vai detaļu skata.
     */
    public function transition(Request $request, Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return back()->with('error', 'Tabula repairs šobrīd nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'target_status' => ['required', Rule::in(self::STATUSES)],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'target_status.required' => 'Izvēlies jauno remonta statusu.',
        ]);

        $draft = $this->validatedTransitionDraft($request, $repair, $validated['target_status']);

        if (! in_array($validated['target_status'], $this->allowedTransitionTargets($repair->status), true)) {
            return back()->with('error', 'Šādu remonta statusa maiņu veikt nevar.');
        }

        if ($validated['target_status'] === 'completed' && ! filled($draft['description'])) {
            return back()->with('error', 'Lai pabeigtu remontu, jāaizpilda apraksts.');
        }

        if (
            $validated['target_status'] === 'completed'
            && $draft['repair_type'] === 'external'
            && (! filled($draft['vendor_name']) || ! filled($draft['vendor_contact']) || ! filled($draft['invoice_number']))
        ) {
            return back()->with('error', 'Lai pabeigtu ārējo remontu, jānorāda pakalpojuma sniedzējs, kontaktinformācija un rēķina numurs.');
        }


        $before = $repair->only([
            'status',
            'start_date',
            'end_date',
            'cost',
            'vendor_name',
            'vendor_contact',
            'invoice_number',
        ]);
        $payload = array_merge($draft, ['status' => $validated['target_status']]);

        // Statusa pāreja maina arī datumu laukus: gaidīšana notīra sākumu/beigas,
        // darba sākšana ieliek sākuma datumu, bet pabeigšana ieliek beigu datumu.
        if ($validated['target_status'] === 'waiting') {
            $payload['start_date'] = null;
            $payload['end_date'] = null;
            $payload['cost'] = null;
            $payload['issue_reported_by'] = null;
        } elseif ($validated['target_status'] === 'in-progress') {
            $payload['start_date'] = filled($repair->start_date) ? $repair->start_date->toDateString() : now()->toDateString();
            $payload['end_date'] = null;
            $payload['issue_reported_by'] = auth()->id();
        } elseif ($validated['target_status'] === 'completed') {
            $payload['end_date'] = now()->toDateString();
        }

        $repair->update($this->normalizeRepairPayloadForPersistence($payload));
        $this->syncDeviceStatus($repair, $before['status'] ?? null);
        $after = $repair->fresh()->only(array_keys($before));

        AuditTrail::updatedFromState(
            auth()->id(),
            $repair,
            $before,
            $after,
            description: 'Remonta statuss mainīts: ' . $this->labelForStatus((string) ($before['status'] ?? 'waiting')) . ' -> ' . $this->labelForStatus((string) ($after['status'] ?? 'waiting'))
        );

        // Remonta statusa maiņa ir lietotājam redzams notikums: sākts, pabeigts vai atcelts.
        // Serviss pats izvēlas, kuros statusos paziņojums ir jāsūta.
        app(UserNotifier::class)->repairStatusChanged($repair->fresh(['device']), (string) ($before['status'] ?? 'waiting'), (string) ($after['status'] ?? $validated['target_status']));

        return back()->with('success', 'Remonta statuss atjaunināts');
    }
    /**
     * Ko dara: Novirza uz remontu rediģēšanu modāli ar konkrēto remonta ID.
     *
     * Kā strādā: Šī metode ir vecs ceļš, kuru uztur tikai atpakaļ saderības dēļ. Atspoguļo pieprasījumu uz pilnu remontu skata lapu.
     *
     * Kad pielietojas: Izsaukšana: GET /repairs/{id} | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Direkta saite uz konkrētu remontu (retk, parasti izmanto modāli).
     */
    public function show(Repair $repair)
    {
        return redirect()->route('repairs.index', [
            'repair_modal' => 'edit',
            'modal_repair' => $repair->id,
        ]);
    }


    /**
     * Ko dara: Izveido remontu vaicājumu, ko pašreizējais lietotājs drīkst redzēt.
     *
     * Kā strādā: Vadītājam atgriež visus remontus, bet parastam lietotājam tikai viņa ziņotos remontus vai remontus viņam piesaistītām ierīcēm.
     *
     * Kad pielietojas: Izsauc no: `index()`, `findByCode()`.
     */
    private function visibleRepairsQuery(User $user): Builder
    {
        return Repair::query()->when(! $user->canManageRequests(), function (Builder $query) use ($user) {
            $query->where(function (Builder $builder) use ($user) {
                $builder->where('issue_reported_by', $user->id)
                    ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('assigned_to_id', $user->id));
            });
        });
    }

    /**
     * Ko dara: Sagatavo remonta formas izvēlnes, statusus un etiķetes.
     *
     * Kā strādā: Ielādē formas ierīces, aktīvos lietotājus, statusu/prioritāšu/tipu sarakstus un latviskās etiķetes vienā datu masīvā.
     *
     * Kad pielietojas: Izsauc no: remonta formas datu sagatavošanas plūsmas.
     */
    private function formData(?Repair $repair = null): array
    {
        $devices = $repair
            ? Device::query()
                ->select([
                    'id',
                    'code',
                    'name',
                    'manufacturer',
                    'model',
                    'device_type_id',
                    'assigned_to_id',
                    'building_id',
                    'room_id',
                    'status',
                ])
                ->with([
                    'assignedTo:id,full_name',
                    'building:id,building_name',
                    'room:id,room_number,room_name',
                    'type:id,type_name',
                ])
                ->where('status', '!=', Device::STATUS_WRITEOFF)
                ->when($repair->device_id, fn (Builder $query) => $query->orWhere('id', $repair->device_id))
                ->orderBy('name')
                ->get()
            : $this->availableDevicesForCreate()->get();

        return [
            'devices' => $devices,
            'deviceOptions' => $this->deviceOptions($devices),
            'users' => User::active()
                ->select(['id', 'full_name', 'job_title', 'email'])
                ->orderBy('full_name')
                ->get(),
            'statuses' => self::STATUSES,
            'repairTypes' => self::TYPES,
            'priorities' => self::PRIORITIES,
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
        ];
    }

    /**
     * Ko dara: Validē un normalizē formas datus pirms saglabāšanas.
     *
     * Kā strādā: Validē remonta ierīci, tipu, prioritāti, izmaksas un ārējā remonta laukus, pārbauda konfliktus ar pieteikumiem un normalizē saglabāšanas payload.
     *
     * Kad pielietojas: Izsauc no: izveides un rediģēšanas darbības.
     */
    private function validatedData(Request $request, ?Repair $repair = null): array
    {
        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['nullable', 'string'],
            'repair_type' => ['required', Rule::in(self::TYPES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'request_id' => ['nullable', 'exists:repair_requests,id'],
        ], [
            'device_id.required' => 'Izvēlies ierīci remonta ierakstam.',
            'description.required' => 'Apraksti remonta darbu vai problēmu.',
            'repair_type.required' => 'Izvēlies remonta tipu.',
        ]);

        foreach ([
            'priority',
            'vendor_name',
            'vendor_contact',
            'invoice_number',
            'request_id',
        ] as $field) {
            $validated[$field] = $validated[$field] ?? null;
        }

        $validated['status'] = $repair?->status ?? 'waiting';
        $validated['priority'] = $validated['priority'] ?? ($repair?->priority ?? 'medium');
        $validated['issue_reported_by'] = $repair?->issue_reported_by ?? null;
        $validated['accepted_by'] = $repair?->accepted_by ?? $this->user()?->id;
        $validated['request_id'] = $validated['request_id'] ?? $repair?->request_id ?? null;
        $validated['start_date'] = $repair?->start_date?->toDateString();
        $validated['end_date'] = $repair?->end_date?->toDateString();

        // Esošam remontam ierīci nemainām, jo remonta vēsture, pieteikumi un audits ir piesaistīti konkrētai ierīcei.
        // Ja remonts izveidots kļūdainai ierīcei, drošāk ir to atcelt un veidot jaunu ierakstu.
        if ($repair && (int) $validated['device_id'] !== (int) $repair->device_id) {
            throw ValidationException::withMessages([
                'device_id' => ['Esošam remontam ierīci mainīt nevar. Atcel šo remontu un izveido jaunu ierakstu pareizajai ierīcei.'],
            ]);
        }

        $device = Device::query()->find($validated['device_id']);
        if ($device && $device->status === Device::STATUS_WRITEOFF && (! $repair || (int) $repair->device_id !== (int) $device->id)) {
            throw ValidationException::withMessages([
                'device_id' => ['Šo ierīci nevar nodot remontā, jo tā ir norakstīta.'],
            ]);
        }

        // Jaunu remontu drīkst veidot tikai brīvai aktīvai ierīcei.
        // Pārējie statusi nozīmē, ka ierīcei jau ir cits process vai tā nav lietošanā.
        if (! $repair && $device && $device->status !== Device::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'device_id' => ['Jaunu remontu var izveidot tikai aktīvai ierīcei.'],
            ]);
        }

        // Pirms jauna remonta izveides pārbaudām visas pieteikumu plūsmas.
        // Tas novērš konfliktu, kur viena ierīce vienlaikus tiek remontēta, norakstīta vai nodota citam lietotājam.
        if (! $repair && $device && RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums.'],
            ]);
        }

        if (! $repair && $device && WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums.'],
            ]);
        }

        if (! $repair && $device && DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums.'],
            ]);
        }

        if ($device) {
            $activeRepairQuery = $device->repairs()->whereIn('status', ['waiting', 'in-progress']);
            if ($repair) {
                $activeRepairQuery->whereKeyNot($repair->id);
            }

            if ($activeRepairQuery->exists()) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir aktīvs remonta ieraksts.'],
                ]);
            }
        }

        if ($validated['status'] === 'waiting') {
            $validated['cost'] = null;
        }

        // Iekšējam remontam piegādātāja un rēķina lauki nav vajadzīgi,
        // tāpēc tos notīrām, lai datubāzē nepaliek veci ārējā remonta dati.
        if ($validated['repair_type'] === 'internal') {
            $validated['vendor_name'] = null;
            $validated['vendor_contact'] = null;
            $validated['invoice_number'] = null;
        }

        return $validated;
    }

    /**
     * Ko dara: Validē remonta statusa pārejas laikā iesniegtos papildlaukus.
     *
     * Kā strādā: Validē statusa maiņas laikā padotos labojumus, saglabā esošās vērtības kā noklusējumu un pielāgo ārējā/iekšējā remonta laukus.
     *
     * Kad pielietojas: Izsauc no: `transition()`.
     */
    private function validatedTransitionDraft(Request $request, Repair $repair, string $targetStatus): array
    {
        $draft = $this->validateInput($request, [
            'description' => ['nullable', 'string'],
            'repair_type' => ['nullable', Rule::in(self::TYPES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
        ]);

        $draft = [
            'description' => array_key_exists('description', $draft) ? trim((string) $draft['description']) : (string) ($repair->description ?? ''),
            'repair_type' => $draft['repair_type'] ?? $repair->repair_type ?? 'internal',
            'priority' => $draft['priority'] ?? $repair->priority ?? 'medium',
            'cost' => array_key_exists('cost', $draft) ? $draft['cost'] : $repair->cost,
            'vendor_name' => array_key_exists('vendor_name', $draft) ? trim((string) $draft['vendor_name']) : $repair->vendor_name,
            'vendor_contact' => array_key_exists('vendor_contact', $draft) ? trim((string) $draft['vendor_contact']) : $repair->vendor_contact,
            'invoice_number' => array_key_exists('invoice_number', $draft) ? trim((string) $draft['invoice_number']) : $repair->invoice_number,
        ];

        if ($draft['repair_type'] === 'internal') {
            $draft['vendor_name'] = null;
            $draft['vendor_contact'] = null;
            $draft['invoice_number'] = null;
        }

        if ($draft['repair_type'] === 'external' && $targetStatus === 'waiting') {
            $draft['cost'] = null;
        }

        return $this->normalizeRepairPayloadForPersistence($draft);
    }

    /**
     * Ko dara: Atgriež ierīces, kurām drīkst izveidot jaunu remonta ierakstu.
     *
     * Kā strādā: Atgriež aktīvas ierīces, kurām nav aktīva remonta un nav gaidošu remonta, norakstīšanas vai nodošanas pieteikumu.
     *
     * Kad pielietojas: Izsauc no: `formData()`.
     */
    private function availableDevicesForCreate(): Builder
    {
        return Device::query()
            ->select([
                'id',
                'code',
                'name',
                'manufacturer',
                'model',
                'device_type_id',
                'assigned_to_id',
                'building_id',
                'room_id',
                'status',
            ])
            ->with([
                'assignedTo:id,full_name',
                'building:id,building_name',
                'room:id,room_number,room_name',
                'type:id,type_name',
            ])
            ->where('status', Device::STATUS_ACTIVE)
            ->whereDoesntHave('repairs', fn (Builder $query) => $query->whereIn('status', ['waiting', 'in-progress']))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('repair_requests')
                    ->whereColumn('repair_requests.device_id', 'devices.id')
                    ->where('repair_requests.status', RepairRequest::STATUS_SUBMITTED);
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('writeoff_requests')
                    ->whereColumn('writeoff_requests.device_id', 'devices.id')
                    ->where('writeoff_requests.status', WriteoffRequest::STATUS_SUBMITTED);
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('device_transfers')
                    ->whereColumn('device_transfers.device_id', 'devices.id')
                    ->where('device_transfers.status', DeviceTransfer::STATUS_SUBMITTED);
            })
            ->orderBy('name');
    }

    /**
     * Ko dara: Sagatavo ierīču izvēlnes opcijas formai vai filtram.
     *
     * Kā strādā: Ierīču kolekciju pārvērš dropdown opcijās ar nosaukumu, kodu, tipu, modeli, atbildīgo, atrašanās vietu un meklēšanas tekstu.
     *
     * Kad pielietojas: Izsauc no: saraksta datu sagatavošanas metodes.
     */
    private function deviceOptions($devices)
    {
        return collect($devices)->map(function (Device $device) {
            $description = collect([
                $device->type?->type_name,
                collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                $device->assignedTo?->full_name ? 'pašlaik: ' . $device->assignedTo->full_name : null,
                $device->room?->room_number ? 'telpa ' . $device->room->room_number : null,
                $device->building?->building_name,
            ])->filter()->implode(' | ');

            return [
                'value' => (string) $device->id,
                'label' => $device->name . ' (' . ($device->code ?: 'bez koda') . ')',
                'description' => $description,
                'search' => implode(' ', array_filter([
                    $device->name,
                    $device->code,
                    $device->type?->type_name,
                    $device->manufacturer,
                    $device->model,
                    $device->assignedTo?->full_name,
                    $device->room?->room_number,
                    $device->room?->room_name,
                    $device->building?->building_name,
                ])),
            ];
        })->values();
    }

    /**
     * Ko dara: Sinhronizē ierīces statusu ar remonta ieraksta statusu.
     *
     * Kā strādā: Ja remonts ir gaidošs vai procesā, ierīci pārslēdz uz `repair`; ja remonts beidzas un nav citu aktīvu remontu, ierīci atgriež aktīvā statusā.
     *
     * Kad pielietojas: Izsauc no: `store()`, `update()`, `transition()`.
     */
    private function syncDeviceStatus(Repair $repair, ?string $previousRepairStatus = null): void
    {
        $device = $repair->device()->first();
        if (! $device) {
            return;
        }

        $hasOtherActiveRepairs = $device->repairs()
            ->whereIn('status', ['waiting', 'in-progress'])
            ->whereKeyNot($repair->id)
            ->exists();

        if (in_array($repair->status, ['waiting', 'in-progress'], true) || $hasOtherActiveRepairs) {
            if ($device->status !== 'repair') {
                $before = ['status' => $device->status];
                // Ja remonts ir gaidos vai procesā, ierīces kopējais statuss jāparāda kā "remontā".
                // forceFill šeit maina tikai sistēmas kontrolētu statusa lauku, nevis lietotāja brīvu ievadi.
                $device->forceFill(['status' => 'repair'])->save();
                AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => 'repair']);
            }

            return;
        }

        if (($previousRepairStatus === 'waiting' || $previousRepairStatus === 'in-progress' || $device->status === 'repair') && ! $hasOtherActiveRepairs) {
            $before = ['status' => $device->status];
            // Kad aktīvu remontu vairs nav, ierīci automātiski atgriežam aktīvā statusā.
            $device->forceFill(['status' => Device::STATUS_ACTIVE])->save();
            AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => Device::STATUS_ACTIVE]);
        }
    }

    /**
     * Ko dara: Atjauno ierīces statusu pēc remonta ieraksta dzēšanas.
     *
     * Kā strādā: Pēc dzēšanas pārbauda, vai ierīcei nav citu aktīvu remontu; ja nav, atjauno iepriekšējo vai aktīvo statusu.
     *
     * Kad pielietojas: Izsauc no: `destroy()`.
     */
    private function restoreDeviceAfterRepairRemoval(int $deviceId, ?string $previousRepairStatus, ?string $restoreStatus): void
    {
        $device = Device::query()->find($deviceId);
        if (! $device) {
            return;
        }

        $hasActiveRepairs = $device->repairs()->whereIn('status', ['waiting', 'in-progress'])->exists();
        if ($hasActiveRepairs) {
            return;
        }

        if ($previousRepairStatus === 'waiting' || $previousRepairStatus === 'in-progress' || $device->status === 'repair') {
            // Dzēšot pēdējo aktīvo remontu, ierīce nedrīkst palikt "remontā" bez remonta ieraksta.
            $device->forceFill(['status' => Device::STATUS_ACTIVE])->save();
        }
    }

    /**
     * Ko dara: Normalizē saraksta filtrus vienotā masīvā.
     *
     * Kā strādā: No URL nolasa meklēšanu, ierīci, pieteicēju, statusus, prioritātes, tipu, datumu filtru un "mani remonti" pazīmi drošā masīvā.
     *
     * Kad pielietojas: Izsauc no: saraksta skata un meklēšanas metodes.
     */
    private function normalizedIndexFilters(Request $request, bool $canManageRepairs): array
    {
        // Statusu un prioritāšu vērtības normalizējam no URL, lai saraksta skats,
        // ātrā meklēšana un audita pieraksti lietotu vienādu filtru struktūru.
        $availableStatuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
        $rawStatuses = $request->query('status', []);
        $selectedStatuses = collect(is_array($rawStatuses) ? $rawStatuses : [$rawStatuses])
            ->map(fn ($status) => strtolower(trim((string) $status)))
            ->filter(fn ($status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        // Ja filtrus notīra, noklusētie statusi netiek uzlikti atpakaļ tajā pašā
        // pieprasījumā; citādi vadītājam pēc noklusējuma rāda aktīvos remontus.
        if ($request->boolean('clear_all')) {
            $selectedStatuses = [];
        } elseif ($canManageRepairs && ! $request->has('statuses_filter') && ! $request->has('status')) {
            $selectedStatuses = ['waiting', 'in-progress'];
        }

        $availablePriorities = ['low', 'medium', 'high', 'critical'];
        $rawPriorities = $request->query('priorities', []);
        $selectedPriorities = collect(is_array($rawPriorities) ? $rawPriorities : [$rawPriorities])
            ->map(fn ($p) => strtolower(trim((string) $p)))
            ->filter(fn ($p) => in_array($p, $availablePriorities, true))
            ->unique()
            ->values()
            ->all();

        if ($request->boolean('clear_all')) {
            $selectedPriorities = [];
        }

        // Atgriežam tikai pārbaudītas vērtības, ko tālāk droši var izmantot
        // Eloquent vaicājumos un Blade filtru laukos.
        return [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'device_id' => ctype_digit((string) $request->query('device_id', '')) ? (int) $request->query('device_id') : null,
            'device_query' => trim((string) $request->query('device_query', '')),
            'requester_id' => ctype_digit((string) $request->query('requester_id', '')) ? (int) $request->query('requester_id') : null,
            'requester_query' => trim((string) $request->query('requester_query', '')),
            'statuses' => $selectedStatuses,
            'priorities' => $selectedPriorities,
            'repair_type' => in_array($request->query('repair_type', ''), ['internal', 'external'], true) ? $request->query('repair_type') : null,
            'date_field' => in_array($request->query('date_field', 'start_date'), ['start_date', 'end_date'], true) ? $request->query('date_field') : 'start_date',
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'mine' => $request->boolean('mine'),
        ];
    }

    /**
     * Ko dara: Pielieto saraksta filtrus Eloquent vaicājumam.
     *
     * Kā strādā: Pielieto brīvo meklēšanu remonta, ierīces un lietotāju laukos, kā arī ierīces, pieteicēja, statusa, prioritātes, tipa un datuma filtrus.
     *
     * Kad pielietojas: Izsauc no: saraksta skata un meklēšanas metodes.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        if ($filters['q'] !== '' && ! in_array('q', $skip, true)) {
            $term = $filters['q'];
            $likeTerm = '%'.$term.'%';

            // Remontu brīvā meklēšana aptver gan paša remonta laukus, gan saistīto ierīci
            // un pieteicēju/ziņotāju. Grupēšana vajadzīga, lai OR nosacījumi nesašķeltu pārējos filtrus.
            $query->where(function (Builder $filterQuery) use ($likeTerm) {
                $filterQuery
                    ->where('repairs.description', 'like', $likeTerm)
                    ->orWhere('repairs.vendor_name', 'like', $likeTerm)
                    ->orWhere('repairs.vendor_contact', 'like', $likeTerm)
                    ->orWhere('repairs.invoice_number', 'like', $likeTerm)
                    ->orWhereHas('device', function (Builder $deviceQuery) use ($likeTerm) {
                        $deviceQuery
                            ->where('code', 'like', $likeTerm)
                            ->orWhere('serial_number', 'like', $likeTerm)
                            ->orWhere('name', 'like', $likeTerm)
                            ->orWhere('manufacturer', 'like', $likeTerm)
                            ->orWhere('model', 'like', $likeTerm);
                    })
                    ->orWhereHas('request.responsibleUser', fn (Builder $requesterQuery) => $requesterQuery->where('full_name', 'like', $likeTerm))
                    ->orWhereHas('reporter', fn (Builder $reporterQuery) => $reporterQuery->where('full_name', 'like', $likeTerm));
            });
        }

        if ($filters['device_id'] && ! in_array('device_id', $skip, true)) {
            $query->where('repairs.device_id', $filters['device_id']);
        }

        if ($filters['requester_id'] && ! in_array('requester_id', $skip, true)) {
            $requesterId = $filters['requester_id'];

            // Pieteicējs remontam var būt divās vietās: sākotnējā remonta pieteikumā
            // vai tieši remonta ieraksta `issue_reported_by` laukā.
            $query->where(function (Builder $requesterQuery) use ($requesterId) {
                $requesterQuery->whereHas('request', fn (Builder $builder) => $builder->where('responsible_user_id', $requesterId))
                    ->orWhere('repairs.issue_reported_by', $requesterId);
            });
        }

        if (! in_array('status', $skip, true) && count($filters['statuses']) > 0 && count($filters['statuses']) < count(self::STATUSES)) {
            $query->whereIn('repairs.status', $filters['statuses']);
        }

        if (! in_array('priority', $skip, true) && count($filters['priorities'] ?? []) > 0 && count($filters['priorities']) < count(self::PRIORITIES)) {
            $query->whereIn('repairs.priority', $filters['priorities']);
        }

        if (! in_array('repair_type', $skip, true) && filled($filters['repair_type'])) {
            $query->where('repairs.repair_type', $filters['repair_type']);
        }

        $dateField = in_array($filters['date_field'] ?? 'start_date', ['start_date', 'end_date'], true)
            ? $filters['date_field']
            : 'start_date';

        if ($filters['date_from'] !== '' && ! in_array('date_from', $skip, true)) {
            $query->whereDate('repairs.'.$dateField, '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '' && ! in_array('date_to', $skip, true)) {
            $query->whereDate('repairs.'.$dateField, '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Ko dara: Pielieto drošu kārtošanu saraksta vaicājumam.
     *
     * Kā strādā: Katrai atļautajai kolonnai pievieno savu join/order loģiku un statusus/prioritātes kārto ar SQL CASE prioritāšu secībā.
     *
     * Kad pielietojas: Izsauc no: saraksta skata un meklēšanas metodes.
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        // Kārtošanas virzienu vēlreiz ierobežojam uz asc/desc, jo tas tiek
        // ievietots arī raw order izteiksmēs statusiem un prioritātēm.
        $direction = $sorting['direction'] === 'asc' ? 'asc' : 'desc';

        // Katra redzamā kolonna saņem savu join/order loģiku; relāciju laukus
        // kārtojam ar leftJoin, lai sarakstā neizkristu ieraksti bez relācijas.
        switch ($sorting['sort']) {
            case 'code':
                $query->leftJoin('devices as repair_sort_device', 'repair_sort_device.id', '=', 'repairs.device_id')
                    ->orderBy('repair_sort_device.code', $direction)
                    ->orderBy('repair_sort_device.serial_number', $direction);
                break;
            case 'name':
                $query->leftJoin('devices as repair_sort_device', 'repair_sort_device.id', '=', 'repairs.device_id')
                    ->orderBy('repair_sort_device.name', $direction)
                    ->orderBy('repair_sort_device.manufacturer', $direction)
                    ->orderBy('repair_sort_device.model', $direction);
                break;
            case 'assigned':
                $query->leftJoin('devices as repair_sort_device', 'repair_sort_device.id', '=', 'repairs.device_id')
                    ->leftJoin('users as repair_sort_assigned', 'repair_sort_assigned.id', '=', 'repair_sort_device.assigned_to_id')
                    ->orderBy('repair_sort_assigned.full_name', $direction);
                break;
            case 'location':
                $query->leftJoin('devices as repair_sort_device', 'repair_sort_device.id', '=', 'repairs.device_id')
                    ->leftJoin('rooms as repair_sort_room', 'repair_sort_room.id', '=', 'repair_sort_device.room_id')
                    ->leftJoin('buildings as repair_sort_building', 'repair_sort_building.id', '=', 'repair_sort_device.building_id')
                    ->orderBy('repair_sort_building.building_name', $direction)
                    ->orderBy('repair_sort_room.room_number', $direction)
                    ->orderBy('repair_sort_room.room_name', $direction);
                break;
            case 'status':
                $query->orderByRaw(
                    $direction === 'asc'
                        ? "case repairs.status when 'waiting' then 0 when 'in-progress' then 1 when 'completed' then 2 when 'cancelled' then 3 else 4 end"
                        : "case repairs.status when 'cancelled' then 0 when 'completed' then 1 when 'in-progress' then 2 when 'waiting' then 3 else 4 end"
                );
                break;
            case 'priority':
                $query->orderByRaw(
                    $direction === 'asc'
                        ? "case repairs.priority when 'low' then 0 when 'medium' then 1 when 'high' then 2 when 'critical' then 3 else 4 end"
                        : "case repairs.priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 when 'low' then 3 else 4 end"
                );
                break;
            case 'repair_type':
                $query->orderBy('repairs.repair_type', $direction);
                break;
            case 'cost':
                $query->orderBy('repairs.cost', $direction);
                break;
            case 'start_date':
                $query->orderBy('repairs.start_date', $direction);
                break;
            case 'end_date':
                $query->orderBy('repairs.end_date', $direction);
                break;
            default:
                // Ja kārtošana nav atpazīta, remontus rādam pēc svarīguma:
                // kritiskie un jaunākie ieraksti nonāk saraksta augšā.
                $query->orderByRaw("case repairs.priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 when 'low' then 3 else 4 end")
                    ->orderByDesc('repairs.id');
                return;
        }

        $query->orderByDesc('repairs.id');
    }

    /**
     * Ko dara: Normalizē kārtošanas parametrus tabulas galvenei un audita ierakstiem.
     *
     * Kā strādā: Normalizē `sort` un `direction` query parametrus pret atļautajiem laukiem un pievieno latvisku label audita tekstiem.
     *
     * Kad pielietojas: Izsauc no: saraksta skata un meklēšanas metodes.
     */
    private function normalizedSorting(Request $request): array
    {
        $sort = strtolower(trim((string) $request->query('sort', 'priority')));
        $direction = strtolower(trim((string) $request->query('direction', 'desc')));

        if (! in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'priority';
        }

        return [
            'sort' => $sort,
            'direction' => $direction === 'asc' ? 'asc' : 'desc',
            'label' => $this->sortOptions()[$sort]['label'] ?? 'prioritātes',
        ];
    }

    /**
     * Ko dara: Atgriež saraksta kārtošanas opciju etiķetes.
     *
     * Kā strādā: Atgriež remonta saraksta kārtojamo lauku label karti, ko izmanto tabulas galvene un audita paziņojumi.
     *
     * Kad pielietojas: Izsauc no: saraksta skatus un kārtošanas normalizēšanu.
     */
    private function sortOptions(): array
    {
        return [
            'code' => ['label' => 'koda'],
            'name' => ['label' => 'nosaukuma'],
            'assigned' => ['label' => 'piešķirtās personas'],
            'location' => ['label' => 'atrašanās vietas'],
            'status' => ['label' => 'remonta statusa'],
            'priority' => ['label' => 'prioritātes'],
            'repair_type' => ['label' => 'remonta tipa'],
            'cost' => ['label' => 'izmaksām'],
            'start_date' => ['label' => 'sākuma datuma'],
            'end_date' => ['label' => 'beigu datuma'],
        ];
    }

    /**
     * Ko dara: Reģistrē remontu saraksta filtrēšanu un kārtošanu audita žurnālā.
     *
     * Kā strādā: No aktīvajiem filtriem izveido audita payload un pieraksta filtrēšanu/kārtošanu tikai tad, ja lietotājs mainījis atlasi vai secību.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function auditRepairListInteractions(Request $request, User $user, array $filters, array $sorting): void
    {
        $filterPayload = array_filter([
            'teksts' => $filters['q'] ?? '',
            'ierīce' => $filters['device_query'] ?? '',
            'pieteicējs' => $filters['requester_query'] ?? '',
            'statusi' => count($filters['statuses'] ?? []) > 0 && count($filters['statuses'] ?? []) < count(self::STATUSES) ? ($filters['statuses'] ?? []) : [],
            'prioritātes' => count($filters['priorities'] ?? []) > 0 && count($filters['priorities']) < count(self::PRIORITIES) ? ($filters['priorities'] ?? []) : [],
            'remonta tips' => $filters['repair_type'] ?? '',
            'datuma lauks' => (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '') ? (($filters['date_field'] ?? 'start_date') === 'end_date' ? 'beigu datums' : 'sākuma datums') : '',
            'no datuma' => $filters['date_from'] ?? '',
            'līdz datumam' => $filters['date_to'] ?? '',
            'mani remonti' => ! empty($filters['mine']),
        ], fn (mixed $value) => $value !== null && $value !== '' && $value !== []);

        if ($filterPayload !== []) {
            AuditTrail::filter(
                $user,
                'Repair',
                $filterPayload,
                'Filtrēti remonti: ' . implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
                    if (is_array($value)) {
                        return $label.': '.implode(', ', $value);
                    }

                    if (is_bool($value)) {
                        return $label . ': ' . ($value ? 'jā' : 'nē');
                    }

                    return $label.': '.$value;
                })->all())
            );
        }

        if (($sorting['sort'] ?? 'priority') !== 'priority' || ($sorting['direction'] ?? 'desc') !== 'desc' || $request->has('sort')) {
            AuditTrail::sort(
                $user,
                'Repair',
                $sorting['label'] ?? 'prioritātes',
                $sorting['direction'] ?? 'desc',
                'Kārtoti remonti pēc ' . ($sorting['label'] ?? 'prioritātes') . ' ' . (($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā') . '.'
            );
        }
    }

    /**
     * Ko dara: Saskaita remontus pa statusiem kopsavilkuma kartītēm.
     *
     * Kā strādā: No bāzes vaicājuma sagrupē remontus pēc statusa un atgriež kopskaitu un katra statusa skaitu kopsavilkuma kartītēm.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function repairSummary(Builder $baseQuery): array
    {
        $rows = (clone $baseQuery)
            ->selectRaw('repairs.status, COUNT(*) as aggregate')
            ->groupBy('repairs.status')
            ->pluck('aggregate', 'repairs.status');

        return [
            'total' => (int) $rows->sum(),
            'waiting' => (int) ($rows['waiting'] ?? 0),
            'in_progress' => (int) ($rows['in-progress'] ?? 0),
            'completed' => (int) ($rows['completed'] ?? 0),
            'cancelled' => (int) ($rows['cancelled'] ?? 0),
        ];
    }

    /**
     * Ko dara: Saskaita remontus pa prioritātēm kopsavilkuma kartītēm.
     *
     * Kā strādā: No bāzes vaicājuma sagrupē remontus pēc prioritātes un atgriež zemas, vidējas, augstas un kritiskas prioritātes skaitu.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function prioritySummary(Builder $baseQuery): array
    {
        $rows = (clone $baseQuery)
            ->selectRaw('repairs.priority, COUNT(*) as aggregate')
            ->groupBy('repairs.priority')
            ->pluck('aggregate', 'repairs.priority');

        return [
            'low' => (int) ($rows['low'] ?? 0),
            'medium' => (int) ($rows['medium'] ?? 0),
            'high' => (int) ($rows['high'] ?? 0),
            'critical' => (int) ($rows['critical'] ?? 0),
        ];
    }

    /**
     * Ko dara: Saskaita remontus pa remonta tipiem kopsavilkuma kartītēm.
     *
     * Kā strādā: No bāzes vaicājuma sagrupē remontus pēc remonta tipa un atgriež iekšējo, ārējo un kopējo remonta skaitu.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function typeSummary(Builder $baseQuery): array
    {
        $rows = (clone $baseQuery)
            ->selectRaw('repairs.repair_type, COUNT(*) as aggregate')
            ->groupBy('repairs.repair_type')
            ->pluck('aggregate', 'repairs.repair_type');

        return [
            'internal' => (int) ($rows['internal'] ?? 0),
            'external' => (int) ($rows['external'] ?? 0),
            'total' => (int) $rows->sum(),
        ];
    }

    /**
     * Ko dara: Sagatavo ierīču izvēlnes opcijas remontu filtram.
     *
     * Kā strādā: No filtrētā remonta vaicājuma paņem unikālos ierīču ID, ielādē šo ierīču kontekstu un pārvērš tās filtra opcijās.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function repairDeviceOptions(Builder $repairsQuery): Collection
    {
        // Vispirms no jau filtrētā remonta vaicājuma paņemam tikai ierīču ID,
        // lai filtra izvēlnē netiktu rādītas ierīces ārpus aktuālās datu kopas.
        $deviceIds = (clone $repairsQuery)
            ->whereNotNull('repairs.device_id')
            ->distinct()
            ->pluck('repairs.device_id')
            ->filter()
            ->values();

        if ($deviceIds->isEmpty()) {
            return collect();
        }

        // Pēc ID ielādējam pašas ierīces ar tipu un atrašanās vietu, lai opcijām
        // var izveidot saprotamu nosaukumu, aprakstu un meklēšanas tekstu.
        return Device::query()
            ->select([
                'id',
                'code',
                'serial_number',
                'name',
                'manufacturer',
                'model',
                'device_type_id',
                'building_id',
                'room_id',
            ])
            ->with([
                'type:id,type_name',
                'room:id,room_number,room_name',
                'building:id,building_name',
            ])
            ->whereIn('id', $deviceIds)
            ->orderBy('name')
            ->get()
            ->map(function (Device $device) {
                // Apraksts apvieno tehnisko un atrašanās vietas informāciju, ko
                // filtra izvēlnē var parādīt kā īsu kontekstu zem nosaukuma.
                $description = collect([
                    $device->type?->type_name,
                    collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                    $device->room?->room_number ? 'telpa '.$device->room->room_number : null,
                    $device->building?->building_name,
                ])->filter()->implode(' | ');

                return [
                    'value' => (string) $device->id,
                    'label' => $device->name.' ('.($device->code ?: 'bez koda').')',
                    'description' => $description,
                    'search' => implode(' ', array_filter([
                        $device->name,
                        $device->code,
                        $device->serial_number,
                        $device->manufacturer,
                        $device->model,
                        $device->type?->type_name,
                        $device->room?->room_number,
                        $device->room?->room_name,
                        $device->building?->building_name,
                    ])),
                ];
            })
            ->values();
    }

    /**
     * Ko dara: Sagatavo pieteicēju izvēlnes opcijas remontu filtram.
     *
     * Kā strādā: No remontiem savāc pieteicēju ID gan no remonta pieteikuma, gan no `issue_reported_by`, ielādē lietotājus un pārvērš dropdown opcijās.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function repairRequesterOptions(Builder $repairsQuery): Collection
    {
        $requesterIds = collect([
            (clone $repairsQuery)
                ->leftJoin('repair_requests as repair_requesters', 'repair_requesters.id', '=', 'repairs.request_id')
                ->whereNotNull('repair_requesters.responsible_user_id')
                ->distinct()
                ->pluck('repair_requesters.responsible_user_id'),
            (clone $repairsQuery)
                ->whereNotNull('repairs.issue_reported_by')
                ->distinct()
                ->pluck('repairs.issue_reported_by'),
        ])->flatten()->filter()->unique()->values();

        if ($requesterIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->select(['id', 'full_name', 'job_title', 'email'])
            ->whereIn('id', $requesterIds)
            ->orderBy('full_name')
            ->get()
            ->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => $user->full_name,
                'description' => $user->job_title ?: $user->email,
                'search' => implode(' ', array_filter([$user->full_name, $user->job_title, $user->email])),
            ]);
    }

    /**
     * Ko dara: Atgriež remonta statusu cilvēkam saprotamās etiķetes.
     *
     * Kā strādā: Tehniskos remonta statusus sasaista ar latviskiem tekstiem, ko izmanto saraksti, formas un audita apraksti.
     *
     * Kad pielietojas: Izsauc no: skatu datu sagatavošanas metodes.
     */
    private function statusLabels(): array
    {
        return [
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
    }

    /**
     * Ko dara: Atgriež remonta prioritāšu cilvēkam saprotamās etiķetes.
     *
     * Kā strādā: Tehniskās prioritāšu vērtības pārvērš latviskās etiķetēs no zemas līdz kritiskai.
     *
     * Kad pielietojas: Izsauc no: `index()`, `formData()`.
     */
    private function priorityLabels(): array
    {
        return [
            'low' => 'Zema',
            'medium' => 'Vidēja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
    }

    /**
     * Ko dara: Atgriež remonta tipu cilvēkam saprotamās etiķetes.
     *
     * Kā strādā: Tehniskos remonta tipus `internal` un `external` pārvērš latviskos nosaukumos.
     *
     * Kad pielietojas: Izsauc no: `index()`, `formData()`.
     */
    private function typeLabels(): array
    {
        return [
            'internal' => 'Iekšējais',
            'external' => 'Ārējais',
        ];
    }

    /**
     * Ko dara: Atgriež konkrēta remonta statusa etiķeti audita aprakstiem.
     *
     * Kā strādā: Atgriež statusa label no `statusLabels()` kartes vai pašu tehnisko vērtību, ja statuss nav definēts.
     *
     * Kad pielietojas: Izsauc no: `transition()`.
     */
    private function labelForStatus(string $status): string
    {
        return $this->statusLabels()[$status] ?? $status;
    }

    /**
     * Ko dara: Definē atļautos nākamos statusus remonta dzīves ciklā.
     *
     * Kā strādā: Ar `match` definē remonta dzīves cikla atļautās pārejas, piemēram, no `waiting` uz `in-progress` vai `cancelled`.
     *
     * Kad pielietojas: Izsauc no: `transition()`.
     */
    private function allowedTransitionTargets(string $status): array
    {
        return match ($status) {
            'waiting' => ['in-progress', 'cancelled'],
            'in-progress' => ['waiting', 'completed', 'cancelled'],
            'completed' => ['in-progress'],
            default => [],
        };
    }
}
