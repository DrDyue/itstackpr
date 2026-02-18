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
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();

        $table->foreignId('building_id')
            ->constrained('buildings')
            ->cascadeOnDelete();

        $table->integer('floor_number');
        $table->string('room_number', 20);
        $table->string('room_name', 100)->nullable();

        // ответственное лицо за кабинет (опционально)
        $table->foreignId('employee_id')
            ->nullable()
            ->constrained('employees')
            ->nullOnDelete();

        $table->string('department', 100)->nullable();
        $table->string('notes', 200)->nullable();

        // чтобы в одном здании не было двух одинаковых номеров кабинета
        $table->unique(['building_id', 'room_number']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
