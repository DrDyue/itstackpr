<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_log')) {
            return;
        }

        $this->addIndexIfMissing('audit_log', 'audit_log_timestamp_id_idx', fn (Blueprint $table) => $table->index(['timestamp', 'id'], 'audit_log_timestamp_id_idx'));
        $this->addIndexIfMissing('audit_log', 'audit_log_action_idx', fn (Blueprint $table) => $table->index('action', 'audit_log_action_idx'));
        $this->addIndexIfMissing('audit_log', 'audit_log_entity_type_idx', fn (Blueprint $table) => $table->index('entity_type', 'audit_log_entity_type_idx'));
        $this->addIndexIfMissing('audit_log', 'audit_log_user_id_idx', fn (Blueprint $table) => $table->index('user_id', 'audit_log_user_id_idx'));
        $this->addIndexIfMissing('audit_log', 'audit_log_severity_idx', fn (Blueprint $table) => $table->index('severity', 'audit_log_severity_idx'));
        $this->addIndexIfMissing('audit_log', 'audit_log_entity_lookup_idx', fn (Blueprint $table) => $table->index(['entity_type', 'entity_id'], 'audit_log_entity_lookup_idx'));
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_log')) {
            return;
        }

        Schema::table('audit_log', function (Blueprint $table) {
            foreach ([
                'audit_log_timestamp_id_idx',
                'audit_log_action_idx',
                'audit_log_entity_type_idx',
                'audit_log_user_id_idx',
                'audit_log_severity_idx',
                'audit_log_entity_lookup_idx',
            ] as $indexName) {
                if ($this->hasIndex('audit_log', $indexName)) {
                    $table->dropIndex($indexName);
                }
            }
        });
    }

    private function addIndexIfMissing(string $tableName, string $indexName, callable $definition): void
    {
        if ($this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
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
