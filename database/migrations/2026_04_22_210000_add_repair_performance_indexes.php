<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('repairs', 'repairs_device_status_idx', fn (Blueprint $table) => $table->index(['device_id', 'status'], 'repairs_device_status_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_status_idx', fn (Blueprint $table) => $table->index('status', 'repairs_status_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_priority_idx', fn (Blueprint $table) => $table->index('priority', 'repairs_priority_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_repair_type_idx', fn (Blueprint $table) => $table->index('repair_type', 'repairs_repair_type_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_accepted_by_status_idx', fn (Blueprint $table) => $table->index(['accepted_by', 'status'], 'repairs_accepted_by_status_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_issue_reported_by_idx', fn (Blueprint $table) => $table->index('issue_reported_by', 'repairs_issue_reported_by_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_request_id_idx', fn (Blueprint $table) => $table->index('request_id', 'repairs_request_id_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_start_date_idx', fn (Blueprint $table) => $table->index('start_date', 'repairs_start_date_idx'));
        $this->addIndexIfMissing('repairs', 'repairs_end_date_idx', fn (Blueprint $table) => $table->index('end_date', 'repairs_end_date_idx'));

        $this->addIndexIfMissing('repair_requests', 'repair_requests_device_status_idx', fn (Blueprint $table) => $table->index(['device_id', 'status'], 'repair_requests_device_status_idx'));
        $this->addIndexIfMissing('repair_requests', 'repair_requests_responsible_user_idx', fn (Blueprint $table) => $table->index('responsible_user_id', 'repair_requests_responsible_user_idx'));

        $this->addIndexIfMissing('writeoff_requests', 'writeoff_requests_device_status_idx', fn (Blueprint $table) => $table->index(['device_id', 'status'], 'writeoff_requests_device_status_idx'));
        $this->addIndexIfMissing('device_transfers', 'device_transfers_device_status_idx', fn (Blueprint $table) => $table->index(['device_id', 'status'], 'device_transfers_device_status_idx'));
    }

    public function down(): void
    {
        $this->dropIndexIfExists('repairs', 'repairs_device_status_idx');
        $this->dropIndexIfExists('repairs', 'repairs_status_idx');
        $this->dropIndexIfExists('repairs', 'repairs_priority_idx');
        $this->dropIndexIfExists('repairs', 'repairs_repair_type_idx');
        $this->dropIndexIfExists('repairs', 'repairs_accepted_by_status_idx');
        $this->dropIndexIfExists('repairs', 'repairs_issue_reported_by_idx');
        $this->dropIndexIfExists('repairs', 'repairs_request_id_idx');
        $this->dropIndexIfExists('repairs', 'repairs_start_date_idx');
        $this->dropIndexIfExists('repairs', 'repairs_end_date_idx');

        $this->dropIndexIfExists('repair_requests', 'repair_requests_device_status_idx');
        $this->dropIndexIfExists('repair_requests', 'repair_requests_responsible_user_idx');

        $this->dropIndexIfExists('writeoff_requests', 'writeoff_requests_device_status_idx');
        $this->dropIndexIfExists('device_transfers', 'device_transfers_device_status_idx');
    }

    private function addIndexIfMissing(string $tableName, string $indexName, callable $definition): void
    {
        if (! Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! $this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => (int) (DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                [$tableName, $indexName]
            )->aggregate ?? 0) > 0,
            'pgsql' => (int) (DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                [$tableName, $indexName]
            )->aggregate ?? 0) > 0,
            'sqlite' => collect(DB::select("PRAGMA index_list('{$tableName}')"))
                ->contains(fn (object $index) => ($index->name ?? null) === $indexName),
            default => false,
        };
    }
};
