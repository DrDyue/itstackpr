<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_types')) {
            return;
        }

        Schema::table('device_types', function (Blueprint $table) {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('device_types', 'category') ? 'category' : null,
                Schema::hasColumn('device_types', 'description') ? 'description' : null,
                Schema::hasColumn('device_types', 'icon_name') ? 'icon_name' : null,
                Schema::hasColumn('device_types', 'expected_lifetime_years') ? 'expected_lifetime_years' : null,
                Schema::hasColumn('device_types', 'created_at') ? 'created_at' : null,
                Schema::hasColumn('device_types', 'updated_at') ? 'updated_at' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('device_types')) {
            return;
        }

        Schema::table('device_types', function (Blueprint $table) {
            if (! Schema::hasColumn('device_types', 'category')) {
                $table->string('category', 50)->nullable();
            }

            if (! Schema::hasColumn('device_types', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('device_types', 'icon_name')) {
                $table->string('icon_name', 50)->nullable();
            }

            if (! Schema::hasColumn('device_types', 'expected_lifetime_years')) {
                $table->integer('expected_lifetime_years')->nullable()->default(5);
            }

            if (! Schema::hasColumn('device_types', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('device_types', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }
};
