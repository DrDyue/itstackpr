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

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(AuthBootstrapper $bootstrapper): View
    {
        $bootstrapStatus = $bootstrapper->prepareLoginScreen();

        return view('auth.login', [
            'authSetupMessage' => $bootstrapStatus['message'] ?? null,
        ]);
    }

    /**
     * Handle an incoming authentication request.
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
     * Destroy an authenticated session.
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
