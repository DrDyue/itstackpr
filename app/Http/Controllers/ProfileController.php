<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()?->load('employee');

        return view('profile.edit', [
            'user' => $user,
            'employee' => $user?->employee,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user()->load('employee');
        $employee = $user->employee;
        abort_unless($employee, 404);

        $validated = $request->validated();
        $before = $employee->only(['full_name', 'email', 'phone', 'job_title']);

        DB::transaction(function () use ($employee, $validated) {
            $employee->update([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?: null,
                'job_title' => $validated['job_title'] ?: null,
            ]);
        });

        $employee->refresh();
        $after = $employee->only(array_keys($before));

        AuditTrail::updatedFromState(
            $user->id,
            $employee,
            $before,
            $after,
            description: 'Profila dati atjauninati: ' . AuditTrail::labelFor($employee)
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        AuditTrail::deleted($user?->id, $user, 'Lietotaja konts dzests: ' . AuditTrail::labelFor($user), AuditTrail::SEVERITY_WARNING);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
