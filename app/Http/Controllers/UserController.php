<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = [User::ROLE_ADMIN, User::ROLE_IT_WORKER, User::ROLE_USER];

    public function index(Request $request)
    {
        $this->requireAdmin();

        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'email' => trim((string) $request->query('email', '')),
            'role' => trim((string) $request->query('role', '')),
            'is_active' => (string) $request->query('is_active', ''),
        ];

        $users = User::query()
            ->when($filters['name'] !== '', fn ($query) => $query->where('full_name', 'like', '%' . $filters['name'] . '%'))
            ->when($filters['email'] !== '', fn ($query) => $query->where('email', 'like', '%' . $filters['email'] . '%'))
            ->when($filters['role'] !== '', fn ($query) => $query->where('role', $filters['role']))
            ->when($filters['is_active'] !== '', fn ($query) => $query->where('is_active', $filters['is_active'] === '1'))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => self::ROLES,
        ]);
    }

    public function create()
    {
        $this->requireAdmin();

        return view('users.create', [
            'roles' => self::ROLES,
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

        AuditTrail::deleted(auth()->id(), $user, severity: AuditTrail::SEVERITY_WARNING);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Lietotajs dzests');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['phone'] = $validated['phone'] ?: null;
        $validated['job_title'] = $validated['job_title'] ?: null;
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
