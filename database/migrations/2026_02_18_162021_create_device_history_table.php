<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('device_history', function (Blueprint $table) {
        $table->id();

        $table->foreignId('device_id')
            ->constrained('devices')
            ->cascadeOnDelete();

        $table->string('action', 50);              // CREATE / UPDATE / MOVE / STATUS_CHANGE ...
        $table->string('field_changed', 100)->nullable();

        $table->text('old_value')->nullable();
        $table->text('new_value')->nullable();

        $table->foreignId('changed_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamp('timestamp')->useCurrent();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_history');
    }
};
