<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Pašreizējā lietotāja paroles maiņa profila sadaļā.
 */
class PasswordController extends Controller
{
    /**
     * Update the user's password.
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
            'Lietotaja parole nomainita: ' . AuditTrail::labelFor($request->user()),
            AuditTrail::SEVERITY_WARNING
        );

        return back()->with('status', 'password-updated');
    }
}
