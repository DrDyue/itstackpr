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

        $this->ensureTimestampColumns('buildings');
        $this->ensureTimestampColumns('rooms');
        $this->ensureTimestampColumns('device_types');
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }

    private function ensureTimestampColumns(string $table): void
    {
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
            ->update(['updated_at' => now()]);
    }
};
