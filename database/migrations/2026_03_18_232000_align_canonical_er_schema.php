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

        $this->alignDevices($driver);
        $this->alignRepairs($driver);
        $this->alignTransfers();
        $this->normalizeRequestStatuses($driver, 'repair_requests');
        $this->normalizeRequestStatuses($driver, 'writeoff_requests');
        $this->normalizeRequestStatuses($driver, 'device_transfers');
    }

    public function down(): void
    {
        // Intentionally non-destructive.
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

        if ($driver === 'mysql' && Schema::hasColumn('devices', 'status')) {
            DB::statement("ALTER TABLE devices MODIFY status ENUM('active', 'repair', 'writeoff', 'written_off', 'reserve', 'broken', 'kitting') NOT NULL DEFAULT 'active'");
            DB::table('devices')->whereIn('status', ['reserve', 'broken', 'kitting'])->update(['status' => 'active']);
            DB::table('devices')->where('status', 'written_off')->update(['status' => 'writeoff']);
            DB::statement("ALTER TABLE devices MODIFY status ENUM('active', 'repair', 'writeoff') NOT NULL DEFAULT 'active'");
        }
    }

    private function alignRepairs(string $driver): void
    {
        if (! Schema::hasTable('repairs')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'issue_reported_by')) {
                $table->unsignedBigInteger('issue_reported_by')->nullable()->after('device_id');
            }

            if (! Schema::hasColumn('repairs', 'accepted_by')) {
                $table->unsignedBigInteger('accepted_by')->nullable()->after('issue_reported_by');
            }

            if (! Schema::hasColumn('repairs', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (! Schema::hasColumn('repairs', 'request_id')) {
                $table->unsignedBigInteger('request_id')->nullable()->after('accepted_by');
            }
        });

        if (Schema::hasColumn('repairs', 'reported_by_user_id')) {
            DB::table('repairs')
                ->whereNull('issue_reported_by')
                ->update(['issue_reported_by' => DB::raw('reported_by_user_id')]);
        }

        if (Schema::hasColumn('repairs', 'accepted_by_user_id')) {
            DB::table('repairs')
                ->whereNull('accepted_by')
                ->update(['accepted_by' => DB::raw('accepted_by_user_id')]);
        }

        if (Schema::hasColumn('repairs', 'actual_completion')) {
            DB::table('repairs')
                ->whereNull('end_date')
                ->update(['end_date' => DB::raw('actual_completion')]);
        }

        if ($driver === 'mysql' && Schema::hasTable('repair_requests') && Schema::hasColumn('repair_requests', 'repair_id')) {
            DB::statement("
                UPDATE repairs r
                INNER JOIN repair_requests rr ON rr.repair_id = r.id
                SET r.request_id = rr.id
                WHERE r.request_id IS NULL
            ");
        }
    }

    private function alignTransfers(): void
    {
        if (! Schema::hasTable('device_transfers')) {
            return;
        }

        Schema::table('device_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('device_transfers', 'transfered_to_id')) {
                $table->unsignedBigInteger('transfered_to_id')->nullable()->after('responsible_user_id');
            }
        });

        if (Schema::hasColumn('device_transfers', 'transfer_to_user_id')) {
            DB::table('device_transfers')
                ->whereNull('transfered_to_id')
                ->update(['transfered_to_id' => DB::raw('transfer_to_user_id')]);
        }
    }

    private function normalizeRequestStatuses(string $driver, string $table): void
    {
        if ($driver !== 'mysql' || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
            return;
        }

        DB::statement("ALTER TABLE {$table} MODIFY status ENUM('submitted', 'approved', 'rejected', 'indicated', 'pending', 'denied') NOT NULL DEFAULT 'submitted'");
        DB::table($table)->where('status', 'pending')->update(['status' => 'submitted']);
        DB::table($table)->where('status', 'denied')->update(['status' => 'rejected']);
        DB::table($table)->where('status', 'indicated')->update(['status' => 'rejected']);
        DB::statement("ALTER TABLE {$table} MODIFY status ENUM('submitted', 'approved', 'rejected') NOT NULL DEFAULT 'submitted'");
    }
};
