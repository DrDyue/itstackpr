<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $this->ensureCanonicalUserColumns();
        $this->backfillUsersFromEmployees($driver);
        $this->createMissingUsersFromEmployees();
        $this->normalizeAdminRoles();
        $this->alignRooms($driver);
        $this->alignDevices($driver);
        $this->alignRepairs($driver);
        $this->dropLegacyColumns($driver);
        $this->dropLegacyTables($driver);
    }

    public function down(): void
    {
        // Legacy cleanup migration is intentionally irreversible.
    }

    private function ensureCanonicalUserColumns(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email', 100)->nullable()->after('full_name');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 100)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    private function backfillUsersFromEmployees(string $driver): void
    {
        if (
            $driver !== 'mysql'
            || ! Schema::hasTable('employees')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'employee_id')
        ) {
            return;
        }

        DB::statement('
            UPDATE users u
            INNER JOIN employees e ON e.id = u.employee_id
            SET
                u.full_name = COALESCE(NULLIF(u.full_name, \'\'), e.full_name),
                u.email = COALESCE(NULLIF(u.email, \'\'), e.email),
                u.phone = COALESCE(NULLIF(u.phone, \'\'), e.phone),
                u.job_title = COALESCE(NULLIF(u.job_title, \'\'), e.job_title),
                u.is_active = COALESCE(u.is_active, e.is_active, 1),
                u.updated_at = COALESCE(u.updated_at, u.created_at, CURRENT_TIMESTAMP)
        ');
    }

    private function createMissingUsersFromEmployees(): void
    {
        if (
            ! Schema::hasTable('employees')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'employee_id')
        ) {
            return;
        }

        $existingEmployeeIds = DB::table('users')
            ->whereNotNull('employee_id')
            ->pluck('employee_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $employees = DB::table('employees')
            ->when($existingEmployeeIds !== [], fn ($query) => $query->whereNotIn('id', $existingEmployeeIds))
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $passwordHash = Hash::make('password');
        $adminEmails = [
            'artis.berzins@ludzas.lv',
            'linda.kalnina@ludzas.lv',
            'janis.ozols@ludzas.lv',
        ];

        $rows = $employees->map(function ($employee) use ($passwordHash, $adminEmails) {
            return [
                'employee_id' => $employee->id,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'job_title' => $employee->job_title,
                'password' => $passwordHash,
                'role' => in_array($employee->email, $adminEmails, true) ? 'admin' : 'user',
                'is_active' => (bool) ($employee->is_active ?? true),
                'remember_token' => null,
                'last_login' => null,
                'created_at' => $employee->created_at ?? now(),
                'updated_at' => $employee->created_at ?? now(),
            ];
        })->all();

        DB::table('users')->insert($rows);
    }

    private function normalizeAdminRoles(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        DB::table('users')->where('role', 'it_worker')->update(['role' => 'admin']);

        DB::table('users')
            ->whereIn('email', [
                'artis.berzins@ludzas.lv',
                'linda.kalnina@ludzas.lv',
                'janis.ozols@ludzas.lv',
            ])
            ->update(['role' => 'admin']);

        DB::table('users')
            ->whereNotNull('email')
            ->whereNotIn('email', [
                'artis.berzins@ludzas.lv',
                'linda.kalnina@ludzas.lv',
                'janis.ozols@ludzas.lv',
            ])
            ->update(['role' => 'user']);

        DB::table('users')
            ->whereNull('email')
            ->update(['role' => 'user']);
    }

    private function alignRooms(string $driver): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        Schema::table('rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('rooms', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('room_name');
            }
        });

        if (
            $driver === 'mysql'
            && Schema::hasColumn('rooms', 'employee_id')
            && Schema::hasColumn('users', 'employee_id')
        ) {
            DB::statement('
                UPDATE rooms r
                INNER JOIN users u ON u.employee_id = r.employee_id
                SET r.user_id = u.id
                WHERE r.user_id IS NULL
            ');
        }
    }

    private function alignDevices(string $driver): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'assigned_to_id')) {
                $table->unsignedBigInteger('assigned_to_id')->nullable()->after('room_id');
            }
        });

        if (Schema::hasColumn('devices', 'assigned_user_id')) {
            DB::table('devices')
                ->whereNull('assigned_to_id')
                ->update(['assigned_to_id' => DB::raw('assigned_user_id')]);
        }

        if ($driver === 'mysql' && Schema::hasColumn('devices', 'assigned_to')) {
            DB::statement('
                UPDATE devices d
                INNER JOIN users u ON u.full_name = d.assigned_to
                SET d.assigned_to_id = u.id
                WHERE d.assigned_to_id IS NULL AND d.assigned_to IS NOT NULL AND d.assigned_to <> \'\'
            ');
        }
    }

    private function alignRepairs(string $driver): void
    {
        if (! Schema::hasTable('repairs')) {
            return;
        }

        if (Schema::hasColumn('repairs', 'assigned_to') && Schema::hasColumn('repairs', 'accepted_by')) {
            DB::table('repairs')
                ->whereNull('accepted_by')
                ->update(['accepted_by' => DB::raw('assigned_to')]);
        }

        if (Schema::hasColumn('repairs', 'actual_completion') && Schema::hasColumn('repairs', 'end_date')) {
            DB::table('repairs')
                ->whereNull('end_date')
                ->update(['end_date' => DB::raw('actual_completion')]);
        }

        if (Schema::hasColumn('repairs', 'estimated_completion') && Schema::hasColumn('repairs', 'end_date')) {
            DB::table('repairs')
                ->whereNull('end_date')
                ->update(['end_date' => DB::raw('estimated_completion')]);
        }

        if (
            $driver === 'mysql'
            && Schema::hasTable('repair_requests')
            && Schema::hasColumn('repair_requests', 'repair_id')
            && Schema::hasColumn('repairs', 'request_id')
        ) {
            DB::statement('
                UPDATE repairs r
                INNER JOIN repair_requests rr ON rr.repair_id = r.id
                SET r.request_id = rr.id
                WHERE r.request_id IS NULL
            ');
        }
    }

    private function dropLegacyColumns(string $driver): void
    {
        if ($driver !== 'mysql') {
            return;
        }

        $this->dropForeignIfExists('users', 'users_employee_id_foreign');
        $this->dropIndexIfExists('users', 'users_employee_id_unique');
        $this->dropColumnIfExists('users', 'employee_id');

        $this->dropForeignIfExists('rooms', 'rooms_employee_id_foreign');
        $this->dropIndexIfExists('rooms', 'rooms_employee_id_foreign');
        $this->dropColumnIfExists('rooms', 'employee_id');

        $this->dropColumnIfExists('devices', 'assigned_to');
        $this->dropColumnIfExists('devices', 'assigned_user_id');

        $this->dropForeignIfExists('repairs', 'repairs_assigned_to_foreign');
        $this->dropIndexIfExists('repairs', 'repairs_assigned_to_foreign');
        $this->dropColumnIfExists('repairs', 'assigned_to');
        $this->dropColumnIfExists('repairs', 'reported_by_user_id');
        $this->dropColumnIfExists('repairs', 'assigned_to_user_id');
        $this->dropColumnIfExists('repairs', 'accepted_by_user_id');
        $this->dropColumnIfExists('repairs', 'estimated_completion');
        $this->dropColumnIfExists('repairs', 'actual_completion');

        $this->dropColumnIfExists('repair_requests', 'repair_id');
        $this->dropColumnIfExists('device_transfers', 'transfer_to_user_id');
    }

    private function dropLegacyTables(string $driver): void
    {
        // Legacy tables already dropped in 2026_03_18_010000_drop_unused_legacy_features.php
        // This is a no-op to maintain migration compatibility.
    }

    private function dropForeignIfExists(string $table, string $foreign): void
    {
        if (! Schema::hasTable($table) || ! $this->hasMysqlConstraint($table, $foreign)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreign}");
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasMysqlIndex($table, $index)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    private function hasMysqlConstraint(string $table, string $constraint): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ?',
            [$table, $constraint]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }

    private function hasMysqlIndex(string $table, string $index): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
