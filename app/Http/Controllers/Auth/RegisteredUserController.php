<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403);
        }

        $employees = Employee::query()
            ->where('is_active', true)
            ->whereDoesntHave('user')
            ->orderBy('full_name')
            ->get();

        $roles = ['user', 'admin'];

        return view('auth.register', compact('employees', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'employee_id' => ['required', 'exists:employees,id', 'unique:users,employee_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:user,admin'],
        ]);

        $user = User::create([
            'employee_id' => $request->employee_id,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        event(new Registered($user));

        return redirect(route('users.index'))->with('success', 'Lietotājs veiksmīgi izveidots');
    }
}
