<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('devices')) {
            DB::table('devices')
                ->where('status', 'kitting')
                ->update(['status' => 'reserve']);

            if (Schema::hasColumn('devices', 'assigned_employee_id')) {
                Schema::table('devices', function (Blueprint $table) {
                    $table->dropColumn('assigned_employee_id');
                });
            }
        }

        if (Schema::hasTable('repairs') && Schema::hasColumn('repairs', 'reported_employee_id')) {
            Schema::table('repairs', function (Blueprint $table) {
                $table->dropColumn('reported_employee_id');
            });
        }

        Schema::dropIfExists('device_set_items');
        Schema::dropIfExists('device_sets');
        Schema::dropIfExists('device_history');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('database_backups');
        Schema::dropIfExists('backup_settings');
    }

    public function down(): void
    {
        // Legacy cleanup is intentionally not reversible.
    }
};
