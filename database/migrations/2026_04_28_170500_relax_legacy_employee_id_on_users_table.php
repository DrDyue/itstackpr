<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'employee_id')
        ) {
            return;
        }

        $column = collect(Schema::getColumns('users'))
            ->firstWhere('name', 'employee_id');

        if (($column['nullable'] ?? false) === true) {
            return;
        }

        $metadata = DB::selectOne("SHOW COLUMNS FROM `users` LIKE 'employee_id'");
        $type = (string) ($metadata->Type ?? '');

        if ($type === '') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `users` MODIFY `employee_id` %s NULL',
            $type,
        ));
    }

    public function down(): void
    {
        // Legacy compatibility fix is intentionally irreversible.
    }
};
