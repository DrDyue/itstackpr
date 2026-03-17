<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();
            $table->foreignId('reported_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('accepted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('description');
            $table->enum('status', ['waiting', 'in-progress', 'completed', 'cancelled'])->default('waiting');
            $table->string('device_status_before_repair', 20)->nullable();
            $table->enum('repair_type', ['internal', 'external'])->default('internal');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('estimated_completion')->nullable();
            $table->date('actual_completion')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('vendor_name', 100)->nullable();
            $table->string('vendor_contact', 100)->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
