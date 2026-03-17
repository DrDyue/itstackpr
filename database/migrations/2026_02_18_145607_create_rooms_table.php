<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')
                ->constrained('buildings')
                ->cascadeOnDelete();
            $table->integer('floor_number');
            $table->string('room_number', 20);
            $table->string('room_name', 100)->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('department', 100)->nullable();
            $table->string('notes', 200)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique(['building_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
