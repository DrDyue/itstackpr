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

        $this->alignUsers($driver);
        $this->alignDevices($driver);
        $this->alignRepairs($driver);
        $this->alignRepairRequests($driver);
        $this->alignWriteoffRequests($driver);
        $this->alignDeviceTransfers($driver);
    }

    public function down(): void
    {
        // Compatibility migration is intentionally non-destructive.
    }

    private function alignUsers(string $driver): void
    {
        if ($driver === 'mysql' && Schema::hasColumn('users', 'role')) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'it_worker', 'user') NOT NULL DEFAULT 'user'");
        }
    }

    private function alignDevices(string $driver): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('room_id');
            }

            if (! Schema::hasColumn('devices', 'warranty_photo_name')) {
                $table->string('warranty_photo_name', 50)->nullable()->after('warranty_until');
            }
        });

        if ($driver === 'mysql' && Schema::hasColumn('devices', 'status')) {
            DB::statement("ALTER TABLE devices MODIFY status ENUM('active', 'reserve', 'broken', 'repair', 'written_off', 'kitting', 'writeoff') NOT NULL DEFAULT 'active'");
            DB::table('devices')->where('status', 'writeoff')->update(['status' => 'written_off']);
            DB::statement("ALTER TABLE devices MODIFY status ENUM('active', 'reserve', 'broken', 'repair', 'written_off', 'kitting') NOT NULL DEFAULT 'active'");
        }
    }

    private function alignRepairs(string $driver): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'reported_by_user_id')) {
                $table->unsignedBigInteger('reported_by_user_id')->nullable()->after('device_id');
            }

            if (! Schema::hasColumn('repairs', 'assigned_to_user_id')) {
                $table->unsignedBigInteger('assigned_to_user_id')->nullable()->after('reported_by_user_id');
            }

            if (! Schema::hasColumn('repairs', 'accepted_by_user_id')) {
                $table->unsignedBigInteger('accepted_by_user_id')->nullable()->after('assigned_to_user_id');
            }

            if (! Schema::hasColumn('repairs', 'device_status_before_repair')) {
                $table->string('device_status_before_repair', 20)->nullable()->after('status');
            }

            if (! Schema::hasColumn('repairs', 'estimated_completion')) {
                $table->date('estimated_completion')->nullable()->after('start_date');
            }

            if (! Schema::hasColumn('repairs', 'actual_completion')) {
                $table->date('actual_completion')->nullable()->after('estimated_completion');
            }

            if (! Schema::hasColumn('repairs', 'diagnosis')) {
                $table->text('diagnosis')->nullable()->after('actual_completion');
            }

            if (! Schema::hasColumn('repairs', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('diagnosis');
            }
        });

        if (Schema::hasColumn('repairs', 'issue_reported_by')) {
            DB::table('repairs')
                ->whereNull('reported_by_user_id')
                ->update(['reported_by_user_id' => DB::raw('issue_reported_by')]);
        }

        if (Schema::hasColumn('repairs', 'accepted_by')) {
            DB::table('repairs')
                ->whereNull('accepted_by_user_id')
                ->update(['accepted_by_user_id' => DB::raw('accepted_by')]);
        }

        if (Schema::hasColumn('repairs', 'end_date')) {
            DB::table('repairs')
                ->whereNull('actual_completion')
                ->update(['actual_completion' => DB::raw('end_date')]);
        }

        if ($driver === 'mysql' && Schema::hasColumn('repairs', 'status')) {
            DB::statement("ALTER TABLE repairs MODIFY status ENUM('waiting', 'in-progress', 'completed', 'cancelled') NOT NULL DEFAULT 'waiting'");
        }
    }

    private function alignRepairRequests(string $driver): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('repair_requests', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('repair_requests', 'repair_id')) {
                $table->unsignedBigInteger('repair_id')->nullable()->after('reviewed_by_user_id');
            }

            if (! Schema::hasColumn('repair_requests', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('repair_id');
            }
        });

        if ($driver === 'mysql' && Schema::hasColumn('repair_requests', 'status')) {
            DB::statement("ALTER TABLE repair_requests MODIFY status ENUM('submitted', 'pending', 'approved', 'denied', 'indicated') NOT NULL DEFAULT 'pending'");
            DB::table('repair_requests')->where('status', 'submitted')->update(['status' => 'pending']);
            DB::table('repair_requests')->where('status', 'indicated')->update(['status' => 'denied']);
            DB::statement("ALTER TABLE repair_requests MODIFY status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending'");
        }

        if (Schema::hasColumn('repairs', 'request_id') && Schema::hasColumn('repair_requests', 'repair_id')) {
            DB::statement("
                UPDATE repair_requests rr
                INNER JOIN repairs r ON r.request_id = rr.id
                SET rr.repair_id = r.id
                WHERE rr.repair_id IS NULL
            ");
        }
    }

    private function alignWriteoffRequests(string $driver): void
    {
        Schema::table('writeoff_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('writeoff_requests', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('writeoff_requests', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_by_user_id');
            }
        });

        if ($driver === 'mysql' && Schema::hasColumn('writeoff_requests', 'status')) {
            DB::statement("ALTER TABLE writeoff_requests MODIFY status ENUM('submitted', 'pending', 'approved', 'denied', 'indicated') NOT NULL DEFAULT 'pending'");
            DB::table('writeoff_requests')->where('status', 'submitted')->update(['status' => 'pending']);
            DB::table('writeoff_requests')->where('status', 'indicated')->update(['status' => 'denied']);
            DB::statement("ALTER TABLE writeoff_requests MODIFY status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending'");
        }
    }

    private function alignDeviceTransfers(string $driver): void
    {
        Schema::table('device_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('device_transfers', 'transfer_to_user_id')) {
                $table->unsignedBigInteger('transfer_to_user_id')->nullable()->after('responsible_user_id');
            }

            if (! Schema::hasColumn('device_transfers', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('device_transfers', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_by_user_id');
            }
        });

        if (Schema::hasColumn('device_transfers', 'transfered_to_id')) {
            DB::table('device_transfers')
                ->whereNull('transfer_to_user_id')
                ->update(['transfer_to_user_id' => DB::raw('transfered_to_id')]);
        }

        if ($driver === 'mysql' && Schema::hasColumn('device_transfers', 'status')) {
            DB::statement("ALTER TABLE device_transfers MODIFY status ENUM('submitted', 'pending', 'approved', 'denied', 'indicated') NOT NULL DEFAULT 'pending'");
            DB::table('device_transfers')->where('status', 'submitted')->update(['status' => 'pending']);
            DB::table('device_transfers')->where('status', 'indicated')->update(['status' => 'denied']);
            DB::statement("ALTER TABLE device_transfers MODIFY status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending'");
        }
    }
};
