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

            // Auditam izmantojam vienu laika zīmogu, nevis created_at un updated_at.
            $table->timestamp('timestamp')->useCurrent()->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Darbības glabājam kā string, lai bez sāpēm var pievienot jaunus audita tipus.
            $table->string('action', 50);

            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description');
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
