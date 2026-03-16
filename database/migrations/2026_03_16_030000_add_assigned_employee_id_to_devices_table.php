<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('devices', 'assigned_employee_id')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->foreignId('assigned_employee_id')
                    ->nullable()
                    ->after('assigned_to')
                    ->constrained('employees')
                    ->nullOnDelete();
            });
        }

        $employeeIdsByName = DB::table('employees')->pluck('id', 'full_name');

        DB::table('devices')
            ->select(['id', 'assigned_to'])
            ->whereNull('assigned_employee_id')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->get()
            ->each(function ($device) use ($employeeIdsByName) {
                $employeeId = $employeeIdsByName[$device->assigned_to] ?? null;

                if ($employeeId === null) {
                    return;
                }

                DB::table('devices')
                    ->where('id', $device->id)
                    ->update(['assigned_employee_id' => $employeeId]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('devices', 'assigned_employee_id')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assigned_employee_id');
            });
        }
    }
};
