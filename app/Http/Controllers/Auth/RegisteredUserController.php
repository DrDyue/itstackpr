<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Only allow authenticated users to access
        if (!\Illuminate\Support\Facades\Auth::check()) {
            redirect(route('login'))->send();
        }
        
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        $roles = ['technician', 'user', 'admin'];
        
        return view('auth.register', compact('employees', 'roles'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id', 'unique:users,employee_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:technician,user,admin'],
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
