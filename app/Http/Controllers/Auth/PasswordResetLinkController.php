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
     * Parāda paroles maiņas pieprasījuma formu.
     *
     * Forma ir noraižu draudzīga un ļauj lietotājiem lūgt administratora palaišanu
     * paroles maiņai, jo automātiskā e-pasta sistēma nav iespējota.
     *
     * Izsaukšana: GET /forgot-password | Pieejams: nav autentificēts.
     * Scenārijs: Lietotājs klikšķina uz "Aizmirsu paroli" pieslēgšanās lapā.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Reģistrē paroles maiņas pieprasījumu administratoru apstrādei ar audita atzīmi.
     *
     * Meklē lietotāju pēc e-pasta un iestata maiņas pieprasījuma marķieri. Audita žurnāls
     * tiek atjaunināts. Skaits norāda, ka pieprasījums tika saņemts neatkarīgi no tā, vai
     * lietotājs tika atrasts (drošības dēļ).
     *
     * Izsaukšana: POST /forgot-password | Pieejams: nav autentificēts.
     * Scenārijs: Lietotājs aizpilda paroles maiņas pieprasījuma formu un klikšķina Nosūtīt.
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
