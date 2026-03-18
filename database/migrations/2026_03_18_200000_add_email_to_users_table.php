<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! Schema::hasColumn('users', 'full_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('full_name', 100)->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email', 100)->nullable()->after('full_name');
            });
        }

        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 100)->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'job_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('job_title', 100)->nullable()->after('phone');
            });
        }

        if ($driver === 'mysql' && Schema::hasTable('employees') && Schema::hasColumn('users', 'employee_id')) {
            DB::statement('
                UPDATE users u
                INNER JOIN employees e ON e.id = u.employee_id
                SET
                    u.full_name = COALESCE(u.full_name, e.full_name),
                    u.email = COALESCE(u.email, e.email),
                    u.phone = COALESCE(u.phone, e.phone),
                    u.job_title = COALESCE(u.job_title, e.job_title),
                    u.is_active = COALESCE(u.is_active, e.is_active, 1)
            ');
        }

        if ($driver === 'mysql' && ! $this->hasUniqueIndex('users', 'users_email_unique')) {
            DB::statement('ALTER TABLE users ADD UNIQUE KEY users_email_unique (email)');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
                $table->dropColumn('email');
            });
        }
    }

    private function hasUniqueIndex(string $table, string $index): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
