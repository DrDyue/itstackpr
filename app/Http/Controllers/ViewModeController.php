<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pārslēdz administratoru starp admina un darbinieka skatu.
 */
class ViewModeController extends Controller
{
    /**
     * Saglabā izvēlēto skata režīmu sesijā.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $this->validateInput($request, [
            'mode' => ['required', Rule::in([User::VIEW_MODE_ADMIN, User::VIEW_MODE_USER])],
        ], [
            'mode.required' => 'Izvēlies skata režīmu.',
        ]);

        $request->session()->put(User::VIEW_MODE_SESSION_KEY, $validated['mode']);

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
