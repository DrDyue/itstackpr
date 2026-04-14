<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Paroles atjaunošanas saites pieprasīšanas plūsma.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Parāda paroles atjaunošanas saites pieprasīšanas skatu.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Apstrādā ienākošu paroles atjaunošanas saites pieprasījumu.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Nosūtām paroles atjaunošanas saiti lietotājam un atgriežam
        // atbilstošu paziņojumu par rezultātu.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
