<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'reported_employee_id')) {
                $table->foreignId('reported_employee_id')
                    ->nullable()
                    ->after('issue_reported_by')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
        });

        $repairs = DB::table('repairs')
            ->whereNull('reported_employee_id')
            ->whereNotNull('issue_reported_by')
            ->get(['id', 'issue_reported_by']);

        if ($repairs->isEmpty()) {
            return;
        }

        $userIds = $repairs->pluck('issue_reported_by')->unique()->values();
        $employeeIdsByUser = DB::table('users')
            ->whereIn('id', $userIds)
            ->pluck('employee_id', 'id');

        foreach ($repairs as $repair) {
            $employeeId = $employeeIdsByUser[$repair->issue_reported_by] ?? null;

            if ($employeeId === null) {
                continue;
            }

            DB::table('repairs')
                ->where('id', $repair->id)
                ->update(['reported_employee_id' => $employeeId]);
        }
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'reported_employee_id')) {
                $table->dropConstrainedForeignId('reported_employee_id');
            }
        });
    }
};
