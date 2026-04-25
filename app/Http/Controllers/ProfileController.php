<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\RuntimeSchemaBootstrapper;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;

/**
 * Lietotāja profila rediģēšana un konta dzēšana.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly RuntimeSchemaBootstrapper $runtimeSchemaBootstrapper
    ) {
    }

    /**
     * Profila rediģēšanas skats — novirza uz galveno lapu pēc lomas.
     *
     * Administrators tiek novirzīts uz darba virsmu, bet parasts lietotājs
     * uz ierīču sarakstu. Profila modālis tiek atvērts JavaScript pusē.
     *
     * Izsaukšana: GET /profile | Pieejams: jebkurš autentificēts lietotājs.
     * Scenārijs: Lietotājs klikšķina uz "Manu profilu" vai tiek novirzīts uz šo URL.
     */
    public function edit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 404);

        return redirect()->route($user->canManageRequests() ? 'dashboard' : 'devices.index');
    }

    /**
     * Saglabā lietotāja profila pamatdatus ar pūriņu izsekošanu audita žurnālā.
     *
     * Izmaiņas tiek salīdzinātas ar iepriekšējo stāvokli un reģistrētas audita žurnālā.
     * Pēc veiksmīgas saglabāšanas nosūta sesijā signālu profila modāļa aizvēršanai.
     *
     * Izsaukšana: PUT /profile | Pieejams: jebkurš autentificēts lietotājs.
     * Scenārijs: Lietotājs aizpilda profila formu (vārds, e-pasts, tālrunis, amats) un saglabā.
     */
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

        return Redirect::back()
            ->with('success', 'Profila informācija saglabāta.')
            ->with('close_profile_modals', true);
    }

    /**
     * Saglabā administratora skata preferenču iestatījumus (piemēram, norakstīto ierīču slēpšana).
     *
     * Pieejams tikai administratoriem. Iestatījumi glabājas `user_settings` JSON kolonnā.
     * Izmaiņas tiek reģistrētas audita žurnālā ar skaidru aprakstu.
     */
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

        $this->persistProfileSettings($user, $settings);

        $after = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => $user->fresh()->prefersHiddenWrittenOffDevices(),
        ];

        AuditTrail::updatedFromState(
            $user->id,
            $user,
            $before,
            $after,
            description: $this->profileSettingsAuditDescription($user, $before, $after)
        );

        return Redirect::back()
            ->with('success', 'Iestatījumi saglabāti.')
            ->with('close_profile_modals', true);
    }

    /**
     * Saglabā profila iestatījumus, automātiski apstrādājot trūkstošās kolonnas.
     *
     * Ja `user_settings` kolonna vēl nav datubāzē (legacy shēma), tiek izsaukts
     * RuntimeSchemaBootstrapper, lai to izveidotu, un saglabāšana tiek atkārtota.
     */
    private function persistProfileSettings(User $user, array $settings): void
    {
        try {
            $user->forceFill([
                'user_settings' => $settings,
            ])->save();
        } catch (QueryException $exception) {
            if (! $this->isMissingUserSettingsColumn($exception)) {
                throw $exception;
            }

            $this->runtimeSchemaBootstrapper->ensure();

            if (! Schema::hasColumn('users', 'user_settings')) {
                throw $exception;
            }

            $user->refresh();
            $user->forceFill([
                'user_settings' => $settings,
            ])->save();
        }
    }

    /**
     * Pārbauda, vai datubāzes kļūda ir par trūkstošo `user_settings` kolonnu.
     */
    private function isMissingUserSettingsColumn(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), "unknown column 'user_settings'");
    }

    /**
     * Sagatavo cilvēkam saprotamu audita ieraksta aprakstu par iestatījumu maiņu.
     *
     * Ja vērtība nav mainījusies, apraksts to norāda. Ja mainījusies — parāda
     * pirms/pēc pāreju (ieslēgts/izslēgts).
     */
    private function profileSettingsAuditDescription(User $user, array $before, array $after): string
    {
        $beforeValue = (bool) ($before[User::SETTING_HIDE_WRITEOFF_DEVICES] ?? false);
        $afterValue = (bool) ($after[User::SETTING_HIDE_WRITEOFF_DEVICES] ?? false);

        if ($beforeValue === $afterValue) {
            return 'Administrēšanas skata iestatījums netika mainīts: "Paslēpt norakstītās ierīces" palika '.($afterValue ? 'ieslēgts' : 'izslēgts').': ' . AuditTrail::labelFor($user);
        }

        return 'Administrēšanas skata iestatījums "Paslēpt norakstītās ierīces" mainīts: '
            . ($beforeValue ? 'ieslēgts' : 'izslēgts')
            . ' -> '
            . ($afterValue ? 'ieslēgts' : 'izslēgts')
            . ': '
            . AuditTrail::labelFor($user);
    }

    /**
     * Dzēš lietotāja kontu pēc paroles apstiprināšanas.
     *
     * Pirms dzēšanas reģistrē audita žurnālā brīdinājuma līmeņa notikumu.
     * Sesija tiek pilnīgi atiestatīta, lai novērstu jebkādu sesijas noplūdi.
     */
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
