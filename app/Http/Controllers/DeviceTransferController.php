<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasRepairStatusLabels;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
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
 * Ko dara: Pārvalda ierīču nodošanas pieteikumus starp lietotājiem.
 *
 * Kā strādā: Ļauj izveidot nodošanas pieteikumu, parāda sarakstu ar filtriem, apstrādā saņēmēja lēmumu un sagatavo nodošanas aktu.
 *
 * Kad pielietojas: Kad lietotājs vēlas nodot sev piešķirtu ierīci citam lietotājam vai saņēmējs apstiprina/noraida nodošanu.
 */
class DeviceTransferController extends Controller
{
    use HasRepairStatusLabels;

    // Atļautās ierīču nodošanas pieteikumu saraksta kārtošanas kolonnas.
    private const SORTABLE_COLUMNS = ['code', 'name', 'requester', 'recipient', 'created_at', 'status'];

    /**
     * Ko dara: Parāda ierīču nodošanas pieteikumu sarakstu ar lomas atkarīgu filtrēšanu.
     *
     * Kā strādā: Administrators redz visus pieteikumus. Parasts lietotājs redz tikai savus iesniegtos un saņemtos pieteikumus. Atspoguļo gaidošo, apstiprināto un noraidīto pieteikumu kopsavilkumu.
     *
     * Kad pielietojas: Izsaukšana: GET /device-transfers | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs navigē uz "Ierīču nodošana" sadaļu vai atgriežas no pieteikuma skata.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        // Saglabājam tos pašus filtrus un lomu ierobežojumus, kas darbojas
        // nodošanas pieteikumu sarakstā, lai AJAX meklēšana neatklātu svešus datus.
        $canManageTransfers = $user->canManageRequests();
        $availableStatuses = [
            DeviceTransfer::STATUS_SUBMITTED,
            DeviceTransfer::STATUS_APPROVED,
            DeviceTransfer::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses);
        $sorting = $this->normalizedSorting($request);

        // Pirms saglabāšanas pārbaudām, vai funkcionalitātes tabula vispār ir
        // pieejama, jo vecākā instalācijā migrācijas var vēl nebūt palaistas.
        if (! $this->featureTableExists('device_transfers')) {
            return view('device_transfers.index', [
                'transfers' => collect(),
                'transferSummary' => [
                    'total' => 0,
                    'submitted' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'isAdmin' => $canManageTransfers,
                'sorting' => $sorting,
                'sortOptions' => $this->sortOptions(),
                'deviceOptions' => collect(),
                'createDeviceOptions' => collect(),
                'requesterOptions' => collect(),
                'recipientOptions' => collect(),
                'createRecipientOptions' => collect(),
                'selectedEditableRequest' => null,
                'currentUserId' => $user->id,
                'incomingPendingCount' => 0,
                'featureMessage' => 'Tabula device_transfers šobrīd nav pieejama.',
            ]);
        }

        // Parastam lietotājam redzamība ir sašaurināta uz viņa iesniegtajiem vai saņemtajiem pieteikumiem.
        // Administrators redz visu, tāpēc viņam šis where bloks netiek pielikts.
        $baseQuery = DeviceTransfer::query()
            ->when(! $canManageTransfers, function (Builder $query) use ($user, $filters) {
                $query->where(function (Builder $builder) use ($user, $filters) {
                    if ($filters['incoming']) {
                        $builder->where('transfered_to_id', $user->id);
                    } else {
                        $builder->where('responsible_user_id', $user->id)
                            ->orWhere('transfered_to_id', $user->id);
                    }
                });
            });

        // Šis skaitītājs vajadzīgs lietotāja saskarnē, lai atsevišķi izceltu ienākošos
        // pieteikumus, par kuriem tieši šim lietotājam jāpieņem lēmums.
        $incomingPendingCount = ! $canManageTransfers
            ? (clone $baseQuery)
                ->where('transfered_to_id', $user->id)
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->count()
            : 0;

        // Filtru izvēlnes veidojam no jau redzamās datu kopas, lai lietotājs neredzētu
        // ierīces vai personas, kas viņam pēc lomas nav pieejamas.
        $deviceOptions = $this->transferDeviceOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id', 'code']))
                ->with(['device.type'])
                ->get()
        );

        $requesterOptions = $this->transferUserOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id', 'code']))
                ->with('responsibleUser')
                ->get()
                ->pluck('responsibleUser')
                ->filter()
                ->unique('id')
                ->values()
        );

        $recipientOptions = $this->transferUserOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['recipient_id', 'code']))
                ->with('transferTo')
                ->get()
                ->pluck('transferTo')
                ->filter()
                ->unique('id')
                ->reject(fn (User $recipient) => filled($filters['requester_id']) && $recipient->id === $filters['requester_id'])
                ->values()
        );

        $createDeviceOptions = ! $canManageTransfers
            ? $this->deviceOptions($this->availableDevicesForUser($user)->get())
            : collect();
        $createRecipientOptions = $this->recipientOptions(
            User::active()
                ->whereKeyNot($user->id)
                ->orderBy('full_name')
                ->get()
        );

        // Ielādējam filtrēto sarakstu ar ierīces kodu tādā pašā secībā, kā skatā,
        // jo pēc atrastā indeksa tiek aprēķināta tabulas lapa un izcelšanas ID.
        $transfersQuery = (clone $baseQuery)
            ->with(['device.type', 'responsibleUser', 'transferTo', 'reviewedBy'])
            ->select('device_transfers.*');

        $this->applyIndexFilters($transfersQuery, $filters);
        $this->applySorting($transfersQuery, $sorting);

        $transfers = $transfersQuery->get();

        AuditTrail::viewed($user, 'DeviceTransfer', null, 'Atvērts ierīču nodošanas pieteikumu saraksts.');
        $this->auditDeviceTransferListInteractions($request, $user, $filters, $sorting);

        return view('device_transfers.index', [
            'transfers' => $transfers,
            'transferSummary' => [
                'total' => (clone $baseQuery)->count(),
                'submitted' => (clone $baseQuery)->where('status', DeviceTransfer::STATUS_SUBMITTED)->count(),
                'approved' => (clone $baseQuery)->where('status', DeviceTransfer::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', DeviceTransfer::STATUS_REJECTED)->count(),
            ],
            'filters' => $filters,
            'statuses' => $availableStatuses,
            'statusLabels' => $this->requestStatusLabels(),
            'isAdmin' => $canManageTransfers,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'deviceOptions' => $deviceOptions,
            'createDeviceOptions' => $createDeviceOptions,
            'requesterOptions' => $requesterOptions,
            'recipientOptions' => $recipientOptions,
            'createRecipientOptions' => $createRecipientOptions,
            'selectedEditableRequest' => ! $canManageTransfers && ctype_digit((string) $request->query('modal_request'))
                ? DeviceTransfer::query()
                    ->with('device')
                    ->whereKey((int) $request->query('modal_request'))
                    ->where('responsible_user_id', $user->id)
                    ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                    ->first()
                : null,
            'currentUserId' => $user->id,
            'incomingPendingCount' => $incomingPendingCount,
        ]);
    }

    /**
     * Ko dara: Atrod nodošanas pieteikumu pēc saistītās ierīces koda filtrētajā sarakstā.
     *
     * Kā strādā: Meklēšana ņem vērā aktīvos filtrus un atgriež lapas numuru un elementa ID. Nepradīmētiem lietotājiem meklēšana tiek ierobežota tikai uz viņu pieteikumiem.
     *
     * Kad pielietojas: Izsaukšana: GET /device-transfers/find-by-code | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: JavaScript izsauc šo metodi, kad lietotājs raksta mājēšanas lodziņā.
     */
    public function findByCode(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'DeviceTransfer', $code, 'Meklēts ierīces nodošanas pieteikums pēc ierīces koda: '.$code);

        $canManageTransfers = $user->canManageRequests();
        $availableStatuses = [
            DeviceTransfer::STATUS_SUBMITTED,
            DeviceTransfer::STATUS_APPROVED,
            DeviceTransfer::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses);
        $sorting = $this->normalizedSorting($request);

        $baseQuery = DeviceTransfer::query()
            ->when(! $canManageTransfers, function (Builder $query) use ($user, $filters) {
                $query->where(function (Builder $builder) use ($user, $filters) {
                    if ($filters['incoming']) {
                        $builder->where('transfered_to_id', $user->id);
                    } else {
                        $builder->where('responsible_user_id', $user->id)
                            ->orWhere('transfered_to_id', $user->id);
                    }
                });
            });

        $transfersQuery = (clone $baseQuery)
            ->with('device:id,code')
            ->select('device_transfers.*');

        $this->applyIndexFilters($transfersQuery, $filters);
        $this->applySorting($transfersQuery, $sorting);

        $transfers = $transfersQuery->get();
        $needle = mb_strtolower($code);
        $foundIndex = null;

        // Precīzi salīdzinām normalizētu ierīces kodu, lai daļēja sakritība
        // neizceltu nepareizu nodošanas pieteikumu.
        foreach ($transfers as $index => $transfer) {
            $deviceCode = mb_strtolower(trim((string) ($transfer->device?->code ?? '')));
            if ($deviceCode === $needle) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === null) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        // Atbilde pasaka frontendam, kuru rindu izcelt pēc pāriešanas uz sarakstu.
        return response()->json([
            'found' => true,
            'page' => 1,
            'term' => $code,
            'highlight_id' => 'device-transfer-'.$transfers->values()[$foundIndex]->id,
        ]);
    }


    /**
     * Ko dara: Saglabā jaunu ierīces nodošanas pieprasījumu.
     *
     * Kā strādā: Validē ierīci, saņēmēju un iemeslu, pārbauda ierīces pieejamību, nosaka pašreizējo atbildīgo un izveido pieteikumu statusā `submitted`.
     *
     * Kad pielietojas: Kad lietotājs vai vadītājs iesniedz jaunu ierīces nodošanas pieteikumu.
     */
    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('device_transfers')) {
            return redirect()->route('device-transfers.index')->with('error', 'Ierīču nodošanas pieteikumus šobrīd nevar saglabāt, jo tabula device_transfers nav pieejama.');
        }

        try {
            // Validācija nodrošina, ka ir izvēlēta ierīce, cits saņēmējs un norādīts
            // nodošanas iemesls, ko vēlāk redz gan vadītājs, gan saņēmējs.
            $validated = $this->validateInput($request, [
                'device_id' => ['required', 'exists:devices,id'],
                'transfered_to_id' => ['required', 'exists:users,id', Rule::notIn([$user->id])],
                'transfer_reason' => ['required', 'string', 'min:10', 'max:2000'],
            ], [
                'device_id.required' => 'Izvēlies ierīci, kuru vēlies nodot.',
                'transfered_to_id.required' => 'Izvēlies saņēmēju.',
                'transfer_reason.required' => 'Apraksti nodošanas iemeslu.',
                'transfer_reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
                'transfer_reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
            ]);

            // Ierīci meklējam tikai starp lietotājam pieejamajām aktīvajām ierīcēm,
            // lai nevarētu pieteikt svešas vai norakstītas ierīces nodošanu.
            $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
            if (! $device) {
                throw ValidationException::withMessages([
                    'device_id' => [$user->canManageRequests()
                        ? 'Admins var pieteikt nodošanu tikai aktīvai un piešķirtai ierīcei.'
                        : 'Vari pieteikt nodošanu tikai savai piesaistītai ierīcei.'],
                ]);
            }

            // Nodošanas pieteikuma atbildīgais ir pašreizējais īpašnieks; vadītāja
            // gadījumā to nosakām pēc pašas ierīces piesaistes.
            $ownerId = $this->transferOwnerId($user, $device);
            if (! $ownerId) {
                throw ValidationException::withMessages([
                    'device_id' => ['Izvēlētajai ierīcei nav piešķirta atbildīgā persona.'],
                ]);
            }

            if ((int) $validated['transfered_to_id'] === (int) $ownerId) {
                throw ValidationException::withMessages([
                    'transfered_to_id' => ['Saņēmējs nevar būt tas pats lietotājs, kam ierīce jau ir piešķirta.'],
                ]);
            }

            // Pārliecināmies, ka ierīcei jau nav cita aktīva pieprasījuma, kas
            // konfliktētu ar nodošanu.
            $this->ensureDeviceCanAcceptTransferRequest($device);
        } catch (ValidationException $exception) {
            return $this->redirectRequestValidationException($request, $exception, 'device-transfers.index', 'transfer');
        }

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $ownerId,
            'transfered_to_id' => $validated['transfered_to_id'],
            'transfer_reason' => $validated['transfer_reason'],
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $transfer);
        AuditTrail::submit($user->id, $transfer, 'Iesniegts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($transfer));

        return redirect()->route('device-transfers.index')->with('success', 'Ierīces nodošanas pieteikums izveidots');
    }

    /**
     * Ko dara: Saņēmēja lēmums par ierīces pieņemšanu vai noraidīšanu.
     *
     * Kā strādā: Pārbauda, vai pieteikums vēl ir iesniegts, vai lietotājs drīkst pieņemt lēmumu, un transakcijā apstiprina vai noraida nodošanu.
     *
     * Kad pielietojas: Kad saņēmējs vai vadītājs izskata konkrētu nodošanas pieteikumu.
     */
    public function review(Request $request, DeviceTransfer $deviceTransfer)
    {
        $reviewer = $this->user();
        abort_unless($reviewer, 403);

        if (! $this->featureTableExists('device_transfers')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ierīču nodošanas pieteikumu tabula šobrīd nav pieejama.'], 503);
            }

            return back()->with('error', 'Ierīču nodošanas pieteikumu tabula šobrīd nav pieejama.');
        }

        // Nodošanu drīkst izskatīt tikai saņēmējs, kuram ierīce tiek nodota.
        // Tas neļauj citam lietotājam apstiprināt svešu pieteikumu un pārrakstīt ierīci uz sevi.
        $canReview = (int) $deviceTransfer->transfered_to_id === (int) $reviewer->id;
        abort_unless($canReview, 403);

        // Pieteikums ir vienreizējs lēmums. Pēc apstiprināšanas vai noraidīšanas
        // to vairs nedrīkst apstrādāt atkārtoti, jo tas mainītu ierīces atbildīgo.
        if ($deviceTransfer->status !== DeviceTransfer::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Šis pieteikums jau ir izskatīts.'], 409);
            }

            return back()->with('error', 'Šis pieteikums jau ir izskatīts.');
        }

        // Ja forma nesūta telpas izvēli, noklusēti saglabājam esošo telpu.
        // Tas ir svarīgi nodošanām, kur mainās tikai atbildīgais lietotājs, nevis ierīces atrašanās vieta.
        $keepCurrentRoom = ! $request->exists('keep_current_room') || $request->boolean('keep_current_room');

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED])],
            'keep_current_room' => ['nullable', 'boolean'],
            'room_id' => [
                Rule::requiredIf(fn () => $request->input('status') === DeviceTransfer::STATUS_APPROVED && ! $keepCurrentRoom),
                'nullable',
                'exists:rooms,id',
            ],
        ], [
            'status.required' => 'Izvēlies lēmumu nodošanas pieteikumam.',
            'room_id.required' => 'Izvēlies telpu, uz kuru novietot ierīci.',
        ]);

        $before = $deviceTransfer->only(['status', 'reviewed_by_user_id', 'review_notes']);

        // Saglabājam iepriekšējo atbildīgo, lai pēc apstiprinātas nodošanas
        // jaunajam lietotājam paziņojumu sūtītu tikai tad, ja atbildīgais tiešām mainījās.
        $previousAssignedUserId = null;

        // Nodošanas lēmums un ierīces atbildīgā maiņa ir viena biznesa darbība.
        // Transakcija pasargā no situācijas, kur pieteikums ir apstiprināts, bet ierīce vēl nav pārrakstīta.
        DB::transaction(function () use ($validated, $deviceTransfer, $reviewer, $keepCurrentRoom, &$previousAssignedUserId) {
            // Vispirms saglabājam pašu lēmumu par pieteikumu.
            // Ja lēmums ir noraidījums, tālāk ierīces dati netiek mainīti.
            $deviceTransfer->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $reviewer->id,
                'review_notes' => null,
            ]);

            if ($validated['status'] !== 'approved') {
                return;
            }

            // Bloķējam ierīci, jo nodošanas laikā mainās tās atbildīgais lietotājs un reizēm arī telpa.
            // Tas neļauj paralēli izpildīt citu statusa vai atrašanās vietas maiņu tai pašai ierīcei.
            $device = $deviceTransfer->device()->lockForUpdate()->first();

            if (! $device || $device->status !== Device::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'status' => ['Ierīci nevar nodot, jo tās statuss kopš pieteikuma izveides ir mainījies.'],
                ]);
            }

            // Vecais atbildīgais tiek paturēts paziņojumu loģikai:
            // pēc nodošanas sistēma var saprast, vai ierīce tiešām ieguva jaunu lietotāju.
            $previousAssignedUserId = $device->assigned_to_id ? (int) $device->assigned_to_id : null;
            $targetRoom = null;

            if (! $keepCurrentRoom) {
                // Saņēmējs var atstāt ierīci esošajā telpā vai norādīt jaunu telpu,
                // piemēram, ja pēc nodošanas ierīce fiziski pārvietojas uz citu kabinetu.
                $targetRoom = filled($validated['room_id'] ?? null)
                    ? Room::query()->find($validated['room_id'])
                    : null;

                if (! $targetRoom) {
                    throw ValidationException::withMessages([
                        'room_id' => ['Izvēlētā telpa nav atrasta.'],
                    ]);
                }
            }

            // Šeit notiek faktiska īpašnieka/atbildīgā maiņa ierīces ierakstā.
            // Ja telpa netika mainīta, saglabājas iepriekšējā atrašanās vieta.
            $device->forceFill([
                'assigned_to_id' => $deviceTransfer->transfered_to_id,
                'room_id' => $targetRoom?->id ?? $device->room_id,
                'building_id' => $targetRoom?->building_id ?? $device->building_id,
            ]);

            // Saglabāšana pabeidz nodošanas biznesa darbību: pēc šīs vietas ierīces kartītē
            // redzams jaunais atbildīgais un, ja izvēlēts, arī jaunā telpa/ēka.
            $device->save();
        });

        $after = $deviceTransfer->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($reviewer->id, $deviceTransfer, $before, $after);
        if ($validated['status'] === DeviceTransfer::STATUS_APPROVED) {
            AuditTrail::approve($reviewer->id, $deviceTransfer, 'Apstiprināts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($deviceTransfer));
        } else {
            AuditTrail::reject($reviewer->id, $deviceTransfer, null, 'Noraidīts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($deviceTransfer));
        }

        // Nodošanas rezultāts interesē pieteikuma iesniedzēju, bet apstiprināšanas gadījumā
        // jaunajam atbildīgajam papildus jāparāda arī "piešķirta ierīce" paziņojums.
        $freshTransfer = $deviceTransfer->fresh(['device', 'responsibleUser', 'transferTo']);
        app(UserNotifier::class)->requestReviewed($freshTransfer, $validated['status']);
        if ($validated['status'] === DeviceTransfer::STATUS_APPROVED && $freshTransfer?->device) {
            app(UserNotifier::class)->deviceAssigned($freshTransfer->device, $previousAssignedUserId);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ierīces nodošanas pieteikums izskatīts',
                'status' => $validated['status'],
                'request_id' => $deviceTransfer->id,
            ]);
        }

        return back()->with('success', 'Ierīces nodošanas pieteikums izskatīts');
    }

    /**
     * Ko dara: Parāda ierīces nodošanas aktu drukāšanai vai PDF eksportam.
     *
     * Kā strādā: Pieejams tikai apstiprinātiem pieteikumiem. Atgriež standalone HTML dokumentu ar informāciju par ierīci, abām pusēm un vietu parakstiem, lai to varētu izdrukāt vai saglabāt kā PDF.
     *
     * Kad pielietojas: Izsaukšana: GET /device-transfers/{deviceTransfer}/act | Pieejams: jebkurš autentificēts lietotājs.
     */
    public function printAct(DeviceTransfer $deviceTransfer)
    {
        $user = $this->user();
        abort_unless($user, 403);

        abort_unless($deviceTransfer->status === DeviceTransfer::STATUS_APPROVED, 404);

        $deviceTransfer->load(['device.type', 'device.building', 'device.room', 'responsibleUser', 'transferTo', 'reviewedBy']);

        AuditTrail::viewed($user, 'DeviceTransfer', $deviceTransfer->id, 'Atvērts nodošanas akts drukāšanai: '.AuditTrail::labelFor($deviceTransfer));

        return view('device_transfers.print_act', ['transfer' => $deviceTransfer]);
    }

    /**
     * Ko dara: Atgriež lietotājam pieejamās ierīces konkrētās plūsmas izveides formai.
     *
     * Kā strādā: Atgriež tikai aktīvas, piešķirtas ierīces bez aktīva remonta, remonta pieteikuma, norakstīšanas pieteikuma vai citas nodošanas.
     *
     * Kad pielietojas: Izsauc no: saraksta datu sagatavošanas metodes un `store()`.
     */
    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->when($user->canManageRequests(), fn (Builder $query) => $query->whereNotNull('assigned_to_id'))
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('assigned_to_id', $user->id))
            ->where('status', Device::STATUS_ACTIVE)
            ->whereDoesntHave('repairs', fn (Builder $query) => $query->whereIn('status', ['waiting', 'in-progress']))
            ->whereDoesntHave('repairRequests', fn (Builder $query) => $query->where('status', RepairRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('writeoffRequests', fn (Builder $query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('transfers', fn (Builder $query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED))
            ->with(['type', 'building', 'room', 'assignedTo', 'activeRepair'])
            ->orderBy('name');
    }

    /**
     * Ko dara: Sagatavo ierīču izvēlnes opcijas formai vai filtram.
     *
     * Kā strādā: Ierīču kolekciju pārvērš dropdown opcijās ar label, aprakstu un meklēšanas tekstu, kur iekļauts tips, modelis, atbildīgais un atrašanās vieta.
     *
     * Kad pielietojas: Izsauc no: saraksta datu sagatavošanas metodes.
     */
    private function deviceOptions($devices)
    {
        return collect($devices)->map(function (Device $device) {
            $description = collect([
                $device->type?->type_name,
                collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                $device->assignedTo?->full_name ? 'pašlaik: '.$device->assignedTo->full_name : null,
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
                    $device->assignedTo?->full_name,
                    $device->room?->room_number,
                    $device->room?->room_name,
                    $device->building?->building_name,
                ])),
            ];
        })->values();
    }

    /**
     * Ko dara: Sagatavo saņēmēju izvēlnes opcijas nodošanas formai.
     *
     * Kā strādā: Lietotāju kolekciju pārvērš formu opcijās ar vārdu, amatu/e-pastu un meklēšanas tekstu.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function recipientOptions($users)
    {
        return collect($users)->map(fn (User $recipient) => [
            'value' => (string) $recipient->id,
            'label' => $recipient->full_name,
            'description' => $recipient->job_title ?: $recipient->email,
            'search' => implode(' ', array_filter([
                $recipient->full_name,
                $recipient->job_title,
                $recipient->email,
            ])),
        ])->values();
    }

    /**
     * Ko dara: Pārbauda, vai ierīcei drīkst izveidot nodošanas pieteikumu.
     *
     * Kā strādā: Pārbauda, vai ierīce nav remontā un tai nav gaidoša remonta, norakstīšanas vai nodošanas pieteikuma; konflikta gadījumā izmet validācijas kļūdu.
     *
     * Kad pielietojas: Izsauc no: `store()`.
     */
    private function ensureDeviceCanAcceptTransferRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau notiek remonts ('.$this->repairStatusLabel($device->activeRepair?->status).'), tāpēc nodošanas pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums, tāpēc nodošanas pieteikumu veidot nevar.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums, tāpēc nodošanas pieteikumu veidot nevar.'],
            ]);
        }
    }

    /**
     * Ko dara: Nosaka nodošanas pieteikuma sākotnējo atbildīgo lietotāju.
     *
     * Kā strādā: Ja darbību veic vadītājs, īpašnieku ņem no ierīces `assigned_to_id`; parastam lietotājam īpašnieks ir pats lietotājs.
     *
     * Kad pielietojas: Izsauc no: `store()`.
     */
    private function transferOwnerId(User $actor, Device $device): ?int
    {
        if ($actor->canManageRequests()) {
            return $device->assigned_to_id ? (int) $device->assigned_to_id : null;
        }

        return $actor->id;
    }

    /**
     * Ko dara: Sakārto saraksta filtru stāvokli nodošanas pieprasījumiem.
     *
     * Kā strādā: Nolasa URL filtrus, pieslēdz profila noklusētos statusus vai šodienas datumu un normalizē statusu atlasi pret atļauto sarakstu.
     *
     * Kad pielietojas: Nodošanas pieteikumu saraksta, ienākošo pieteikumu un ātrās meklēšanas datu sagatavošanā.
     */
    private function normalizedIndexFilters(Request $request, array $availableStatuses): array
    {
        // Nosakām, vai lietotājs pats pieskārās statusu filtram; tas atšķir
        // noklusēto filtru no apzināti izvēlētas tukšas atlases.
        $statusFilterTouched = $request->has('statuses_filter');
        $filtersCleared = $request->boolean('clear');
        $hasOtherFilters = $request->filled('q')
            || $request->filled('code')
            || $request->filled('device_id')
            || $request->filled('requester_id')
            || $request->filled('recipient_id')
            || $request->filled('date_from')
            || $request->filled('date_to')
            || $request->boolean('incoming');
        $user = $this->user();
        $canManageTransfers = $user?->canManageRequests() ?? false;
        // Vadītājam var būt profila noklusējums "iesniegtie" vai "šodienas",
        // bet parastam lietotājam automātiski neatņemam viņa vēsturiskos ierakstus.
        $defaultRequestFilter = $canManageTransfers ? $user->defaultRequestFilter() : User::REQUEST_FILTER_ALL;
        $shouldApplyDefault = $canManageTransfers && ! $filtersCleared && ! $hasOtherFilters && ! $statusFilterTouched;
        $defaultStatuses = $shouldApplyDefault && $defaultRequestFilter === User::REQUEST_FILTER_SUBMITTED
            ? [DeviceTransfer::STATUS_SUBMITTED]
            : [];
        $defaultDate = $shouldApplyDefault && $defaultRequestFilter === User::REQUEST_FILTER_TODAY
            ? now()->toDateString()
            : '';
        // Statusus normalizējam pret atļauto sarakstu, lai URL parametrs nevar
        // ielikt vaicājumā neeksistējošu statusa vērtību.
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
            'recipient_id' => ctype_digit((string) $request->query('recipient_id', '')) ? (int) $request->query('recipient_id') : null,
            'recipient_query' => trim((string) $request->query('recipient_query', '')),
            'date_from' => trim((string) $request->query('date_from', $defaultDate)),
            'date_to' => trim((string) $request->query('date_to', $defaultDate)),
            'statuses' => $selectedStatuses,
            'status_filter_touched' => $statusFilterTouched,
            'incoming' => $request->boolean('incoming'),
        ];
    }

    /**
     * Ko dara: Pielieto meklēšanu un filtrus nodošanas pieteikumu vaicājumam.
     *
     * Kā strādā: Pievieno kārtošanai vajadzīgos `leftJoin` uz ierīci, pieteicēju un saņēmēju, pēc tam droši kārto pēc atļautās kolonnas.
     *
     * Kad pielietojas: Kad nodošanas pieteikumu sarakstā vai ātrajā meklēšanā jāievēro lietotāja izvēlētā kārtošana.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        $skipLookup = array_flip($skip);

        // Precīzais koda filtrs tiek normalizēts uz mazajiem burtiem, lai atrastu
        // konkrēto ierīci arī tad, ja ievadē ir atšķirīgs reģistrs vai atstarpes.
        if (! isset($skipLookup['code']) && $filters['code'] !== '') {
            $code = mb_strtolower(trim($filters['code']));

            $query->whereHas('device', function (Builder $deviceQuery) use ($code) {
                $deviceQuery->whereRaw('LOWER(TRIM(code)) = ?', [$code]);
            });
        }

        // Brīvā meklēšana pārbauda vairākus ierīces laukus, jo lietotājs var
        // atcerēties tikai kodu, sērijas numuru, nosaukumu, ražotāju vai modeli.
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
            $query->where('device_transfers.device_id', $filters['device_id']);
        }

        if (! isset($skipLookup['requester_id']) && filled($filters['requester_id'])) {
            $query->where('device_transfers.responsible_user_id', $filters['requester_id']);
        }

        if (! isset($skipLookup['recipient_id']) && filled($filters['recipient_id'])) {
            $query->where('device_transfers.transfered_to_id', $filters['recipient_id']);
        }

        // Ienākošo nodošanu skatā rādam tikai pieteikumus, kas vēl gaida
        // saņēmēja vai vadītāja lēmumu.
        if (! isset($skipLookup['incoming']) && $filters['incoming']) {
            $query->where('device_transfers.status', DeviceTransfer::STATUS_SUBMITTED);
        }

        if (! isset($skipLookup['date_from']) && filled($filters['date_from'])) {
            $query->whereDate('device_transfers.created_at', '>=', $filters['date_from']);
        }

        if (! isset($skipLookup['date_to']) && filled($filters['date_to'])) {
            $query->whereDate('device_transfers.created_at', '<=', $filters['date_to']);
        }

        // Statusu filtru pielietojam tikai tad, ja nav izvēlēti visi statusi;
        // pretējā gadījumā WHERE IN būtu lieks un neko nemainītu.
        if (! isset($skipLookup['statuses'])) {
            $selectedStatuses = $filters['statuses'] ?? [];

            if ($selectedStatuses !== [] && count($selectedStatuses) < 3) {
                $query->whereIn('device_transfers.status', $selectedStatuses);
            }
        }

        return $query;
    }

    /**
     * Ko dara: Pielieto drošu kārtošanu pēc atļautajām kolonnām.
     *
     * Kā strādā: Pārbauda `sort` un `direction` query parametrus pret atļautajiem variantiem un pievieno latvisku label aktīvā kārtojuma tekstam.
     *
     * Kad pielietojas: Pirms saraksta vaicājuma kārtošanas un audita paziņojuma izveides.
     */
    private function applySorting(Builder $query, array $sorting): void
    {
        $query
            ->leftJoin('devices as sortable_devices', 'device_transfers.device_id', '=', 'sortable_devices.id')
            ->leftJoin('users as sortable_requesters', 'device_transfers.responsible_user_id', '=', 'sortable_requesters.id')
            ->leftJoin('users as sortable_recipients', 'device_transfers.transfered_to_id', '=', 'sortable_recipients.id');

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
            case 'recipient':
                $query->orderByRaw('LOWER(COALESCE(sortable_recipients.full_name, "")) '.$sorting['direction']);
                break;
            case 'status':
                $query->orderByRaw("
                    CASE device_transfers.status
                        WHEN 'submitted' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                        ELSE 4
                    END {$sorting['direction']}
                ");
                break;
            case 'created_at':
            default:
                $query->orderBy('device_transfers.created_at', $sorting['direction']);
                break;
        }

        $query->orderBy('device_transfers.id', $sorting['direction'] === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Ko dara: Normalizē kārtošanas parametrus tabulas galvenei un toast paziņojumiem.
     *
     * Kā strādā: Atgriež atļauto kārtošanas lauku label karti, lai UI un audits lietotu vienādus nosaukumus.
     *
     * Kad pielietojas: Saraksta galvenes, aktīvā kārtojuma teksta un audita aprakstu veidošanā.
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
     * Kā strādā: Atgriež atļauto kārtošanas lauku label karti, lai UI un audits lietotu vienādus nosaukumus.
     *
     * Kad pielietojas: Saraksta galvenes, aktīvā kārtojuma teksta un audita aprakstu veidošanā.
     */
    private function sortOptions(): array
    {
        return [
            'code' => ['label' => 'koda'],
            'name' => ['label' => 'nosaukuma'],
            'requester' => ['label' => 'pieteicēja'],
            'recipient' => ['label' => 'saņēmēja'],
            'created_at' => ['label' => 'iesniegšanas datuma'],
            'status' => ['label' => 'statusa'],
        ];
    }

    /**
     * Ko dara: Reģistrē nodošanas pieteikumu saraksta filtrēšanu un kārtošanu audita žurnālā.
     *
     * Kā strādā: No filtriem izveido audita payload, pieraksta filtrēšanas notikumu un atsevišķi pieraksta kārtošanu, ja tā atšķiras no noklusējuma.
     *
     * Kad pielietojas: Izsauc no: `index()`.
     */
    private function auditDeviceTransferListInteractions(Request $request, User $user, array $filters, array $sorting): void
    {
        $filterPayload = array_filter([
            'teksts' => $filters['q'] ?? '',
            'ierīce' => $filters['device_query'] ?? '',
            'pieteicējs' => $filters['requester_query'] ?? '',
            'saņēmējs' => $filters['recipient_query'] ?? '',
            'ienākošie' => ! empty($filters['incoming']),
            'no datuma' => $filters['date_from'] ?? '',
            'līdz datumam' => $filters['date_to'] ?? '',
            'statusi' => count($filters['statuses'] ?? []) > 0 && count($filters['statuses'] ?? []) < 3 ? ($filters['statuses'] ?? []) : [],
        ], fn (mixed $value) => $value !== null && $value !== '' && $value !== []);

        if ($filterPayload !== []) {
            AuditTrail::filter(
                $user,
                'DeviceTransfer',
                $filterPayload,
                'Filtrēti ierīču nodošanas pieteikumi: '.implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
                    if (is_array($value)) {
                        return $label.': '.implode(', ', $value);
                    }

                    if (is_bool($value)) {
                        return $label.': '.($value ? 'jā' : 'nē');
                    }

                    return $label.': '.$value;
                })->all())
            );
        }

        if (($sorting['sort'] ?? 'created_at') !== 'created_at' || ($sorting['direction'] ?? 'desc') !== 'desc' || $request->has('sort')) {
            AuditTrail::sort(
                $user,
                'DeviceTransfer',
                $sorting['label'] ?? 'iesniegšanas datuma',
                $sorting['direction'] ?? 'desc',
                'Kārtoti ierīču nodošanas pieteikumi pēc '.($sorting['label'] ?? 'iesniegšanas datuma').' '.(($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }
    }

    /**
     * Ko dara: Sagatavo ierīču dropdown opcijas nodošanas pieteikumu filtram.
     *
     * Kā strādā: No nodošanas kolekcijas paņem unikālās ierīces un pārvērš tās dropdown opcijās ar aprakstu un meklēšanas lauku.
     *
     * Kad pielietojas: Nodošanas pieteikumu saraksta ierīces filtra opciju sagatavošanā.
     */
    private function transferDeviceOptions($transfers)
    {
        return collect($transfers)
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
     * Ko dara: Sagatavo lietotāju dropdown opcijas pieprasījumu filtriem.
     *
     * Kā strādā: Sakārto lietotājus pēc vārda un katru pārvērš dropdown opcijā ar amatu, e-pastu un meklēšanas tekstu.
     *
     * Kad pielietojas: Pieteicēja un saņēmēja filtru opciju sagatavošanā nodošanas sarakstā.
     */
    private function transferUserOptions($users)
    {
        return collect($users)
            ->sortBy(fn (User $person) => mb_strtolower($person->full_name))
            ->values()
            ->map(fn (User $person) => [
                'value' => (string) $person->id,
                'label' => $person->full_name,
                'description' => implode(' | ', array_filter([
                    $person->job_title,
                    $person->email,
                ])),
                'search' => implode(' ', array_filter([
                    $person->full_name,
                    $person->job_title,
                    $person->email,
                ])),
            ]);
    }
}
