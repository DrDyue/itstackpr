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

            $table->string('code', 20)->nullable()->unique();
            $table->string('name', 200);

            $table->foreignId('device_type_id')
                ->constrained('device_types')
                ->restrictOnDelete();

            $table->string('model', 100);
            $table->enum('status_id', ['active', 'reserve', 'broken', 'repair', 'retired', 'kitting'])
                ->default('active');

            $table->foreignId('building_id')
                ->nullable()
                ->constrained('buildings')
                ->nullOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            $table->string('assigned_to', 100)->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('warranty_until')->nullable();
            $table->string('warranty_photo_name', 50)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('device_image_url', 500)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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
