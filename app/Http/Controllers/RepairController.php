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

    /**
     * Parāda remontu sarakstu un sadalījumu kolonnās.
     */
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'repair_type' => trim((string) $request->query('repair_type', '')),
            'priority_sort' => trim((string) $request->query('priority_sort', '')),
            'mine' => $request->boolean('mine'),
        ];

        if (! $this->featureTableExists('repairs')) {
            return view('repairs.index', [
                'repairs' => collect(),
                'repairColumns' => [
                    'waiting' => collect(),
                    'in-progress' => collect(),
                    'completed' => collect(),
                ],
                'repairSummary' => [
                    'total' => 0,
                    'waiting' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                ],
                'filters' => $filters,
                'statuses' => self::STATUSES,
                'repairTypes' => self::TYPES,
                'priorities' => self::PRIORITIES,
                'statusLabels' => $this->statusLabels(),
                'priorityLabels' => $this->priorityLabels(),
                'typeLabels' => $this->typeLabels(),
                'canManageRepairs' => $user->canManageRequests(),
                'featureMessage' => 'Tabula repairs šobrīd nav pieejama.',
            ]);
        }

        $summaryQuery = $this->visibleRepairsQuery($user)
            ->when($filters['mine'] && $user->canManageRequests(), fn (Builder $query) => $query->where('accepted_by', $user->id));

        $repairs = $this->visibleRepairsQuery($user)
            ->with(['device.building', 'device.room', 'executor', 'acceptedBy', 'request.responsibleUser', 'request.reviewedBy'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $searchQuery) use ($term) {
                    $searchQuery->where('description', 'like', "%{$term}%")
                        ->orWhere('invoice_number', 'like', "%{$term}%")
                        ->orWhere('vendor_name', 'like', "%{$term}%")
                        ->orWhereHas('device', function (Builder $deviceQuery) use ($term) {
                            $deviceQuery->where('code', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%");
                        });
                });
            })
            ->when($filters['status'] !== '' && in_array($filters['status'], self::STATUSES, true), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['priority'] !== '' && in_array($filters['priority'], self::PRIORITIES, true), fn (Builder $query) => $query->where('priority', $filters['priority']))
            ->when($filters['repair_type'] !== '' && in_array($filters['repair_type'], self::TYPES, true), fn (Builder $query) => $query->where('repair_type', $filters['repair_type']))
            ->when($filters['mine'] && $user->canManageRequests(), fn (Builder $query) => $query->where('accepted_by', $user->id))
            ->orderByRaw(
                ($filters['priority_sort'] === 'asc')
                    ? "case priority when 'low' then 0 when 'medium' then 1 when 'high' then 2 when 'critical' then 3 else 4 end"
                    : "case priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 when 'low' then 3 else 4 end"
            )
            ->orderByRaw("case when status = 'waiting' then 0 when status = 'in-progress' then 1 when status = 'completed' then 2 else 3 end")
            ->orderByDesc('id')
            ->get();

        $repairColumns = [
            'waiting' => $repairs->where('status', 'waiting')->values(),
            'in-progress' => $repairs->where('status', 'in-progress')->values(),
            'completed' => $repairs->filter(fn (Repair $repair) => in_array($repair->status, ['completed', 'cancelled'], true))->values(),
        ];

        return view('repairs.index', [
            'repairs' => $repairs,
            'repairColumns' => $repairColumns,
            'repairSummary' => [
                'total' => (clone $summaryQuery)->count(),
                'waiting' => (clone $summaryQuery)->where('status', 'waiting')->count(),
                'in_progress' => (clone $summaryQuery)->where('status', 'in-progress')->count(),
                'completed' => (clone $summaryQuery)->whereIn('status', ['completed', 'cancelled'])->count(),
            ],
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'repairTypes' => self::TYPES,
            'priorities' => self::PRIORITIES,
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
            'canManageRepairs' => $user->canManageRequests(),
        ]);
    }

    /**
     * Parāda jauna remonta formu administratoram.
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

        if (! in_array($validated['target_status'], $this->allowedTransitionTargets($repair->status), true)) {
            return back()->with('error', 'Sadu remonta statusa mainu veikt nevar.');
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
                'device_id' => ['Esosam remontam ierīci mainīt nevar. Atcel so remontu un izveido jaunu ierakstu pareizajai ierīcei.'],
            ]);
        }

        $device = Device::query()->find($validated['device_id']);
        if ($device && $device->status === Device::STATUS_WRITEOFF && (! $repair || (int) $repair->device_id !== (int) $device->id)) {
            throw ValidationException::withMessages([
                'device_id' => ['So ierīci nevar nodot remonta, jo ta ir norakstīta.'],
            ]);
        }

        if (! $repair && $device && $device->status !== Device::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'device_id' => ['Jaunu remontu var izveidot tikai aktīvai ierīcei.'],
            ]);
        }

        if (! $repair && $device && RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai ierīcei jau ir gaidošs remonta pieteikums.'],
            ]);
        }

        if (! $repair && $device && WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai ierīcei jau ir gaidošs norakstīšanas pieteikums.'],
            ]);
        }

        if (! $repair && $device && DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai ierīcei jau ir gaidošs nodošanas pieteikums.'],
            ]);
        }

        if ($device) {
            $activeRepairQuery = $device->repairs()->whereIn('status', ['waiting', 'in-progress']);
            if ($repair) {
                $activeRepairQuery->whereKeyNot($repair->id);
            }

            if ($activeRepairQuery->exists()) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai ierīcei jau ir aktīvs remonta ieraksts.'],
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
                $device->assignedTo?->full_name ? 'paslaik: ' . $device->assignedTo->full_name : null,
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
