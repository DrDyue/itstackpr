<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // building notes should allow empty values from forms
        if ($driver === 'sqlite') {
            Schema::table('buildings', function (Blueprint $table) {
                $table->string('notes', 200)->nullable()->change();
            });

            Schema::table('devices', function (Blueprint $table) {
                $table->string('warranty_photo_name', 50)->nullable()->change();
            });
        } else {
            DB::statement("ALTER TABLE buildings MODIFY notes VARCHAR(200) NULL");

            // device warranty photo is optional in forms
            DB::statement("ALTER TABLE devices MODIFY warranty_photo_name VARCHAR(50) NULL");
        }

        // Device set compatibility layer was removed from the final schema.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not revert destructive schema/data alignment automatically.
    }
};
