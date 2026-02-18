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
    Schema::create('device_sets', function (Blueprint $table) {
        $table->id();

        $table->string('set_name', 100);
        $table->string('set_code', 50)->unique();     // например KIT-0001
        $table->enum('status', ['draft', 'active', 'returned', 'archived'])
            ->default('draft');

        $table->foreignId('room_id')
            ->nullable()
            ->constrained('rooms')
            ->nullOnDelete();

        $table->string('assigned_to', 100)->nullable(); // кому выдан набор (может быть имя/отдел)

        $table->text('notes')->nullable();

        $table->foreignId('created_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_sets');
    }
};
