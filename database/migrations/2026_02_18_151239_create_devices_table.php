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
    Schema::create('devices', function (Blueprint $table) {
        $table->id();

        $table->string('code', 50)->unique();
        $table->string('name', 100);

        $table->foreignId('device_type_id')
            ->constrained('device_types')
            ->cascadeOnDelete();

        $table->string('model', 100)->nullable();

        $table->enum('status', [
        'active',
        'in_repair',
        'retired',
        'reserved'
        ])->default('active');

        $table->foreignId('building_id')
            ->nullable()
            ->constrained('buildings')
            ->nullOnDelete();

        $table->foreignId('room_id')
            ->nullable()
            ->constrained('rooms')
            ->nullOnDelete();

        $table->unsignedBigInteger('assigned_to')->nullable();

        $table->date('purchase_date')->nullable();
        $table->decimal('purchase_price', 10, 2)->nullable();
        $table->date('warranty_until')->nullable();

        $table->string('warranty_photo_name')->nullable();
        $table->string('serial_number', 100)->nullable();
        $table->string('manufacturer', 100)->nullable();

        $table->text('notes')->nullable();
        $table->string('device_image_url')->nullable();

        $table->unsignedBigInteger('created_by')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
