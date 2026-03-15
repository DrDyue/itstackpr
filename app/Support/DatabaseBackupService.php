<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    private const FORMAT_VERSION = 1;

    private const CATALOG_FILE = 'meta/catalog.json';

    private const SETTINGS_FILE = 'meta/settings.json';

    private const EXCLUDED_TABLES = [
        'database_backups',
        'backup_settings',
    ];

    public function createBackup(?User $user = null, string $trigger = 'manual'): Fluent
    {
        $startedAt = microtime(true);
        $snapshot = $this->buildSnapshot($trigger, $user?->id);
        $payload = json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $path = $this->generatePath($trigger, 'json');
        $disk = $this->disk();

        $this->ensureDirectoryFor($path);
        Storage::disk($disk)->put($path, $payload);

        $backup = $this->upsertBackupRecord([
            'id' => $this->generateBackupId(),
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
            'created_by_name' => $this->creatorName($user, $trigger),
            'file_size_bytes' => (int) Storage::disk($disk)->size($path),
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
            'total_tables' => (int) ($snapshot['meta']['table_count'] ?? 0),
            'total_rows' => (int) ($snapshot['meta']['row_count'] ?? 0),
            'is_current' => false,
            'restore_count' => 0,
            'last_restored_at' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
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

    public function registerUploadedBackup(UploadedFile $file, ?User $user = null): Fluent
    {
        $startedAt = microtime(true);
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Augshupladeto failu neizdevas nolasit.');
        }

        $snapshot = $this->decodeSnapshot($contents);
        $path = $this->generatePath('uploaded', $file->getClientOriginalExtension() ?: 'json');
        $disk = $this->disk();

        $this->ensureDirectoryFor($path);
        Storage::disk($disk)->put($path, $contents);

        $meta = $snapshot['meta'] ?? [];
        $backup = $this->upsertBackupRecord([
            'id' => $this->generateBackupId(),
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
            'created_by_name' => $this->creatorName($user, 'uploaded'),
            'file_size_bytes' => (int) Storage::disk($disk)->size($path),
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
            'total_tables' => count($this->restorableTables(collect($snapshot['tables'] ?? [])->all())),
            'total_rows' => $this->tableRowCount(collect($snapshot['tables'] ?? [])->all()),
            'is_current' => false,
            'restore_count' => 0,
            'last_restored_at' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
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

    public function restoreBackup(string|Fluent $backup, ?User $user = null): Fluent
    {
        $record = $this->resolveBackup($backup);
        $snapshot = $this->readBackupFile($record);
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
        $record = $this->markBackupAsCurrent((string) $record->id);

        if (! $this->usingInMemorySqlite()) {
            DB::purge(DB::getDefaultConnection());
            DB::reconnect(DB::getDefaultConnection());
        }

        AuditTrail::write(
            $user?->id,
            'RESTORE',
            'DatabaseBackup',
            (string) $record->id,
            'Database restored from backup: ' . $record->name,
            'warning'
        );

        return $record;
    }

    public function deleteBackup(string|Fluent $backup): void
    {
        $record = $this->resolveBackup($backup);

        if ($record->is_current) {
            throw new RuntimeException('Aktivo atjaunoto kopiju dzest nedrikst.');
        }

        Storage::disk((string) $record->disk)->delete((string) $record->file_path);
        $payload = $this->readCatalogPayload();
        $payload['backups'] = collect($payload['backups'] ?? [])
            ->reject(fn (array $item) => (string) ($item['id'] ?? '') === (string) $record->id)
            ->values()
            ->all();
        $this->writeCatalogPayload($payload);
    }

    public function getSettings(): Fluent
    {
        $payload = $this->readSettingsPayload();

        return $this->toSettingsRecord($payload);
    }

    public function updateSettings(array $attributes): Fluent
    {
        $payload = array_merge($this->defaultSettingsData(), $this->readSettingsPayload(), $attributes);
        $payload['updated_at'] = now()->toIso8601String();

        if (! array_key_exists('created_at', $payload) || ! $payload['created_at']) {
            $payload['created_at'] = now()->toIso8601String();
        }

        $this->writeSettingsPayload($payload);

        return $this->toSettingsRecord($payload);
    }

    public function markScheduledRun(CarbonImmutable $scheduledAt): Fluent
    {
        return $this->updateSettings([
            'last_scheduled_backup_at' => $scheduledAt->toIso8601String(),
        ]);
    }

    public function allBackups(): Collection
    {
        return collect($this->readCatalogPayload()['backups'] ?? [])
            ->map(fn (array $record) => $this->toBackupRecord($record))
            ->sortByDesc(fn (Fluent $record) => optional($record->created_at)?->getTimestamp() ?? 0)
            ->values();
    }

    public function paginateBackups(int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage('page');
        $items = $this->allBackups();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function findBackup(string $id): ?Fluent
    {
        return $this->allBackups()->first(fn (Fluent $record) => (string) $record->id === $id);
    }

    public function nextRunAt(Fluent $settings, ?CarbonImmutable $from = null): ?CarbonImmutable
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

    public function scheduledMomentForReference(Fluent $settings, CarbonImmutable $reference): CarbonImmutable
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

    public function dueScheduledRun(Fluent $settings, ?CarbonImmutable $now = null): ?CarbonImmutable
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

    private function readBackupFile(Fluent $backup): array
    {
        if (! Storage::disk((string) $backup->disk)->exists((string) $backup->file_path)) {
            throw new RuntimeException('Rezerves kopijas fails serveri vairs nav atrodams.');
        }

        return $this->decodeSnapshot(Storage::disk((string) $backup->disk)->get((string) $backup->file_path));
    }

    private function decodeSnapshot(string $contents): array
    {
        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
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
        })->map(function ($row) {
            $values = array_values((array) $row);

            return (string) ($values[0] ?? '');
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

    private function generateBackupId(): string
    {
        return now()->format('YmdHis') . '-' . Str::lower(Str::random(8));
    }

    private function creatorName(?User $user, string $trigger): string
    {
        if ($trigger === 'scheduled') {
            return 'Sistema';
        }

        return trim((string) ($user?->employee?->full_name ?? 'Manuali'));
    }

    private function resolveBackup(string|Fluent $backup): Fluent
    {
        if ($backup instanceof Fluent) {
            return $backup;
        }

        $record = $this->findBackup($backup);

        if (! $record) {
            throw new RuntimeException('Rezerves kopija nav atrasta.');
        }

        return $record;
    }

    private function markBackupAsCurrent(string $backupId): Fluent
    {
        $payload = $this->readCatalogPayload();
        $updatedRecord = null;

        $payload['backups'] = collect($payload['backups'] ?? [])
            ->map(function (array $record) use ($backupId, &$updatedRecord) {
                $isCurrent = (string) ($record['id'] ?? '') === $backupId;
                $record['is_current'] = $isCurrent;
                $record['updated_at'] = now()->toIso8601String();

                if ($isCurrent) {
                    $record['restore_count'] = (int) ($record['restore_count'] ?? 0) + 1;
                    $record['last_restored_at'] = now()->toIso8601String();
                    $updatedRecord = $record;
                }

                return $record;
            })
            ->all();

        $this->writeCatalogPayload($payload);

        if (! $updatedRecord) {
            throw new RuntimeException('Rezerves kopiju neizdevas atzimet ka aktivu.');
        }

        return $this->toBackupRecord($updatedRecord);
    }

    private function upsertBackupRecord(array $record): Fluent
    {
        $payload = $this->readCatalogPayload();
        $existing = false;

        $payload['backups'] = collect($payload['backups'] ?? [])
            ->map(function (array $item) use ($record, &$existing) {
                if ((string) ($item['id'] ?? '') !== (string) $record['id']) {
                    return $item;
                }

                $existing = true;

                return array_merge($item, $record);
            })
            ->when(! $existing, fn (Collection $items) => $items->push($record))
            ->values()
            ->all();

        $this->writeCatalogPayload($payload);

        return $this->toBackupRecord($record);
    }

    private function readCatalogPayload(): array
    {
        return $this->readJsonFile(self::CATALOG_FILE, [
            'version' => self::FORMAT_VERSION,
            'backups' => [],
        ]);
    }

    private function writeCatalogPayload(array $payload): void
    {
        $payload['version'] = self::FORMAT_VERSION;
        $payload['backups'] = array_values($payload['backups'] ?? []);
        $this->writeJsonFile(self::CATALOG_FILE, $payload);
    }

    private function readSettingsPayload(): array
    {
        return array_merge(
            $this->defaultSettingsData(),
            $this->readJsonFile(self::SETTINGS_FILE, $this->defaultSettingsData())
        );
    }

    private function writeSettingsPayload(array $payload): void
    {
        $this->writeJsonFile(self::SETTINGS_FILE, array_merge($this->defaultSettingsData(), $payload));
    }

    private function readJsonFile(string $path, array $default): array
    {
        if (! Storage::disk($this->disk())->exists($path)) {
            return $default;
        }

        try {
            $data = json_decode((string) Storage::disk($this->disk())->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $default;
        }

        return is_array($data) ? $data : $default;
    }

    private function writeJsonFile(string $path, array $payload): void
    {
        $this->ensureDirectoryFor($path);
        Storage::disk($this->disk())->put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }

    private function ensureDirectoryFor(string $path): void
    {
        $directory = trim(dirname($path), '.\\/');

        if ($directory !== '') {
            Storage::disk($this->disk())->makeDirectory($directory);
        }
    }

    private function defaultSettingsData(): array
    {
        return [
            'enabled' => true,
            'frequency' => 'daily',
            'run_at' => '02:00:00',
            'weekly_day' => 1,
            'monthly_day' => 1,
            'last_scheduled_backup_at' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function toBackupRecord(array $record): Fluent
    {
        return new Fluent([
            'id' => (string) ($record['id'] ?? ''),
            'name' => (string) ($record['name'] ?? ''),
            'disk' => (string) ($record['disk'] ?? $this->disk()),
            'file_path' => (string) ($record['file_path'] ?? ''),
            'format' => (string) ($record['format'] ?? 'json'),
            'database_connection' => (string) ($record['database_connection'] ?? ''),
            'database_driver' => (string) ($record['database_driver'] ?? ''),
            'database_name' => (string) ($record['database_name'] ?? ''),
            'trigger_type' => (string) ($record['trigger_type'] ?? 'manual'),
            'creator_type' => (string) ($record['creator_type'] ?? 'user'),
            'created_by_user_id' => $record['created_by_user_id'] ?? null,
            'created_by_name' => (string) ($record['created_by_name'] ?? ''),
            'file_size_bytes' => (int) ($record['file_size_bytes'] ?? 0),
            'duration_ms' => (int) ($record['duration_ms'] ?? 0),
            'total_tables' => (int) ($record['total_tables'] ?? 0),
            'total_rows' => (int) ($record['total_rows'] ?? 0),
            'is_current' => (bool) ($record['is_current'] ?? false),
            'restore_count' => (int) ($record['restore_count'] ?? 0),
            'last_restored_at' => filled($record['last_restored_at'] ?? null) ? CarbonImmutable::parse((string) $record['last_restored_at']) : null,
            'created_at' => filled($record['created_at'] ?? null) ? CarbonImmutable::parse((string) $record['created_at']) : null,
            'updated_at' => filled($record['updated_at'] ?? null) ? CarbonImmutable::parse((string) $record['updated_at']) : null,
        ]);
    }

    private function toSettingsRecord(array $record): Fluent
    {
        return new Fluent([
            'enabled' => (bool) ($record['enabled'] ?? true),
            'frequency' => (string) ($record['frequency'] ?? 'daily'),
            'run_at' => (string) ($record['run_at'] ?? '02:00:00'),
            'weekly_day' => (int) ($record['weekly_day'] ?? 1),
            'monthly_day' => (int) ($record['monthly_day'] ?? 1),
            'last_scheduled_backup_at' => filled($record['last_scheduled_backup_at'] ?? null) ? CarbonImmutable::parse((string) $record['last_scheduled_backup_at']) : null,
            'created_at' => filled($record['created_at'] ?? null) ? CarbonImmutable::parse((string) $record['created_at']) : null,
            'updated_at' => filled($record['updated_at'] ?? null) ? CarbonImmutable::parse((string) $record['updated_at']) : null,
        ]);
    }
}
