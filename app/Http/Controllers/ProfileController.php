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
 * Ko dara: Pārvalda lietotāja profilu, paroli, iestatījumus un konta dzēšanu.
 *
 * Kā strādā: Saglabā profila datus, administratora darba vides iestatījumus, auditē izmaiņas un apstrādā legacy shēmas gadījumus.
 *
 * Kad pielietojas: Kad lietotājs labo profilu vai administrators maina savas darba vides preferences.
 */
class ProfileController extends Controller
{
    /**
     * Ko dara: Inicializē profila kontrolierim vajadzīgo shēmas palīgservisu.
     *
     * Kā strādā: Laravel servisa konteiners padod RuntimeSchemaBootstrapper instanci, ko kontrolieris vēlāk izmanto profila iestatījumu saglabāšanā.
     *
     * Kad pielietojas: Kad Laravel izveido ProfileController instanci pirms profila maršrutu apstrādes.
     */
    public function __construct(
        private readonly RuntimeSchemaBootstrapper $runtimeSchemaBootstrapper
    ) {
    }

    /**
     * Ko dara: Profila rediģēšanas skats — novirza uz galveno lapu pēc lomas.
     *
     * Kā strādā: Administrators tiek novirzīts uz darba virsmu, bet parasts lietotājs uz ierīču sarakstu. Ja pieprasījumā ir `profile_modal`, tas tiek pārsūtīts tālāk uz mērķa lapu, lai profila modālis atvērtos JavaScript pusē.
     *
     * Kad pielietojas: Izsaukšana: GET /profile | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs klikšķina uz "Manu profilu" vai tiek novirzīts uz šo URL.
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
     * Ko dara: Saglabā lietotāja profila pamatdatus ar pūriņu izsekošanu audita žurnālā.
     *
     * Kā strādā: Izmaiņas tiek salīdzinātas ar iepriekšējo stāvokli un reģistrētas audita žurnālā. Pēc veiksmīgas saglabāšanas nosūta sesijā signālu profila modāļa aizvēršanai.
     *
     * Kad pielietojas: Izsaukšana: PUT /profile | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs aizpilda profila formu (vārds, e-pasts, tālrunis, amats) un saglabā.
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
     * Ko dara: Saglabā administratora skata preferenču iestatījumus (piemēram, norakstīto ierīču slēpšana).
     *
     * Kā strādā: Pieejams tikai administratoriem. Iestatījumi glabājas `user_settings` JSON kolonnā. Izmaiņas tiek reģistrētas audita žurnālā ar skaidru aprakstu.
     *
     * Kad pielietojas: Kad administrators profila sadaļā maina darba vides noklusējumus.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 404);
        abort_unless($user->isAdmin(), 403);

        // Iestatījumus validējam ar atsevišķu kļūdu grozu, lai profila modālī
        // kļūdas neiejauktos paroles vai konta dzēšanas formās.
        $validated = $request->validateWithBag('profileSettings', [
            'hide_written_off_devices' => ['required', 'boolean'],
            'default_start_page' => ['required', 'string', Rule::in(User::startPageOptions())],
            'default_view_mode' => ['required', 'string', Rule::in(User::defaultViewModeOptions())],
            'default_device_filter' => ['required', 'string', Rule::in(User::deviceFilterOptions())],
            'notification_visual_mode' => ['required', 'string', Rule::in(User::notificationVisualOptions())],
            'default_request_filter' => ['required', 'string', Rule::in(User::requestFilterOptions())],
        ]);

        // Pirms saglabāšanas no modeļa nolasām cilvēkam saprotamās vērtības,
        // lai auditā varētu parādīt precīzu "pirms/pēc" izmaiņu sarakstu.
        $before = [
            User::SETTING_HIDE_WRITEOFF_DEVICES => $user->prefersHiddenWrittenOffDevices(),
            User::SETTING_DEFAULT_START_PAGE => $user->defaultStartPage(),
            User::SETTING_DEFAULT_VIEW_MODE => $user->defaultViewMode(),
            User::SETTING_DEFAULT_DEVICE_FILTER => $user->defaultDeviceFilter(),
            User::SETTING_NOTIFICATION_VISUAL_MODE => $user->notificationVisualMode(),
            User::SETTING_DEFAULT_REQUEST_FILTER => $user->defaultRequestFilter(),
        ];

        // Saglabājam tikai atļautās atslēgas JSON iestatījumos, nepārrakstot
        // iespējamas citas profila vērtības, kas var atrasties tajā pašā kolonnā.
        $settings = is_array($user->user_settings) ? $user->user_settings : [];
        $settings[User::SETTING_HIDE_WRITEOFF_DEVICES] = (bool) $validated['hide_written_off_devices'];
        $settings[User::SETTING_DEFAULT_START_PAGE] = $validated['default_start_page'];
        $settings[User::SETTING_DEFAULT_VIEW_MODE] = $validated['default_view_mode'];
        $settings[User::SETTING_DEFAULT_DEVICE_FILTER] = $validated['default_device_filter'];
        $settings[User::SETTING_NOTIFICATION_VISUAL_MODE] = $validated['notification_visual_mode'];
        $settings[User::SETTING_DEFAULT_REQUEST_FILTER] = $validated['default_request_filter'];

        $this->persistProfileSettings($user, $settings);

        // Pēc saglabāšanas pārlasām lietotāju no datubāzes, lai audits salīdzina
        // tieši tās vērtības, kas patiešām tika ierakstītas.
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
     * Ko dara: Saglabā profila iestatījumus, automātiski apstrādājot trūkstošās kolonnas.
     *
     * Kā strādā: Ja `user_settings` kolonna vēl nav datubāzē (legacy shēma), tiek izsaukts RuntimeSchemaBootstrapper, lai to izveidotu, un saglabāšana tiek atkārtota.
     *
     * Kad pielietojas: Kad profila iestatījumi jāsaglabā arī instalācijās, kur `user_settings` kolonna var vēl nebūt izveidota.
     */
    private function persistProfileSettings(User $user, array $settings): void
    {
        try {
            // forceFill šeit tiek izmantots kontrolētam iestatījumu JSON laukam,
            // pat ja šis lauks nav modeļa mass-assignment sarakstā.
            $user->forceFill([
                'user_settings' => $settings,
            ])->save();
        } catch (QueryException $exception) {
            if (! $this->isMissingUserSettingsColumn($exception)) {
                throw $exception;
            }

            // Ja kolonna trūka vecākā datubāzes shēmā, bootstrapper mēģinās to izveidot runtime laikā.
            $this->runtimeSchemaBootstrapper->ensure();

            // Pēc labošanas pārbaudām, vai kolonna tiešām eksistē; ja nē, nemēģinām kļūdu noslēpt.
            if (! Schema::hasColumn('users', 'user_settings')) {
                throw $exception;
            }

            $user->refresh();
            // Lietotāju pārlasām un saglabājam atkārtoti, jo modeļa stāvoklis mainījās pēc shēmas labošanas.
            $user->forceFill([
                'user_settings' => $settings,
            ])->save();
        }
    }

    /**
     * Ko dara: Pārbauda, vai datubāzes kļūda ir par trūkstošo `user_settings` kolonnu.
     *
     * Kā strādā: Pārbauda datubāzes kļūdas tekstu un nosaka, vai saglabāšana izgāzās tieši trūkstošas `user_settings` kolonnas dēļ.
     *
     * Kad pielietojas: `persistProfileSettings()` kļūdas apstrādē, lai atšķirtu migrācijas problēmu no citām datubāzes kļūdām.
     */
    private function isMissingUserSettingsColumn(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), "unknown column 'user_settings'");
    }

    /**
     * Ko dara: Sagatavo cilvēkam saprotamu audita ieraksta aprakstu par iestatījumu maiņu.
     *
     * Kā strādā: Ja vērtība nav mainījusies, apraksts to norāda. Ja mainījusies — parāda pirms/pēc pāreju (ieslēgts/izslēgts).
     *
     * Kad pielietojas: Kad profila iestatījumu izmaiņas jāpārvērš saprotamā audita aprakstā.
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

    /**
     * Ko dara: Pārvērš profila iestatījuma tehnisko vērtību cilvēkam saprotamā tekstā.
     *
     * Kā strādā: Katram iestatījuma tipam tehnisko konstantu vai boolean vērtību pārvērš latviskā tekstā, ko izmanto audita pierakstā.
     *
     * Kad pielietojas: Izsauc no: `profileSettingsAuditDescription()`.
     */
    private function settingValueLabel(mixed $value, ?string $key = null): string
    {
        // Katram izvēlnes tipa iestatījumam tehnisko konstantu pārvēršam tekstā,
        // lai audita žurnāls būtu saprotams bez koda skatīšanās.
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

        // Boolean un tukšās vērtības apstrādājam kopīgi, jo tās izmanto vairākas
        // profila izvēles un auditā tām jāizskatās vienādi.
        return match ($value) {
            true => 'ieslēgts',
            false => 'izslēgts',
            null, '' => 'nav norādīts',
            default => (string) $value,
        };
    }

    /**
     * Ko dara: Dzēš lietotāja kontu pēc paroles apstiprināšanas.
     *
     * Kā strādā: Pirms dzēšanas reģistrē audita žurnālā brīdinājuma līmeņa notikumu. Sesija tiek pilnīgi atiestatīta, lai novērstu jebkādu sesijas noplūdi.
     *
     * Kad pielietojas: Kad lietotājs profila sadaļā apstiprina sava konta dzēšanu ar paroli.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Konta dzēšanai prasa pašreizējo paroli; Laravel salīdzina ievadi ar saglabāto paroles hash.
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        AuditTrail::deleted($user?->id, $user, 'Lietotāja konts dzēsts: ' . AuditTrail::labelFor($user), AuditTrail::SEVERITY_WARNING);

        // Vispirms izrakstām lietotāju, lai pēc dzēšanas sesija vairs nebūtu piesaistīta neeksistējošam kontam.
        Auth::logout();
        $user?->delete();

        // Sesijas anulēšana un jauns CSRF tokens nodrošina, ka vecie pieprasījumi pēc konta dzēšanas vairs neder.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
