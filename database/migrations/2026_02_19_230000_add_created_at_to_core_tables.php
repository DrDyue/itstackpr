<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['employees', 'buildings', 'rooms', 'device_types', 'device_set_items'];

        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'created_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->timestamp('created_at')->nullable()->useCurrent();
                });
            }
        }

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('created_at')
                ->update(['created_at' => now()]);
        }
    }

    public function down(): void
    {
        $tables = ['employees', 'buildings', 'rooms', 'device_types', 'device_set_items'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'created_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('created_at');
                });
            }
        }
    }
};
