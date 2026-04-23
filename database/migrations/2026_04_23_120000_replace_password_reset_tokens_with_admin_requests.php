<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'password_reset_requested_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_reset_requested_at')->nullable();
            });
        }

        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'password_reset_requested_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_reset_requested_at');
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }
};
