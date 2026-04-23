<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Iekšējais paroles maiņas pieprasījums administratoriem.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Parāda paroles maiņas pieprasījuma skatu.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Reģistrē paroles maiņas pieprasījumu administratoru apstrādei.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user) {
            $user->forceFill([
                'password_reset_requested_at' => now(),
            ])->save();

            AuditTrail::write(
                null,
                AuditTrail::ACTION_SUBMIT,
                'User',
                (string) $user->id,
                'Lietotājs pieprasīja paroles maiņu administratora apstrādei.'
            );
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'Paroles maiņas pieprasījums ir saņemts. Sistēmas administrators to izskatīs un sazināsies ar jums.');
    }
}
