<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ko dara: Pārvalda administratora skata režīma pārslēgšanu.
 *
 * Kā strādā: Saglabā izvēlēto skata režīmu sesijā vai lietotāja iestatījumos un auditē pāreju starp admina un darbinieka skatu.
 *
 * Kad pielietojas: Kad administrators pārslēdzas uz citu darba režīmu saskarnē.
 */
class ViewModeController extends Controller
{
    /**
     * Ko dara: Saglabā administratora izvēlēto skata režīmu sesijā ar redirectu uz atbilstošo lapu.
     *
     * Kā strādā: Administrators var pārslēgties starp admina skatu (darba virsma) un parastā darbinieka skatu (ierīču saraksts). Pārejas tiek reģistrētas audita žurnālā.
     *
     * Kad pielietojas: Izsaukšana: POST /view-mode | Pieejams: tikai administrators. Scenārijs: Administrator klikšķina uz "Pārslēgt uz admina/darbinieka skatu" novilktnē.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user?->isAdmin(), 403);
        $previousMode = $user->currentViewMode();

        $validated = $this->validateInput($request, [
            'mode' => ['required', Rule::in([User::VIEW_MODE_ADMIN, User::VIEW_MODE_USER])],
        ], [
            'mode.required' => 'Izvēlies skata režīmu.',
        ]);

        $request->session()->put(User::VIEW_MODE_SESSION_KEY, $validated['mode']);
        $settings = is_array($user->user_settings) ? $user->user_settings : [];
        $settings[User::SETTING_LAST_VIEW_MODE] = $validated['mode'];
        $user->forceFill(['user_settings' => $settings])->save();

        AuditTrail::switchViewMode($user, $previousMode, $validated['mode']);

        return redirect()->route(
            $validated['mode'] === User::VIEW_MODE_ADMIN ? 'dashboard' : 'devices.index'
        )->with(
            'success',
            $validated['mode'] === User::VIEW_MODE_ADMIN
                ? 'Ieslēgts admina skats.'
                : 'Ieslēgts darbinieka skats.'
        );
    }
}
