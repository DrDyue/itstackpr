<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Autentifikācijas sagatavošanas serviss.
 *
 * Tas nodrošina, ka pieslēgšanās forma un demo konti darbojas arī daļēji
 * nesinhronizētās vai legacy datubāzes vidēs.
 */
class AuthBootstrapper
{
    private const DEMO_PASSWORD = 'password';

    private const DEMO_ACCOUNTS = [
        'artis.berzins@ludzas.lv' => [
            'full_name' => 'Artis Bērziņš',
            'phone' => '+37126000001',
            'job_title' => 'Sistēmas administrators',
            'role' => User::ROLE_ADMIN,
        ],
        'ilze.strautina@ludzas.lv' => [
            'full_name' => 'Ilze Strautiņa',
            'phone' => '+37126000004',
            'job_title' => 'Projektu koordinatore',
            'role' => User::ROLE_USER,
        ],
    ];

    /**
     * Sagatavo pieslēgšanās ekrānu un vajadzības gadījumā demo kontus.
     */
    public function prepareLoginScreen(): array
    {
        return $this->bootstrap(alwaysEnsureDemoUsers: true);
    }

    /**
     * Sagatavo autentifikācijas vidi pirms reālas pieteikšanās.
     */
    public function prepareAuthentication(string $email, string $password): array
    {
        return $this->bootstrap(
            alwaysEnsureDemoUsers: false,
            requestedEmail: $email,
            requestedPassword: $password
        );
    }

    private function bootstrap(bool $alwaysEnsureDemoUsers, string $requestedEmail = '', string $requestedPassword = ''): array
    {
        try {
            if (! Schema::hasTable('users')) {
                return [
                    'ready' => false,
                    'message' => 'Datubāze nav gatava autentifikācijai: tabula users nav atrasta.',
                ];
            }

            $this->ensureUsersSchema();
            $this->backfillLegacyUserData();
            $this->normalizeLegacyRoles();

            if ($alwaysEnsureDemoUsers || $this->isDemoLoginAttempt($requestedEmail, $requestedPassword)) {
                $this->ensureDemoUsers();
            }
        } catch (Throwable $e) {
            Log::error('Auth bootstrap failed: ' . $e->getMessage());
        }

        $ready = $this->hasUsersColumn('email') && $this->hasUsersColumn('password');

        return [
            'ready' => $ready,
            'message' => $ready
                ? null
                : 'Autentifikācija nav pieejama, jo users tabula nav pilnībā sinhronizēta ar aplikāciju.',
        ];
    }

    private function ensureUsersSchema(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'password')) {
                $table->string('password', 255)->nullable();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->nullable();
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->string('remember_token', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'last_login')) {
                $table->timestamp('last_login')->nullable();
            }

            if (! Schema::hasColumn('users', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private function backfillLegacyUserData(): void
    {
        if ($this->hasUsersColumn('name') && $this->hasUsersColumn('full_name')) {
            DB::table('users')
                ->where(function ($query) {
                    $query->whereNull('full_name')->orWhere('full_name', '');
                })
                ->update(['full_name' => DB::raw('name')]);
        }

        // Legacy employees table sync removed - employees table is dropped in migration 2026_03_18_010000_drop_unused_legacy_features
    }

    private function normalizeLegacyRoles(): void
    {
        if (! $this->hasUsersColumn('role')) {
            return;
        }

        try {
            DB::table('users')
                ->whereNull('role')
                ->update(['role' => User::ROLE_USER]);
        } catch (QueryException $e) {
            Log::warning('Unable to normalize null user roles: ' . $e->getMessage());
        }
    }

    private function ensureDemoUsers(): void
    {
        foreach (self::DEMO_ACCOUNTS as $email => $account) {
            $this->upsertDemoUser($email, $account);
        }
    }

    private function upsertDemoUser(string $email, array $account): void
    {
        if (! $this->hasUsersColumn('email') || ! $this->hasUsersColumn('password')) {
            return;
        }

        $payload = [
            'email' => $email,
            'password' => Hash::make(self::DEMO_PASSWORD),
        ];

        if ($this->hasUsersColumn('full_name')) {
            $payload['full_name'] = $account['full_name'];
        }

        if ($this->hasUsersColumn('phone')) {
            $payload['phone'] = $account['phone'];
        }

        if ($this->hasUsersColumn('job_title')) {
            $payload['job_title'] = $account['job_title'];
        }

        if ($this->hasUsersColumn('is_active')) {
            $payload['is_active'] = true;
        }

        if ($this->hasUsersColumn('remember_token')) {
            $payload['remember_token'] = null;
        }

        if ($this->hasUsersColumn('last_login')) {
            $payload['last_login'] = null;
        }

        if ($this->hasUsersColumn('created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->hasUsersColumn('updated_at')) {
            $payload['updated_at'] = now();
        }

        try {
            DB::table('users')->updateOrInsert(['email' => $email], $payload);
        } catch (QueryException $e) {
            Log::warning('Demo user full upsert failed, retrying with minimal payload: ' . $e->getMessage());

            $minimalPayload = [
                'email' => $email,
                'password' => Hash::make(self::DEMO_PASSWORD),
            ];

            if ($this->hasUsersColumn('created_at')) {
                $minimalPayload['created_at'] = now();
            }

            if ($this->hasUsersColumn('updated_at')) {
                $minimalPayload['updated_at'] = now();
            }

            DB::table('users')->updateOrInsert(['email' => $email], $minimalPayload);
        }

        $this->applyBestRoleValue($email, $account['role']);
    }

    private function applyBestRoleValue(string $email, string $desiredRole): void
    {
        if (! $this->hasUsersColumn('role') || ! $this->hasUsersColumn('email')) {
            return;
        }

        $candidates = match ($desiredRole) {
            User::ROLE_ADMIN => [User::ROLE_ADMIN, User::ROLE_IT_WORKER],
            default => [User::ROLE_USER],
        };

        foreach ($candidates as $candidate) {
            try {
                DB::table('users')
                    ->where('email', $email)
                    ->update(['role' => $candidate]);

                return;
            } catch (QueryException $e) {
                Log::warning("Unable to set demo role '{$candidate}' for {$email}: " . $e->getMessage());
            }
        }
    }

    private function isDemoLoginAttempt(string $email, string $password): bool
    {
        return $password === self::DEMO_PASSWORD && array_key_exists($email, self::DEMO_ACCOUNTS);
    }

    private function hasUsersColumn(string $column): bool
    {
        try {
            return Schema::hasColumn('users', $column);
        } catch (Throwable $e) {
            Log::warning('Unable to inspect users table column ' . $column . ': ' . $e->getMessage());

            return false;
        }
    }
}
