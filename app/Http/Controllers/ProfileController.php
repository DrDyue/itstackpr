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
use Illuminate\Validation\Rule;

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
     * uz ierīču sarakstu. Ja pieprasījumā ir `profile_modal`, tas tiek
     * pārsūtīts tālāk uz mērķa lapu, lai profila modālis atvērtos JavaScript pusē.
     *
     * Izsaukšana: GET /profile | Pieejams: jebkurš autentificēts lietotājs.
     * Scenārijs: Lietotājs klikšķina uz "Manu profilu" vai tiek novirzīts uz šo URL.
     */
    public function edit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 404);

        $targetRoute = $user->canManageRequests() ? 'dashboard' : 'devices.index';
        $query = $request->query('profile_modal') ? ['profile_modal' => $request->query('profile_modal')] : [];

        return redirect()->route($targetRoute, $query);
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
            'default_start_page' => ['required', 'string', Rule::in(User::startPageOptions())],
            'default_view_mode' => ['required', 'string', Rule::in(User::defaultViewModeOptions())],
            'default_device_filter' => ['required', 'string', Rule::in(User::deviceFilterOptions())],
            'notification_visual_mode' => ['required', 'string', Rule::in(User::notificationVisualOptions())],
            'default_request_filter' => ['required', 'string', Rule::in(User::requestFilterOptions())],
        ]);

        $before = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => $user->prefersHiddenWrittenOffDevices(),
            User::SETTING_DEFAULT_START_PAGE => $user->defaultStartPage(),
            User::SETTING_DEFAULT_VIEW_MODE => $user->defaultViewMode(),
            User::SETTING_DEFAULT_DEVICE_FILTER => $user->defaultDeviceFilter(),
            User::SETTING_NOTIFICATION_VISUAL_MODE => $user->notificationVisualMode(),
            User::SETTING_DEFAULT_REQUEST_FILTER => $user->defaultRequestFilter(),
        ];

        $settings = is_array($user->user_settings) ? $user->user_settings : [];
        $settings[User::SETTING_HIDE_WRITEOFF_DEVICES] = (bool) $validated['hide_written_off_devices'];
        $settings[User::SETTING_DEFAULT_START_PAGE] = $validated['default_start_page'];
        $settings[User::SETTING_DEFAULT_VIEW_MODE] = $validated['default_view_mode'];
        $settings[User::SETTING_DEFAULT_DEVICE_FILTER] = $validated['default_device_filter'];
        $settings[User::SETTING_NOTIFICATION_VISUAL_MODE] = $validated['notification_visual_mode'];
        $settings[User::SETTING_DEFAULT_REQUEST_FILTER] = $validated['default_request_filter'];

        $this->persistProfileSettings($user, $settings);

        $updatedUser = $user->fresh();
        $after = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => $updatedUser->prefersHiddenWrittenOffDevices(),
            User::SETTING_DEFAULT_START_PAGE => $updatedUser->defaultStartPage(),
            User::SETTING_DEFAULT_VIEW_MODE => $updatedUser->defaultViewMode(),
            User::SETTING_DEFAULT_DEVICE_FILTER => $updatedUser->defaultDeviceFilter(),
            User::SETTING_NOTIFICATION_VISUAL_MODE => $updatedUser->notificationVisualMode(),
            User::SETTING_DEFAULT_REQUEST_FILTER => $updatedUser->defaultRequestFilter(),
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
        $labels = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => 'norakstīto ierīču slēpšana',
            User::SETTING_DEFAULT_START_PAGE => 'sākuma lapa',
            User::SETTING_DEFAULT_VIEW_MODE => 'noklusētais skata režīms',
            User::SETTING_DEFAULT_DEVICE_FILTER => 'ierīču noklusētais filtrs',
            User::SETTING_NOTIFICATION_VISUAL_MODE => 'paziņojumu izcelšana',
            User::SETTING_DEFAULT_REQUEST_FILTER => 'pieteikumu noklusētais filtrs',
        ];

        $changes = collect($after)
            ->filter(fn (mixed $value, string $key) => ($before[$key] ?? null) !== $value)
            ->map(fn (mixed $value, string $key) => ($labels[$key] ?? $key).': '.$this->settingValueLabel($before[$key] ?? null, $key).' -> '.$this->settingValueLabel($value, $key))
            ->values()
            ->all();

        if ($changes === []) {
            return 'Admina darba vides iestatījumi saglabāti bez izmaiņām: '.AuditTrail::labelFor($user);
        }

        return 'Admina darba vides iestatījumi mainīti: '.implode('; ', $changes).': '.AuditTrail::labelFor($user);
    }

    private function settingValueLabel(mixed $value, ?string $key = null): string
    {
        if ($key === User::SETTING_DEFAULT_START_PAGE) {
            return match ($value) {
                User::START_PAGE_DASHBOARD => 'Dashboard',
                User::START_PAGE_DEVICES => 'Ierīces',
                User::START_PAGE_REPAIR_REQUESTS => 'Remonta pieteikumi',
                User::START_PAGE_WRITEOFF_REQUESTS => 'Norakstīšanas pieteikumi',
                User::START_PAGE_DEVICE_TRANSFERS => 'Nodošanas pieteikumi',
                User::START_PAGE_AUDIT_LOG => 'Audita žurnāls',
                default => 'nav norādīts',
            };
        }

        if ($key === User::SETTING_DEFAULT_VIEW_MODE) {
            return match ($value) {
                User::VIEW_MODE_ADMIN => 'admina skats',
                User::VIEW_MODE_USER => 'darbinieka skats',
                User::DEFAULT_VIEW_MODE_LAST => 'atcerēties pēdējo',
                default => 'nav norādīts',
            };
        }

        if ($key === User::SETTING_DEFAULT_DEVICE_FILTER) {
            return match ($value) {
                User::DEVICE_FILTER_ACTIVE => 'aktīvās ierīces',
                User::DEVICE_FILTER_REPAIR => 'remontā esošās ierīces',
                User::DEVICE_FILTER_ALL => 'visas ierīces',
                default => 'nav norādīts',
            };
        }

        if ($key === User::SETTING_DEFAULT_REQUEST_FILTER) {
            return match ($value) {
                User::REQUEST_FILTER_SUBMITTED => 'iesniegtie pieteikumi',
                User::REQUEST_FILTER_TODAY => 'šodienas pieteikumi',
                User::REQUEST_FILTER_ALL => 'visi pieteikumi',
                default => 'nav norādīts',
            };
        }

        if ($key === User::SETTING_NOTIFICATION_VISUAL_MODE) {
            return match ($value) {
                User::NOTIFICATION_VISUAL_SUBTLE => 'klusāka izcelšana',
                User::NOTIFICATION_VISUAL_OFF => 'bez toast paziņojumiem',
                User::NOTIFICATION_VISUAL_ANIMATED => 'pilna animācija',
                default => 'nav norādīts',
            };
        }

        return match ($value) {
            true => 'ieslēgts',
            false => 'izslēgts',
            null, '' => 'nav norādīts',
            default => (string) $value,
        };
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
