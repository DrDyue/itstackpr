<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Jaunas paroles iestatīšana ar tokenu.
 */
class NewPasswordController extends Controller
{
    /**
     * Parāda paroles atiestatīšanas skatu.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Apstrādā ienākošu jaunās paroles pieprasījumu.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Mēģinām atiestatīt lietotāja paroli. Ja process ir veiksmīgs,
        // atjaunojam paroli lietotāja modelī un saglabājam to datubāzē.
        // Pretējā gadījumā atgriežam atbilstošu kļūdas paziņojumu.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // Ja parole atiestatīta veiksmīgi, novirzām lietotāju uz pieslēgšanās lapu.
        // Ja rodas kļūda, atgriežam lietotāju atpakaļ ar kļūdas paziņojumu.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
