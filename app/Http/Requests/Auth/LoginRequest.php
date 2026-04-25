<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\AuthBootstrapper;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pieslēgšanās validācijas un throttling noteikumi.
 *
 * Apvieno e-pasta un paroles validāciju ar autentifikācijas loģiku
 * un rate limiting aizsardzību pret brute force uzbrukumiem.
 * Atbalsta pēdējās pieslēgšanās laika ievākšanu un sistēmas shēmas pārbaudi.
 */
class LoginRequest extends FormRequest
{
    /**
     * Pārbauda autorizāciju — pieslēgšanās forma ir pieejama jebkuram.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Definē validācijas noteikumus pieslēgšanās datu validācijai.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Sagatavo datus validācijai — konvertē e-pastu uz mazo burtu un noņem espacijas.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Nodrošina lietotāju draudzīgus kļūdu paziņojumus validācijas kļūmju gadījumā.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Lauks "E-pasts" ir obligāts.',
            'email.email' => 'Lauks "E-pasts" nav derīgs e-pasta adreses formāts.',
            'password.required' => 'Lauks "Parole" ir obligāts.',
        ];
    }

    /**
     * Apstrādā autentifikāciju ar rate limiting aizsardzību un pēdējās pieslēgšanās atjauninājumu.
     *
     * Validē e-pastu un paroli, pārbauda sistēmas smiesnību, pierakstā lietotāju
     * un atjaunina pēdējo pieslēgšanās laiku. Rate limiting bloķē ļaunprātīgus mēģinājumus.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = (string) $this->string('email');
        $password = (string) $this->string('password');
        $bootstrapStatus = app(AuthBootstrapper::class)->prepareAuthentication($email, $password);

        if (! ($bootstrapStatus['ready'] ?? false)) {
            throw ValidationException::withMessages([
                'email' => [$bootstrapStatus['message'] ?? 'Autentifikācija nav pieejama, jo datubāze nav pilnībā sagatavota.'],
            ]);
        }

        try {
            $userQuery = User::query()->whereRaw('LOWER(email) = ?', [$email]);

            if ($this->hasUsersColumn('is_active')) {
                $userQuery->where('is_active', true);
            }

            $user = $userQuery->first();
        } catch (QueryException $e) {
            // Ja shēma nav sinhronizēta (trūkst kolonnas), uztveram to kā neveiksmīgu pieslēgšanos,
            // lai izvairītos no 500 kļūdas. Papildus pierakstām to logā diagnostikai.
            Log::error('Login query failed (possible missing column): ' . $e->getMessage());

            $user = null;
        }

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $this->touchLastLogin($user);

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Pārbauda, vai pieslēgšanās mēģinājumus nav pārāk daudz (rate limiting).
     *
     * Pieļauj ne vairāk kā 5 ļaunprātīgus mēģinājumus vienas IP/e-pasta kombinācijas attiecībā uz stundu.
     * Ja limitu pārsniedz, izmet Lockout notikumu un validācijas kļūdu.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Ģenerē rate limiting atslēgu, kas kombinē e-pastu un IP adresi.
     *
     * Šī kombinācija nodrošina unikālo bloķēšanu katram lietotājam no atsevišķas IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }

    /**
     * Atjaunina lietotāja pēdējo pieslēgšanās laiku, ja tabula to atbalsta.
     *
     * Ja atjauninājums neizdodas (kolonnas nava pieejama), logs pieraksta brīdinājumu.
     */
    private function touchLastLogin(User $user): void
    {
        if (! $this->hasLastLoginColumn($user)) {
            return;
        }

        try {
            DB::table($user->getTable())
                ->where('id', $user->getKey())
                ->update(['last_login' => now()]);
        } catch (QueryException $e) {
            Log::warning('Unable to update last_login during login: ' . $e->getMessage());
        }
    }

    /**
     * Pārbauda, vai users tabula satur last_login kolonnu.
     *
     * Izmanto shēmas inspektoru, lai pārbaudītu kolonnas esamību.
     * Ja pārbaude neizdodas, logs pieraksta brīdinājumu un atgriež false.
     */
    private function hasLastLoginColumn(User $user): bool
    {
        try {
            return DB::connection($user->getConnectionName())
                ->getSchemaBuilder()
                ->hasColumn($user->getTable(), 'last_login');
        } catch (\Throwable $e) {
            Log::warning('Unable to inspect users schema during login: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Pārbauda, vai users tabula satur konkrētu kolonnu.
     *
     * Izmanto shēmas inspektoru, lai pārbaudītu kolonnas esamību.
     * Ja pārbaude neizdodas, logs pieraksta brīdinājumu un atgriež false.
     */
    private function hasUsersColumn(string $column): bool
    {
        try {
            return DB::connection()->getSchemaBuilder()->hasColumn('users', $column);
        } catch (\Throwable $e) {
            Log::warning('Unable to inspect users table during login: ' . $e->getMessage());

            return false;
        }
    }
}
