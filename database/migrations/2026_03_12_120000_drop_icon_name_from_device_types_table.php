<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            if (Schema::hasColumn('device_types', 'icon_name')) {
                $table->dropColumn('icon_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            if (! Schema::hasColumn('device_types', 'icon_name')) {
                $table->string('icon_name', 50)->nullable()->after('category');
            }
        });
    }
};
