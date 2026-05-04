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
     * Kā strādā: Validē lietotāja e-pastu/paroli `LoginRequest` klasē, atjauno sesijas ID, saglabā admina skata režīmu un pieraksta login auditu.
     *
     * Kad pielietojas: Izsaukšana: POST /login. Scenārijs: lietotājs iesniedz pieslēgšanās formu un pēc veiksmīgas autentifikācijas tiek novirzīts uz sākuma lapu.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Pēc veiksmīgas pieslēgšanās atjaunojam sesijas ID,
        // lai vecu vai uzminētu sesiju nevar izmantot pieslēgšanās pārņemšanai.
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
     * Kā strādā: Pieraksta izrakstīšanās auditu, izsauc Laravel web guard logout, anulē sesiju un ģenerē jaunu CSRF tokenu.
     *
     * Kad pielietojas: Izsaukšana: POST /logout. Scenārijs: lietotājs izvēlas izrakstīties no sistēmas.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        AuditTrail::logout($user);

        // Laravel web guard aizmirst autentificēto lietotāju servera pusē.
        Auth::guard('web')->logout();

        // Pilnībā anulējam sesiju, lai pēc logout vecie sesijas dati vairs nav derīgi.
        $request->session()->invalidate();

        // Jauns CSRF tokens nodrošina, ka vecas formas vai tokeni pēc izrakstīšanās vairs neder.
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
