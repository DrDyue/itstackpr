<?php

namespace App\Support;

use App\Models\BackupSetting;
use App\Models\DatabaseBackup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    private const FORMAT_VERSION = 1;

    private const EXCLUDED_TABLES = [
        'database_backups',
        'backup_settings',
    ];

    public function createBackup(?User $user = null, string $trigger = 'manual'): DatabaseBackup
    {
        $startedAt = microtime(true);
        $snapshot = $this->buildSnapshot($trigger, $user?->id);
        $payload = json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $path = $this->generatePath($trigger, 'json');
        $disk = $this->disk();

        Storage::disk($disk)->put($path, $payload);

        $backup = DatabaseBackup::create([
            'name' => $this->defaultName($trigger),
            'disk' => $disk,
            'file_path' => $path,
            'format' => 'json',
            'database_connection' => DB::getDefaultConnection(),
            'database_driver' => $this->connection()->getDriverName(),
            'database_name' => $this->databaseName(),
            'trigger_type' => $trigger,
            'creator_type' => $trigger === 'scheduled' ? 'system' : 'user',
            'created_by_user_id' => $user?->id,
            'file_size_bytes' => (int) Storage::disk($disk)->size($path),
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
            'total_tables' => (int) ($snapshot['meta']['table_count'] ?? 0),
            'total_rows' => (int) ($snapshot['meta']['row_count'] ?? 0),
        ]);

        AuditTrail::write(
            $user?->id,
            'BACKUP',
            'DatabaseBackup',
            (string) $backup->id,
            'Database backup created: ' . $backup->name,
            'info'
        );

        return $backup;
    }

    public function registerUploadedBackup(UploadedFile $file, ?User $user = null): DatabaseBackup
    {
        $startedAt = microtime(true);
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Augshupladeto failu neizdevas nolasit.');
        }

        $snapshot = $this->decodeSnapshot($contents);
        $path = $this->generatePath('uploaded', $file->getClientOriginalExtension() ?: 'json');
        $disk = $this->disk();

        Storage::disk($disk)->put($path, $contents);

        $meta = $snapshot['meta'] ?? [];
        $backup = DatabaseBackup::create([
            'name' => $this->uploadedName($file->getClientOriginalName()),
            'disk' => $disk,
            'file_path' => $path,
            'format' => 'json',
            'database_connection' => DB::getDefaultConnection(),
            'database_driver' => (string) ($meta['driver'] ?? $this->connection()->getDriverName()),
            'database_name' => (string) ($meta['database'] ?? $this->databaseName()),
            'trigger_type' => 'uploaded',
            'creator_type' => 'user',
            'created_by_user_id' => $user?->id,
            'file_size_bytes' => (int) Storage::disk($disk)->size($path),
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
            'total_tables' => count($this->restorableTables(collect($snapshot['tables'] ?? [])->all())),
            'total_rows' => $this->tableRowCount(collect($snapshot['tables'] ?? [])->all()),
        ]);

        AuditTrail::write(
            $user?->id,
            'BACKUP',
            'DatabaseBackup',
            (string) $backup->id,
            'Backup file uploaded from computer: ' . $backup->name,
            'info'
        );

        return $backup;
    }

    public function restoreBackup(DatabaseBackup $backup, ?User $user = null): void
    {
        $snapshot = $this->readBackupFile($backup);
        $driver = (string) ($snapshot['meta']['driver'] ?? '');
        $currentDriver = $this->connection()->getDriverName();

        if ($driver !== '' && $driver !== $currentDriver) {
            throw new RuntimeException('Rezerves kopija ir veidota citam datubazes draiverim un nav drosi atjaunojama saja vide.');
        }

        $tables = $this->restorableTables($snapshot['tables'] ?? []);
        if ($tables === []) {
            throw new RuntimeException('Rezerves kopija nesatur atjaunojamas tabulas.');
        }

        $this->applySnapshot($tables);

        DatabaseBackup::query()->where('is_current', true)->update(['is_current' => false]);
        $backup->forceFill([
            'is_current' => true,
            'restore_count' => $backup->restore_count + 1,
            'last_restored_at' => now(),
        ])->save();

        if (! $this->usingInMemorySqlite()) {
            DB::purge(DB::getDefaultConnection());
            DB::reconnect(DB::getDefaultConnection());
        }

        AuditTrail::write(
            $user?->id,
            'RESTORE',
            'DatabaseBackup',
            (string) $backup->id,
            'Database restored from backup: ' . $backup->name,
            'warning'
        );
    }

    public function deleteBackup(DatabaseBackup $backup): void
    {
        if ($backup->is_current) {
            throw new RuntimeException('Aktivo atjaunoto kopiju dzest nedrikst.');
        }

        Storage::disk($backup->disk)->delete($backup->file_path);
        $backup->delete();
    }

    public function nextRunAt(BackupSetting $settings, ?CarbonImmutable $from = null): ?CarbonImmutable
    {
        if (! $settings->enabled) {
            return null;
        }

        $reference = $from ?? CarbonImmutable::now();
        $candidate = $this->scheduledMomentForReference($settings, $reference);

        if ($candidate->greaterThan($reference)) {
            return $candidate;
        }

        return match ($settings->frequency) {
            'weekly' => $this->scheduledMomentForReference($settings, $reference->addWeek()),
            'monthly' => $this->scheduledMomentForReference($settings, $reference->addMonthNoOverflow()),
            default => $this->scheduledMomentForReference($settings, $reference->addDay()),
        };
    }

    public function scheduledMomentForReference(BackupSetting $settings, CarbonImmutable $reference): CarbonImmutable
    {
        $time = $this->normalizeRunAt($settings->run_at);

        return match ($settings->frequency) {
            'weekly' => $reference
                ->startOfWeek()
                ->addDays(max(1, min(7, (int) $settings->weekly_day)) - 1)
                ->setTimeFromTimeString($time),
            'monthly' => $reference
                ->startOfMonth()
                ->addDays(min(max(1, (int) $settings->monthly_day), $reference->daysInMonth) - 1)
                ->setTimeFromTimeString($time),
            default => $reference->startOfDay()->setTimeFromTimeString($time),
        };
    }

    public function dueScheduledRun(BackupSetting $settings, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        if (! $settings->enabled) {
            return null;
        }

        $current = $now ?? CarbonImmutable::now();
        $scheduledAt = $this->scheduledMomentForReference($settings, $current);

        if ($current->lt($scheduledAt)) {
            return null;
        }

        $lastRun = $settings->last_scheduled_backup_at
            ? CarbonImmutable::instance($settings->last_scheduled_backup_at)
            : null;

        if ($lastRun && $lastRun->gte($scheduledAt)) {
            return null;
        }

        return $scheduledAt;
    }

    private function applySnapshot(array $tables): void
    {
        $driver = $this->connection()->getDriverName();
        $currentTables = $this->listTables()
            ->reject(fn (string $table) => in_array($table, self::EXCLUDED_TABLES, true))
            ->values();

        $this->disableForeignKeyChecks($driver);

        try {
            foreach ($currentTables as $table) {
                DB::statement($this->dropStatement($table, $driver));
            }

            foreach ($tables as $table) {
                $name = (string) ($table['name'] ?? '');
                $createSql = trim((string) ($table['create_sql'] ?? ''));

                if ($name === '' || $createSql === '') {
                    throw new RuntimeException('Rezerves kopijas struktura ir bojata.');
                }

                DB::unprepared($createSql);
                $this->insertRows($name, $table['rows'] ?? []);
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Datubazes atjaunosana neizdevas: ' . $exception->getMessage(), 0, $exception);
        } finally {
            $this->enableForeignKeyChecks($driver);
        }
    }

    private function insertRows(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            $normalized = array_map(function ($row) {
                if (! is_array($row)) {
                    throw new RuntimeException('Rezerves kopija satur nederigu rindas strukturu.');
                }

                return $row;
            }, $chunk);

            if ($normalized !== []) {
                DB::table($table)->insert($normalized);
            }
        }
    }

    private function buildSnapshot(string $trigger, ?int $userId): array
    {
        $tables = $this->listTables()
            ->reject(fn (string $table) => in_array($table, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();

        $exportedTables = [];
        $rowCount = 0;

        foreach ($tables as $table) {
            $rows = $this->tableRows($table);
            $rowCount += count($rows);

            $exportedTables[] = [
                'name' => $table,
                'create_sql' => $this->createTableSql($table),
                'row_count' => count($rows),
                'rows' => $rows,
            ];
        }

        return [
            'meta' => [
                'format_version' => self::FORMAT_VERSION,
                'created_at' => now()->toIso8601String(),
                'trigger' => $trigger,
                'created_by_user_id' => $userId,
                'connection' => DB::getDefaultConnection(),
                'driver' => $this->connection()->getDriverName(),
                'database' => $this->databaseName(),
                'table_count' => count($exportedTables),
                'row_count' => $rowCount,
            ],
            'tables' => $exportedTables,
        ];
    }

    private function readBackupFile(DatabaseBackup $backup): array
    {
        if (! Storage::disk($backup->disk)->exists($backup->file_path)) {
            throw new RuntimeException('Rezerves kopijas fails serveri vairs nav atrodams.');
        }

        return $this->decodeSnapshot(Storage::disk($backup->disk)->get($backup->file_path));
    }

    private function decodeSnapshot(string $contents): array
    {
        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('Fails nav deriga rezerves kopija.');
        }

        if (! is_array($payload) || ! isset($payload['meta'], $payload['tables']) || ! is_array($payload['tables'])) {
            throw new RuntimeException('Fails nav deriga rezerves kopijas struktura.');
        }

        return $payload;
    }

    private function listTables(): Collection
    {
        $driver = $this->connection()->getDriverName();

        return collect(match ($driver) {
            'sqlite' => DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"),
            default => DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"),
        })->map(function ($row) use ($driver) {
            $values = array_values((array) $row);

            return (string) ($driver === 'sqlite' ? ($values[0] ?? '') : ($values[0] ?? ''));
        })->filter()->values();
    }

    private function createTableSql(string $table): string
    {
        return match ($this->connection()->getDriverName()) {
            'sqlite' => (string) DB::table('sqlite_master')
                ->where('type', 'table')
                ->where('name', $table)
                ->value('sql'),
            default => $this->mysqlCreateTableSql($table),
        };
    }

    private function mysqlCreateTableSql(string $table): string
    {
        $escaped = str_replace('`', '``', $table);
        $result = DB::select("SHOW CREATE TABLE `{$escaped}`");
        $payload = (array) ($result[0] ?? []);

        return (string) ($payload['Create Table'] ?? array_values($payload)[1] ?? '');
    }

    private function tableRows(string $table): array
    {
        return DB::table($table)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function restorableTables(array $tables): array
    {
        return collect($tables)
            ->filter(fn ($table) => is_array($table) && ! in_array((string) ($table['name'] ?? ''), self::EXCLUDED_TABLES, true))
            ->values()
            ->all();
    }

    private function tableRowCount(array $tables): int
    {
        return collect($this->restorableTables($tables))
            ->sum(fn ($table) => (int) ($table['row_count'] ?? count($table['rows'] ?? [])));
    }

    private function disableForeignKeyChecks(string $driver): void
    {
        match ($driver) {
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            default => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
        };
    }

    private function enableForeignKeyChecks(string $driver): void
    {
        match ($driver) {
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
        };
    }

    private function dropStatement(string $table, string $driver): string
    {
        $wrapped = $driver === 'sqlite'
            ? '"' . str_replace('"', '""', $table) . '"'
            : '`' . str_replace('`', '``', $table) . '`';

        return 'DROP TABLE IF EXISTS ' . $wrapped;
    }

    private function generatePath(string $prefix, string $extension): string
    {
        $directory = trim((string) config('backups.directory', 'backups'), '/');
        $extension = trim($extension) !== '' ? strtolower($extension) : 'json';

        return sprintf(
            '%s/%s/%s-%s.%s',
            $directory,
            now()->format('Y/m'),
            $prefix,
            now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)),
            $extension
        );
    }

    private function defaultName(string $trigger): string
    {
        $label = match ($trigger) {
            'scheduled' => 'Automatiska kopija',
            'uploaded' => 'Importeta kopija',
            default => 'Manuala kopija',
        };

        return $label . ' ' . now()->format('d.m.Y H:i');
    }

    private function uploadedName(string $originalName): string
    {
        $trimmed = trim($originalName);

        return $trimmed !== ''
            ? 'Importets fails: ' . $trimmed
            : $this->defaultName('uploaded');
    }

    private function elapsedMilliseconds(float $startedAt): int
    {
        return (int) max(1, round((microtime(true) - $startedAt) * 1000));
    }

    private function normalizeRunAt(?string $runAt): string
    {
        $value = trim((string) $runAt);

        if ($value === '') {
            return '02:00:00';
        }

        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private function disk(): string
    {
        return (string) config('backups.disk', 'local');
    }

    private function databaseName(): ?string
    {
        $connection = $this->connection();

        return method_exists($connection, 'getDatabaseName')
            ? $connection->getDatabaseName()
            : null;
    }

    private function connection(): Connection
    {
        return DB::connection();
    }

    private function usingInMemorySqlite(): bool
    {
        return $this->connection()->getDriverName() === 'sqlite'
            && $this->databaseName() === ':memory:';
    }
}
