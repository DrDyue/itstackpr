<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        $users = User::with('employee')
            ->when($q, function ($query) use ($q) {
                $query->where('username', 'like', "%{$q}%")
                    ->orWhere('role', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->get();

        return view('users.index', compact('users', 'q'));
    }

    public function create()
    {
        $employees = Employee::orderBy('full_name')->get();
        $roles = ['admin', 'manager', 'technician', 'user'];

        return view('users.create', compact('employees', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id', 'unique:users,employee_id'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:admin,manager,technician,user'],
            'is_active' => ['boolean'],
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit(User $user)
    {
        $employees = Employee::orderBy('full_name')->get();
        $roles = ['admin', 'manager', 'technician', 'user'];

        return view('users.edit', compact('user', 'employees', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id', 'unique:users,employee_id,' . $user->id],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:admin,manager,technician,user'],
            'is_active' => ['boolean'],
        ]);

        if ($data['password']) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}
