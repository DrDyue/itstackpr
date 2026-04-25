<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pārslēdz administratoru starp admina un darbinieka skatu.
 */
class ViewModeController extends Controller
{
    /**
     * Saglabā administratora izvēlēto skata režīmu sesijā ar redirectu uz atbilstošo lapu.
     *
     * Administrators var pārslēgties starp admina skatu (darba virsma) un parastā darbinieka
     * skatu (ierīču saraksts). Pārejas tiek reģistrētas audita žurnālā.
     *
     * Izsaukšana: POST /view-mode | Pieejams: tikai administrators.
     * Scenārijs: Administrator klikšķina uz "Pārslēgt uz admina/darbinieka skatu" novilktnē.
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
