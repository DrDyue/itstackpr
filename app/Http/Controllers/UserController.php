<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Lietotāju administrēšanas kontrolieris.
 */
class UserController extends Controller
{
    private const ROLES = [User::ROLE_ADMIN, User::ROLE_USER];
    private const SORTABLE_COLUMNS = ['full_name', 'email', 'phone', 'role', 'job_title', 'is_active', 'last_login'];

    public function index(Request $request)
    {
        $this->requireAdmin();

        $filters = [
            'search' => trim((string) $request->query('search', $request->query('q', ''))),
            'roles' => collect($request->query('role', []))
                ->map(fn (mixed $role) => trim((string) $role))
                ->filter(fn (string $role) => in_array($role, self::ROLES, true))
                ->unique()
                ->values()
                ->all(),
            'is_active' => (string) $request->query('is_active', ''),
            'last_login' => trim((string) $request->query('last_login', '')),
            'job_title_query' => trim((string) $request->query('job_title_query', '')),
            'email_query' => trim((string) $request->query('email_query', '')),
        ];
        $filters['has_role_filter'] = count($filters['roles']) > 0 && count($filters['roles']) < count(self::ROLES);
        $sorting = $this->normalizedSorting($request);

        $legacyName = trim((string) $request->query('name', ''));
        $legacyEmail = trim((string) $request->query('email', ''));

        $usersQuery = User::query()
            ->select(['id', 'full_name', 'email', 'phone', 'job_title', 'role', 'is_active', 'last_login'])
            ->when($legacyName !== '', fn ($query) => $query->where('full_name', 'like', '%' . $legacyName . '%'))
            ->when($legacyEmail !== '', fn ($query) => $query->where('email', 'like', '%' . $legacyEmail . '%'))
            ->when($filters['search'] !== '', fn ($query) => $query->where('full_name', 'like', '%' . $filters['search'] . '%'))
            ->when($filters['job_title_query'] !== '', fn ($query) => $query->where('job_title', 'like', '%' . $filters['job_title_query'] . '%'))
            ->when($filters['email_query'] !== '', fn ($query) => $query->where('email', 'like', '%' . $filters['email_query'] . '%'))
            ->when($filters['has_role_filter'], fn ($query) => $query->whereIn('role', $filters['roles']))
            ->when($filters['is_active'] !== '', fn ($query) => $query->where('is_active', $filters['is_active'] === '1'))
            ->when($filters['last_login'] === 'today', fn ($query) => $query->whereDate('last_login', today()))
            ->when($filters['last_login'] === 'recent', fn ($query) => $query->where('last_login', '>=', now()->subDays(7)))
            ->when($filters['last_login'] === 'never', fn ($query) => $query->whereNull('last_login'));

        $this->applySorting($usersQuery, $sorting);

        $users = $usersQuery
            ->paginate(15)
            ->withQueryString();

        $userSummaryQuery = User::query();

        AuditTrail::viewed($this->user(), 'User', null, 'Atvērts lietotāju saraksts.');

        if ($filters['search'] !== '' || $filters['has_role_filter'] || $filters['is_active'] !== '' || $filters['last_login'] !== '' || $filters['job_title_query'] !== '' || $filters['email_query'] !== '') {
            AuditTrail::filter($this->user(), 'User', [
                'vārds' => $filters['search'],
                'amats' => $filters['job_title_query'],
                'e-pasts' => $filters['email_query'],
                'lomas' => $filters['has_role_filter'] ? ($filters['roles'] ?? []) : [],
                'statuss' => $filters['is_active'],
                'pēdējā pieslēgšanās' => $filters['last_login'],
            ], 'Filtrēts lietotāju saraksts.');
        }

        if (($sorting['sort'] ?? 'full_name') !== 'full_name' || ($sorting['direction'] ?? 'asc') !== 'asc' || $request->has('sort')) {
            AuditTrail::sort(
                $this->user(),
                'User',
                $sorting['label'] ?? 'vārda un uzvārda',
                $sorting['direction'] ?? 'asc',
                'Kārtots lietotāju saraksts pēc '.($sorting['label'] ?? 'vārda un uzvārda').' '.(($sorting['direction'] ?? 'asc') === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā').'.'
            );
        }

        return view('users.index', [
            'users' => $users,
            'userSummary' => [
                'total' => (clone $userSummaryQuery)->count(),
                'admin' => (clone $userSummaryQuery)->where('role', User::ROLE_ADMIN)->count(),
                'user' => (clone $userSummaryQuery)->where('role', User::ROLE_USER)->count(),
            ],
            'filters' => $filters,
            'sorting' => $sorting,
            'sortOptions' => $this->sortOptions(),
            'roles' => self::ROLES,
            'roleLabels' => $this->roleLabels(),
            'selectedModalUser' => ctype_digit((string) $request->query('modal_user'))
                ? User::query()->select(['id', 'full_name', 'email', 'phone', 'job_title', 'role', 'is_active', 'last_login'])->find((int) $request->query('modal_user'))
                : null,
        ]);
    }

    /**
     * Atrod lietotāju pēc vārda un uzvārda aktīvajā filtrētajā sarakstā.
     */
    public function findByName(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($this->user(), 'User', $search, 'Meklēts lietotājs pēc vārda un uzvārda: '.$search);

        $filters = [
            'roles' => collect($request->query('role', []))
                ->map(fn (mixed $role) => trim((string) $role))
                ->filter(fn (string $role) => in_array($role, self::ROLES, true))
                ->unique()
                ->values()
                ->all(),
            'is_active' => (string) $request->query('is_active', ''),
            'last_login' => trim((string) $request->query('last_login', '')),
        ];
        $filters['has_role_filter'] = count($filters['roles']) > 0 && count($filters['roles']) < count(self::ROLES);
        $sorting = $this->normalizedSorting($request);

        $usersQuery = User::query()
            ->when($filters['has_role_filter'], fn ($query) => $query->whereIn('role', $filters['roles']))
            ->when($filters['is_active'] !== '', fn ($query) => $query->where('is_active', $filters['is_active'] === '1'))
            ->when($filters['last_login'] === 'today', fn ($query) => $query->whereDate('last_login', today()))
            ->when($filters['last_login'] === 'recent', fn ($query) => $query->where('last_login', '>=', now()->subDays(7)))
            ->when($filters['last_login'] === 'never', fn ($query) => $query->whereNull('last_login'));

        $this->applySorting($usersQuery, $sorting);

        $users = $usersQuery->get(['id', 'full_name']);

        $needle = mb_strtolower($search);
        $foundIndex = $users->search(function (User $user) use ($needle) {
            return str_contains(mb_strtolower($user->full_name), $needle);
        });

        if ($foundIndex === false) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        return response()->json([
            'found' => true,
            'page' => intdiv((int) $foundIndex, 15) + 1,
            'term' => $search,
            'highlight_id' => 'user-'.$users->values()[(int) $foundIndex]->id,
        ]);
    }

    public function redirectToCreateModal(): RedirectResponse
    {
        $this->requireAdmin();
        AuditTrail::viewed($this->user(), 'User', null, 'Atvērta lietotāja izveides forma.');

        return $this->redirectToUserModal('create');
    }

    public function show(User $user)
    {
        $this->requireAdmin();
        AuditTrail::viewed($this->user(), 'User', (string) $user->id, 'Atvērta lietotāja karte: '.AuditTrail::labelFor($user));

        $managedUser = User::query()
            ->select(['id', 'full_name', 'email', 'phone', 'job_title', 'role', 'is_active', 'last_login', 'created_at'])
            ->withCount([
                'assignedDevices',
                'repairRequests as active_repair_requests_count' => fn ($query) => $query->where('status', RepairRequest::STATUS_SUBMITTED),
                'writeoffRequests as active_writeoff_requests_count' => fn ($query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED),
                'outgoingTransfers as active_transfer_requests_count' => fn ($query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED),
                'incomingTransfers as incoming_transfer_requests_count' => fn ($query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED),
                'auditLogs as audit_logs_total_count',
            ])
            ->findOrFail($user->id);

        $assignedDevices = Device::query()
            ->select(['id', 'code', 'name', 'device_type_id', 'room_id', 'building_id'])
            ->with([
                'type:id,type_name',
                'room:id,room_number,room_name',
                'building:id,building_name',
            ])
            ->where('assigned_to_id', $managedUser->id)
            ->orderBy('code')
            ->limit(8)
            ->get();

        $openRepairRequests = RepairRequest::query()
            ->select(['id', 'device_id', 'responsible_user_id', 'description', 'status', 'created_at'])
            ->with([
                'device:id,code,name,device_type_id,room_id',
                'device.type:id,type_name',
                'device.room:id,room_number,room_name',
            ])
            ->where('responsible_user_id', $managedUser->id)
            ->where('status', RepairRequest::STATUS_SUBMITTED)
            ->latest()
            ->limit(6)
            ->get();

        $openWriteoffRequests = WriteoffRequest::query()
            ->select(['id', 'device_id', 'responsible_user_id', 'reason', 'status', 'created_at'])
            ->with([
                'device:id,code,name,device_type_id,room_id',
                'device.type:id,type_name',
                'device.room:id,room_number,room_name',
            ])
            ->where('responsible_user_id', $managedUser->id)
            ->where('status', WriteoffRequest::STATUS_SUBMITTED)
            ->latest()
            ->limit(6)
            ->get();

        $outgoingTransfers = DeviceTransfer::query()
            ->select(['id', 'device_id', 'responsible_user_id', 'transfered_to_id', 'transfer_reason', 'status', 'created_at'])
            ->with([
                'device:id,code,name,device_type_id,room_id',
                'device.type:id,type_name',
                'device.room:id,room_number,room_name',
                'transferTo:id,full_name',
            ])
            ->where('responsible_user_id', $managedUser->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->latest()
            ->limit(6)
            ->get();

        $incomingTransfers = DeviceTransfer::query()
            ->select(['id', 'device_id', 'responsible_user_id', 'transfered_to_id', 'transfer_reason', 'status', 'created_at'])
            ->with([
                'device:id,code,name,device_type_id,room_id',
                'device.type:id,type_name',
                'device.room:id,room_number,room_name',
                'responsibleUser:id,full_name',
            ])
            ->where('transfered_to_id', $managedUser->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->latest()
            ->limit(6)
            ->get();

        $recentAuditLogs = AuditLog::query()
            ->where('user_id', $managedUser->id)
            ->select(['id', 'user_id', 'action', 'entity_type', 'entity_id', 'description', 'severity', 'timestamp'])
            ->latest('timestamp')
            ->limit(5)
            ->get();

        $activityHistory = AuditLog::query()
            ->where('user_id', $managedUser->id)
            ->select(['id', 'user_id', 'action', 'entity_type', 'entity_id', 'description', 'severity', 'timestamp'])
            ->latest('timestamp')
            ->paginate(12, ['*'], 'activity_page')
            ->withQueryString();

        $managedUser->active_requests_total = (int) (
            ($managedUser->active_repair_requests_count ?? 0)
            + ($managedUser->active_writeoff_requests_count ?? 0)
            + ($managedUser->active_transfer_requests_count ?? 0)
            + ($managedUser->incoming_transfer_requests_count ?? 0)
        );

        return view('users.show', [
            'managedUser' => $managedUser,
            'assignedDevices' => $assignedDevices,
            'openRepairRequests' => $openRepairRequests,
            'openWriteoffRequests' => $openWriteoffRequests,
            'outgoingTransfers' => $outgoingTransfers,
            'incomingTransfers' => $incomingTransfers,
            'recentAuditLogs' => $recentAuditLogs,
            'activityHistory' => $activityHistory,
            'roleLabels' => $this->roleLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $validated = $this->validatedData($request);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        AuditTrail::created(auth()->id(), $user);

        return redirect()->route('users.index')->with('success', 'Lietotājs veiksmīgi izveidots');
    }

    public function redirectToEditModal(User $user): RedirectResponse
    {
        $this->requireAdmin();
        AuditTrail::viewed($this->user(), 'User', (string) $user->id, 'Atvērta lietotāja labošanas forma: '.AuditTrail::labelFor($user));

        return $this->redirectToUserModal('edit', $user);
    }

    public function update(Request $request, User $user)
    {
        $this->requireAdmin();

        $before = $user->only(['full_name', 'email', 'phone', 'job_title', 'role', 'is_active']);
        $validated = $this->validatedData($request, $user);

        if (! filled($validated['password'] ?? null)) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $after = $user->fresh()->only(array_keys($before));

        if (array_key_exists('password', $validated)) {
            $before['password'] = '[veca parole]';
            $after['password'] = '[jauna parole]';
        }

        AuditTrail::updatedFromState(auth()->id(), $user, $before, $after);

        return redirect()->route('users.index')->with('success', 'Lietotājs veiksmīgi atjaunināts');
    }

    public function destroy(User $user)
    {
        $this->requireAdmin();

        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Nevar dzēst savu lietotāja kontu.');
        }

        $blockingRelations = collect([
            'piesaistītas ierīces' => $user->assignedDevices()->count(),
            'atbildētās telpas' => $user->responsibleRooms()->count(),
            'izveidoti remonta pieteikumi' => $user->repairRequests()->count(),
            'izskatīti remonta pieteikumi' => $user->reviewedRepairRequests()->count(),
            'izveidoti norakstīšanas pieteikumi' => $user->writeoffRequests()->count(),
            'izskatīti norakstīšanas pieteikumi' => $user->reviewedWriteoffRequests()->count(),
            'izveidotas nodošanas' => $user->outgoingTransfers()->count(),
            'saņemtas nodošanas' => $user->incomingTransfers()->count(),
            'izskatītas nodošanas' => $user->reviewedTransfers()->count(),
            'izveidotas ierīces' => $user->createdDevices()->count(),
            'pieteikti remonti' => $user->reportedRepairs()->count(),
            'apstiprināti remonti' => $user->acceptedRepairs()->count(),
            'audita ieraksti' => $user->auditLogs()->count(),
        ])->filter(fn (int $count) => $count > 0);

        if ($blockingRelations->isNotEmpty()) {
            $summary = $blockingRelations
                ->map(fn (int $count, string $label) => $label . ' (' . $count . ')')
                ->implode(', ');

            return redirect()->route('users.index')->with(
                'error',
                'Lietotāju nevar izdzēst, jo viņam vēl ir piesaistīti ieraksti: ' . $summary . '. Vispirms atsien vai pārvieto šos ierakstus.'
            );
        }

        AuditTrail::deleted(auth()->id(), $user, severity: AuditTrail::SEVERITY_WARNING);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Lietotājs dzēsts');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $validated = $this->validateInput($request, [
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'full_name.required' => 'Norādi lietotāja vārdu un uzvārdu.',
            'email.required' => 'Norādi lietotāja e-pastu.',
            'password.required' => 'Jaunam lietotājam parole ir obligāta.',
            'password.min' => 'Parolei jābūt vismaz :min simbolu garai.',
        ]);

        $validated['phone'] = $validated['phone'] ?: null;
        $validated['job_title'] = $validated['job_title'] ?: null;
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    private function roleLabels(): array
    {
        return [
            User::ROLE_ADMIN => 'Admins',
            User::ROLE_USER => 'Darbinieks',
        ];
    }

    private function normalizedSorting(Request $request): array
    {
        $sort = trim((string) $request->query('sort', 'full_name'));
        $direction = trim((string) $request->query('direction', 'asc'));

        if (! in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'full_name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'last_login' ? 'desc' : 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'label' => $this->sortOptions()[$sort]['label'] ?? 'vārda un uzvārda',
        ];
    }

    private function redirectToUserModal(string $mode, ?User $user = null): RedirectResponse
    {
        $parameters = ['user_modal' => $mode];

        if ($user) {
            $parameters['modal_user'] = $user->id;
        }

        return redirect()->route('users.index', $parameters);
    }

    private function applySorting($query, array $sorting): void
    {
        switch ($sorting['sort']) {
            case 'email':
                $query->orderByRaw('LOWER(COALESCE(email, "")) '.$sorting['direction']);
                break;
            case 'phone':
                $query->orderByRaw('LOWER(COALESCE(phone, "")) '.$sorting['direction']);
                break;
            case 'role':
                $query->orderByRaw("
                    CASE role
                        WHEN 'admin' THEN 1
                        WHEN 'user' THEN 2
                        ELSE 3
                    END {$sorting['direction']}
                ");
                break;
            case 'job_title':
                $query->orderByRaw('LOWER(COALESCE(job_title, "")) '.$sorting['direction']);
                break;
            case 'is_active':
                $query->orderBy('is_active', $sorting['direction']);
                break;
            case 'last_login':
                $query->orderByRaw('CASE WHEN last_login IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('last_login', $sorting['direction']);
                break;
            case 'full_name':
            default:
                $query->orderByRaw('LOWER(COALESCE(full_name, "")) '.$sorting['direction']);
                break;
        }

        $query->orderBy('id', $sorting['direction'] === 'asc' ? 'asc' : 'desc');
    }

    private function sortOptions(): array
    {
        return [
            'full_name' => ['label' => 'vārda un uzvārda'],
            'email' => ['label' => 'e-pasta'],
            'phone' => ['label' => 'tālruņa'],
            'role' => ['label' => 'lomas'],
            'job_title' => ['label' => 'amata'],
            'is_active' => ['label' => 'statusa'],
            'last_login' => ['label' => 'pēdējās pieslēgšanās'],
        ];
    }
}
