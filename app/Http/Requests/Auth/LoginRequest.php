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
            $user = User::query()
                ->where('email', $this->email)
                ->where('is_active', true)
                ->first();
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
}
