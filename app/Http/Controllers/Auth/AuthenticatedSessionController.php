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
 * Pieslēgšanās un izrakstīšanās plūsma.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Parāda pieslēgšanās skatu ar autentifikācijas konfigurācijas paziņojumu.
     *
     * Uzlāde augšupejošā konfigurācija to pārbauda un rāda norādes, ja sistēma nav gatava.
     *
     * Izsaukšana: GET /login | Pieejams: nav autentificēts.
     * Scenārijs: Lietotājs navigē uz pieslēgšanās URL vai tiek novirzīts no aizsargātās lapas.
     */
    public function create(AuthBootstrapper $bootstrapper): View
    {
        $bootstrapStatus = $bootstrapper->prepareLoginScreen();

        return view('auth.login', [
            'authSetupMessage' => $bootstrapStatus['message'] ?? null,
        ]);
    }

    /**
     * Apstrādā ienākošu autentifikācijas pieprasījumu.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        if ($request->user()?->isAdmin()) {
            $request->session()->put(User::VIEW_MODE_SESSION_KEY, User::VIEW_MODE_ADMIN);
        } else {
            $request->session()->forget(User::VIEW_MODE_SESSION_KEY);
        }
        AuditTrail::login($request->user());

        return redirect()->intended(
            $request->user()?->canManageRequests()
                ? route('dashboard', absolute: false)
                : route('devices.index', absolute: false)
        );
    }

    /**
     * Izbeidz autentificēto sesiju.
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
