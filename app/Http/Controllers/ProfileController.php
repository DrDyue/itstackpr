<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Lietotāja profila rediģēšana un konta dzēšana.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 404);

        $validated = $request->validated();
        $before = $user->only(['full_name', 'email', 'phone', 'job_title']);

        $user->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'job_title' => $validated['job_title'] ?: null,
        ]);

        $after = $user->fresh()->only(array_keys($before));

        AuditTrail::updatedFromState(
            $user->id,
            $user,
            $before,
            $after,
            description: 'Profila dati atjaunināti: ' . AuditTrail::labelFor($user)
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 404);
        abort_unless($user->isAdmin(), 403);

        $validated = $request->validateWithBag('profileSettings', [
            'hide_written_off_devices' => ['required', 'boolean'],
        ]);

        $before = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => $user->prefersHiddenWrittenOffDevices(),
        ];

        $settings = is_array($user->user_settings) ? $user->user_settings : [];
        $settings[User::SETTING_HIDE_WRITEOFF_DEVICES] = (bool) $validated['hide_written_off_devices'];

        $user->forceFill([
            'user_settings' => $settings,
        ])->save();

        $after = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => $user->fresh()->prefersHiddenWrittenOffDevices(),
        ];

        AuditTrail::updatedFromState(
            $user->id,
            $user,
            $before,
            $after,
            description: 'Profila iestatījumi atjaunināti: ' . AuditTrail::labelFor($user)
        );

        return Redirect::route('profile.edit')->with('success', 'Iestatījumi saglabāti.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        AuditTrail::deleted($user?->id, $user, 'Lietotāja konts dzēsts: ' . AuditTrail::labelFor($user), AuditTrail::SEVERITY_WARNING);

        Auth::logout();
        $user?->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
