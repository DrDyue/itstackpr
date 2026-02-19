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
    Schema::create('audit_log', function (Blueprint $table) {
        $table->id();

        // в документе есть timestamp (не created_at/updated_at)
        $table->timestamp('timestamp')->useCurrent()->nullable();

        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // enum как в документе
        $table->enum('action', [
            'CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'BACKUP', 'RESTORE', 'VIEW'
        ]);

        $table->string('entity_type', 50);
        $table->unsignedBigInteger('entity_id')->nullable();

        $table->text('description');

        // severity как в документе
        $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
