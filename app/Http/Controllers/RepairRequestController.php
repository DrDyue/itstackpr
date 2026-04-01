<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Lietotāju remonta pieteikumu plūsma.
 *
 * Kontrolieris pārvalda gan lietotāja iesniegšanu, gan administratora
 * izskatīšanu un remonta ieraksta izveidi pēc apstiprināšanas.
 */
class RepairRequestController extends Controller
{
    private const SORTABLE_COLUMNS = ['code', 'name', 'requester', 'created_at', 'status'];

    /**
     * Parāda remonta pieteikumu sarakstu atbilstoši lomai un filtriem.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $canReview = $user->canManageRequests();
        $availableStatuses = [
            RepairRequest::STATUS_SUBMITTED,
            RepairRequest::STATUS_APPROVED,
            RepairRequest::STATUS_REJECTED,
        ];
        $filters = $this->normalizedIndexFilters($request, $availableStatuses, $canReview);
        $sorting = $this->normalizedSorting($request);

        if (! $this->featureTableExists('repair_requests')) {
            return view('repair_requests.index', [
                'requests' => $this->emptyPaginator(),
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
                'requesterOptions' => collect(),
                'featureMessage' => 'Tabula repair_requests šobrīd nav pieejama.',
            ]);
        }

        $baseQuery = RepairRequest::query()
            ->when(! $canReview, fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $deviceOptions = $this->repairDeviceOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id']))
                ->with(['device.type'])
                ->get()
        );

        $requesterOptions = $this->repairRequesterOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id']))
                ->with('responsibleUser')
                ->get()
        );

        $requestsQuery = (clone $baseQuery)
            ->with(['device.type', 'responsibleUser', 'reviewedBy', 'repair'])
            ->select('repair_requests.*');

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery
            ->paginate(20)
            ->withQueryString();

        return view('repair_requests.index', [
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
            'requesterOptions' => $requesterOptions,
        ]);
    }

    /**
     * Atrod remonta pieteikumu pēc saistītās ierīces koda filtrētajā sarakstā.
     */
    public function findByCode(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

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

        $this->applyIndexFilters($requestsQuery, $filters);
        $this->applySorting($requestsQuery, $sorting);

        $requests = $requestsQuery->get();
        $needle = mb_strtolower($code);
        $foundIndex = null;

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
            'page' => intdiv($foundIndex, 20) + 1,
            'term' => $code,
            'highlight_id' => 'repair-request-'.$requests->values()[$foundIndex]->id,
        ]);
    }

    /**
     * Parāda jauna remonta pieteikuma formu lietotājam.
     */
    public function create(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('repair_requests')) {
            return view('repair_requests.create', [
                'devices' => collect(),
                'deviceOptions' => collect(),
                'featureMessage' => 'Tabula repair_requests šobrīd nav pieejama.',
            ]);
        }

        $devices = $this->availableDevicesForUser($user)->get();
        $selectedDeviceId = (string) $request->query('device_id', '');
        $selectedDevice = ctype_digit($selectedDeviceId)
            ? $devices->firstWhere('id', (int) $selectedDeviceId)
            : null;

        return view('repair_requests.create', [
            'devices' => $devices,
            'deviceOptions' => $this->deviceOptions($devices),
            'selectedDeviceId' => $selectedDevice?->id ? (string) $selectedDevice->id : '',
            'selectedDeviceLabel' => $selectedDevice
                ? $selectedDevice->name.' ('.($selectedDevice->code ?: 'bez koda').')'
                : '',
        ]);
    }

    /**
     * Saglabā jaunu remonta pieteikumu.
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
     * Administratora lēmums par remonta pieteikumu.
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

        DB::transaction(function () use ($validated, $repairRequest, $manager, &$payload) {
            if ($validated['status'] === 'approved') {
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

                $repair = $this->createRepairRecord([
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

                $device->forceFill(['status' => 'repair'])->save();

                if (array_key_exists('repair_id', $repairRequest->getAttributes())) {
                    $payload['repair_id'] = $repair->id;
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Remonta pieteikums izskatīts',
                'status' => $validated['status'],
                'request_id' => $repairRequest->id,
            ]);
        }

        return back()->with('success', 'Remonta pieteikums izskatīts');
    }

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

    private function repairStatusLabel(?string $status): string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            default => 'Remonta',
        };
    }

    /**
     * Sakārto saraksta filtru stāvokli, ieskaitot admina noklusēto "iesniegts".
     */
    private function normalizedIndexFilters(Request $request, array $availableStatuses, bool $canReview): array
    {
        $statusFilterTouched = $request->has('statuses_filter');
        $filtersCleared = $request->boolean('clear');
        $hasOtherFilters = $request->filled('q') 
            || $request->filled('device_id') 
            || $request->filled('requester_id') 
            || $request->filled('date_from') 
            || $request->filled('date_to');
        $defaultStatuses = $canReview && ! $filtersCleared && ! $hasOtherFilters ? [RepairRequest::STATUS_SUBMITTED] : [];
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : $defaultStatuses))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        return [
            'q' => trim((string) $request->query('q', '')),
            'request_id' => ctype_digit((string) $request->query('request_id', '')) ? (int) $request->query('request_id') : null,
            'device_id' => ctype_digit((string) $request->query('device_id', '')) ? (int) $request->query('device_id') : null,
            'device_query' => trim((string) $request->query('device_query', '')),
            'requester_id' => ctype_digit((string) $request->query('requester_id', '')) ? (int) $request->query('requester_id') : null,
            'requester_query' => trim((string) $request->query('requester_query', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'statuses' => $selectedStatuses,
            'status_filter_touched' => $statusFilterTouched,
        ];
    }

    /**
     * Pielieto meklēšanu un filtrus pieteikumu vaicājumam.
     */
    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        $skipLookup = array_flip($skip);

        if (! isset($skipLookup['q']) && $filters['q'] !== '') {
            $term = $filters['q'];

            $query->whereHas('device', function (Builder $deviceQuery) use ($term) {
                $deviceQuery->where('code', $term);
            });
        }

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

        if (! isset($skipLookup['statuses'])) {
            $selectedStatuses = $filters['statuses'] ?? [];

            if ($selectedStatuses !== [] && count($selectedStatuses) < 3) {
                $query->whereIn('repair_requests.status', $selectedStatuses);
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
            'created_at' => ['label' => 'iesniegšanas datuma'],
            'status' => ['label' => 'statusa'],
        ];
    }

    /**
     * Sagatavo ierīču dropdown opcijas remonta pieteikumu filtram.
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
     * Sagatavo pieteicēju dropdown opcijas remonta pieteikumu filtram.
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
