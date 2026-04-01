<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_log')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE audit_log MODIFY action VARCHAR(50) NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE audit_log ALTER COLUMN action TYPE VARCHAR(50)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Atgriešana uz ENUM nav droša, jo esošajos datos var būt jaunās darbību vērtības.
    }
};
