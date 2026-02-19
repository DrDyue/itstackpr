<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();

            $table->string('type_name', 30)->unique();
            $table->string('category', 50);
            $table->string('icon_name', 50)->nullable();
            $table->text('description')->nullable();
            $table->integer('expected_lifetime_years')->nullable()->default(5);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_types');
    }
};
