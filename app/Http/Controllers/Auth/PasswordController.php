<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Ko dara: Pārvalda autentificēta lietotāja paroles maiņu.
 *
 * Kā strādā: Validē pašreizējo paroli, pārbauda jauno paroli, šifrē to un saglabā lietotāja kontā.
 *
 * Kad pielietojas: Kad lietotājs profila sadaļā maina savu paroli.
 */
class PasswordController extends Controller
{
    /**
     * Ko dara: Atjauno pašreizējā lietotāja paroli ar validāciju un audita reģistrāciju.
     *
     * Kā strādā: Validē pašreizējo paroli un pārbauda jauno paroles nosacījumus. Parole tiek šifrēta pirms saglabāšanas. Pēc veiksmīgas maiņas sesija tiek signalizēta profila modāļa aizvēršanai.
     *
     * Kad pielietojas: Izsaukšana: PUT /password | Pieejams: autentificēts. Scenārijs: Lietotājs ievada jauno paroli profila modāļa paroles cilnē.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditTrail::updated(
            $request->user()->id,
            $request->user()->fresh(),
            ['password'],
            'Lietotāja parole nomainīta: ' . AuditTrail::labelFor($request->user()),
            AuditTrail::SEVERITY_WARNING
        );

        return back()
            ->with('success', 'Parole nomainīta.')
            ->with('close_profile_modals', true);
    }
}
