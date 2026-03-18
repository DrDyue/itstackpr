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

        $this->ensureTimestampColumns('users');
        $this->ensureTimestampColumns('devices');
        $this->ensureTimestampColumns('repairs');
        $this->ensureTimestampColumns('repair_requests');
        $this->ensureTimestampColumns('writeoff_requests');
        $this->ensureTimestampColumns('device_transfers');

        $this->alignUsers($driver);
        $this->alignDevices($driver);
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }

    private function ensureTimestampColumns(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'created_at')) {
                $blueprint->timestamp('created_at')->nullable()->useCurrent();
            }

            if (! Schema::hasColumn($table, 'updated_at')) {
                $blueprint->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        DB::table($table)
            ->whereNull('created_at')
            ->update(['created_at' => now()]);

        DB::table($table)
            ->whereNull('updated_at')
            ->update(['updated_at' => DB::raw('created_at')]);

        DB::table($table)
            ->whereNull('updated_at')
            ->update(['updated_at' => now()]);
    }

    private function alignUsers(string $driver): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'it_worker', 'user') NOT NULL DEFAULT 'user'");
        }
    }

    private function alignDevices(string $driver): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('room_id');
            }

            if (! Schema::hasColumn('devices', 'warranty_photo_name')) {
                $table->string('warranty_photo_name', 50)->nullable()->after('warranty_until');
            }
        });

        if (Schema::hasColumn('devices', 'assigned_to_id')) {
            DB::table('devices')
                ->whereNull('assigned_user_id')
                ->update(['assigned_user_id' => DB::raw('assigned_to_id')]);
        }

        if ($driver === 'mysql' && Schema::hasColumn('devices', 'status')) {
            DB::statement("ALTER TABLE devices MODIFY status ENUM('active', 'reserve', 'broken', 'repair', 'written_off', 'kitting', 'writeoff') NOT NULL DEFAULT 'active'");
            DB::table('devices')->where('status', 'writeoff')->update(['status' => 'written_off']);
            DB::statement("ALTER TABLE devices MODIFY status ENUM('active', 'reserve', 'broken', 'repair', 'written_off', 'kitting') NOT NULL DEFAULT 'active'");
        }

        if ($driver === 'mysql' && Schema::hasColumn('devices', 'purchase_date')) {
            DB::statement('ALTER TABLE devices MODIFY purchase_date DATE NULL');
        }
    }
};
