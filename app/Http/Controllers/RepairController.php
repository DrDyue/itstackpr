<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\Employee;
use App\Models\Repair;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RepairController extends Controller
{
    private const STATUSES = ['waiting', 'in-progress', 'completed', 'cancelled'];
    private const TYPES = ['internal', 'external'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    private const DEVICE_RESTORABLE_STATUSES = ['active', 'reserve', 'broken', 'kitting'];
    private const DEVICE_STATUSES_BLOCKED_FOR_NEW_REPAIR = ['repair', 'retired'];
    private const ALLOWED_TRANSITIONS = [
        'waiting' => ['in-progress', 'cancelled'],
        'in-progress' => ['waiting', 'completed', 'cancelled'],
        'completed' => ['in-progress'],
        'cancelled' => ['waiting', 'in-progress'],
    ];

    public function index(Request $request)
    {
        $ownership = $this->defaultOwnershipFilter($request);
        $filters = [
            'q' => (string) $request->query('q', ''),
            'status' => (string) $request->query('status', ''),
            'repair_type' => (string) $request->query('repair_type', ''),
            'building_id' => (string) $request->query('building_id', ''),
            'priority' => (string) $request->query('priority', ''),
            'ownership' => $ownership,
        ];

        $repairs = $this->applyFilters($this->repairsQuery(), $request)
            ->orderByDesc('id')
            ->get();

        $quickFilterRepairs = $this->applyFilters($this->repairsQuery(), $request, ['repair_type'])
            ->get(['id', 'repair_type']);

        $columns = [
            'waiting' => $repairs->where('status', 'waiting')->values(),
            'in-progress' => $repairs->where('status', 'in-progress')->values(),
            'completed' => $repairs->where('status', 'completed')->values(),
        ];

        $cancelledRepairs = $repairs->where('status', 'cancelled')->values();
        $stats = $this->statsFor($repairs, $columns, $cancelledRepairs);

        return view('repairs.index', array_merge([
            'repairs' => $repairs,
            'columns' => $columns,
            'cancelledRepairs' => $cancelledRepairs,
            'stats' => $stats,
            'buildings' => Building::query()->orderBy('building_name')->get(),
            'filters' => $filters,
            'quickTypeCounts' => [
                'all' => $quickFilterRepairs->count(),
                'internal' => $quickFilterRepairs->where('repair_type', 'internal')->count(),
                'external' => $quickFilterRepairs->where('repair_type', 'external')->count(),
            ],
        ], $this->viewMeta()));
    }

    public function create(Request $request)
    {
        return view('repairs.create', array_merge($this->formData(), [
            'defaultReporterId' => auth()->user()?->employee_id,
            'preselectedDeviceId' => $request->query('device_id'),
        ], $this->viewMeta()));
    }

    public function store(Request $request)
    {
        $repair = Repair::create($this->validatedData($request));
        $repair->load(['device', 'reporter', 'legacyReporter.employee', 'assignee.employee']);
        $this->syncDeviceStatus($repair);
        AuditTrail::created(auth()->id(), $repair, severity: null);

        return redirect()->route('repairs.index')->with('success', 'Remonts veiksmigi pievienots');
    }

    public function edit(Repair $repair)
    {
        $this->authorizeRepairAccess($repair);
        $repair->load(['device.building', 'device.room', 'device.type', 'reporter', 'legacyReporter.employee', 'assignee.employee']);

        return view('repairs.edit', array_merge([
            'repair' => $repair,
            'timeline' => $this->timelineFor($repair),
        ], $this->formData($repair), $this->viewMeta()));
    }

    public function update(Request $request, Repair $repair)
    {
        $this->authorizeRepairAccess($repair);
        $before = $repair->only([
            'device_id', 'description', 'status', 'repair_type', 'priority', 'start_date',
            'estimated_completion', 'actual_completion', 'cost', 'vendor_name', 'vendor_contact',
            'invoice_number', 'issue_reported_by', 'reported_employee_id', 'assigned_to',
        ]);
        $repair->update($this->validatedData($request));
        $repair->load(['device', 'reporter', 'legacyReporter.employee', 'assignee.employee']);
        $this->syncDeviceStatus($repair, $before['status'] ?? null);
        $after = $repair->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(auth()->id(), $repair, $before, $after);

        return redirect()->route('repairs.edit', $repair)->with('success', 'Remonts atjauninats');
    }

    public function destroy(Repair $repair)
    {
        $this->authorizeRepairAccess($repair);
        $previousStatus = $repair->status;
        AuditTrail::deleted(auth()->id(), $repair);
        $repair->delete();
        $this->syncDeviceStatus($repair, $previousStatus);

        return redirect()->route('repairs.index')->with('success', 'Remonts dzests');
    }

    public function transition(Request $request, Repair $repair)
    {
        $this->authorizeRepairAccess($repair);
        $data = $request->validate([
            'target_status' => ['required', Rule::in(self::STATUSES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $targetStatus = $data['target_status'];

        if (! $this->isTransitionAllowed((string) $repair->status, $targetStatus)) {
            return back()->withErrors([
                'transition' => 'Sadu statusa parvietosanu nevar izpildit no pasreizeja stavokla.',
            ]);
        }

        $before = $repair->only([
            'status',
            'assigned_to',
            'estimated_completion',
            'actual_completion',
            'cost',
        ]);

        $payload = ['status' => $targetStatus];

        if ($targetStatus === 'in-progress' && empty($repair->assigned_to) && auth()->check()) {
            $payload['assigned_to'] = $data['assigned_to'] ?? auth()->id();
        } elseif (array_key_exists('assigned_to', $data) && filled($data['assigned_to'] ?? null)) {
            $payload['assigned_to'] = $data['assigned_to'];
        }

        if ($targetStatus === 'in-progress' && ! filled($repair->start_date)) {
            $payload['start_date'] = now()->toDateString();
        }

        if ($targetStatus === 'completed') {
            if ($repair->repair_type === 'external') {
                if (! filled($repair->vendor_name)) {
                    return back()->withErrors([
                        'vendor_name' => 'Arejam remontam pirms pabeigsanas noradi pakalpojuma sniedzeju.',
                    ]);
                }

                if (! filled($repair->vendor_contact)) {
                    return back()->withErrors([
                        'vendor_contact' => 'Arejam remontam pirms pabeigsanas noradi pakalpojuma sniedzeja kontaktu.',
                    ]);
                }

                if (! filled($repair->invoice_number)) {
                    return back()->withErrors([
                        'invoice_number' => 'Arejam remontam pirms pabeigsanas noradi rekina numuru.',
                    ]);
                }
            }

            $payload['actual_completion'] = now()->toDateString();
        } elseif ($targetStatus !== 'completed' && $repair->status === 'completed') {
            $payload['actual_completion'] = null;
        }

        if (filled($data['estimated_completion'] ?? null)) {
            $payload['estimated_completion'] = $data['estimated_completion'];
        }

        if (array_key_exists('cost', $data) && $data['cost'] !== null && $data['cost'] !== '') {
            $payload['cost'] = $data['cost'];
        }

        $repair->update($payload);
        $repair->load(['device', 'reporter', 'legacyReporter.employee', 'assignee.employee']);
        $this->syncDeviceStatus($repair, $before['status'] ?? null);

        $after = $repair->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState(
            auth()->id(),
            $repair,
            $before,
            $after,
            description: 'Remonta statuss mainits: ' . $this->statusLabel($before['status'] ?? 'waiting') . ' -> ' . $this->statusLabel($after['status'] ?? 'waiting')
        );

        return back()->with('success', 'Remonta statuss atjauninats');
    }

    public function show(Repair $repair)
    {
        return redirect()->route('repairs.index');
    }

    private function formData(?Repair $repair = null): array
    {
        $devices = Device::query()
            ->with(['createdBy.employee', 'building', 'room', 'type'])
            ->whereNotIn('status', self::DEVICE_STATUSES_BLOCKED_FOR_NEW_REPAIR)
            ->when($repair?->device_id, function ($query) use ($repair) {
                $query->orWhere('id', $repair->device_id);
            })
            ->orderBy('name')
            ->get();

        return [
            'devices' => $devices,
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->get(),
            'users' => User::with('employee')->where('is_active', true)->orderByDesc('id')->get(),
            'buildings' => Building::query()->orderBy('building_name')->get(),
            'statuses' => self::STATUSES,
            'repairTypes' => self::TYPES,
            'priorities' => self::PRIORITIES,
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'repair_type' => ['required', Rule::in(self::TYPES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'start_date' => ['nullable', 'date'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'issue_reported_by' => ['nullable', 'exists:employees,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        foreach ([
            'status', 'priority', 'estimated_completion', 'actual_completion',
            'vendor_name', 'vendor_contact', 'invoice_number', 'issue_reported_by', 'assigned_to',
        ] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        $repair = $request->route('repair');
        $device = Device::query()->find($data['device_id']);

        if (
            $device
            && in_array($device->status, self::DEVICE_STATUSES_BLOCKED_FOR_NEW_REPAIR, true)
            && (! $repair || (int) $repair->device_id !== (int) $device->id)
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'device_id' => ['So ierici nevar nodot remonta, jo ta jau ir remonta vai ir norakstita.'],
            ]);
        }

        $data['status'] = $data['status'] ?? ($repair?->status ?? 'waiting');
        $data['priority'] = $data['priority'] ?? ($repair?->priority ?? 'medium');
        $data['start_date'] = filled($data['start_date'] ?? null)
            ? $data['start_date']
            : ($repair?->start_date?->format('Y-m-d') ?? null);

        if (($data['status'] ?? null) === 'waiting' && ! filled($data['start_date'] ?? null) && ! $this->allowsNullStartDate()) {
            // Backward compatibility for environments where repairs.start_date is still NOT NULL.
            $data['start_date'] = now()->toDateString();
        }

        if ($this->supportsDeviceStatusBeforeRepairColumn()) {
            $data['device_status_before_repair'] = $repair?->device_status_before_repair
                ?? $this->normalizeDeviceStatusForRestore($device?->status);
        }

        if ($data['repair_type'] === 'internal') {
            $data['vendor_name'] = null;
            $data['vendor_contact'] = null;
            $data['invoice_number'] = null;
        }

        if ($data['repair_type'] === 'external') {
            if (! filled($data['vendor_name'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'vendor_name' => ['Arejam remontam noradi pakalpojuma sniedzeju.'],
                ]);
            }

            if (! filled($data['vendor_contact'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'vendor_contact' => ['Arejam remontam noradi pakalpojuma sniedzeja kontaktu.'],
                ]);
            }
        }

        if (($data['issue_reported_by'] ?? null) === null && auth()->check()) {
            $data['issue_reported_by'] = auth()->user()?->employee_id;
        }

        if (($data['assigned_to'] ?? null) === null) {
            $data['assigned_to'] = $device?->created_by;
        }

        $selectedEmployeeId = $data['issue_reported_by'] ?? null;
        $reporterUserId = null;

        if ($selectedEmployeeId !== null) {
            $reporterUserId = User::query()
                ->where('employee_id', $selectedEmployeeId)
                ->value('id');
        }

        if ($this->supportsReportedEmployeeColumn()) {
            $data['reported_employee_id'] = $selectedEmployeeId;
            $data['issue_reported_by'] = $reporterUserId;
        } else {
            if ($selectedEmployeeId !== null && $reporterUserId === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'issue_reported_by' => ['So darbinieku var saglabat tikai pec datubazes migracijas palaides.'],
                ]);
            }

            $data['issue_reported_by'] = $reporterUserId;
        }

        if (
            ! empty($data['estimated_completion'])
            && strtotime((string) $data['estimated_completion']) < strtotime((string) $data['start_date'])
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'estimated_completion' => ['Planotais beigums nevar but agraks par sakuma datumu.'],
            ]);
        }

        if (
            ! empty($data['actual_completion'])
            && strtotime((string) $data['actual_completion']) < strtotime((string) $data['start_date'])
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'actual_completion' => ['Faktiskais beigums nevar but agraks par sakuma datumu.'],
            ]);
        }

        if (($data['status'] ?? null) === 'in-progress' && $repair && empty($data['estimated_completion'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'estimated_completion' => ['Procesa remontam noradi planoto beigu datumu.'],
            ]);
        }

        if (($data['status'] ?? null) === 'waiting') {
            $data['cost'] = null;
            $data['estimated_completion'] = null;
            $data['actual_completion'] = null;
        }

        if (($data['status'] ?? null) === 'completed' && empty($data['actual_completion'])) {
            $data['actual_completion'] = $repair?->actual_completion?->format('Y-m-d') ?? now()->toDateString();
        }

        if (($data['status'] ?? null) === 'completed' && (($data['cost'] ?? null) === null || $data['cost'] === '')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cost' => ['Pabeigtam remontam noradi gala izmaksas.'],
            ]);
        }

        return $data;
    }

    private function repairsQuery(): Builder
    {
        return Repair::query()->with([
            'device.building',
            'device.room',
            'device.type',
            'reporter',
            'legacyReporter.employee',
            'assignee.employee',
        ]);
    }

    private function applyFilters(Builder $query, Request $request, array $ignoredFilters = []): Builder
    {
        $ownership = in_array('ownership', $ignoredFilters, true)
            ? $this->defaultOwnershipFilter($request)
            : (string) $request->query('ownership', $this->defaultOwnershipFilter($request));
        $q = in_array('q', $ignoredFilters, true) ? '' : trim((string) $request->query('q', ''));
        $status = in_array('status', $ignoredFilters, true) ? '' : (string) $request->query('status', '');
        $repairType = in_array('repair_type', $ignoredFilters, true) ? '' : (string) $request->query('repair_type', '');
        $buildingId = in_array('building_id', $ignoredFilters, true) ? '' : (string) $request->query('building_id', '');
        $priority = in_array('priority', $ignoredFilters, true) ? '' : (string) $request->query('priority', '');
        $user = auth()->user();
        $employeeId = $user?->employee_id;

        return $query
            ->when($user && $user->role !== 'admin', function (Builder $builder) use ($ownership, $user, $employeeId) {
                $builder->where(function (Builder $ownedBuilder) use ($ownership, $user, $employeeId) {
                    if ($ownership === 'assigned-to-me') {
                        $ownedBuilder->where('assigned_to', $user->id);

                        return;
                    }

                    if ($ownership === 'reported-by-me') {
                        if ($this->supportsReportedEmployeeColumn()) {
                            $ownedBuilder->where('reported_employee_id', $employeeId);
                        } else {
                            $ownedBuilder->where('issue_reported_by', $user->id);
                        }

                        return;
                    }

                    $ownedBuilder->where('assigned_to', $user->id)
                        ->orWhere(function (Builder $reporterBuilder) use ($user, $employeeId) {
                            if ($this->supportsReportedEmployeeColumn()) {
                                $reporterBuilder->where('reported_employee_id', $employeeId);
                            } else {
                                $reporterBuilder->where('issue_reported_by', $user->id);
                            }
                        });
                });
            })
            ->when($status !== '' && in_array($status, self::STATUSES, true), function (Builder $builder) use ($status) {
                $builder->where('status', $status);
            })
            ->when($repairType !== '' && in_array($repairType, self::TYPES, true), function (Builder $builder) use ($repairType) {
                $builder->where('repair_type', $repairType);
            })
            ->when($priority !== '' && in_array($priority, self::PRIORITIES, true), function (Builder $builder) use ($priority) {
                $builder->where('priority', $priority);
            })
            ->when($buildingId !== '', function (Builder $builder) use ($buildingId) {
                $builder->whereHas('device', function (Builder $deviceQuery) use ($buildingId) {
                    $deviceQuery->where('building_id', $buildingId);
                });
            })
            ->when($q !== '', function (Builder $builder) use ($q) {
                $builder->where(function (Builder $searchQuery) use ($q) {
                    $searchQuery
                        ->where('description', 'like', "%{$q}%")
                        ->orWhere('invoice_number', 'like', "%{$q}%")
                        ->orWhere('vendor_name', 'like', "%{$q}%")
                        ->orWhereHas('device', function (Builder $deviceQuery) use ($q) {
                            $deviceQuery
                                ->where('code', 'like', "%{$q}%")
                                ->orWhere('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('device.building', function (Builder $buildingQuery) use ($q) {
                            $buildingQuery->where('building_name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('device.room', function (Builder $roomQuery) use ($q) {
                            $roomQuery
                                ->where('room_number', 'like', "%{$q}%")
                                ->orWhere('room_name', 'like', "%{$q}%");
                        });
                });
            });
    }

    private function statsFor($repairs, array $columns, $cancelledRepairs): array
    {
        $completed = $columns['completed'];
        $activeRepairs = $columns['waiting']->concat($columns['in-progress']);
        $averageDays = $completed
            ->filter(fn (Repair $repair) => $repair->start_date && $repair->actual_completion)
            ->map(fn (Repair $repair) => Carbon::parse($repair->start_date)->diffInDays(Carbon::parse($repair->actual_completion)))
            ->avg();

        return [
            'total' => $repairs->count(),
            'waiting' => $columns['waiting']->count(),
            'in_progress' => $columns['in-progress']->count(),
            'completed' => $columns['completed']->count(),
            'cancelled' => $cancelledRepairs->count(),
            'active_cost' => (float) $activeRepairs->sum(fn (Repair $repair) => (float) ($repair->cost ?? 0)),
            'completed_cost' => (float) $completed->sum(fn (Repair $repair) => (float) ($repair->cost ?? 0)),
            'average_days' => $averageDays !== null ? round((float) $averageDays, 1) : null,
            'latest_started_at' => $repairs->filter(fn (Repair $repair) => $repair->created_at)->max('created_at'),
        ];
    }

    private function timelineFor(Repair $repair)
    {
        return AuditLog::query()
            ->with('user.employee')
            ->where('entity_type', 'Repair')
            ->where('entity_id', (string) $repair->id)
            ->orderByDesc('timestamp')
            ->limit(20)
            ->get();
    }

    private function syncDeviceStatus(Repair $repair, ?string $previousStatus = null): void
    {
        $device = $repair->device()->first();

        if (! $device) {
            return;
        }

        $hasActiveRepairs = $device->repairs()
            ->whereIn('status', ['waiting', 'in-progress'])
            ->exists();

        if ($hasActiveRepairs) {
            $this->updateDeviceStatusForRepair($device, 'repair', $repair);

            return;
        }

        if (($previousStatus === 'waiting' || $previousStatus === 'in-progress' || $device->status === 'repair')) {
            $this->updateDeviceStatusForRepair(
                $device,
                $this->statusBeforeRepair($repair, $device),
                $repair
            );
        }
    }

    private function updateDeviceStatusForRepair(Device $device, string $targetStatus, Repair $repair): void
    {
        if ($device->status === $targetStatus) {
            return;
        }

        $oldStatus = (string) $device->status;
        $device->forceFill(['status' => $targetStatus])->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action' => 'STATUS_CHANGE',
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $targetStatus,
            'changed_by' => auth()->id(),
        ]);

        AuditTrail::writeForModel(
            auth()->id(),
            'UPDATE',
            $device,
            'Ierices statuss saskanots no remonta #' . $repair->id . ': ' . $this->deviceStatusLabel($oldStatus) . ' -> ' . $this->deviceStatusLabel($targetStatus),
            'info'
        );
    }

    private function normalizeDeviceStatusForRestore(?string $status): string
    {
        return in_array($status, self::DEVICE_RESTORABLE_STATUSES, true)
            ? $status
            : 'active';
    }

    private function statusBeforeRepair(Repair $repair, Device $device): string
    {
        if ($this->supportsDeviceStatusBeforeRepairColumn() && filled($repair->device_status_before_repair)) {
            return $this->normalizeDeviceStatusForRestore($repair->device_status_before_repair);
        }

        $latestStatusChange = DeviceHistory::query()
            ->where('device_id', $device->id)
            ->where('field_changed', 'status')
            ->where('new_value', 'repair')
            ->latest('timestamp')
            ->value('old_value');

        return $this->normalizeDeviceStatusForRestore($latestStatusChange);
    }

    private function supportsDeviceStatusBeforeRepairColumn(): bool
    {
        static $hasColumn;

        return $hasColumn ??= Schema::hasColumn('repairs', 'device_status_before_repair');
    }

    private function supportsReportedEmployeeColumn(): bool
    {
        static $hasColumn;

        return $hasColumn ??= Schema::hasColumn('repairs', 'reported_employee_id');
    }

    private function allowsNullStartDate(): bool
    {
        static $allowsNull;

        if ($allowsNull !== null) {
            return $allowsNull;
        }

        $databaseName = DB::getDatabaseName();

        $column = DB::table('information_schema.columns')
            ->select('is_nullable')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'repairs')
            ->where('column_name', 'start_date')
            ->first();

        return $allowsNull = strtoupper((string) ($column->is_nullable ?? 'YES')) === 'YES';
    }

    private function defaultOwnershipFilter(Request $request): string
    {
        return auth()->user()?->role === 'admin'
            ? (string) $request->query('ownership', '')
            : (string) $request->query('ownership', 'all-mine');
    }

    private function authorizeRepairAccess(Repair $repair): void
    {
        $user = auth()->user();

        if (! $user || $user->role === 'admin') {
            return;
        }

        $employeeId = $user->employee_id;
        $reportedEmployeeId = $this->supportsReportedEmployeeColumn()
            ? ($repair->reported_employee_id ?? null)
            : null;

        $hasAccess = (int) ($repair->assigned_to ?? 0) === (int) $user->id
            || ($reportedEmployeeId !== null && (int) $reportedEmployeeId === (int) $employeeId)
            || (int) ($repair->issue_reported_by ?? 0) === (int) $user->id;

        abort_unless($hasAccess, 403);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            default => 'Gaida',
        };
    }

    private function deviceStatusLabel(string $status): string
    {
        return match ($status) {
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'retired' => 'Norakstita',
            'kitting' => 'Komplektacija',
            default => 'Aktiva',
        };
    }

    private function isTransitionAllowed(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    private function viewMeta(): array
    {
        return [
            'statusLabels' => [
                'waiting' => 'Gaida',
                'in-progress' => 'Procesa',
                'completed' => 'Pabeigts',
                'cancelled' => 'Atcelts',
            ],
            'statusClasses' => [
                'waiting' => 'bg-amber-100 text-amber-800 ring-amber-200',
                'in-progress' => 'bg-sky-100 text-sky-800 ring-sky-200',
                'completed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                'cancelled' => 'bg-slate-200 text-slate-700 ring-slate-300',
            ],
            'typeLabels' => [
                'internal' => 'Ieksejais',
                'external' => 'Arejais',
            ],
            'typeIcons' => [
                'internal' => 'wrench',
                'external' => 'truck',
            ],
            'typeClasses' => [
                'internal' => 'bg-violet-100 text-violet-800 ring-violet-200',
                'external' => 'bg-rose-100 text-rose-800 ring-rose-200',
            ],
            'statusIcons' => [
                'waiting' => 'clock',
                'in-progress' => 'wrench',
                'completed' => 'check',
                'cancelled' => 'x-mark',
            ],
            'priorityLabels' => [
                'low' => 'Zema',
                'medium' => 'Videja',
                'high' => 'Augsta',
                'critical' => 'Kritiska',
            ],
            'priorityIcons' => [
                'low' => 'arrow-down',
                'medium' => 'bars',
                'high' => 'arrow-up',
                'critical' => 'flame',
            ],
            'priorityClasses' => [
                'low' => 'bg-slate-100 text-slate-700 ring-slate-200',
                'medium' => 'bg-amber-100 text-amber-800 ring-amber-200',
                'high' => 'bg-orange-100 text-orange-800 ring-orange-200',
                'critical' => 'bg-rose-100 text-rose-800 ring-rose-200',
            ],
        ];
    }
}
