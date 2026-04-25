<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Jauna lietotāja reģistrācija administratora vajadzībām.
 */
class RegisteredUserController extends Controller
{
    /**
     * Parāda jauna lietotāja reģistrācijas formu administratoram.
     *
     * Pieejams tikai administratoram. Forma ļauj piešķirt lomu jaunajam kontam
     * un aizpildīt visu nepieciešamo informāciju (vārds, e-pasts, tālrunis, amats).
     *
     * Izsaukšana: GET /register | Pieejams: tikai administrators.
     * Scenārijs: Administrator navigē uz "Jauns lietotājs" vai atver reģistrācijas formu.
     */
    public function create(): View
    {
        $this->requireAdmin();

        return view('auth.register', [
            'roles' => [User::ROLE_ADMIN, User::ROLE_USER],
        ]);
    }

    /**
     * Apstrādā jauna lietotāja reģistrācijas formu ar validāciju un audita reģistrāciju.
     *
     * Paroli šifrē ar Hash::make pirms saglabāšanas. Pēc izveides izsauc Laravel `Registered`
     * notikumu (kas var palaist e-pastu) un reģistrē izveidi audita žurnālā.
     * Administrator tiek novirzīts uz lietotāju sarakstu.
     *
     * Izsaukšana: POST /register | Pieejams: tikai administrators.
     * Scenārijs: Administrator aizpilda jauno lietotāju formu un klikšķina "Reģistrēt".
     */
    public function store(Request $request): RedirectResponse
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'job_title' => $validated['job_title'] ?: null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        AuditTrail::created(Auth::id(), $user);
        event(new Registered($user));

        return redirect(route('users.index'))->with('success', 'Lietotājs veiksmīgi izveidots');
    }
}
