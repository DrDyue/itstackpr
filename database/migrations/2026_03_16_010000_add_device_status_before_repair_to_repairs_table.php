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
            if (! Schema::hasColumn('repairs', 'device_status_before_repair')) {
                $table->string('device_status_before_repair', 20)->nullable();
            }
        });

        $repairs = DB::table('repairs')
            ->whereNull('device_status_before_repair')
            ->get(['id', 'device_id']);

        if ($repairs->isEmpty()) {
            return;
        }

        $deviceIds = $repairs->pluck('device_id')->filter()->unique()->values();
        $deviceStatuses = DB::table('devices')
            ->whereIn('id', $deviceIds)
            ->pluck('status', 'id');

        $latestRepairStatusHistory = DB::table('device_history')
            ->where('field_changed', 'status')
            ->where('new_value', 'repair')
            ->orderByDesc('timestamp')
            ->get(['device_id', 'old_value'])
            ->unique('device_id')
            ->keyBy('device_id');

        foreach ($repairs as $repair) {
            $status = $deviceStatuses[$repair->device_id] ?? 'active';

            if ($status === 'repair') {
                $status = $latestRepairStatusHistory[$repair->device_id]->old_value ?? 'active';
            }

            if (! in_array($status, ['active', 'reserve', 'broken', 'kitting'], true)) {
                $status = 'active';
            }

            DB::table('repairs')
                ->where('id', $repair->id)
                ->update(['device_status_before_repair' => $status]);
        }
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'device_status_before_repair')) {
                $table->dropColumn('device_status_before_repair');
            }
        });
    }
};
