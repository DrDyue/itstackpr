<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = [User::ROLE_ADMIN, User::ROLE_USER];

    public function index(Request $request)
    {
        $this->requireAdmin();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
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

        $legacyName = trim((string) $request->query('name', ''));
        $legacyEmail = trim((string) $request->query('email', ''));

        $users = User::query()
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery->where('full_name', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%')
                        ->orWhere('phone', 'like', '%' . $term . '%')
                        ->orWhere('job_title', 'like', '%' . $term . '%');
                });
            })
            ->when($legacyName !== '', fn ($query) => $query->where('full_name', 'like', '%' . $legacyName . '%'))
            ->when($legacyEmail !== '', fn ($query) => $query->where('email', 'like', '%' . $legacyEmail . '%'))
            ->when($filters['has_role_filter'], fn ($query) => $query->whereIn('role', $filters['roles']))
            ->when($filters['is_active'] !== '', fn ($query) => $query->where('is_active', $filters['is_active'] === '1'))
            ->when($filters['last_login'] === 'today', fn ($query) => $query->whereDate('last_login', today()))
            ->when($filters['last_login'] === 'recent', fn ($query) => $query->where('last_login', '>=', now()->subDays(7)))
            ->when($filters['last_login'] === 'never', fn ($query) => $query->whereNull('last_login'))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        $userSummaryQuery = User::query();

        return view('users.index', [
            'users' => $users,
            'userSummary' => [
                'total' => (clone $userSummaryQuery)->count(),
                'admin' => (clone $userSummaryQuery)->where('role', User::ROLE_ADMIN)->count(),
                'user' => (clone $userSummaryQuery)->where('role', User::ROLE_USER)->count(),
            ],
            'filters' => $filters,
            'roles' => self::ROLES,
            'roleLabels' => $this->roleLabels(),
        ]);
    }

    public function create()
    {
        $this->requireAdmin();

        return view('users.create', [
            'roles' => self::ROLES,
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

        return redirect()->route('users.index')->with('success', 'Lietotajs veiksmigi izveidots');
    }

    public function edit(User $user)
    {
        $this->requireAdmin();

        return view('users.edit', [
            'user' => $user,
            'roles' => self::ROLES,
            'roleLabels' => $this->roleLabels(),
        ]);
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

        return redirect()->route('users.index')->with('success', 'Lietotajs veiksmigi atjauninats');
    }

    public function destroy(User $user)
    {
        $this->requireAdmin();

        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Nevar dzest savu lietotaja kontu.');
        }

        $blockingRelations = collect([
            'piesaistitas ierices' => $user->assignedDevices()->count(),
            'atbildetas telpas' => $user->responsibleRooms()->count(),
            'izveidoti remonta pieteikumi' => $user->repairRequests()->count(),
            'izskatiti remonta pieteikumi' => $user->reviewedRepairRequests()->count(),
            'izveidoti norakstisanas pieteikumi' => $user->writeoffRequests()->count(),
            'izskatiti norakstisanas pieteikumi' => $user->reviewedWriteoffRequests()->count(),
            'izveidotas nodosanas' => $user->outgoingTransfers()->count(),
            'sanemtas nodosanas' => $user->incomingTransfers()->count(),
            'izskatitas nodosanas' => $user->reviewedTransfers()->count(),
            'izveidotas ierices' => $user->createdDevices()->count(),
            'pieteikti remonti' => $user->reportedRepairs()->count(),
            'apstiprinati remonti' => $user->acceptedRepairs()->count(),
            'audita ieraksti' => $user->auditLogs()->count(),
        ])->filter(fn (int $count) => $count > 0);

        if ($blockingRelations->isNotEmpty()) {
            $summary = $blockingRelations
                ->map(fn (int $count, string $label) => $label . ' (' . $count . ')')
                ->implode(', ');

            return redirect()->route('users.index')->with(
                'error',
                'Lietotaju nevar izdzest, jo vinam vel ir piesaistiti ieraksti: ' . $summary . '. Vispirms atsien vai parvieto sos ierakstus.'
            );
        }

        AuditTrail::deleted(auth()->id(), $user, severity: AuditTrail::SEVERITY_WARNING);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Lietotajs dzests');
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
            'full_name.required' => 'Noradi lietotaja vardu un uzvardu.',
            'email.required' => 'Noradi lietotaja e-pastu.',
            'password.required' => 'Jaunam lietotajam parole ir obligata.',
            'password.min' => 'Parolei jabut vismaz :min simbolus garai.',
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
}
