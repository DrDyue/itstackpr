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
    private const RESTORABLE_DEVICE_STATUSES = ['active', 'reserve', 'broken', 'kitting'];

    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'repair_type' => trim((string) $request->query('repair_type', '')),
        ];

        $repairs = $this->visibleRepairsQuery($user)
            ->with(['device.building', 'device.room', 'reporter', 'assignee', 'acceptedBy', 'request'])
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
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('repairs.index', [
            'repairs' => $repairs,
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

        return view('repairs.create', array_merge($this->formData(), [
            'preselectedDeviceId' => $request->query('device_id'),
            'defaultReporterId' => $this->user()?->id,
        ]));
    }

    public function store(Request $request)
    {
        $manager = $this->requireManager();
        $validated = $this->validatedData($request);
        $device = Device::query()->findOrFail($validated['device_id']);
        $validated['device_status_before_repair'] = $this->normalizeDeviceStatusForRestore($device->status);
        $validated['accepted_by_user_id'] = $manager->id;

        $repair = Repair::create($validated);
        $repair->load(['device', 'reporter', 'assignee', 'acceptedBy']);

        $this->syncDeviceStatus($repair);
        AuditTrail::created($manager->id, $repair);

        return redirect()->route('repairs.index')->with('success', 'Remonts veiksmigi pievienots');
    }

    public function edit(Repair $repair)
    {
        $this->requireManager();
        $repair->load(['device', 'reporter', 'assignee', 'acceptedBy']);

        return view('repairs.edit', array_merge([
            'repair' => $repair,
        ], $this->formData($repair)));
    }

    public function update(Request $request, Repair $repair)
    {
        $this->requireManager();

        $before = $repair->only([
            'device_id',
            'reported_by_user_id',
            'assigned_to_user_id',
            'accepted_by_user_id',
            'description',
            'status',
            'repair_type',
            'priority',
            'start_date',
            'estimated_completion',
            'actual_completion',
            'diagnosis',
            'resolution_notes',
            'cost',
            'vendor_name',
            'vendor_contact',
            'invoice_number',
        ]);

        $repair->update($this->validatedData($request, $repair));
        $repair->load(['device', 'reporter', 'assignee', 'acceptedBy']);
        $this->syncDeviceStatus($repair, $before['status'] ?? null);

        $after = $repair->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $repair, $before, $after);

        return redirect()->route('repairs.edit', $repair)->with('success', 'Remonts atjauninats');
    }

    public function destroy(Repair $repair)
    {
        $this->requireManager();

        $previousStatus = $repair->status;
        AuditTrail::deleted(auth()->id(), $repair);
        $repair->delete();
        $this->restoreDeviceAfterRepairRemoval($repair->device_id, $previousStatus, $repair->device_status_before_repair);

        return redirect()->route('repairs.index')->with('success', 'Remonts dzests');
    }

    public function transition(Request $request, Repair $repair)
    {
        $this->requireManager();

        $validated = $request->validate([
            'target_status' => ['required', Rule::in(self::STATUSES)],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $before = $repair->only(['status', 'assigned_to_user_id', 'estimated_completion', 'actual_completion', 'cost']);
        $payload = ['status' => $validated['target_status']];

        if (filled($validated['assigned_to_user_id'] ?? null)) {
            $payload['assigned_to_user_id'] = $validated['assigned_to_user_id'];
        }

        if (filled($validated['estimated_completion'] ?? null)) {
            $payload['estimated_completion'] = $validated['estimated_completion'];
        }

        if (array_key_exists('cost', $validated) && $validated['cost'] !== null && $validated['cost'] !== '') {
            $payload['cost'] = $validated['cost'];
        }

        if ($validated['target_status'] === 'in-progress' && ! filled($repair->start_date)) {
            $payload['start_date'] = now()->toDateString();
        }

        if ($validated['target_status'] === 'completed') {
            $payload['actual_completion'] = $validated['actual_completion'] ?? now()->toDateString();
        } elseif (array_key_exists('actual_completion', $validated)) {
            $payload['actual_completion'] = $validated['actual_completion'] ?: null;
        }

        $repair->update($payload);
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
                $builder->where('reported_by_user_id', $user->id)
                    ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('assigned_user_id', $user->id));
            });
        });
    }

    private function formData(?Repair $repair = null): array
    {
        return [
            'devices' => Device::query()
                ->with(['assignedUser', 'building', 'room', 'type'])
                ->where('status', '!=', 'written_off')
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
        $validated = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'reported_by_user_id' => ['nullable', 'exists:users,id'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'description' => ['required', 'string'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'repair_type' => ['required', Rule::in(self::TYPES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'start_date' => ['nullable', 'date'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
            'diagnosis' => ['nullable', 'string'],
            'resolution_notes' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
        ]);

        foreach ([
            'reported_by_user_id',
            'assigned_to_user_id',
            'status',
            'priority',
            'start_date',
            'estimated_completion',
            'actual_completion',
            'diagnosis',
            'resolution_notes',
            'vendor_name',
            'vendor_contact',
            'invoice_number',
        ] as $field) {
            $validated[$field] = $validated[$field] ?: null;
        }

        $validated['status'] = $validated['status'] ?? ($repair?->status ?? 'waiting');
        $validated['priority'] = $validated['priority'] ?? ($repair?->priority ?? 'medium');
        $validated['reported_by_user_id'] = $validated['reported_by_user_id'] ?? $this->user()?->id;
        $validated['assigned_to_user_id'] = $validated['assigned_to_user_id'] ?? $repair?->assigned_to_user_id;

        $device = Device::query()->find($validated['device_id']);
        if ($device && $device->status === 'written_off' && (! $repair || (int) $repair->device_id !== (int) $device->id)) {
            throw ValidationException::withMessages([
                'device_id' => ['So ierici nevar nodot remonta, jo ta ir norakstita.'],
            ]);
        }

        if ($validated['repair_type'] === 'internal') {
            $validated['vendor_name'] = null;
            $validated['vendor_contact'] = null;
            $validated['invoice_number'] = null;
        }

        if ($validated['repair_type'] === 'external' && ! filled($validated['vendor_name'])) {
            throw ValidationException::withMessages([
                'vendor_name' => ['Arejam remontam noradi pakalpojuma sniedzeju.'],
            ]);
        }

        if ($validated['repair_type'] === 'external' && ! filled($validated['vendor_contact'])) {
            throw ValidationException::withMessages([
                'vendor_contact' => ['Arejam remontam noradi pakalpojuma sniedzeja kontaktu.'],
            ]);
        }

        if (
            filled($validated['estimated_completion'])
            && filled($validated['start_date'])
            && strtotime((string) $validated['estimated_completion']) < strtotime((string) $validated['start_date'])
        ) {
            throw ValidationException::withMessages([
                'estimated_completion' => ['Planotais beigums nevar but agraks par sakuma datumu.'],
            ]);
        }

        if (
            filled($validated['actual_completion'])
            && filled($validated['start_date'])
            && strtotime((string) $validated['actual_completion']) < strtotime((string) $validated['start_date'])
        ) {
            throw ValidationException::withMessages([
                'actual_completion' => ['Faktiskais beigums nevar but agraks par sakuma datumu.'],
            ]);
        }

        if ($validated['status'] === 'completed' && ! filled($validated['actual_completion'])) {
            $validated['actual_completion'] = now()->toDateString();
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
            $targetStatus = $this->normalizeDeviceStatusForRestore($repair->device_status_before_repair);
            $before = ['status' => $device->status];
            $device->forceFill(['status' => $targetStatus])->save();
            AuditTrail::updatedFromState(auth()->id(), $device, $before, ['status' => $targetStatus]);
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
            $device->forceFill([
                'status' => $this->normalizeDeviceStatusForRestore($restoreStatus),
            ])->save();
        }
    }

    private function normalizeDeviceStatusForRestore(?string $status): string
    {
        return in_array($status, self::RESTORABLE_DEVICE_STATUSES, true) ? $status : 'active';
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
