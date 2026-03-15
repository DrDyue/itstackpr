<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('disk', 50)->default('local');
            $table->string('file_path')->unique();
            $table->string('format', 20)->default('json');
            $table->string('database_connection', 50);
            $table->string('database_driver', 20);
            $table->string('database_name')->nullable();
            $table->string('trigger_type', 20)->default('manual');
            $table->string('creator_type', 20)->default('user');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('total_tables')->default(0);
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->boolean('is_current')->default(false);
            $table->unsignedInteger('restore_count')->default(0);
            $table->timestamp('last_restored_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('frequency', 20)->default('daily');
            $table->time('run_at')->default('02:00:00');
            $table->unsignedTinyInteger('weekly_day')->default(1);
            $table->unsignedTinyInteger('monthly_day')->default(1);
            $table->timestamp('last_scheduled_backup_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
        Schema::dropIfExists('database_backups');
    }
};
