<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('devices') && Schema::hasColumn('devices', 'warranty_photo_name')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->dropColumn('warranty_photo_name');
            });
        }

        if (Schema::hasTable('device_types') && Schema::hasColumn('device_types', 'expected_lifetime_years')) {
            Schema::table('device_types', function (Blueprint $table) {
                $table->dropColumn('expected_lifetime_years');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('devices') && ! Schema::hasColumn('devices', 'warranty_photo_name')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->string('warranty_photo_name', 100)->nullable()->after('warranty_until');
            });
        }

        if (Schema::hasTable('device_types') && ! Schema::hasColumn('device_types', 'expected_lifetime_years')) {
            Schema::table('device_types', function (Blueprint $table) {
                $table->integer('expected_lifetime_years')->nullable()->after('description');
            });
        }
    }
};
