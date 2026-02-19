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
            $table->unsignedTinyInteger('expected_lifetime_years')->nullable(); // 0..255

            // timestamps не делаем, раз у тебя их нигде не используешь (можно добавить, но не обязательно)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_types');
    }
};
