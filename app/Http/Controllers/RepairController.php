<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Faktisko remonta darbu pārvaldība administratoram.
 *
 * Šeit dzīvo remonta saraksts, remonta izveide bez pieprasījuma
 * un statusu pārejas visā remonta dzīves ciklā.
 */
class RepairController extends Controller
{
    private const STATUSES = ['waiting', 'in-progress', 'completed', 'cancelled'];
    private const TYPES = ['internal', 'external'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    private const SORTABLE_COLUMNS = ['code', 'name', 'assigned', 'location', 'status', 'priority', 'repair_type', 'cost', 'start_date', 'end_date'];

    /**
     * Parāda remontu sarakstu vienotā tabulā ar filtriem un kārtošanu.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $canManageRepairs = $user->canManageRequests();
        $filters = $this->normalizedIndexFilters($request, $canManageRepairs);
        $sorting = $this->normalizedSorting($request);

        if (! $this->featureTableExists('repairs')) {
            return view('repairs.index', [
                'repairs' => $this->emptyPaginator(),
                'repairSummary' => [
                    'total' => 0,
                    'waiting' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                ],
                'filters' => $filters,
                'statuses' => ['waiting', 'in-progress', 'completed', 'cancelled'],
                'statusLabels' => $this->statusLabels(),
                'priorityLabels' => $this->priorityLabels(),
                'typeLabels' => $this->typeLabels(),
                'canManageRepairs' => $canManageRepairs,
                'sorting' => $sorting,
                'sortOptions' => $this->sortOptions(),
                'deviceOptions' => collect(),
                'requesterOptions' => collect(),
                'featureMessage' => 'Tabula repairs šobrīd nav pieejama.',
            ]);
        }

        $baseQuery = $this->visibleRepairsQuery($user)
            ->when($filters['mine'] && $canManageRepairs, fn (Builder $query) => $query->where('repairs.accepted_by', $user->id));

        $deviceOptions = $this->repairDeviceOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['device_id']))
                ->with(['device.type', 'device.room', 'device.building'])
                ->get()
        );

        $requesterOptions = $this->repairRequesterOptions(
            (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['requester_id']))
                ->with(['request.responsibleUser', 'reporter'])
                ->get()
        );

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

        $repairs = $repairsQuery
            ->paginate(20)
            ->withQueryString();

        return view('repairs.index', [
            'repairs' => $repairs,
            'repairSummary' => [
                'total' => (clone $baseQuery)->count(),
                'waiting' => (clone $baseQuery)->where('repairs.status', 'waiting')->count(),
                'in_progress' => (clone $baseQuery)->where('repairs.status', 'in-progress')->count(),
                'completed' => (clone $baseQuery)->whereIn('repairs.status', ['completed', 'cancelled'])->count(),
            ],
            'filters' => $filters,
            'statuses' => ['waiting', 'in-progress', 'completed', 'cancelled'],
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
            'canManageRepairs' => $canManageRepairs,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'deviceOptions' => $deviceOptions,
            'requesterOptions' => $requesterOptions,
        ]);
    }

    /**
     * Par?da jauna remonta formu administratoram.
     */
    public function create(Request $request)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return view('repairs.create', array_merge($this->formData(), [
                'preselectedDeviceId' => $request->query('device_id'),
                'defaultExecutorId' => null,
                'featureMessage' => 'Tabula repairs šobrīd nav pieejama.',
            ]));
        }

        return view('repairs.create', array_merge($this->formData(), [
            'preselectedDeviceId' => $request->query('device_id'),
            'defaultExecutorId' => null,
        ]));
    }

    /**
     * Saglabā jaunu remonta ierakstu.
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
     * Parāda remonta rediģēšanas formu.
     */
    public function edit(Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Tabula repairs šobrīd nav pieejama.');
        }

        $repair->load(['device', 'executor', 'acceptedBy', 'request.responsibleUser', 'request.reviewedBy']);

        return view('repairs.edit', array_merge([
            'repair' => $repair,
        ], $this->formData($repair)));
    }

    /**
     * Atjaunina remonta ierakstu.
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

        return redirect()->route('repairs.edit', $repair)->with('success', 'Remonts atjaunināts');
    }

    /**
     * Dzēš remonta ierakstu un izlīdzina ierīces statusu.
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
     * Veic atļautu pāreju starp remonta statusiem.
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

        if ($validated['target_status'] === 'in-progress' && !filled($repair->description)) {
             return back()->with('error', 'Pirms remonta sākšanas ir nepieciešams aizpildīt remonta aprakstu.');
        }

        if (! in_array($validated['target_status'], $this->allowedTransitionTargets($repair->status), true)) {
            return back()->with('error', 'Sadu remonta statusa mainu veikt nevar.');
        }

        if (
            $validated['target_status'] === 'in-progress'
            && $repair->repair_type === 'external'
            && (! filled($repair->vendor_name) || ! filled($repair->vendor_contact))
        ) {
            return back()->with('error', 'Lai sāktu ārējo remontu, vispirms aizpildi pakalpojuma sniedzēju un kontaktu remonta kartītē.');
        }

        if (
            $validated['target_status'] === 'completed'
            && $repair->repair_type === 'external'
            && (empty($repair->invoice_number) || (empty($repair->cost) && empty($validated['cost'])))
        ) {
            return back()->with('error', 'Lai pabeigtu ārējo remontu, jābūt norādītam rēķina numuram un izmaksām.');
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
        $payload = ['status' => $validated['target_status']];

        if (array_key_exists('cost', $validated) && $validated['cost'] !== null && $validated['cost'] !== '') {
            $payload['cost'] = $validated['cost'];
        }

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

        return back()->with('success', 'Remonta statuss atjaunināts');
    }

    /**
     * Vecais show ceļš projektā tiek izmantots kā pāradresācija uz rediģēšanu.
     */
    public function show(Repair $repair)
    {
        return redirect()->route('repairs.index');
    }

    private function visibleRepairsQuery(User $user): Builder
    {
        return Repair::query()->when(! $user->canManageRequests(), function (Builder $query) use ($user) {
            $query->where(function (Builder $builder) use ($user) {
                $builder->where('issue_reported_by', $user->id)
                    ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('assigned_to_id', $user->id));
            });
        });
    }

    private function formData(?Repair $repair = null): array
    {
        $devices = $repair
            ? Device::query()
                ->with(['assignedTo', 'building', 'room', 'type'])
                ->where('status', '!=', Device::STATUS_WRITEOFF)
                ->when($repair->device_id, fn (Builder $query) => $query->orWhere('id', $repair->device_id))
                ->orderBy('name')
                ->get()
            : $this->availableDevicesForCreate()->get();

        return [
            'devices' => $devices,
            'deviceOptions' => $this->deviceOptions($devices),
            'users' => User::active()->orderBy('full_name')->get(),
            'statuses' => self::STATUSES,
            'repairTypes' => self::TYPES,
            'priorities' => self::PRIORITIES,
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
        ];
    }

    private function validatedData(Request $request, ?Repair $repair = null): array
    {
        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],
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

        if (! $repair && $device && $device->status !== Device::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'device_id' => ['Jaunu remontu var izveidot tikai aktīvai ierīcei.'],
            ]);
        }

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

        if ($validated['repair_type'] === 'internal') {
            $validated['vendor_name'] = null;
            $validated['vendor_contact'] = null;
            $validated['invoice_number'] = null;
        }

        $isExternalInProcess = $validated['repair_type'] === 'external' && (($repair?->status ?? $validated['status']) === 'in-progress');

        if ($validated['repair_type'] === 'external' && ! $isExternalInProcess) {
            $validated['vendor_name'] = null;
            $validated['vendor_contact'] = null;
            $validated['invoice_number'] = null;
        }

        if ($isExternalInProcess && ! filled($validated['vendor_name'])) {
            throw ValidationException::withMessages([
                'vendor_name' => ['Ārējam remontam norādi pakalpojuma sniedzēju.'],
            ]);
        }

        if ($isExternalInProcess && ! filled($validated['vendor_contact'])) {
            throw ValidationException::withMessages([
                'vendor_contact' => ['Ārējam remontam norādi pakalpojuma sniedzēja kontaktu.'],
            ]);
        }

        return $validated;
    }

    private function availableDevicesForCreate(): Builder
    {
        return Device::query()
            ->with(['assignedTo', 'building', 'room', 'type'])
            ->where('status', Device::STATUS_ACTIVE)
            ->whereDoesntHave('repairs', fn (Builder $query) => $query->whereIn('status', ['waiting', 'in-progress']))
            ->whereDoesntHave('repairRequests', fn (Builder $query) => $query->where('status', RepairRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('writeoffRequests', fn (Builder $query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('transfers', fn (Builder $query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED))
            ->orderBy('name');
    }

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
                $device->forceFill(['status' => 'repair'])->save();
                AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => 'repair']);
            }

            return;
        }

        if (($previousRepairStatus === 'waiting' || $previousRepairStatus === 'in-progress' || $device->status === 'repair') && ! $hasOtherActiveRepairs) {
            $before = ['status' => $device->status];
            $device->forceFill(['status' => Device::STATUS_ACTIVE])->save();
            AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => Device::STATUS_ACTIVE]);
        }
    }

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
            $device->forceFill(['status' => Device::STATUS_ACTIVE])->save();
        }
    }

    private function normalizedIndexFilters(Request $request, bool $canManageRepairs): array
    {
        $availableStatuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
        $rawStatuses = $request->query('status', []);
        $selectedStatuses = collect(is_array($rawStatuses) ? $rawStatuses : [$rawStatuses])
            ->map(fn ($status) => strtolower(trim((string) $status)))
            ->filter(fn ($status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        if ($canManageRepairs && ! $request->has('statuses_filter')) {
            $selectedStatuses = ['waiting', 'in-progress'];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'code' => trim((string) $request->query('code', '')),
            'device_id' => ctype_digit((string) $request->query('device_id', '')) ? (int) $request->query('device_id') : null,
            'device_query' => trim((string) $request->query('device_query', '')),
            'requester_id' => ctype_digit((string) $request->query('requester_id', '')) ? (int) $request->query('requester_id') : null,
            'requester_query' => trim((string) $request->query('requester_query', '')),
            'statuses' => $selectedStatuses,
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'mine' => $request->boolean('mine'),
        ];
    }

    private function applyIndexFilters(Builder $query, array $filters, array $skip = []): Builder
    {
        if ($filters['q'] !== '' && ! in_array('q', $skip, true)) {
            $term = $filters['q'];

            $query->whereHas('device', function (Builder $deviceQuery) use ($term) {
                $deviceQuery->where('code', $term);
            });
        }

        if ($filters['code'] !== '' && ! in_array('code', $skip, true)) {
            $query->whereHas('device', function (Builder $deviceQuery) use ($filters) {
                $deviceQuery->whereRaw('LOWER(code) = ?', [mb_strtolower($filters['code'])]);
            });
        }

        if ($filters['device_id'] && ! in_array('device_id', $skip, true)) {
            $query->where('repairs.device_id', $filters['device_id']);
        }

        if ($filters['requester_id'] && ! in_array('requester_id', $skip, true)) {
            $requesterId = $filters['requester_id'];

            $query->where(function (Builder $requesterQuery) use ($requesterId) {
                $requesterQuery->whereHas('request', fn (Builder $builder) => $builder->where('responsible_user_id', $requesterId))
                    ->orWhere('repairs.issue_reported_by', $requesterId);
            });
        }

        if (! in_array('status', $skip, true) && count($filters['statuses']) > 0 && count($filters['statuses']) < 3) {
            $query->whereIn('repairs.status', $filters['statuses']);
        }

        if ($filters['date_from'] !== '' && ! in_array('date_from', $skip, true)) {
            $query->whereDate('repairs.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '' && ! in_array('date_to', $skip, true)) {
            $query->whereDate('repairs.created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, array $sorting): void
    {
        $direction = $sorting['direction'] === 'asc' ? 'asc' : 'desc';

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
                $query->orderByRaw("case repairs.priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 when 'low' then 3 else 4 end")
                    ->orderByDesc('repairs.id');
                return;
        }

        $query->orderByDesc('repairs.id');
    }

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

    private function repairDeviceOptions($repairs)
    {
        return collect($repairs)
            ->pluck('device')
            ->filter()
            ->unique('id')
            ->map(function (Device $device) {
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

    private function repairRequesterOptions($repairs)
    {
        return collect($repairs)
            ->map(fn (Repair $repair) => $repair->request?->responsibleUser ?: $repair->reporter)
            ->filter()
            ->unique('id')
            ->sortBy('full_name')
            ->values()
            ->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => $user->full_name,
                'description' => $user->job_title ?: $user->email,
                'search' => implode(' ', array_filter([$user->full_name, $user->job_title, $user->email])),
            ]);
    }

    private function statusLabels(): array
    {
        return [
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
    }

    private function priorityLabels(): array
    {
        return [
            'low' => 'Zema',
            'medium' => 'Vidēja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
    }

    private function typeLabels(): array
    {
        return [
            'internal' => 'Iekšējais',
            'external' => 'Ārējais',
        ];
    }

    private function labelForStatus(string $status): string
    {
        return $this->statusLabels()[$status] ?? $status;
    }

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
