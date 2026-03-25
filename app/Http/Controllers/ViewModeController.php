<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ViewModeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $this->validateInput($request, [
            'mode' => ['required', Rule::in([User::VIEW_MODE_ADMIN, User::VIEW_MODE_USER])],
        ], [
            'mode.required' => 'Izvelies skata rezimu.',
        ]);

        $request->session()->put(User::VIEW_MODE_SESSION_KEY, $validated['mode']);

        return redirect()->route(
            $validated['mode'] === User::VIEW_MODE_ADMIN ? 'dashboard' : 'devices.index'
        )->with(
            'success',
            $validated['mode'] === User::VIEW_MODE_ADMIN
                ? 'Ieslegts admina skats.'
                : 'Ieslegts darbinieka skats.'
        );
    }
}
