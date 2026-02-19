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
        $q = $request->query('q');

        $users = User::with('employee')
            ->when($q, function ($query) use ($q) {
                $query->where('role', 'like', "%{$q}%")
                    ->orWhereHas('employee', function ($employeeQuery) use ($q) {
                        $employeeQuery->where('full_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            })
            ->orderByDesc('id')
            ->get();

        return view('users.index', compact('users', 'q'));
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
