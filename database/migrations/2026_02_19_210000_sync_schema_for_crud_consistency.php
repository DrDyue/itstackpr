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
        // building notes should allow empty values from forms
        DB::statement("ALTER TABLE buildings MODIFY notes VARCHAR(200) NULL");

        // device warranty photo is optional in forms
        DB::statement("ALTER TABLE devices MODIFY warranty_photo_name VARCHAR(50) NULL");

        Schema::table('device_sets', function (Blueprint $table) {
            if (! Schema::hasColumn('device_sets', 'set_name')) {
                $table->string('set_name', 100)->nullable()->after('description');
            }
            if (! Schema::hasColumn('device_sets', 'set_code')) {
                $table->string('set_code', 50)->nullable()->unique()->after('set_name');
            }
            if (! Schema::hasColumn('device_sets', 'status')) {
                $table->enum('status', ['draft', 'active', 'returned', 'archived'])
                    ->default('draft')
                    ->after('set_code');
            }
            if (! Schema::hasColumn('device_sets', 'room_id')) {
                $table->foreignId('room_id')->nullable()->after('status')->constrained('rooms')->nullOnDelete();
            }
            if (! Schema::hasColumn('device_sets', 'assigned_to')) {
                $table->string('assigned_to', 100)->nullable()->after('room_id');
            }
            if (! Schema::hasColumn('device_sets', 'notes')) {
                $table->text('notes')->nullable()->after('assigned_to');
            }
            if (! Schema::hasColumn('device_sets', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            }
        });

        // Backfill new fields for existing rows
        DB::statement("UPDATE device_sets SET set_name = name WHERE set_name IS NULL OR set_name = ''");
        DB::statement("UPDATE device_sets SET notes = description WHERE notes IS NULL");
        DB::statement("UPDATE device_sets SET set_code = CONCAT('KIT-', LPAD(id, 5, '0')) WHERE set_code IS NULL OR set_code = ''");

        Schema::table('device_set_items', function (Blueprint $table) {
            if (! Schema::hasColumn('device_set_items', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('device_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not revert destructive schema/data alignment automatically.
    }
};
