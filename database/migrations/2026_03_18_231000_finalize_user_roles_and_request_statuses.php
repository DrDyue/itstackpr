<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->normalizeUserRoles();
        $this->normalizeRequestStatuses('repair_requests');
        $this->normalizeRequestStatuses('writeoff_requests');
        $this->normalizeRequestStatuses('device_transfers');
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }

    private function normalizeUserRoles(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'it_worker', 'user') NOT NULL DEFAULT 'user'");
        DB::table('users')->where('role', 'it_worker')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'user') NOT NULL DEFAULT 'user'");
    }

    private function normalizeRequestStatuses(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
            return;
        }

        DB::statement("ALTER TABLE {$table} MODIFY status ENUM('submitted', 'pending', 'approved', 'denied', 'rejected', 'indicated') NOT NULL DEFAULT 'submitted'");
        DB::table($table)->where('status', 'pending')->update(['status' => 'submitted']);
        DB::table($table)->where('status', 'denied')->update(['status' => 'rejected']);
        DB::table($table)->where('status', 'indicated')->update(['status' => 'rejected']);
        DB::statement("ALTER TABLE {$table} MODIFY status ENUM('submitted', 'approved', 'rejected') NOT NULL DEFAULT 'submitted'");
    }
};
