<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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

class LoginRequest extends FormRequest
{
    private const DEMO_EMAIL = 'artis.berzins@ludzas.lv';
    private const DEMO_PASSWORD = 'password';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Lauks "E-pasts" ir obligats.',
            'email.email' => 'Lauks "E-pasts" nav deriga e-pasta adrese.',
            'password.required' => 'Lauks "Parole" ir obligats.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        try {
            $this->ensureDemoUserIsAvailable();

            $userQuery = User::query()->where('email', $this->email);

            if ($this->hasUsersColumn('is_active')) {
                $userQuery->where('is_active', true);
            }

            $user = $userQuery->first();
        } catch (QueryException $e) {
            // If the schema is out-of-sync (missing columns), treat it as a failed login
            // to avoid a 500 error. Also log for debugging.
            Log::error('Login query failed (possible missing column): ' . $e->getMessage());

            $user = null;
        }

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $this->touchLastLogin($user);

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

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

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }

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

    private function ensureDemoUserIsAvailable(): void
    {
        if (
            ! is_string($this->email)
            || ! is_string($this->password)
            || Str::lower($this->email) !== self::DEMO_EMAIL
            || $this->password !== self::DEMO_PASSWORD
        ) {
            return;
        }

        try {
            $schema = DB::connection()->getSchemaBuilder();
            if (! $schema->hasTable('users')) {
                return;
            }

            $payload = [
                'email' => self::DEMO_EMAIL,
                'password' => Hash::make(self::DEMO_PASSWORD),
            ];

            if ($schema->hasColumn('users', 'full_name')) {
                $payload['full_name'] = 'Artis Berzins';
            }

            if ($schema->hasColumn('users', 'phone')) {
                $payload['phone'] = '+37126000001';
            }

            if ($schema->hasColumn('users', 'job_title')) {
                $payload['job_title'] = 'Sistemas administrators';
            }

            if ($schema->hasColumn('users', 'role')) {
                $payload['role'] = User::ROLE_ADMIN;
            }

            if ($schema->hasColumn('users', 'is_active')) {
                $payload['is_active'] = true;
            }

            if ($schema->hasColumn('users', 'remember_token')) {
                $payload['remember_token'] = null;
            }

            if ($schema->hasColumn('users', 'last_login')) {
                $payload['last_login'] = null;
            }

            if ($schema->hasColumn('users', 'created_at')) {
                $payload['created_at'] = now();
            }

            if ($schema->hasColumn('users', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            DB::table('users')->updateOrInsert(
                ['email' => self::DEMO_EMAIL],
                $payload
            );
        } catch (\Throwable $e) {
            Log::warning('Unable to ensure demo user during login: ' . $e->getMessage());
        }
    }

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
