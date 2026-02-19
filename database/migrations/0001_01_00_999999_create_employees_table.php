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
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('full_name', 100);
        $table->string('email', 100)->unique();
        $table->string('phone', 100)->nullable();
        $table->string('job_title', 100)->nullable();
        $table->boolean('is_active')->nullable();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
