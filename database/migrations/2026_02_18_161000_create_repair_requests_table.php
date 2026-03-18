<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();
            $table->foreignId('responsible_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('description');
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('repair_id')
                ->nullable()
                ->unique()
                ->constrained('repairs')
                ->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
