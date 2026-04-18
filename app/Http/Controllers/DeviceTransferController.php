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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Ierīču nodošanas pieprasījumu plūsma.
 *
 * Lietotājs iesniedz nodošanu citam lietotājam, savukārt saņēmējs
 * pieņem vai noraida šo nodošanu.
 */
class DeviceTransferController extends Controller
{
    use HasRepairStatusLabels;

    private const SORTABLE_COLUMNS = ['code', 'name', 'requester', 'recipient', 'created_at', 'status'];

    /**
     * Parāda nodošanas pieprasījumu sarakstu ar lomas atkarīgu loģiku.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $canManageTransfers = $user->canManageRequests();
        $availableStatuses = [
            DeviceTransfer::STATUS_SUBMITTED,
            DeviceTransfer::STATUS_APPROVED,
            DeviceTransfer::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses);
        $sorting = $this->normalizedSorting($request);

        if (! $this->featureTableExists('device_transfers')) {
            return view('device_transfers.index', [
                'transfers' => $this->emptyPaginator(),
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

        $incomingPendingCount = ! $canManageTransfers
            ? (clone $baseQuery)
                ->where('transfered_to_id', $user->id)
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->count()
            : 0;

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

        $transfersQuery = (clone $baseQuery)
            ->with(['device.type', 'responsibleUser', 'transferTo', 'reviewedBy'])
            ->select('device_transfers.*');

        $this->applyIndexFilters($transfersQuery, $filters);
        $this->applySorting($transfersQuery, $sorting);

        $transfers = $transfersQuery
            ->paginate(20)
            ->withQueryString();

        AuditTrail::viewed($user, 'DeviceTransfer', null, 'Atvērts ierīču pārsūtīšanas pieteikumu saraksts.');
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
     * Atrod nodošanas pieteikumu pēc saistītās ierīces koda filtrētajā sarakstā.
     */
    public function findByCode(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'DeviceTransfer', $code, 'Meklēts ierīces pārsūtīšanas pieteikums pēc ierīces koda: '.$code);

        $canManageTransfers = $user->canManageRequests();
        $availableStatuses = [
            DeviceTransfer::STATUS_SUBMITTED,
            DeviceTransfer::STATUS_APPROVED,
            DeviceTransfer::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses);
        $sorting = $this->normalizedSorting($request);

        $baseQuery = DeviceTransfer::query()
            ->when(! $canManageTransfers, function (Builder $query) use ($user) {
                $query->where(function (Builder $builder) use ($user) {
                    $builder->where('responsible_user_id', $user->id)
                        ->orWhere('transfered_to_id', $user->id);
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

        return response()->json([
            'found' => true,
            'page' => intdiv($foundIndex, 20) + 1,
            'term' => $code,
            'highlight_id' => 'device-transfer-'.$transfers->values()[$foundIndex]->id,
        ]);
    }

    /**
     * Parāda jauna nodošanas pieprasījuma formu.
     */
    public function redirectToCreateModal(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        AuditTrail::viewed($user, 'DeviceTransfer', null, 'Atvērts ierīces pārsūtīšanas pieteikuma modālis.');

        return redirect()->route('device-transfers.index', array_filter([
            'device_transfer_modal' => 'create',
            'device_id' => ctype_digit((string) $request->query('device_id', ''))
                ? (int) $request->query('device_id')
                : null,
        ]));
    }

    /**
     * Saglabā jaunu ierīces nodošanas pieprasījumu.
     */
    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('device_transfers')) {
            return redirect()->route('device-transfers.index')->with('error', 'Ierīču pārsūtīšanas pieteikumus šobrīd nevar saglabāt, jo tabula device_transfers nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'transfered_to_id' => ['required', 'exists:users,id', Rule::notIn([$user->id])],
            'transfer_reason' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvēlies ierīci, kuru vēlies nodot.',
            'transfered_to_id.required' => 'Izvēlies saņēmēju.',
            'transfer_reason.required' => 'Apraksti pārsūtīšanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => [$user->canManageRequests()
                    ? 'Admins var pieteikt pārsūtīšanu tikai aktīvai un piešķirtai ierīcei.'
                    : 'Vari pieteikt nodošanu tikai savai piesaistītai ierīcei.'],
            ]);
        }

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

        $this->ensureDeviceCanAcceptTransferRequest($device);

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $ownerId,
            'transfered_to_id' => $validated['transfered_to_id'],
            'transfer_reason' => $validated['transfer_reason'],
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $transfer);
        AuditTrail::submit($user->id, $transfer, 'Iesniegts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($transfer));

        return redirect()->route('device-transfers.index')->with('success', 'Ierīces pārsūtīšanas pieteikums izveidots');
    }

    /**
     * Saņēmēja lēmums par ierīces pieņemšanu vai noraidīšanu.
     */
    public function review(Request $request, DeviceTransfer $deviceTransfer)
    {
        $reviewer = $this->user();
        abort_unless($reviewer, 403);

        if (! $this->featureTableExists('device_transfers')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ierīču pārsūtīšanas pieteikumu tabula šobrīd nav pieejama.'], 503);
            }

            return back()->with('error', 'Ierīču pārsūtīšanas pieteikumu tabula šobrīd nav pieejama.');
        }

        $canReview = (int) $deviceTransfer->transfered_to_id === (int) $reviewer->id;
        abort_unless($canReview, 403);

        if ($deviceTransfer->status !== DeviceTransfer::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Šis pieteikums jau ir izskatīts.'], 409);
            }

            return back()->with('error', 'Šis pieteikums jau ir izskatīts.');
        }

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
            'status.required' => 'Izvēlies lēmumu pārsūtīšanas pieteikumam.',
            'room_id.required' => 'Izvēlies telpu, uz kuru novietot ierīci.',
        ]);

        $before = $deviceTransfer->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $deviceTransfer, $reviewer, $keepCurrentRoom) {
            $deviceTransfer->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $reviewer->id,
                'review_notes' => null,
            ]);

            if ($validated['status'] !== 'approved') {
                return;
            }

            $device = $deviceTransfer->device()->lockForUpdate()->first();

            if (! $device || $device->status !== Device::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'status' => ['Ierīci nevar nodot, jo tās statuss kopš pieteikuma izveides ir mainījies.'],
                ]);
            }

            $targetRoom = null;

            if (! $keepCurrentRoom) {
                $targetRoom = filled($validated['room_id'] ?? null)
                    ? Room::query()->find($validated['room_id'])
                    : null;

                if (! $targetRoom) {
                    throw ValidationException::withMessages([
                        'room_id' => ['Izvēlētā telpa nav atrasta.'],
                    ]);
                }
            }

            $device->forceFill([
                'assigned_to_id' => $deviceTransfer->transfered_to_id,
                'room_id' => $targetRoom?->id ?? $device->room_id,
                'building_id' => $targetRoom?->building_id ?? $device->building_id,
            ]);

            $device->save();
        });

        $after = $deviceTransfer->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($reviewer->id, $deviceTransfer, $before, $after);
        if ($validated['status'] === DeviceTransfer::STATUS_APPROVED) {
            AuditTrail::approve($reviewer->id, $deviceTransfer, 'Apstiprināts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($deviceTransfer));
        } else {
            AuditTrail::reject($reviewer->id, $deviceTransfer, null, 'Noraidīts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($deviceTransfer));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ierīces pārsūtīšanas pieteikums izskatīts',
                'status' => $validated['status'],
                'request_id' => $deviceTransfer->id,
            ]);
        }

        return back()->with('success', 'Ierīces pārsūtīšanas pieteikums izskatīts');
    }

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

    private function transferOwnerId(User $actor, Device $device): ?int
    {
        if ($actor->canManageRequests()) {
            return $device->assigned_to_id ? (int) $device->assigned_to_id : null;
        }

        return $actor->id;
    }

    /**
     * Sakārto saraksta filtru stāvokli nodošanas pieprasījumiem.
     */
    private function normalizedIndexFilters(Request $request, array $availableStatuses): array
    {
        $statusFilterTouched = $request->has('statuses_filter');
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : []))
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
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'statuses' => $selectedStatuses,
            'status_filter_touched' => $statusFilterTouched,
            'incoming' => $request->boolean('incoming'),
        ];
    }

    /**
     * Pielieto meklēšanu un filtrus pārsūtīšanas pieprasījumu vaicājumam.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        $skipLookup = array_flip($skip);

        if (! isset($skipLookup['code']) && $filters['code'] !== '') {
            $query->whereHas('device', function (Builder $deviceQuery) use ($filters) {
                $deviceQuery->where('code', $filters['code']);
            });
        }

        if (! isset($skipLookup['q']) && $filters['q'] !== '') {
            $term = $filters['q'];

            $query->whereHas('device', function (Builder $deviceQuery) use ($term) {
                $deviceQuery->where(function (Builder $q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
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

        if (! isset($skipLookup['incoming']) && $filters['incoming']) {
            $query->where('device_transfers.status', DeviceTransfer::STATUS_SUBMITTED);
        }

        if (! isset($skipLookup['date_from']) && filled($filters['date_from'])) {
            $query->whereDate('device_transfers.created_at', '>=', $filters['date_from']);
        }

        if (! isset($skipLookup['date_to']) && filled($filters['date_to'])) {
            $query->whereDate('device_transfers.created_at', '<=', $filters['date_to']);
        }

        if (! isset($skipLookup['statuses'])) {
            $selectedStatuses = $filters['statuses'] ?? [];

            if ($selectedStatuses !== [] && count($selectedStatuses) < 3) {
                $query->whereIn('device_transfers.status', $selectedStatuses);
            }
        }

        return $query;
    }

    /**
     * Pielieto drošu kārtošanu pēc atļautajām kolonnām.
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
     * Normalizē kārtošanas parametrus tabulas galvenei un toast paziņojumiem.
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
     * Lietotāja paziņojumiem izmantojamās kārtošanas etiķetes.
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
                'Filtrēti ierīču pārsūtīšanas pieteikumi: '.implode(' | ', collect($filterPayload)->map(function (mixed $value, string $label) {
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
                'Kārtoti ierīču pārsūtīšanas pieteikumi pēc '.($sorting['label'] ?? 'iesniegšanas datuma').' '.(($sorting['direction'] ?? 'desc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }
    }

    /**
     * Sagatavo ierīču dropdown opcijas nodošanas pieteikumu filtram.
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
     * Sagatavo lietotāju dropdown opcijas pieprasījumu filtriem.
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
