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

            // FK -> devices (NOT NULL)
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            // description (NOT NULL)
            $table->text('description');

            // status ENUM, NULL allowed, default 'waiting'
            $table->enum('status', ['waiting', 'in-progress', 'completed', 'cancelled'])
                ->nullable()
                ->default('waiting');

            // repair_type ENUM (NOT NULL)
            $table->enum('repair_type', ['internal', 'external']);

            // priority ENUM, NULL allowed, default 'medium'
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                ->nullable()
                ->default('medium');

            // dates
            $table->date('start_date'); // NOT NULL
            $table->date('estimated_completion')->nullable()->default(null);
            $table->date('actual_completion')->nullable()->default(null);

            // cost
            $table->decimal('cost', 10, 2)->nullable()->default(null);

            // vendor fields (only needed when external, but keep nullable as in doc)
            $table->string('vendor_name', 100)->nullable()->default(null);
            $table->string('vendor_contact', 100)->nullable()->default(null);
            $table->string('invoice_number', 50)->nullable()->default(null);

            // issue_reported_by -> users (nullable)
            $table->foreignId('issue_reported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // assigned_to -> users (nullable)
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // created_at только один (как в документе)
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
