<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\AuthBootstrapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ko dara: Pārvalda lietotāja pieslēgšanos un atslēgšanos no sistēmas.
 *
 * Kā strādā: Rāda pieslēgšanās formu, validē pieteikšanās datus, atjauno sesiju un reģistrē būtiskus drošības notikumus.
 *
 * Kad pielietojas: Kad lietotājs atver login lapu, pieslēdzas sistēmai vai izrakstās.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Ko dara: Parāda pieslēgšanās skatu ar autentifikācijas konfigurācijas paziņojumu.
     *
     * Kā strādā: Uzlāde augšupejošā konfigurācija to pārbauda un rāda norādes, ja sistēma nav gatava.
     *
     * Kad pielietojas: Izsaukšana: GET /login | Pieejams: nav autentificēts. Scenārijs: Lietotājs navigē uz pieslēgšanās URL vai tiek novirzīts no aizsargātās lapas.
     */
    public function create(AuthBootstrapper $bootstrapper): View
    {
        $bootstrapStatus = $bootstrapper->prepareLoginScreen();

        return view('auth.login', [
            'authSetupMessage' => $bootstrapStatus['message'] ?? null,
        ]);
    }

    /**
     * Ko dara: Apstrādā ienākošu autentifikācijas pieprasījumu.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $user = $request->user();

        if ($user?->isAdmin()) {
            $request->session()->put(User::VIEW_MODE_SESSION_KEY, $user->initialViewMode());
        } else {
            $request->session()->forget(User::VIEW_MODE_SESSION_KEY);
        }
        AuditTrail::login($user);

        return redirect()->intended(
            $user?->canManageRequests()
                ? route($user->defaultStartRouteName(), absolute: false)
                : route('devices.index', absolute: false)
        );
    }

    /**
     * Ko dara: Izbeidz autentificēto sesiju.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        AuditTrail::logout($user);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
