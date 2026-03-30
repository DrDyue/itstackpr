<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Runtime shēmas un legacy datu izlīdzinātājs.
 *
 * Šī klase ļauj projektam palaisties arī vidēs, kur datubāzes shēma nav
 * pilnībā sinhronizēta ar aktuālo Laravel kodu.
 */
class RuntimeSchemaBootstrapper
{
    private const DEFAULT_WAREHOUSE_ROOM_NAME = 'Noliktava';
    private const DEFAULT_WAREHOUSE_ROOM_NUMBER_PREFIX = 'NOL-';
    private const DEFAULT_BUILDING_NAME = 'Ludzas novada pašvaldība';

    private ?int $fallbackOwnerId = null;
    private bool $resolvedFallbackOwnerId = false;

    /**
     * Pārliecinās, ka visas kritiskās tabulas, kolonnas un pamata dati eksistē.
     */
    public function ensure(): void
    {
        try {
            $this->ensureBuildingsTable();
            $this->ensureRoomsTable();
            $this->ensureDeviceTypesTable();
            $this->ensureDevicesTable();
            $this->ensureRepairsTable();
            $this->ensureRepairRequestsTable();
            $this->ensureWriteoffRequestsTable();
            $this->ensureDeviceTransfersTable();
            $this->ensureAuditLogTable();
            $this->normalizeLegacyData();
        } catch (Throwable $e) {
            Log::error('Runtime schema bootstrap failed: ' . $e->getMessage());
        }
    }

    /**
     * Nodrošina ēku tabulas pamata struktūru.
     */
    private function ensureBuildingsTable(): void
    {
        if (! Schema::hasTable('buildings')) {
            Schema::create('buildings', function (Blueprint $table) {
                $table->id();
                $table->string('building_name', 100)->nullable();
                $table->string('address', 200)->nullable();
                $table->string('city', 100)->nullable();
                $table->integer('total_floors')->nullable();
                $table->string('notes', 200)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('buildings', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('buildings', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    /**
     * Nodrošina telpu tabulu, ko sistēma izmanto arī noliktavas loģikai.
     */
    private function ensureRoomsTable(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('building_id')->nullable();
                $table->integer('floor_number')->nullable();
                $table->string('room_number', 20)->nullable();
                $table->string('room_name', 100)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('department', 100)->nullable();
                $table->string('notes', 200)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('rooms', 'building_id', fn (Blueprint $table) => $table->unsignedBigInteger('building_id')->nullable());
        $this->addColumnIfMissing('rooms', 'floor_number', fn (Blueprint $table) => $table->integer('floor_number')->nullable());
        $this->addColumnIfMissing('rooms', 'room_number', fn (Blueprint $table) => $table->string('room_number', 20)->nullable());
        $this->addColumnIfMissing('rooms', 'room_name', fn (Blueprint $table) => $table->string('room_name', 100)->nullable());
        $this->addColumnIfMissing('rooms', 'user_id', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable());
        $this->addColumnIfMissing('rooms', 'department', fn (Blueprint $table) => $table->string('department', 100)->nullable());
        $this->addColumnIfMissing('rooms', 'notes', fn (Blueprint $table) => $table->string('notes', 200)->nullable());
        $this->addColumnIfMissing('rooms', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('rooms', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    /**
     * Nodrošina ierīču tipu vārdnīcu.
     */
    private function ensureDeviceTypesTable(): void
    {
        if (! Schema::hasTable('device_types')) {
            Schema::create('device_types', function (Blueprint $table) {
                $table->id();
                $table->string('type_name', 30)->nullable();
                $table->string('category', 50)->nullable();
                $table->text('description')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('device_types', 'type_name', fn (Blueprint $table) => $table->string('type_name', 30)->nullable());
        $this->addColumnIfMissing('device_types', 'category', fn (Blueprint $table) => $table->string('category', 50)->nullable());
        $this->addColumnIfMissing('device_types', 'description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addColumnIfMissing('device_types', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('device_types', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    /**
     * Nodrošina ierīču tabulu ar visiem biznesa loģikai vajadzīgajiem laukiem.
     */
    private function ensureDevicesTable(): void
    {
        if (! Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->nullable();
                $table->string('name', 200)->nullable();
                $table->unsignedBigInteger('device_type_id')->nullable();
                $table->string('model', 100)->nullable();
                $table->string('status', 30)->default(Device::STATUS_ACTIVE);
                $table->unsignedBigInteger('building_id')->nullable();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->unsignedBigInteger('assigned_to_id')->nullable();
                $table->date('purchase_date')->nullable();
                $table->decimal('purchase_price', 10, 2)->nullable();
                $table->date('warranty_until')->nullable();
                $table->string('serial_number', 100)->nullable();
                $table->string('manufacturer', 100)->nullable();
                $table->text('notes')->nullable();
                $table->text('device_image_url')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('devices', 'code', fn (Blueprint $table) => $table->string('code', 20)->nullable());
        $this->addColumnIfMissing('devices', 'name', fn (Blueprint $table) => $table->string('name', 200)->nullable());
        $this->addColumnIfMissing('devices', 'device_type_id', fn (Blueprint $table) => $table->unsignedBigInteger('device_type_id')->nullable());
        $this->addColumnIfMissing('devices', 'model', fn (Blueprint $table) => $table->string('model', 100)->nullable());
        $this->addColumnIfMissing('devices', 'status', fn (Blueprint $table) => $table->string('status', 30)->default(Device::STATUS_ACTIVE));
        $this->addColumnIfMissing('devices', 'building_id', fn (Blueprint $table) => $table->unsignedBigInteger('building_id')->nullable());
        $this->addColumnIfMissing('devices', 'room_id', fn (Blueprint $table) => $table->unsignedBigInteger('room_id')->nullable());
        $this->addColumnIfMissing('devices', 'assigned_to_id', fn (Blueprint $table) => $table->unsignedBigInteger('assigned_to_id')->nullable());
        $this->addColumnIfMissing('devices', 'purchase_date', fn (Blueprint $table) => $table->date('purchase_date')->nullable());
        $this->addColumnIfMissing('devices', 'purchase_price', fn (Blueprint $table) => $table->decimal('purchase_price', 10, 2)->nullable());
        $this->addColumnIfMissing('devices', 'warranty_until', fn (Blueprint $table) => $table->date('warranty_until')->nullable());
        $this->addColumnIfMissing('devices', 'serial_number', fn (Blueprint $table) => $table->string('serial_number', 100)->nullable());
        $this->addColumnIfMissing('devices', 'manufacturer', fn (Blueprint $table) => $table->string('manufacturer', 100)->nullable());
        $this->addColumnIfMissing('devices', 'notes', fn (Blueprint $table) => $table->text('notes')->nullable());
        $this->addColumnIfMissing('devices', 'device_image_url', fn (Blueprint $table) => $table->text('device_image_url')->nullable());
        $this->addColumnIfMissing('devices', 'created_by', fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable());
        $this->addColumnIfMissing('devices', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('devices', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    /**
     * Nodrošina remonta izpildes ierakstu tabulu.
     */
    private function ensureRepairsTable(): void
    {
        if (! Schema::hasTable('repairs')) {
            Schema::create('repairs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('device_id')->nullable();
                $table->unsignedBigInteger('issue_reported_by')->nullable();
                $table->unsignedBigInteger('accepted_by')->nullable();
                $table->text('description')->nullable();
                $table->string('status', 30)->default('waiting');
                $table->string('repair_type', 30)->default('internal');
                $table->string('priority', 30)->default('medium');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('cost', 10, 2)->nullable();
                $table->string('vendor_name', 100)->nullable();
                $table->string('vendor_contact', 100)->nullable();
                $table->string('invoice_number', 50)->nullable();
                $table->unsignedBigInteger('request_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('repairs', 'device_id', fn (Blueprint $table) => $table->unsignedBigInteger('device_id')->nullable());
        $this->addColumnIfMissing('repairs', 'issue_reported_by', fn (Blueprint $table) => $table->unsignedBigInteger('issue_reported_by')->nullable());
        $this->addColumnIfMissing('repairs', 'accepted_by', fn (Blueprint $table) => $table->unsignedBigInteger('accepted_by')->nullable());
        $this->addColumnIfMissing('repairs', 'description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addColumnIfMissing('repairs', 'status', fn (Blueprint $table) => $table->string('status', 30)->default('waiting'));
        $this->addColumnIfMissing('repairs', 'repair_type', fn (Blueprint $table) => $table->string('repair_type', 30)->default('internal'));
        $this->addColumnIfMissing('repairs', 'priority', fn (Blueprint $table) => $table->string('priority', 30)->default('medium'));
        $this->addColumnIfMissing('repairs', 'start_date', fn (Blueprint $table) => $table->date('start_date')->nullable());
        $this->addColumnIfMissing('repairs', 'end_date', fn (Blueprint $table) => $table->date('end_date')->nullable());
        $this->addColumnIfMissing('repairs', 'cost', fn (Blueprint $table) => $table->decimal('cost', 10, 2)->nullable());
        $this->addColumnIfMissing('repairs', 'vendor_name', fn (Blueprint $table) => $table->string('vendor_name', 100)->nullable());
        $this->addColumnIfMissing('repairs', 'vendor_contact', fn (Blueprint $table) => $table->string('vendor_contact', 100)->nullable());
        $this->addColumnIfMissing('repairs', 'invoice_number', fn (Blueprint $table) => $table->string('invoice_number', 50)->nullable());
        $this->addColumnIfMissing('repairs', 'request_id', fn (Blueprint $table) => $table->unsignedBigInteger('request_id')->nullable());
        $this->addColumnIfMissing('repairs', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('repairs', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
        $this->ensureRepairsDateColumnsAllowNull();
    }

    /**
     * Nodrošina remonta pieprasījumu tabulu.
     */
    private function ensureRepairRequestsTable(): void
    {
        if (! Schema::hasTable('repair_requests')) {
            Schema::create('repair_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('device_id')->nullable();
                $table->unsignedBigInteger('responsible_user_id')->nullable();
                $table->text('description')->nullable();
                $table->string('status', 30)->default(RepairRequest::STATUS_SUBMITTED);
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->unsignedBigInteger('repair_id')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('repair_requests', 'device_id', fn (Blueprint $table) => $table->unsignedBigInteger('device_id')->nullable());
        $this->addColumnIfMissing('repair_requests', 'responsible_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('responsible_user_id')->nullable());
        $this->addColumnIfMissing('repair_requests', 'description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addColumnIfMissing('repair_requests', 'status', fn (Blueprint $table) => $table->string('status', 30)->default(RepairRequest::STATUS_SUBMITTED));
        $this->addColumnIfMissing('repair_requests', 'reviewed_by_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by_user_id')->nullable());
        $this->addColumnIfMissing('repair_requests', 'repair_id', fn (Blueprint $table) => $table->unsignedBigInteger('repair_id')->nullable());
        $this->addColumnIfMissing('repair_requests', 'review_notes', fn (Blueprint $table) => $table->text('review_notes')->nullable());
        $this->addColumnIfMissing('repair_requests', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('repair_requests', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    /**
     * Nodrošina norakstīšanas pieprasījumu tabulu.
     */
    private function ensureWriteoffRequestsTable(): void
    {
        if (! Schema::hasTable('writeoff_requests')) {
            Schema::create('writeoff_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('device_id')->nullable();
                $table->unsignedBigInteger('responsible_user_id')->nullable();
                $table->text('reason')->nullable();
                $table->string('status', 30)->default(WriteoffRequest::STATUS_SUBMITTED);
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('writeoff_requests', 'device_id', fn (Blueprint $table) => $table->unsignedBigInteger('device_id')->nullable());
        $this->addColumnIfMissing('writeoff_requests', 'responsible_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('responsible_user_id')->nullable());
        $this->addColumnIfMissing('writeoff_requests', 'reason', fn (Blueprint $table) => $table->text('reason')->nullable());
        $this->addColumnIfMissing('writeoff_requests', 'status', fn (Blueprint $table) => $table->string('status', 30)->default(WriteoffRequest::STATUS_SUBMITTED));
        $this->addColumnIfMissing('writeoff_requests', 'reviewed_by_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by_user_id')->nullable());
        $this->addColumnIfMissing('writeoff_requests', 'review_notes', fn (Blueprint $table) => $table->text('review_notes')->nullable());
        $this->addColumnIfMissing('writeoff_requests', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('writeoff_requests', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    /**
     * Nodrošina ierīču nodošanas pieprasījumu tabulu.
     */
    private function ensureDeviceTransfersTable(): void
    {
        if (! Schema::hasTable('device_transfers')) {
            Schema::create('device_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('device_id')->nullable();
                $table->unsignedBigInteger('responsible_user_id')->nullable();
                $table->unsignedBigInteger('transfered_to_id')->nullable();
                $table->text('transfer_reason')->nullable();
                $table->string('status', 30)->default(DeviceTransfer::STATUS_SUBMITTED);
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->addColumnIfMissing('device_transfers', 'device_id', fn (Blueprint $table) => $table->unsignedBigInteger('device_id')->nullable());
        $this->addColumnIfMissing('device_transfers', 'responsible_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('responsible_user_id')->nullable());
        $this->addColumnIfMissing('device_transfers', 'transfered_to_id', fn (Blueprint $table) => $table->unsignedBigInteger('transfered_to_id')->nullable());
        $this->addColumnIfMissing('device_transfers', 'transfer_reason', fn (Blueprint $table) => $table->text('transfer_reason')->nullable());
        $this->addColumnIfMissing('device_transfers', 'status', fn (Blueprint $table) => $table->string('status', 30)->default(DeviceTransfer::STATUS_SUBMITTED));
        $this->addColumnIfMissing('device_transfers', 'reviewed_by_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by_user_id')->nullable());
        $this->addColumnIfMissing('device_transfers', 'review_notes', fn (Blueprint $table) => $table->text('review_notes')->nullable());
        $this->addColumnIfMissing('device_transfers', 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing('device_transfers', 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function ensureAuditLogTable(): void
    {
        if (! Schema::hasTable('audit_log')) {
            Schema::create('audit_log', function (Blueprint $table) {
                $table->id();
                $table->timestamp('timestamp')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 30)->nullable();
                $table->string('entity_type', 50)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('description')->nullable();
                $table->string('severity', 30)->default('info');
            });
        }

        $this->addColumnIfMissing('audit_log', 'timestamp', fn (Blueprint $table) => $table->timestamp('timestamp')->nullable());
        $this->addColumnIfMissing('audit_log', 'user_id', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable());
        $this->addColumnIfMissing('audit_log', 'action', fn (Blueprint $table) => $table->string('action', 30)->nullable());
        $this->addColumnIfMissing('audit_log', 'entity_type', fn (Blueprint $table) => $table->string('entity_type', 50)->nullable());
        $this->addColumnIfMissing('audit_log', 'entity_id', fn (Blueprint $table) => $table->unsignedBigInteger('entity_id')->nullable());
        $this->addColumnIfMissing('audit_log', 'description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addColumnIfMissing('audit_log', 'severity', fn (Blueprint $table) => $table->string('severity', 30)->default('info'));
    }

    private function normalizeLegacyData(): void
    {
        $this->alignMysqlStatusColumns();
        $this->normalizeDeviceStatuses();
        $this->normalizeRequestStatuses('repair_requests');
        $this->normalizeRequestStatuses('writeoff_requests');
        $this->normalizeRequestStatuses('device_transfers');
        $this->copyLegacyTransferColumn();
        $this->copyLegacyRepairColumns();
        $this->syncDeviceRepairStates();
        $this->backfillLegacyActiveDeviceAssignments();
    }

    private function alignMysqlStatusColumns(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->alignMysqlEnumStatusColumn(
            'devices',
            'status',
            [Device::STATUS_ACTIVE, Device::STATUS_REPAIR, Device::STATUS_WRITEOFF],
            [Device::STATUS_ACTIVE, Device::STATUS_REPAIR, Device::STATUS_WRITEOFF, 'written_off', 'reserve', 'broken', 'kitting'],
            [
                ['from' => ['reserve', 'broken', 'kitting'], 'to' => Device::STATUS_ACTIVE],
                ['from' => ['written_off'], 'to' => Device::STATUS_WRITEOFF],
            ],
            Device::STATUS_ACTIVE,
        );

        foreach (['repair_requests', 'writeoff_requests', 'device_transfers'] as $table) {
            $this->alignMysqlEnumStatusColumn(
                $table,
                'status',
                [RepairRequest::STATUS_SUBMITTED, RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED],
                [RepairRequest::STATUS_SUBMITTED, RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED, 'pending', 'denied', 'indicated'],
                [
                    ['from' => ['pending'], 'to' => RepairRequest::STATUS_SUBMITTED],
                    ['from' => ['denied', 'indicated'], 'to' => RepairRequest::STATUS_REJECTED],
                ],
                RepairRequest::STATUS_SUBMITTED,
            );
        }
    }

    private function alignMysqlEnumStatusColumn(
        string $table,
        string $column,
        array $canonicalValues,
        array $extendedValues,
        array $normalizations,
        string $default
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $columnType = strtolower($this->mysqlColumnType($table, $column));
        if (! str_starts_with($columnType, 'enum(')) {
            return;
        }

        if ($this->enumMatchesExactly($columnType, $canonicalValues)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` ENUM(%s) NOT NULL DEFAULT %s',
            $table,
            $column,
            $this->quoteEnumValues($extendedValues),
            $this->quoteSqlLiteral($default),
        ));

        foreach ($normalizations as $normalization) {
            $fromValues = array_values(array_filter((array) ($normalization['from'] ?? []), fn ($value) => is_string($value) && $value !== ''));
            $toValue = (string) ($normalization['to'] ?? '');

            if ($fromValues === [] || $toValue === '') {
                continue;
            }

            DB::table($table)->whereIn($column, $fromValues)->update([$column => $toValue]);
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` ENUM(%s) NOT NULL DEFAULT %s',
            $table,
            $column,
            $this->quoteEnumValues($canonicalValues),
            $this->quoteSqlLiteral($default),
        ));
    }

    private function mysqlColumnType(string $table, string $column): string
    {
        $result = DB::selectOne(sprintf("SHOW COLUMNS FROM `%s` LIKE '%s'", $table, $column));

        return (string) ($result->Type ?? '');
    }

    private function enumMatchesExactly(string $currentType, array $expectedValues): bool
    {
        $expectedType = 'enum(' . implode(',', array_map(fn (string $value) => $this->quoteSqlLiteral($value), $expectedValues)) . ')';

        return strtolower($currentType) === strtolower($expectedType);
    }

    private function quoteEnumValues(array $values): string
    {
        return implode(',', array_map(fn (string $value) => $this->quoteSqlLiteral($value), $values));
    }

    private function quoteSqlLiteral(string $value): string
    {
        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    private function normalizeDeviceStatuses(): void
    {
        if (! Schema::hasTable('devices') || ! Schema::hasColumn('devices', 'status')) {
            return;
        }

        DB::table('devices')->whereIn('status', ['reserve', 'broken', 'kitting'])->update(['status' => Device::STATUS_ACTIVE]);
        DB::table('devices')->where('status', 'written_off')->update(['status' => Device::STATUS_WRITEOFF]);
    }

    private function normalizeRequestStatuses(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
            return;
        }

        DB::table($table)->where('status', 'pending')->update(['status' => RepairRequest::STATUS_SUBMITTED]);
        DB::table($table)->where('status', 'denied')->update(['status' => RepairRequest::STATUS_REJECTED]);
        DB::table($table)->where('status', 'indicated')->update(['status' => RepairRequest::STATUS_REJECTED]);
    }

    private function copyLegacyTransferColumn(): void
    {
        if (
            ! Schema::hasTable('device_transfers')
            || ! Schema::hasColumn('device_transfers', 'transfer_to_user_id')
            || ! Schema::hasColumn('device_transfers', 'transfered_to_id')
        ) {
            return;
        }

        DB::table('device_transfers')
            ->whereNull('transfered_to_id')
            ->update(['transfered_to_id' => DB::raw('transfer_to_user_id')]);
    }

    private function copyLegacyRepairColumns(): void
    {
        if (Schema::hasTable('repairs')) {
            if (Schema::hasColumn('repairs', 'reported_by_user_id') && Schema::hasColumn('repairs', 'issue_reported_by')) {
                DB::table('repairs')
                    ->whereNull('issue_reported_by')
                    ->update(['issue_reported_by' => DB::raw('reported_by_user_id')]);
            }

            if (Schema::hasColumn('repairs', 'accepted_by_user_id') && Schema::hasColumn('repairs', 'accepted_by')) {
                DB::table('repairs')
                    ->whereNull('accepted_by')
                    ->update(['accepted_by' => DB::raw('accepted_by_user_id')]);
            }

            if (Schema::hasColumn('repairs', 'assigned_to_user_id') && Schema::hasColumn('repairs', 'accepted_by')) {
                DB::table('repairs')
                    ->whereNull('accepted_by')
                    ->update(['accepted_by' => DB::raw('assigned_to_user_id')]);
            }

            if (Schema::hasColumn('repairs', 'actual_completion') && Schema::hasColumn('repairs', 'end_date')) {
                DB::table('repairs')
                    ->whereNull('end_date')
                    ->update(['end_date' => DB::raw('actual_completion')]);
            }
        }
    }

    private function syncDeviceRepairStates(): void
    {
        if (
            ! Schema::hasTable('devices')
            || ! Schema::hasTable('repairs')
            || ! Schema::hasColumn('devices', 'status')
            || ! Schema::hasColumn('repairs', 'device_id')
            || ! Schema::hasColumn('repairs', 'status')
        ) {
            return;
        }

        $activeRepairDeviceIds = DB::table('repairs')
            ->whereIn('status', ['waiting', 'in-progress'])
            ->whereNotNull('device_id')
            ->distinct()
            ->pluck('device_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        $repairDeviceQuery = DB::table('devices')->where('status', Device::STATUS_REPAIR);

        if ($activeRepairDeviceIds->isNotEmpty()) {
            $repairDeviceQuery->whereNotIn('id', $activeRepairDeviceIds->all());
        }

        $repairDeviceUpdates = ['status' => Device::STATUS_ACTIVE];
        if (Schema::hasColumn('devices', 'updated_at')) {
            $repairDeviceUpdates['updated_at'] = now();
        }

        $repairDeviceQuery->update($repairDeviceUpdates);

        if ($activeRepairDeviceIds->isEmpty()) {
            return;
        }

        $activeRepairUpdates = ['status' => Device::STATUS_REPAIR];
        if (Schema::hasColumn('devices', 'updated_at')) {
            $activeRepairUpdates['updated_at'] = now();
        }

        DB::table('devices')
            ->whereIn('id', $activeRepairDeviceIds->all())
            ->where('status', '!=', Device::STATUS_WRITEOFF)
            ->where('status', '!=', Device::STATUS_REPAIR)
            ->update($activeRepairUpdates);
    }

    private function backfillLegacyActiveDeviceAssignments(): void
    {
        if (
            ! Schema::hasTable('devices')
            || ! Schema::hasTable('rooms')
            || ! Schema::hasTable('buildings')
            || ! Schema::hasTable('users')
        ) {
            return;
        }

        Device::query()
            ->with('room')
            ->where('status', '!=', Device::STATUS_WRITEOFF)
            ->where(function (Builder $query) {
                $query->whereNull('room_id')
                    ->orWhereNull('building_id')
                    ->orWhereNull('assigned_to_id')
                    ->orWhereDoesntHave('room');
            })
            ->orderBy('id')
            ->chunkById(100, function ($devices) {
                $warehouseRoom = null;

                foreach ($devices as $device) {
                    $updates = [];
                    $room = $device->room;

                    if (! $room) {
                        $warehouseRoom ??= $this->ensureWarehouseRoom(
                            $this->resolveLegacyOwnerId($device, null)
                        );

                        $room = $warehouseRoom;
                        $updates['room_id'] = $room->id;
                        $updates['building_id'] = $room->building_id;
                    } elseif ((int) ($device->building_id ?? 0) !== (int) ($room->building_id ?? 0)) {
                        $updates['building_id'] = $room->building_id;
                    }

                    if (empty($device->assigned_to_id)) {
                        $ownerId = $this->resolveLegacyOwnerId($device, $room);

                        if ($ownerId !== null) {
                            $updates['assigned_to_id'] = $ownerId;
                        }
                    }

                    if ($updates === []) {
                        continue;
                    }

                    if (Schema::hasColumn('devices', 'updated_at')) {
                        $updates['updated_at'] = now();
                    }

                    DB::table('devices')
                        ->where('id', $device->id)
                        ->update($updates);
                }
            });
    }

    private function resolveLegacyOwnerId(Device $device, ?Room $room): ?int
    {
        foreach ([$device->assigned_to_id, $device->created_by, $room?->user_id] as $candidateId) {
            $resolvedId = $this->resolveExistingUserId($candidateId);

            if ($resolvedId !== null) {
                return $resolvedId;
            }
        }

        return $this->fallbackOwnerId();
    }

    private function resolveExistingUserId(mixed $candidateId): ?int
    {
        if (! is_numeric($candidateId)) {
            return null;
        }

        $userId = (int) $candidateId;

        if ($userId <= 0) {
            return null;
        }

        return User::query()->whereKey($userId)->exists() ? $userId : null;
    }

    private function fallbackOwnerId(): ?int
    {
        if ($this->resolvedFallbackOwnerId) {
            return $this->fallbackOwnerId;
        }

        $this->resolvedFallbackOwnerId = true;
        $this->fallbackOwnerId = User::query()
            ->where('is_active', true)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_IT_WORKER])
            ->orderBy('id')
            ->value('id')
            ?? User::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id')
            ?? User::query()
                ->orderBy('id')
                ->value('id');

        return $this->fallbackOwnerId;
    }

    private function ensureWarehouseRoom(?int $preferredUserId = null): Room
    {
        $warehouseRoom = Room::query()
            ->with('building')
            ->get()
            ->first(function (Room $room) {
                return $this->isWarehouseLabel($room->room_name)
                    || $this->isWarehouseLabel($room->room_number)
                    || $this->isWarehouseLabel($room->notes);
            });

        if ($warehouseRoom) {
            return $warehouseRoom;
        }

        $building = $this->preferredWarehouseBuilding();

        return Room::query()->create([
            'building_id' => $building->id,
            'floor_number' => 1,
            'room_number' => $this->nextWarehouseRoomNumber($building->id),
            'room_name' => self::DEFAULT_WAREHOUSE_ROOM_NAME,
            'user_id' => $preferredUserId,
            'department' => 'Inventārs',
            'notes' => 'Automātiski izveidota noklusētā noliktavas telpa.',
        ])->load('building');
    }

    private function preferredWarehouseBuilding(): Building
    {
        $preferredBuilding = Building::query()
            ->orderBy('building_name')
            ->get()
            ->first(fn (Building $building) => $this->matchesPreferredBuildingName($building->building_name));

        if ($preferredBuilding) {
            return $preferredBuilding;
        }

        $existingBuilding = Building::query()->orderBy('building_name')->first();

        if ($existingBuilding) {
            return $existingBuilding;
        }

        return Building::query()->create([
            'building_name' => self::DEFAULT_BUILDING_NAME,
            'city' => 'Ludza',
            'total_floors' => 1,
            'notes' => 'Automātiski izveidota noklusētā ēka noliktavas telpai.',
        ]);
    }

    private function nextWarehouseRoomNumber(int $buildingId): string
    {
        $existingNumbers = Room::query()
            ->where('building_id', $buildingId)
            ->pluck('room_number')
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->all();

        $sequence = 1;

        do {
            $candidate = self::DEFAULT_WAREHOUSE_ROOM_NUMBER_PREFIX . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (in_array($candidate, $existingNumbers, true));

        return $candidate;
    }

    private function isWarehouseLabel(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'noliktav');
    }

    private function matchesPreferredBuildingName(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(trim($value)), 'ludz');
    }

    private function ensureRepairsDateColumnsAllowNull(): void
    {
        if (! Schema::hasTable('repairs')) {
            return;
        }

        foreach (['start_date', 'end_date'] as $column) {
            if (! Schema::hasColumn('repairs', $column)) {
                continue;
            }

            $columnDefinition = collect(Schema::getColumns('repairs'))
                ->firstWhere('name', $column);

            if (($columnDefinition['nullable'] ?? false) === true) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` DATE NULL',
                'repairs',
                $column,
            ));
        }
    }

    private function addColumnIfMissing(string $tableName, string $column, callable $definition): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
}
