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
    Schema::create('device_set_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('device_set_id')
            ->constrained('device_sets')
            ->cascadeOnDelete();

        $table->foreignId('device_id')
            ->constrained('devices')
            ->restrictOnDelete();

        $table->integer('quantity')->default(1);

        $table->timestamp('created_at')->useCurrent();

        // чтобы одно и то же устройство не было два раза в одном наборе
        $table->unique(['device_set_id', 'device_id']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_set_items');
    }
};
