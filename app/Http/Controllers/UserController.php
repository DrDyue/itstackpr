<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['admin', 'user'];

    public function index(Request $request)
    {
        $filters = [
            'employee' => trim((string) $request->query('employee', '')),
            'email' => trim((string) $request->query('email', '')),
            'role' => trim((string) $request->query('role', '')),
            'employee_active' => (string) $request->query('employee_active', ''),
            'is_active' => (string) $request->query('is_active', ''),
        ];

        $allowedSorts = ['created_at', 'last_login'];
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $users = User::with('employee')
            ->when($filters['employee'] !== '', function ($query) use ($filters) {
                $query->whereHas('employee', function ($employeeQuery) use ($filters) {
                    $employeeQuery->where('full_name', 'like', '%' . $filters['employee'] . '%');
                });
            })
            ->when($filters['email'] !== '', function ($query) use ($filters) {
                $query->whereHas('employee', function ($employeeQuery) use ($filters) {
                    $employeeQuery->where('email', 'like', '%' . $filters['email'] . '%');
                });
            })
            ->when($filters['role'] !== '', function ($query) use ($filters) {
                $query->where('role', $filters['role']);
            })
            ->when($filters['employee_active'] !== '', function ($query) use ($filters) {
                $query->whereHas('employee', function ($employeeQuery) use ($filters) {
                    $employeeQuery->where('is_active', $filters['employee_active'] === '1');
                });
            })
            ->when($filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active'] === '1');
            })
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => self::ROLES,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        $this->ensureAdminCanCreateUsers();

        return view('users.create', [
            'employees' => Employee::query()
                ->whereDoesntHave('user')
                ->orderBy('full_name')
                ->get(),
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdminCanCreateUsers();

        $data = $this->validatedData($request);
        $data['password'] = bcrypt($data['password']);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Lietotajs veiksmigi izveidots');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'employees' => Employee::query()
                ->where(function ($query) use ($user) {
                    $query->whereDoesntHave('user')
                        ->orWhere('id', $user->employee_id);
                })
                ->orderBy('full_name')
                ->get(),
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validatedData($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Lietotajs veiksmigi atjauninats');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Nevar dzest savu lietotaja kontu.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Lietotajs dzests');
    }

    private function ensureAdminCanCreateUsers(): void
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('users', 'employee_id')->ignore($user?->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
