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
    Schema::create('buildings', function (Blueprint $table) {
        $table->id();
        $table->string('building_name', 100);
        $table->string('address', 200)->nullable();
        $table->string('city', 100)->nullable();
        $table->integer('total_floors')->nullable();
        $table->string('notes', 200);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
