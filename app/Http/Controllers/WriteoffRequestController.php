<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasRepairStatusLabels;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use App\Support\UserNotifier;
use App\Support\WarehouseConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Ko dara: Pārvalda ierīču norakstīšanas pieteikumus.
 *
 * Kā strādā: Ļauj lietotājam pieteikt ierīci norakstīšanai, administratoram pieņemt lēmumu un apstiprināšanas gadījumā pārvietot ierīci uz noliktavu.
 *
 * Kad pielietojas: Kad ierīce vairs nav izmantojama un jāiziet norakstīšanas apstiprināšanas plūsma.
 */
class WriteoffRequestController extends Controller
{
    use HasRepairStatusLabels;



    // Atļautās norakstīšanas pieteikumu saraksta kārtošanas kolonnas.
    private const SORTABLE_COLUMNS = ['code', 'name', 'requester', 'created_at', 'status'];

    /**
     * Ko dara: Parāda norakstīšanas pieteikumu sarakstu ar lomas atkarīgu loģiku un filtriem.
     *
     * Kā strādā: Administrators redz visus pieteikumus. Parasts lietotājs redz tikai savus iesniegtos. Atspoguļo gaidošo, apstiprināto un noraidīto pieteikumu kopsavilkumu.
     *
     * Kad pielietojas: Izsaukšana: GET /writeoff-requests | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs navigē uz "Norakstīšanas pieteikumi" sadaļu sānjoslā.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->writeoffRequestsViewData($request, $user);

        AuditTrail::viewed($user, 'WriteoffRequest', null, 'Atvērts norakstīšanas pieteikumu saraksts.');
        $this->auditWriteoffRequestListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);

        return view('writeoff_requests.index', $viewData);
    }

    /**
     * Ko dara: Atgriež filtrētu norakstīšanas pieteikumu tabulu (async).
     *
     * Kā strādā: Atjauno tikai tabulas HTML fragmentu, izmantojot tos pašus filtrus, kārtošanu un redzamības nosacījumus kā pilnā saraksta lapa.
     *
     * Kad pielietojas: Kad JavaScript norakstīšanas pieteikumu sarakstā maina filtrus vai kārtošanu bez pilnas lapas pārlādes.
     */
    public function table(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->writeoffRequestsViewData($request, $user);
        $this->auditWriteoffRequestListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);
        return view('writeoff_requests.index-table', [
            'requests' => $viewData['requests'],
            'canReview' => $viewData['canReview'],
            'sorting' => $viewData['sorting'],
            'sortOptions' => $viewData['sortOptions'],
            'statusLabels' => $viewData['statusLabels'],
            'sortDirectionLabels' => $viewData['sortDirectionLabels'],
        ]);
    }

    /**
     * Ko dara: Kopīga metode norakstīšanas pieteikumu datu sagatavošanai.
     *
     * Kā strādā: Normalizē filtrus un kārtošanu, ierobežo redzamību pēc lomas, sagatavo sarakstu, kopsavilkumu, filtru opcijas un modālā loga datus.
     *
     * Kad pielietojas: Norakstīšanas pieteikumu pilnās lapas un async tabulas datu sagatavošanā.
     */
    private function writeoffRequestsViewData(Request $request, $user): array
    {
        // Ātrā meklēšana izmanto tos pašus redzamības nosacījumus kā saraksts:
        // vadītājs redz visus, parasts lietotājs tikai savus pieteikumus.
        $canReview = $user->canManageRequests();
        $availableStatuses = [
            WriteoffRequest::STATUS_SUBMITTED,
            WriteoffRequest::STATUS_APPROVED,
            WriteoffRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        // Ja norakstīšanas pieteikumu tabula vēl nav pieejama, skats saņem
        // tukšu, bet strukturāli pilnu atbildi un lapa turpina strādāt.
        if (! $this->featureTableExists('writeoff_requests')) {
            return [
                'requests' => collect(),
                'requestSummary' => [
                    'total' => 0,
                    'submitted' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'canReview' => $canReview,
                'sorting' => $sorting,
                'sortOptions' => $this->sortOptions(),
                'deviceOptions' => collect(),
                'createDeviceOptions' => collect(),
                'requesterOptions' => collect(),
                'selectedEditableRequest' => null,
                'featureMessage' => 'Tabula writeoff_requests šobrīd nav pieejama.',
                'sortDirectionLabels' => ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'],
            ];
        }

        // Bāzes vaicājums nosaka redzamību: vadītājs redz visus pieteikumus,
        // bet parasts lietotājs tikai savus norakstīšanas pieteikumus.
        $baseQuery = WriteoffRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        // Filtru opcijas tiek rēķinātas no redzamās datu kopas, izlaižot pašu
        // filtru, lai izvēlnēs nepazustu citas iespējamās vērtības.
        $deviceOptions = $this->writeoffDeviceOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id', 'code']))
                ->with(['device.type'])
                ->get()
        );

        $requesterOptions = $this->writeoffRequesterOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id', 'code']))
                ->with('responsibleUser')
                ->get()
        );

        $createDeviceOptions = ! $canReview
            ? $this->deviceOptions($this->availableDevicesForUser($user)->get())
            : collect();

        // Galvenais saraksts ielādē saistīto ierīci, iesniedzēju un izskatītāju,
        // lai norakstīšanas skatā var uzreiz parādīt visu nepieciešamo kontekstu.
        // Ielādējam filtrēto norakstīšanas pieteikumu sarakstu ar ierīces kodu,
        // lai pēc atrastā indeksa varētu izcelt pareizo rindu.
        $requestsQuery = (clone $baseQuery)
            ->with(['device.type', 'responsibleUser', 'reviewedBy'])
            ->select('writeoff_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();

        // Skatam atdodam gan tabulas datus, gan kopsavilkuma skaitļus, filtrus
        // un formas opcijas, lai lapa varētu atjaunoties no viena datu avota.
        return [
            'requests' => $requests,
            'requestSummary' => [
                'total' => (clone $baseQuery)->count(),
                'submitted' => (clone $baseQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count(),
                'approved' => (clone $baseQuery)->where('status', WriteoffRequest::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', WriteoffRequest::STATUS_REJECTED)->count(),
            ],
            'filters' => $filters,
            'statuses' => $availableStatuses,
            'statusLabels' => $this->requestStatusLabels(),
            'canReview' => $canReview,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'deviceOptions' => $deviceOptions,
            'createDeviceOptions' => $createDeviceOptions,
            'requesterOptions' => $requesterOptions,
            'selectedEditableRequest' => ! $canReview && ctype_digit((string) $request->query('modal_request'))
                ? WriteoffRequest::query()
                    ->with('device')
                    ->whereKey((int) $request->query('modal_request'))
                    ->where('responsible_user_id', $user->id)
                    ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                    ->first()
                : null,
            'sortDirectionLabels' => ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'],
        ];
    }

    /**
     * Ko dara: Atrod norakstīšanas pieteikumu pēc saistītās ierīces koda filtrētajā sarakstā.
     *
     * Kā strādā: Atkārto saraksta filtrus, kārtošanu un lomu redzamību, ielādē ierīces kodus un atgriež atrastās rindas highlight ID.
     *
     * Kad pielietojas: Kad JavaScript ātrajā meklēšanā norakstīšanas pieteikumu sarakstā meklē pēc ierīces koda.
     */
    public function findByCode(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'WriteoffRequest', $code, 'Meklēts norakstīšanas pieteikums pēc ierīces koda: '.$code);

        $canReview = $user->canManageRequests();
        $availableStatuses = [
            WriteoffRequest::STATUS_SUBMITTED,
            WriteoffRequest::STATUS_APPROVED,
            WriteoffRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        $baseQuery = WriteoffRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $requestsQuery = (clone $baseQuery)
            ->with('device:id,code')
            ->select('writeoff_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();
        $needle = mb_strtolower($code);
        $foundIndex = null;

        // Precīzs normalizēta koda salīdzinājums pasargā no nepareizas rindas
        // izcelšanas, ja kodi ir līdzīgi.
        foreach ($requests as $index => $writeoffRequest) {
            $deviceCode = mb_strtolower(trim((string) ($writeoffRequest->device?->code ?? '')));
            if ($deviceCode === $needle) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === null) {
            return response()->json(['found' => false, 'page' => 1]);
        }
        return response()->json([
            'found' => true,
            'page' => 1,
            'term' => $code,
            'highlight_id' => 'writeoff-request-'.$requests->values()[$foundIndex]->id,
        ]);
    }


    /**
     * Ko dara: Saglabā jaunu norakstīšanas pieteikumu.
     *
     * Kā strādā: Parastam lietotājam validē izvēlēto ierīci un iemeslu, pārbauda konfliktējošus pieteikumus/remontus un izveido `submitted` norakstīšanas pieteikumu.
     *
     * Kad pielietojas: Kad darbinieks savai aktīvajai ierīcei iesniedz norakstīšanas pieteikumu.
     */
    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return redirect()->route('writeoff-requests.index')->with('error', 'Norakstīšanas pieteikumus šobrīd nevar saglabāt, jo tabula writeoff_requests nav pieejama.');
        }

        try {
            $validated = $this->validateInput($request, [
                'device_id' => ['required', 'exists:devices,id'],
                'reason' => ['required', 'string', 'min:10', 'max:2000'],
            ], [
                'device_id.required' => 'Izvēlies ierīci, kuru vēlies norakstīt.',
                'reason.required' => 'Apraksti norakstīšanas iemeslu.',
                'reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
                'reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
            ]);

            $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
            if (! $device) {
                throw ValidationException::withMessages([
                    'device_id' => ['Vari pieteikt norakstīšanu tikai savai piesaistītai ierīcei.'],
                ]);
            }

            $this->ensureDeviceCanAcceptWriteoffRequest($device);
        } catch (ValidationException $exception) {
            return $this->redirectRequestValidationException($request, $exception, 'writeoff-requests.index', 'writeoff');
        }

        $writeoffRequest = WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => $validated['reason'],
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $writeoffRequest);
        AuditTrail::submit($user->id, $writeoffRequest, 'Iesniegts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));

        return redirect()->route('writeoff-requests.index')->with('success', 'Norakstīšanas pieteikums nosūtīts izskatīšanai');
    }

    /**
     * Ko dara: Administratora lēmums par norakstīšanas pieprasījumu.
     *
     * Kā strādā: Vadītājs validē lēmumu, transakcijā bloķē pieteikumu un apstiprināšanas gadījumā pārceļ ierīci uz norakstīto statusu/noliktavu.
     *
     * Kad pielietojas: Kad administrators vai IT vadītājs apstiprina vai noraida iesniegtu norakstīšanas pieteikumu.
     */
    public function review(Request $request, WriteoffRequest $writeoffRequest)
    {
        // Norakstīšana ir administratīvs lēmums, jo tā izņem ierīci no aktīvas aprites.
        // Tāpēc pirms jebkādas datu maiņas pārbaudām vadītāja tiesības.
        $manager = $this->requireManager();

        if (! $this->featureTableExists('writeoff_requests')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Norakstīšanas pieteikumu tabula šobrīd nav pieejama.'], 503);
            }

            return back()->with('error', 'Norakstīšanas pieteikumu tabula šobrīd nav pieejama.');
        }

        // Norakstīšanas pieteikumu izskata tikai vienu reizi.
        // Atkārtota apstiprināšana varētu vēlreiz pārvietot ierīci vai sagrozīt audita vēsturi.
        if ($writeoffRequest->status !== WriteoffRequest::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Šis pieteikums jau ir izskatīts.'], 409);
            }

            return back()->with('error', 'Šis pieteikums jau ir izskatīts.');
        }

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED])],
        ], [
            'status.required' => 'Izvēlies lēmumu norakstīšanas pieteikumam.',
        ]);

        $before = $writeoffRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        // Norakstīšanas apstiprināšana maina gan pieteikuma statusu, gan pašu ierīci.
        // Transakcija nodrošina, ka abas izmaiņas tiek saglabātas kopā vai netiek saglabātas nemaz.
        DB::transaction(function () use ($validated, $writeoffRequest, $manager) {
            $writeoffRequest->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $manager->id,
                'review_notes' => null,
            ]);

            if ($validated['status'] !== WriteoffRequest::STATUS_APPROVED) {
                return;
            }

            // Ierīci bloķējam līdz transakcijas beigām, lai paralēli nevarētu sākt remontu,
            // nodošanu vai citu statusa maiņu tai pašai ierīcei.
            $device = $writeoffRequest->device()->lockForUpdate()->first();

            if (! $device) {
                throw ValidationException::withMessages([
                    'status' => ['Ierīce norakstīšanai vairs nav atrasta.'],
                ]);
            }

            // Norakstīt drīkst tikai aktīvu ierīci bez aktīva remonta.
            // Tas novērš konfliktu starp divām dzīves cikla plūsmām: remontu un norakstīšanu.
            if ($device->status !== Device::STATUS_ACTIVE || $device->activeRepair()->exists()) {
                throw ValidationException::withMessages([
                    'status' => ['Norakstīt var tikai aktīvu ierīci bez aktīva remonta procesā.'],
                ]);
            }

            // Apstiprināta norakstīšana noņem atbildīgo lietotāju un pārvieto ierīci
            // uz noliktavas telpu, lai tā vairs neparādītos kā lietošanā esoša.
            $device->forceFill(array_merge(
                ['status' => Device::STATUS_WRITEOFF],
                $this->writeoffWarehousePayload($manager->id)
            ))->save();
        });

        $after = $writeoffRequest->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($manager->id, $writeoffRequest, $before, $after);
        if ($validated['status'] === WriteoffRequest::STATUS_APPROVED) {
            AuditTrail::approve($manager->id, $writeoffRequest, 'Apstiprināts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));
        } else {
            AuditTrail::reject($manager->id, $writeoffRequest, null, 'Noraidīts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));
        }

        // Paziņojam pieteikuma autoram par lēmumu, jo norakstīšana maina ierīces pieejamību.
        // Šis ieraksts paliek paziņojumu centrā līdz lietotājs to atzīmē kā lasītu.
        app(UserNotifier::class)->requestReviewed($writeoffRequest->fresh(['device', 'responsibleUser']), $validated['status']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Norakstīšanas pieteikums izskatīts',
                'status' => $validated['status'],
                'request_id' => $writeoffRequest->id,
            ]);
        }

        return back()->with('success', 'Norakstīšanas pieteikums izskatīts');
    }

    /**
     * Ko dara: Atgriež lietotājam pieejamās ierīces konkrētās plūsmas izveides formai.
     *
     * Kā strādā: Atgriež lietotāja aktīvās ierīces, kurām nav aktīva remonta, gaidoša remonta/norakstīšanas pieteikuma vai nodošanas.
     *
     * Kad pielietojas: Izsauc no: saraksta datu sagatavošanas metodes un `store()`.
     */
    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->where('assigned_to_id', $user->id)
            ->where('status', Device::STATUS_ACTIVE)
            ->whereDoesntHave('repairs', fn (Builder $query) => $query->whereIn('status', ['waiting', 'in-progress']))
            ->whereDoesntHave('repairRequests', fn (Builder $query) => $query->where('status', RepairRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('writeoffRequests', fn (Builder $query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('transfers', fn (Builder $query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED))
            ->with(['type', 'building', 'room', 'activeRepair'])
            ->orderBy('name');
    }

    /**
     * Ko dara: Pārbauda, vai ierīcei drīkst izveidot norakstīšanas pieteikumu.
     *
     * Kā strādā: Pārbauda, vai ierīce nav remontā un tai nav gaidoša norakstīšanas, remonta vai nodošanas pieteikuma; konflikta gadījumā izmet validācijas kļūdu.
     *
     * Kad pielietojas: Izsauc no: `store()`.
     */
    private function ensureDeviceCanAcceptWriteoffRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau notiek remonts ('.$this->repairStatusLabel($device->activeRepair?->status).'), tāpēc norakstīšanas pieteikumu veidot nevar.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums, tāpēc norakstīšanas pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums, tāpēc norakstīšanas pieteikumu veidot nevar.'],
            ]);
        }
    }

    /**
     * Ko dara: Sagatavo ierīču izvēlnes opcijas formai vai filtram.
     *
     * Kā strādā: Ierīču kolekciju pārvērš dropdown opcijās ar nosaukumu, kodu, tipu, modeli, atrašanās vietu un meklēšanas tekstu.
     *
     * Kad pielietojas: Izsauc no: saraksta datu sagatavošanas metodes.
     */
    private function deviceOptions($devices)
    {
        return collect($devices)->map(function (Device $device) {
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
                    $device->type?->type_name,
                    $device->manufacturer,
                    $device->model,
                    $device->room?->room_number,
                    $device->room?->room_name,
                    $device->building?->building_name,
                ])),
            ];
        })->values();
    }

    /**
     * Ko dara: Sagatavo ierīces noliktavas atrašanās vietas datus pēc norakstīšanas.
     *
     * Kā strādā: Atrod vai izveido norakstīto ierīču noliktavas telpu un sagatavo payload, kas noņem atbildīgo lietotāju un pārliek ierīci uz šo telpu.
     *
     * Kad pielietojas: Izsauc no: `review()`.
     */
    private function writeoffWarehousePayload(?int $preferredUserId = null): array
    {
        // Šī palīgmetode atdala norakstīšanas pieteikuma loģiku no noliktavas datu sagatavošanas.
        // Rezultātā review() metodē paliek skaidrs biznesa solis: nomainīt statusu un pārvietot ierīci.
        $warehouseRoom = $this->ensureWarehouseRoom($preferredUserId);

        return [
            'assigned_to_id' => null,
            'building_id' => $warehouseRoom->building_id,
            'room_id' => $warehouseRoom->id,
        ];
    }

    /**
     * Ko dara: Atrod vai izveido noklusēto noliktavas telpu norakstītām ierīcēm.
     *
     * Kā strādā: Meklē esošu noliktavas telpu pēc telpas nosaukuma/numura; ja tādas nav, izvēlas ēku un izveido jaunu noliktavas telpas ierakstu.
     *
     * Kad pielietojas: Izsauc no: `writeoffWarehousePayload()`.
     */
    private function ensureWarehouseRoom(?int $preferredUserId = null): Room
    {
        // Vispirms mēģinām izmantot jau esošu noliktavas telpu.
        // Meklēšana notiek vairākos laukos, jo reālā datubāzē "noliktava" var būt ierakstīta
        // kā telpas nosaukums, numurs vai piezīme.
        $warehouseRoom = Room::query()
            ->with('building')
            ->get()
            ->first(function (Room $room) {
                return $this->isWarehouseLabel($room->room_name)
                    || $this->isWarehouseLabel($room->room_number)
                    || $this->isWarehouseLabel($room->notes);
            });

        if ($warehouseRoom) {
            // Ja noliktava jau atrasta, jaunu telpu neveidojam, lai sistēmā nerastos dublikāti.
            return $warehouseRoom;
        }

        // Ja noliktavas telpas vēl nav, sistēma pati sagatavo minimālo atrašanās vietu.
        // Tas nozīmē, ka norakstīšana var strādāt arī tukšākā vai tikko uzstādītā datubāzē.
        $building = $this->preferredWarehouseBuilding();

        return Room::query()->create([
            'building_id' => $building->id,
            'floor_number' => 1,
            'room_number' => $this->nextWarehouseRoomNumber($building->id),
            'room_name' => WarehouseConfig::DEFAULT_ROOM_NAME,
            'user_id' => $preferredUserId,
            'department' => 'Inventārs',
            'notes' => 'Automātiski izveidota noklusētā noliktavas telpa.',
        ])->load('building');
    }

    /**
     * Ko dara: Atrod piemērotāko ēku noklusētās noliktavas telpas izveidei.
     *
     * Kā strādā: Priekšroku dod ēkai, kuras nosaukumā ir "ludz"; ja tādas nav, ņem pirmo ēku pēc nosaukuma vai izveido noklusēto noliktavas ēku.
     *
     * Kad pielietojas: Izsauc no: `ensureWarehouseRoom()`.
     */
    private function preferredWarehouseBuilding(): Building
    {
        $preferredBuilding = Building::query()
            ->orderBy('building_name')
            ->get()
            ->first(fn (Building $building) => $this->matchesPreferredBuildingName($building->building_name));

        if ($preferredBuilding) {
            return $preferredBuilding;
        }

        $existingBuilding = Building::query()->orderBy('building_name')->first();

        if ($existingBuilding) {
            return $existingBuilding;
        }

        return Building::query()->create([
            'building_name' => WarehouseConfig::DEFAULT_BUILDING_NAME,
            'city' => 'Ludza',
            'total_floors' => 1,
            'notes' => 'Automātiski izveidota noklusētā ēka noliktavas telpai.',
        ]);
    }

    /**
     * Ko dara: Aprēķina nākamo brīvo noliktavas telpas numuru ēkā.
     *
     * Kā strādā: Nolasa esošos telpu numurus izvēlētajā ēkā un izveido nākamo brīvo noliktavas numuru ar `NOL-` prefiksu.
     *
     * Kad pielietojas: Izsauc no: `ensureWarehouseRoom()`.
     */
    private function nextWarehouseRoomNumber(int $buildingId): string
    {
        $existingNumbers = Room::query()
            ->where('building_id', $buildingId)
            ->pluck('room_number')
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->all();

        $sequence = 1;

        do {
            $candidate = WarehouseConfig::DEFAULT_ROOM_NUMBER_PREFIX.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (in_array($candidate, $existingNumbers, true));

        return $candidate;
    }

    /**
     * Ko dara: Pārbauda, vai teksts norāda uz noliktavas telpu.
     *
     * Kā strādā: Normalizē tekstu uz mazajiem burtiem un pārbauda, vai tas satur noliktavai raksturīgu apzīmējumu.
     *
     * Kad pielietojas: Izsauc no: `ensureWarehouseRoom()`.
     */
    private function isWarehouseLabel(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'noliktav');
    }

    /**
     * Ko dara: Pārbauda, vai ēkas nosaukums atbilst vēlamajai noliktavas ēkai.
     *
     * Kā strādā: Normalizē ēkas nosaukumu un pārbauda, vai tas satur vēlamās ēkas atslēgvārdu "ludz".
     *
     * Kad pielietojas: Izsauc no: `preferredWarehouseBuilding()`.
     */
    private function matchesPreferredBuildingName(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'ludz');
    }

    /**
     * Ko dara: Sakārto saraksta filtru stāvokli, ieskaitot admina noklusēto "iesniegts".
     *
     * Kā strādā: Nolasa URL filtrus, vajadzības gadījumā uzliek vadītāja noklusēto "iesniegts" vai "šodien" filtru un normalizē statusu atlasi.
     *
     * Kad pielietojas: Norakstīšanas pieteikumu sarakstā, async tabulā un ātrajā meklēšanā.
     */
    private function normalizedIndexFilters(Request $request, array $availableStatuses, bool $canReview): array
    {
        $statusFilterTouched = $request->has('statuses_filter');
        $filtersCleared = $request->boolean('clear');
        $hasOtherFilters = $request->filled('q')
            || $request->filled('code')
            || $request->filled('device_id')
            || $request->filled('requester_id')
            || $request->filled('date_from')
            || $request->filled('date_to');
        $defaultRequestFilter = $canReview ? ($this->user()?->defaultRequestFilter() ?? User::REQUEST_FILTER_SUBMITTED) : User::REQUEST_FILTER_SUBMITTED;
        $shouldApplyDefault = $canReview && ! $filtersCleared && ! $hasOtherFilters && ! $statusFilterTouched;
        $defaultStatuses = $shouldApplyDefault && $defaultRequestFilter === User::REQUEST_FILTER_SUBMITTED
            ? [WriteoffRequest::STATUS_SUBMITTED]
            : [];
        $defaultDate = $shouldApplyDefault && $defaultRequestFilter === User::REQUEST_FILTER_TODAY
            ? now()->toDateString()
            : '';
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : $defaultStatuses))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        return [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'device_id' => ctype_digit((string) $request->query('device_id', '')) ? (int) $request->query('device_id') : null,
            'device_query' => trim((string) $request->query('device_query', '')),
            'requester_id' => ctype_digit((string) $request->query('requester_id', '')) ? (int) $request->query('requester_id') : null,
            'requester_query' => trim((string) $request->query('requester_query', '')),
            'date_from' => trim((string) $request->query('date_from', $defaultDate)),
            'date_to' => trim((string) $request->query('date_to', $defaultDate)),
            'statuses' => $selectedStatuses,
            'status_filter_touched' => $statusFilterTouched,
        ];
    }

    /**
     * Ko dara: Pielieto meklēšanu un filtrus pieteikumu vaicājumam.
     *
     * Kā strādā: Pielieto precīzu ierīces koda filtru, brīvo ierīces meklēšanu, ierīces/pieteicēja/datuma/statusa filtrus.
     *
     * Kad pielietojas: Kad norakstīšanas pieteikumu vaicājums jāsašaurina pēc lietotāja izvēlētajiem filtriem.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        $skipLookup = array_flip($skip);

        // Precīzais ierīces koda filtrs ļauj ātri atrast vienu norakstīšanas
        // pieteikumu pēc inventāra koda neatkarīgi no burtu reģistra.
        if (! isset($skipLookup['code']) && $filters['code'] !== '') {
            $code = mb_strtolower(trim($filters['code']));

            $query->whereHas('device', function (Builder $deviceQuery) use ($code) {
                $deviceQuery->whereRaw('LOWER(TRIM(code)) = ?', [$code]);
            });
        }

        // Brīvā meklēšana pārbauda ierīces laukus, jo norakstīšanas pieteikumi
        // skatā tiek identificēti pēc saistītās ierīces informācijas.
        if (! isset($skipLookup['q']) && $filters['q'] !== '') {
            $term = $filters['q'];

            $query->whereHas('device', function (Builder $deviceQuery) use ($term) {
                $deviceQuery->where(function (Builder $q) use ($term) {
                    $q->where('code', 'like', "%{$term}%")
                      ->orWhere('serial_number', 'like', "%{$term}%")
                      ->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('manufacturer', 'like', "%{$term}%")
                      ->orWhere('model', 'like', "%{$term}%");
                });
            });
        }

        if (! isset($skipLookup['device_id']) && filled($filters['device_id'])) {
            $query->where('writeoff_requests.device_id', $filters['device_id']);
        }

        if (! isset($skipLookup['requester_id']) && filled($filters['requester_id'])) {
            $query->where('writeoff_requests.responsible_user_id', $filters['requester_id']);
        }

        if (! isset($skipLookup['date_from']) && filled($filters['date_from'])) {
            $query->whereDate('writeoff_requests.created_at', '>=', $filters['date_from']);
        }

        if (! isset($skipLookup['date_to']) && filled($filters['date_to'])) {
            $query->whereDate('writeoff_requests.created_at', '<=', $filters['date_to']);
        }

        // Statusu filtru pielietojam tikai tad, ja lietotājs ir sašaurinājis
        // sarakstu līdz daļai no pieejamajiem statusiem.
        if (! isset($skipLookup['statuses'])) {
            $selectedStatuses = $filters['statuses'] ?? [];

            if ($selectedStatuses !== [] && count($selectedStatuses) < 3) {
                $query->whereIn('writeoff_requests.status', $selectedStatuses);
            }
        }

        return $query;
    }

    /**
     * Ko dara: Pielieto drošu kārtošanu pēc atļautajām kolonnām.
     *
     * Kā strādā: Pievieno kārtošanai vajadzīgos join uz ierīci un pieteicēju, pēc tam kārto pēc koda, nosaukuma, pieteicēja, statusa vai izveides datuma.
     *
     * Kad pielietojas: Norakstīšanas pieteikumu sarakstā un ātrajā meklēšanā, lai saglabātu vienādu rindas secību.
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        $query
            ->leftJoin('devices as sortable_devices', 'writeoff_requests.device_id', '=', 'sortable_devices.id')
            ->leftJoin('users as sortable_requesters', 'writeoff_requests.responsible_user_id', '=', 'sortable_requesters.id');

        switch ($sorting['sort']) {
            case 'code':
                $query->orderByRaw('LOWER(COALESCE(sortable_devices.code, "")) '.$sorting['direction']);
                break;
            case 'name':
                $query->orderByRaw('LOWER(COALESCE(sortable_devices.name, "")) '.$sorting['direction']);
                break;
            case 'requester':
                $query->orderByRaw('LOWER(COALESCE(sortable_requesters.full_name, "")) '.$sorting['direction']);
                break;
            case 'status':
                $query->orderByRaw("
                    CASE writeoff_requests.status
                        WHEN 'submitted' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                        ELSE 4
                    END {$sorting['direction']}
                ");
                break;
            case 'created_at':
            default:
                $query->orderBy('writeoff_requests.created_at', $sorting['direction']);
                break;
        }

        $query->orderBy('writeoff_requests.id', $sorting['direction'] === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Ko dara: Normalizē kārtošanas parametrus tabulas galvenei un toast paziņojumiem.
     *
     * Kā strādā: Pārbauda `sort` un `direction` query parametrus pret atļautajām vērtībām un pievieno latvisku label.
     *
     * Kad pielietojas: Pirms norakstīšanas pieteikumu saraksta kārtošanas un audita paziņojuma veidošanas.
     */
    private function normalizedSorting(Request $request): array
    {
        $sort = trim((string) $request->query('sort', 'created_at'));
        $direction = trim((string) $request->query('direction', 'desc'));

        if (! in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'created_at' ? 'desc' : 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $this->sortOptions()[$sort]['label'] ?? 'iesniegšanas datuma',
        ];
    }

    /**
     * Ko dara: Lietotāja paziņojumiem izmantojamās kārtošanas etiķetes.
     *
     * Kā strādā: Atgriež atļauto kārtošanas lauku label karti, ko izmanto UI un audita teksti.
     *
     * Kad pielietojas: Norakstīšanas pieteikumu tabulas galvenēs, aktīvā kārtojuma tekstā un auditā.
     */
    private function sortOptions(): array
    {
        return [
            'code' => ['label' => 'koda'],
            'name' => ['label' => 'nosaukuma'],
            'requester' => ['label' => 'pieteicēja'],
            'created_at' => ['label' => 'iesniegšanas datuma'],
            'status' => ['label' => 'statusa'],
        ];
    }

    /**
     * Ko dara: Reģistrē norakstīšanas pieteikumu saraksta filtrēšanu un kārtošanu audita žurnālā.
     *
     * Kā strādā: No filtriem izveido audita payload un pieraksta filtrēšanu/kārtošanu tikai tad, ja lietotājs patiešām mainījis saraksta atlasi vai secību.
     *
     * Kad pielietojas: Izsauc no: `index()`, `table()`.
     */
    private function auditWriteoffRequestListInteractions(Request $request, User $user, array $filters, array $sorting): void
    {
        $filterPayload = array_filter([
            'teksts' => $filters['q'] ?? '',
            'ierīce' => $filters['device_query'] ?? '',
            'pieteicējs' => $filters['requester_query'] ?? '',
            'no datuma' => $filters['date_from'] ?? '',
            'līdz datumam' => $filters['date_to'] ?? '',
            'statusi' => count($filters['statuses'] ?? []) > 0 && count($filters['statuses'] ?? []) < 3 ? ($filters['statuses'] ?? []) : [],
        ], fn (mixed $value) => $value !== null && $value !== '' && $value !== []);

        if ($filterPayload !== []) {
            AuditTrail::filter(
                $user,
                'WriteoffRequest',
                $filterPayload,
                'Filtrēti norakstīšanas pieteikumi: '.implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
                    if (is_array($value)) {
                        return $label.': '.implode(', ', $value);
                    }

                    return $label.': '.$value;
                })->all())
            );
        }

        if (($sorting['sort'] ?? 'created_at') !== 'created_at' || ($sorting['direction'] ?? 'desc') !== 'desc' || $request->has('sort')) {
            AuditTrail::sort(
                $user,
                'WriteoffRequest',
                $sorting['label'] ?? 'iesniegšanas datuma',
                $sorting['direction'] ?? 'desc',
                'Kārtoti norakstīšanas pieteikumi pēc '.($sorting['label'] ?? 'iesniegšanas datuma').' '.(($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }
    }

    /**
     * Ko dara: Sagatavo ierīču dropdown opcijas norakstīšanas pieteikumu filtram.
     *
     * Kā strādā: No pieteikumu kolekcijas paņem unikālās saistītās ierīces un pārvērš tās filtra dropdown opcijās.
     *
     * Kad pielietojas: Norakstīšanas pieteikumu ierīces filtra opciju sagatavošanā.
     */
    private function writeoffDeviceOptions($requests)
    {
        return collect($requests)
            ->pluck('device')
            ->filter()
            ->unique('id')
            ->sortBy(fn (Device $device) => mb_strtolower($device->name.' '.$device->code))
            ->values()
            ->map(function (Device $device) {
                return [
                    'value' => (string) $device->id,
                    'label' => $device->name.' ('.($device->code ?: 'bez koda').')',
                    'description' => collect([
                        $device->type?->type_name,
                        collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                    ])->filter()->implode(' | '),
                    'search' => implode(' ', array_filter([
                        $device->name,
                        $device->code,
                        $device->serial_number,
                        $device->manufacturer,
                        $device->model,
                        $device->type?->type_name,
                    ])),
                ];
            });
    }

    /**
     * Ko dara: Sagatavo pieteicēju dropdown opcijas norakstīšanas pieteikumu filtram.
     *
     * Kā strādā: No pieteikumu kolekcijas paņem unikālos pieteicējus un pārvērš tos dropdown opcijās ar amatu/e-pastu un meklēšanas tekstu.
     *
     * Kad pielietojas: Norakstīšanas pieteikumu pieteicēja filtra opciju sagatavošanā.
     */
    private function writeoffRequesterOptions($requests)
    {
        return collect($requests)
            ->pluck('responsibleUser')
            ->filter()
            ->unique('id')
            ->sortBy(fn (User $requester) => mb_strtolower($requester->full_name))
            ->values()
            ->map(fn (User $requester) => [
                'value' => (string) $requester->id,
                'label' => $requester->full_name,
                'description' => implode(' | ', array_filter([
                    $requester->job_title,
                    $requester->email,
                ])),
                'search' => implode(' ', array_filter([
                    $requester->full_name,
                    $requester->job_title,
                    $requester->email,
                ])),
            ]);
    }
}
