<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasRepairStatusLabels;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use App\Support\UserNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Ko dara: Pārvalda lietotāju remonta pieteikumus.
 *
 * Kā strādā: Ļauj lietotājam iesniegt remonta pieteikumu, administratoram to pārskatīt un apstiprināšanas gadījumā izveidot remonta ierakstu.
 *
 * Kad pielietojas: Kad darbinieks ziņo par ierīces problēmu vai administrators izskata remonta pieteikumu.
 */
class RepairRequestController extends Controller
{
    use HasRepairStatusLabels;

    // Atļautās remonta pieteikumu saraksta kārtošanas kolonnas.
    private const SORTABLE_COLUMNS = ['code', 'name', 'requester', 'created_at', 'status'];

    /**
     * Ko dara: Parāda remonta pieteikumu sarakstu ar lomas atkarīgu filtrēšanu un statusa kopsavilkumu.
     *
     * Kā strādā: Administrators redz visus pieteikumus. Parasts lietotājs redz tikai savus iesniegtos. Atspoguļo gaidošo, apstiprināto un noraidīto pieteikumu kopsavilkumu.
     *
     * Kad pielietojas: Izsaukšana: GET /repair-requests | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs navigē uz "Remonta pieteikumi" sadaļu sānjoslā.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->repairRequestsViewData($request, $user);

        AuditTrail::viewed($user, 'RepairRequest', null, 'Atvērts remonta pieteikumu saraksts.');
        $this->auditRepairRequestListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);

        return view('repair_requests.index', $viewData);
    }

    /**
     * Ko dara: Atgriež filtrētu remonta pieteikumu tabulu bez pilnas lapas pārlādēšanas (async).
     *
     * Kā strādā: Atjaunina tikai tabulas HTML fragmentu, kad tiek mainīti filtri vai kārtošana.
     *
     * Kad pielietojas: Izsaukšana: GET /repair-requests/table | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: JavaScript izsauc šo maršrutu, kad tiek mainīti filtri vai kārtošanas parametri.
     */
    public function table(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $viewData = $this->repairRequestsViewData($request, $user);
        $this->auditRepairRequestListInteractions($request, $user, $viewData['filters'], $viewData['sorting']);
        return view('repair_requests.index-table', [
            'requests' => $viewData['requests'],
            'canReview' => $viewData['canReview'],
            'sorting' => $viewData['sorting'],
            'sortOptions' => $viewData['sortOptions'],
            'statusLabels' => $viewData['statusLabels'],
            'sortDirectionLabels' => $viewData['sortDirectionLabels'],
        ]);
    }

    /**
     * Ko dara: Kopīga metode remonta pieteikumu datu sagatavošanai.
     *
     * Kā strādā: Normalizē filtrus un kārtošanu, ierobežo redzamību pēc lomas, sagatavo sarakstu, kopsavilkumu, filtru opcijas un modālā loga datus.
     *
     * Kad pielietojas: Remonta pieteikumu pilnās lapas un async tabulas datu sagatavošanā.
     */
    private function repairRequestsViewData(Request $request, $user): array
    {
        // Ātrā meklēšana atkārto tās pašas tiesības un filtrus, kas sarakstā:
        // vadītājs redz visus, parasts lietotājs tikai savus pieteikumus.
        $canReview = $user->canManageRequests();
        $availableStatuses = [
            RepairRequest::STATUS_SUBMITTED,
            RepairRequest::STATUS_APPROVED,
            RepairRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        // Ja legacy instalācijā remonta pieteikumu tabula vēl nav izveidota,
        // skats saņem tukšu, bet pilnu datu struktūru un netiek apturēts ar kļūdu.
        if (! $this->featureTableExists('repair_requests')) {
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
                'featureMessage' => 'Tabula repair_requests šobrīd nav pieejama.',
                'sortDirectionLabels' => ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'],
            ];
        }

        // Bāzes vaicājums nosaka redzamību: vadītājs redz visus pieteikumus,
        // bet parasts lietotājs tikai tos, kurus pats iesniedza.
        $baseQuery = RepairRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        // Filtru izvēlnes veidojam no tās pašas redzamās datu kopas, izlaižot
        // pašreizējo filtru, lai lietotājs varētu pārslēgt vērtības.
        $deviceOptions = $this->repairDeviceOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id', 'code']))
                ->with(['device.type'])
                ->get()
        );

        $requesterOptions = $this->repairRequesterOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id', 'code']))
                ->with('responsibleUser')
                ->get()
                ->pluck('responsibleUser')
                ->filter()
                ->unique('id')
                ->values()
        );

        $createDeviceOptions = ! $canReview
            ? $this->deviceOptions($this->availableDevicesForUser($user)->get())
            : collect();

        // Galvenais saraksta vaicājums ielādē ierīci, iesniedzēju un izskatītāju,
        // lai skatā nav jāveic papildu pieprasījumi katrai rindai.
        // Ielādējam filtrēto sarakstu ar ierīces kodu, jo atrastā pozīcija
        // nosaka, kuru rindu frontendam jāizceļ.
        $requestsQuery = (clone $baseQuery)
            ->with(['device.type', 'responsibleUser', 'reviewedBy'])
            ->select('repair_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();

        // Vienā masīvā atdodam tabulas rindas, kopsavilkumu, filtrus un modālo
        // formu opcijas, jo to visu izmanto viens remonta pieteikumu skats.
        return [
            'requests' => $requests,
            'requestSummary' => [
                'total' => (clone $baseQuery)->count(),
                'submitted' => (clone $baseQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count(),
                'approved' => (clone $baseQuery)->where('status', RepairRequest::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', RepairRequest::STATUS_REJECTED)->count(),
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
                ? RepairRequest::query()
                    ->with('device')
                    ->whereKey((int) $request->query('modal_request'))
                    ->where('responsible_user_id', $user->id)
                    ->where('status', RepairRequest::STATUS_SUBMITTED)
                    ->first()
                : null,
            'sortDirectionLabels' => ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'],
        ];
    }

    /**
     * Ko dara: Atrod remonta pieteikumu pēc saistītās ierīces koda filtrētajā sarakstā.
     *
     * Kā strādā: Atkārto saraksta filtrus, kārtošanu un lomu redzamību, ielādē ierīces kodus un atgriež atrastās rindas highlight ID.
     *
     * Kad pielietojas: Kad JavaScript ātrajā meklēšanā remonta pieteikumu sarakstā meklē pēc ierīces koda.
     */
    public function findByCode(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'RepairRequest', $code, 'Meklēts remonta pieteikums pēc ierīces koda: '.$code);

        $canReview = $user->canManageRequests();
        $availableStatuses = [
            RepairRequest::STATUS_SUBMITTED,
            RepairRequest::STATUS_APPROVED,
            RepairRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        $baseQuery = RepairRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $requestsQuery = (clone $baseQuery)
            ->with('device:id,code')
            ->select('repair_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters, ['request_id']);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();
        $needle = mb_strtolower(trim($code));
        $foundIndex = null;

        // Meklējam precīzu ierīces koda sakritību, lai daļēja ievade neizceltu
        // citu remonta pieteikumu.
        foreach ($requests as $index => $repairRequest) {
            $deviceCode = mb_strtolower(trim((string) ($repairRequest->device?->code ?? '')));
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
            'highlight_id' => 'repair-request-'.$requests->values()[$foundIndex]->id,
        ]);
    }


    /**
     * Ko dara: Saglabā jaunu remonta pieteikumu.
     *
     * Kā strādā: Parastam lietotājam validē izvēlēto ierīci un aprakstu, pārbauda konfliktējošus pieteikumus/remontus un izveido `submitted` remonta pieteikumu.
     *
     * Kad pielietojas: Kad darbinieks savai aktīvajai ierīcei iesniedz remonta pieteikumu.
     */
    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('repair_requests')) {
            return redirect()->route('repair-requests.index')->with('error', 'Remonta pieteikumus šobrīd nevar saglabāt, jo tabula repair_requests nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvēlies ierīci, kurai piesaki remontu.',
            'description.required' => 'Apraksti remonta problēmu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt remontu tikai savai piesaistītai ierīcei.'],
            ]);
        }

        $this->ensureDeviceCanAcceptRepairRequest($device);

        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => $validated['description'],
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $repairRequest);
        AuditTrail::submit($user->id, $repairRequest, 'Iesniegts remonta pieteikums: '.AuditTrail::labelFor($repairRequest));

        return redirect()->route('repair-requests.index')->with('success', 'Remonta pieteikums nosūtīts izskatīšanai');
    }

    /**
     * Ko dara: Administratora lēmums par remonta pieteikumu.
     *
     * Kā strādā: Vadītājs validē lēmumu, transakcijā bloķē pieteikumu, apstiprināšanas gadījumā izveido remonta ierakstu un atjaunina ierīces statusu.
     *
     * Kad pielietojas: Kad administrators vai IT vadītājs apstiprina vai noraida iesniegtu remonta pieteikumu.
     */
    public function review(Request $request, RepairRequest $repairRequest)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('repair_requests')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Remonta pieteikumu tabula šobrīd nav pieejama.'], 503);
            }

            return back()->with('error', 'Remonta pieteikumu tabula šobrīd nav pieejama.');
        }

        if ($repairRequest->status !== RepairRequest::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Šis pieteikums jau ir izskatīts.'], 409);
            }

            return back()->with('error', 'Šis pieteikums jau ir izskatīts.');
        }

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED])],
        ], [
            'status.required' => 'Izvēlies lēmumu remonta pieteikumam.',
        ]);

        $repairRequest->loadMissing(['device', 'responsibleUser']);
        $before = $repairRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        $payload = [
            'status' => $validated['status'],
            'reviewed_by_user_id' => $manager->id,
            'review_notes' => null,
        ];

        $createdRepair = null;

        // Remonta pieteikuma apstiprināšana vienlaikus maina pieteikumu, ierīci un izveido remonta ierakstu,
        // tāpēc visas darbības notiek vienā transakcijā, lai datubāzē nepaliktu pusizpildīts stāvoklis.
        DB::transaction(function () use ($validated, $repairRequest, $manager, &$payload, &$createdRepair) {
            if ($validated['status'] === 'approved') {
                // Ierīci bloķējam līdz transakcijas beigām, lai divi administratori vienlaikus
                // nevarētu apstiprināt divus remontus vienai un tai pašai ierīcei.
                $device = $repairRequest->device()->lockForUpdate()->first();

                if (! $device || $device->status === Device::STATUS_WRITEOFF) {
                    throw ValidationException::withMessages([
                        'status' => ['Pieteikumu nevar apstiprināt, jo ierīce vairs nav pieejama remontam.'],
                    ]);
                }

                if ($device->repairs()->whereIn('status', ['waiting', 'in-progress'])->exists()) {
                    throw ValidationException::withMessages([
                        'status' => ['Šai ierīcei jau ir aktīvs remonta ieraksts.'],
                    ]);
                }

                // Apstiprināts pieteikums automātiski kļūst par reālu remonta ierakstu.
                // Sākuma statuss ir "waiting", jo remonta darbs vēl nav faktiski sākts.
                $createdRepair = $this->createRepairRecord([
                    'device_id' => $repairRequest->device_id,
                    'issue_reported_by' => $repairRequest->responsible_user_id,
                    'accepted_by' => $manager->id,
                    'description' => $repairRequest->description,
                    'status' => 'waiting',
                    'repair_type' => 'internal',
                    'priority' => 'medium',
                    'start_date' => null,
                    'end_date' => null,
                    'cost' => null,
                    'vendor_name' => null,
                    'vendor_contact' => null,
                    'invoice_number' => null,
                    'request_id' => $repairRequest->id,
                ]);

                // Tiklīdz remonta ieraksts ir izveidots, ierīce vairs nav brīvi lietojama
                // un sarakstos tiek rādīta kā remontā esoša.
                $device->forceFill(['status' => 'repair'])->save();

                if (array_key_exists('repair_id', $repairRequest->getAttributes())) {
                    $payload['repair_id'] = $createdRepair->id;
                }
            }

            $repairRequest->update($payload);
        });

        $after = $repairRequest->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($manager->id, $repairRequest, $before, $after);
        if ($validated['status'] === RepairRequest::STATUS_APPROVED) {
            AuditTrail::approve($manager->id, $repairRequest, 'Apstiprināts remonta pieteikums: '.AuditTrail::labelFor($repairRequest));
        } else {
            AuditTrail::reject($manager->id, $repairRequest, null, 'Noraidīts remonta pieteikums: '.AuditTrail::labelFor($repairRequest));
        }

        // Pēc administratora lēmuma pieteikuma autoram saglabājam personīgo paziņojumu.
        // To vēlāk nolasa LiveNotificationController un parāda toastā / paziņojumu centrā.
        app(UserNotifier::class)->requestReviewed($repairRequest->fresh(['device', 'responsibleUser']), $validated['status']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Remonta pieteikums izskatīts',
                'status' => $validated['status'],
                'request_id' => $repairRequest->id,
                'repair_edit_url' => $createdRepair
                    ? route('repairs.index', ['repair_modal' => 'edit', 'modal_repair' => $createdRepair->id])
                    : null,
            ]);
        }

        if ($validated['status'] === RepairRequest::STATUS_APPROVED && $createdRepair) {
            return redirect()->route('repairs.index', [
                'repair_modal' => 'edit',
                'modal_repair' => $createdRepair->id,
            ])
                ->with('success', 'Remonta pieteikums apstiprināts un atvērts remonta ieraksts.');
        }

        return back()->with('success', 'Remonta pieteikums izskatīts');
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
     * Ko dara: Pārbauda, vai ierīcei drīkst izveidot remonta pieteikumu.
     *
     * Kā strādā: Pārbauda, vai ierīce nav remontā un tai nav gaidoša remonta, norakstīšanas vai nodošanas pieteikuma; konflikta gadījumā izmet validācijas kļūdu.
     *
     * Kad pielietojas: Izsauc no: `store()`.
     */
    private function ensureDeviceCanAcceptRepairRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau notiek remonts ('.$this->repairStatusLabel($device->activeRepair?->status).'), tāpēc jaunu remonta pieteikumu veidot nevar.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums, tāpēc remonta pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums, tāpēc remonta pieteikumu veidot nevar.'],
            ]);
        }
    }

    /**
     * Ko dara: Sakārto saraksta filtru stāvokli, ieskaitot admina noklusēto "iesniegts".
     *
     * Kā strādā: Nolasa URL filtrus, vajadzības gadījumā uzliek vadītāja noklusēto "iesniegts" vai "šodien" filtru un normalizē statusu atlasi.
     *
     * Kad pielietojas: Remonta pieteikumu sarakstā, async tabulā un ātrajā meklēšanā.
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
            ? [RepairRequest::STATUS_SUBMITTED]
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
            'request_id' => ctype_digit((string) $request->query('request_id', '')) ? (int) $request->query('request_id') : null,
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
     * Kā strādā: Pielieto precīzu ierīces koda filtru, brīvo ierīces meklēšanu, pieteikuma/ierīces/pieteicēja/datuma/statusa filtrus.
     *
     * Kad pielietojas: Kad remonta pieteikumu vaicājums jāsašaurina pēc lietotāja izvēlētajiem filtriem.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        $skipLookup = array_flip($skip);

        // Precīzais ierīces koda filtrs palīdz atrast vienu konkrētu pieteikumu
        // pēc koda, ignorējot lielo/mazo burtu atšķirības.
        if (! isset($skipLookup['code']) && $filters['code'] !== '') {
            $code = mb_strtolower(trim($filters['code']));

            $query->whereHas('device', function (Builder $deviceQuery) use ($code) {
                $deviceQuery->whereRaw('LOWER(TRIM(code)) = ?', [$code]);
            });
        }

        // Brīvā meklēšana iet cauri galvenajiem ierīces laukiem, jo pieteikumu
        // sarakstā lietotājs bieži meklē pēc ierīces, nevis paša pieteikuma ID.
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

        // Modālie skati var padot konkrētu pieteikuma ID, lai saraksts
        // nofiltrētos līdz vienai rediģējamai rindai.
        if (! isset($skipLookup['request_id']) && filled($filters['request_id'])) {
            $query->whereKey($filters['request_id']);
        }

        if (! isset($skipLookup['device_id']) && filled($filters['device_id'])) {
            $query->where('repair_requests.device_id', $filters['device_id']);
        }

        if (! isset($skipLookup['requester_id']) && filled($filters['requester_id'])) {
            $query->where('repair_requests.responsible_user_id', $filters['requester_id']);
        }

        if (! isset($skipLookup['date_from']) && filled($filters['date_from'])) {
            $query->whereDate('repair_requests.created_at', '>=', $filters['date_from']);
        }

        if (! isset($skipLookup['date_to']) && filled($filters['date_to'])) {
            $query->whereDate('repair_requests.created_at', '<=', $filters['date_to']);
        }

        // Statusa WHERE IN pievienojam tikai tad, ja lietotājs nav atstājis
        // visus statusus redzamus.
        if (! isset($skipLookup['statuses'])) {
            $selectedStatuses = $filters['statuses'] ?? [];

            if ($selectedStatuses !== [] && count($selectedStatuses) < 3) {
                $query->whereIn('repair_requests.status', $selectedStatuses);
            }
        }

        return $query;
    }

    /**
     * Ko dara: Pielieto drošu kārtošanu pēc atļautajām kolonnām.
     *
     * Kā strādā: Pievieno kārtošanai vajadzīgos join uz ierīci un pieteicēju, pēc tam kārto pēc koda, nosaukuma, pieteicēja, statusa vai izveides datuma.
     *
     * Kad pielietojas: Remonta pieteikumu sarakstā un ātrajā meklēšanā, lai saglabātu vienādu rindas secību.
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        $query
            ->leftJoin('devices as sortable_devices', 'repair_requests.device_id', '=', 'sortable_devices.id')
            ->leftJoin('users as sortable_requesters', 'repair_requests.responsible_user_id', '=', 'sortable_requesters.id');

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
                    CASE repair_requests.status
                        WHEN 'submitted' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                        ELSE 4
                    END {$sorting['direction']}
                ");
                break;
            case 'created_at':
            default:
                $query->orderBy('repair_requests.created_at', $sorting['direction']);
                break;
        }

        $query->orderBy('repair_requests.id', $sorting['direction'] === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Ko dara: Normalizē kārtošanas parametrus tabulas galvenei un toast paziņojumiem.
     *
     * Kā strādā: Pārbauda `sort` un `direction` query parametrus pret atļautajām vērtībām un pievieno latvisku label.
     *
     * Kad pielietojas: Pirms remonta pieteikumu saraksta kārtošanas un audita paziņojuma veidošanas.
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
     * Kad pielietojas: Remonta pieteikumu tabulas galvenēs, aktīvā kārtojuma tekstā un auditā.
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
     * Ko dara: Reģistrē remonta pieteikumu saraksta filtrēšanu un kārtošanu audita žurnālā.
     *
     * Kā strādā: No filtriem izveido audita payload un pieraksta filtrēšanu/kārtošanu tikai tad, ja lietotājs patiešām mainījis saraksta atlasi vai secību.
     *
     * Kad pielietojas: Izsauc no: `index()`, `table()`.
     */
    private function auditRepairRequestListInteractions(Request $request, User $user, array $filters, array $sorting): void
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
                'RepairRequest',
                $filterPayload,
                'Filtrēti remonta pieteikumi: '.implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
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
                'RepairRequest',
                $sorting['label'] ?? 'iesniegšanas datuma',
                $sorting['direction'] ?? 'desc',
                'Kārtoti remonta pieteikumi pēc '.($sorting['label'] ?? 'iesniegšanas datuma').' '.(($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }
    }

    /**
     * Ko dara: Sagatavo ierīču dropdown opcijas remonta pieteikumu filtram.
     *
     * Kā strādā: No pieteikumu kolekcijas paņem unikālās saistītās ierīces un pārvērš tās filtra dropdown opcijās.
     *
     * Kad pielietojas: Remonta pieteikumu ierīces filtra opciju sagatavošanā.
     */
    private function repairDeviceOptions($requests)
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
     * Ko dara: Sagatavo pieteicēju dropdown opcijas remonta pieteikumu filtram.
     *
     * Kā strādā: No pieteikumu kolekcijas paņem unikālos pieteicējus un pārvērš tos dropdown opcijās ar amatu/e-pastu un meklēšanas tekstu.
     *
     * Kad pielietojas: Remonta pieteikumu pieteicēja filtra opciju sagatavošanā.
     */
    private function repairRequesterOptions($requests)
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
