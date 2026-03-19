<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RepairController extends Controller
{
    private const STATUSES = ['waiting', 'in-progress', 'completed', 'cancelled'];
    private const TYPES = ['internal', 'external'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'repair_type' => trim((string) $request->query('repair_type', '')),
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
                'filters' => $filters,
                'statuses' => self::STATUSES,
                'repairTypes' => self::TYPES,
                'priorities' => self::PRIORITIES,
                'statusLabels' => $this->statusLabels(),
                'priorityLabels' => $this->priorityLabels(),
                'typeLabels' => $this->typeLabels(),
                'canManageRepairs' => $user->canManageRequests(),
                'featureMessage' => 'Tabula repairs sobrid nav pieejama.',
            ]);
        }

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

    public function create(Request $request)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return view('repairs.create', array_merge($this->formData(), [
                'preselectedDeviceId' => $request->query('device_id'),
                'defaultExecutorId' => null,
                'featureMessage' => 'Tabula repairs sobrid nav pieejama.',
            ]));
        }

        return view('repairs.create', array_merge($this->formData(), [
            'preselectedDeviceId' => $request->query('device_id'),
            'defaultExecutorId' => null,
        ]));
    }

    public function store(Request $request)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Remontus sobrid nevar saglabat, jo tabula repairs nav pieejama.');
        }

        $validated = $this->validatedData($request);
        $validated['accepted_by'] = $manager->id;

        $repair = $this->createRepairRecord($validated);
        $repair->load(['device', 'executor', 'acceptedBy', 'request.responsibleUser', 'request.reviewedBy']);

        $this->syncDeviceStatus($repair);
        AuditTrail::created($manager->id, $repair);

        return redirect()->route('repairs.index')->with('success', 'Remonts veiksmigi pievienots');
    }

    public function edit(Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Tabula repairs sobrid nav pieejama.');
        }

        $repair->load(['device', 'executor', 'acceptedBy', 'request.responsibleUser', 'request.reviewedBy']);

        return view('repairs.edit', array_merge([
            'repair' => $repair,
        ], $this->formData($repair)));
    }

    public function update(Request $request, Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Tabula repairs sobrid nav pieejama.');
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

        return redirect()->route('repairs.edit', $repair)->with('success', 'Remonts atjauninats');
    }

    public function destroy(Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return redirect()->route('repairs.index')->with('error', 'Tabula repairs sobrid nav pieejama.');
        }

        $previousStatus = $repair->status;
        AuditTrail::deleted(auth()->id(), $repair);
        $repair->delete();
        $this->restoreDeviceAfterRepairRemoval($repair->device_id, $previousStatus, null);

        return redirect()->route('repairs.index')->with('success', 'Remonts dzests');
    }

    public function transition(Request $request, Repair $repair)
    {
        $this->requireManager();

        if (! $this->featureTableExists('repairs')) {
            return back()->with('error', 'Tabula repairs sobrid nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'target_status' => ['required', Rule::in(self::STATUSES)],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'target_status.required' => 'Izvelies jauno remonta statusu.',
        ]);

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
        } elseif ($validated['target_status'] === 'in-progress') {
            $payload['start_date'] = filled($repair->start_date) ? $repair->start_date->toDateString() : now()->toDateString();
            $payload['end_date'] = null;
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
            description: 'Remonta statuss mainits: ' . $this->labelForStatus((string) ($before['status'] ?? 'waiting')) . ' -> ' . $this->labelForStatus((string) ($after['status'] ?? 'waiting'))
        );

        return back()->with('success', 'Remonta statuss atjauninats');
    }

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
        return [
            'devices' => Device::query()
                ->with(['assignedTo', 'building', 'room', 'type'])
                ->where('status', '!=', Device::STATUS_WRITEOFF)
                ->when($repair?->device_id, fn (Builder $query) => $query->orWhere('id', $repair->device_id))
                ->orderBy('name')
                ->get(),
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
            'issue_reported_by' => ['nullable', 'exists:users,id'],
            'description' => ['required', 'string'],
            'repair_type' => ['required', Rule::in(self::TYPES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'request_id' => ['nullable', 'exists:repair_requests,id'],
        ], [
            'device_id.required' => 'Izvelies ierici remonta ierakstam.',
            'description.required' => 'Apraksti remonta darbu vai problemu.',
            'repair_type.required' => 'Izvelies remonta tipu.',
        ]);

        foreach ([
            'issue_reported_by',
            'priority',
            'vendor_name',
            'vendor_contact',
            'invoice_number',
            'request_id',
        ] as $field) {
            $validated[$field] = $validated[$field] ?: null;
        }

        $validated['status'] = $repair?->status ?? 'waiting';
        $validated['priority'] = $validated['priority'] ?? ($repair?->priority ?? 'medium');
        $validated['issue_reported_by'] = $validated['issue_reported_by'] ?? $repair?->issue_reported_by ?? null;
        $validated['accepted_by'] = $repair?->accepted_by ?? $this->user()?->id;
        $validated['request_id'] = $validated['request_id'] ?? $repair?->request_id ?? null;
        $validated['start_date'] = $repair?->start_date?->toDateString();
        $validated['end_date'] = $repair?->end_date?->toDateString();

        $device = Device::query()->find($validated['device_id']);
        if ($device && $device->status === Device::STATUS_WRITEOFF && (! $repair || (int) $repair->device_id !== (int) $device->id)) {
            throw ValidationException::withMessages([
                'device_id' => ['So ierici nevar nodot remonta, jo ta ir norakstita.'],
            ]);
        }

        if (! $repair && $device && $device->status !== Device::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'device_id' => ['Jaunu remontu var izveidot tikai aktivai iericei.'],
            ]);
        }

        if ($device) {
            $activeRepairQuery = $device->repairs()->whereIn('status', ['waiting', 'in-progress']);
            if ($repair) {
                $activeRepairQuery->whereKeyNot($repair->id);
            }

            if ($activeRepairQuery->exists()) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir aktivs remonta ieraksts.'],
                ]);
            }
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
                'vendor_name' => ['Arejam remontam noradi pakalpojuma sniedzeju.'],
            ]);
        }

        if ($isExternalInProcess && ! filled($validated['vendor_contact'])) {
            throw ValidationException::withMessages([
                'vendor_contact' => ['Arejam remontam noradi pakalpojuma sniedzeja kontaktu.'],
            ]);
        }

        return $validated;
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
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
    }

    private function priorityLabels(): array
    {
        return [
            'low' => 'Zema',
            'medium' => 'Videja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
    }

    private function typeLabels(): array
    {
        return [
            'internal' => 'Ieksejais',
            'external' => 'Arejais',
        ];
    }

    private function labelForStatus(string $status): string
    {
        return $this->statusLabels()[$status] ?? $status;
    }
}
