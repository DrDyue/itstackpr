<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Vienota audita žurnāla rakstīšanas un lokalizēšanas utilītklase.
 */
class AuditTrail
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_BACKUP = 'BACKUP';
    public const ACTION_RESTORE = 'RESTORE';
    public const ACTION_VIEW = 'VIEW';

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Zemākā līmeņa rakstītājs auditam.
     */
    public static function write(
        ?int $userId,
        string $action,
        string $entityType,
        ?string $entityId,
        string $description,
        string $severity = 'info'
    ): void {
        try {
            AuditLog::create([
                'timestamp' => now(),
                'user_id' => $userId,
                'action' => self::normalizeAction($action),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'severity' => self::normalizeSeverity($severity),
            ]);
        } catch (Throwable) {
            // Rezerves kopijām un citām kritiskām darbībām nav jāuzlūzt, ja audits uz brīdi nav pieejams.
        }
    }

    /**
     * Ērts palīgs izveides darbību pierakstam.
     */
    public static function created(?int $userId, Model $model, ?string $description = null, ?string $severity = null): void
    {
        self::writeForModel(
            $userId,
            self::ACTION_CREATE,
            $model,
            $description ?? self::defaultDescription(self::ACTION_CREATE, $model),
            $severity ?? self::severityFor(self::ACTION_CREATE, $model)
        );
    }

    /**
     * Ērts palīgs atjaunošanas darbību pierakstam.
     */
    public static function updated(
        ?int $userId,
        Model $model,
        array $changedFields = [],
        ?string $description = null,
        ?string $severity = null
    ): void {
        self::writeForModel(
            $userId,
            self::ACTION_UPDATE,
            $model,
            $description ?? self::defaultDescription(self::ACTION_UPDATE, $model, $changedFields),
            $severity ?? self::severityFor(self::ACTION_UPDATE, $model)
        );
    }

    /**
     * Aprēķina atšķirības starp veco un jauno stāvokli un saglabā tās auditā.
     */
    public static function updatedFromState(
        ?int $userId,
        Model $model,
        array $before,
        array $after,
        array $ignoredFields = [],
        ?string $description = null,
        ?string $severity = null
    ): void {
        $changes = self::changesFromState($before, $after, $ignoredFields);

        self::updated(
            $userId,
            $model,
            array_keys($changes),
            $description ?? self::detailedUpdateDescription($model, $changes),
            $severity
        );
    }

    /**
     * Ērts palīgs dzēšanas darbību pierakstam.
     */
    public static function deleted(?int $userId, Model $model, ?string $description = null, ?string $severity = null): void
    {
        self::writeForModel(
            $userId,
            self::ACTION_DELETE,
            $model,
            $description ?? self::defaultDescription(self::ACTION_DELETE, $model),
            $severity ?? self::severityFor(self::ACTION_DELETE, $model)
        );
    }

    /**
     * Auditē veiksmīgu pieslēgšanos sistēmai.
     */
    public static function login(?User $user): void
    {
        if (! $user) {
            return;
        }

        self::writeForModel(
            $user->id,
            self::ACTION_LOGIN,
            $user,
            'Lietotājs pieslēdzas: ' . self::labelFor($user),
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē izrakstīšanos no sistēmas.
     */
    public static function logout(?User $user): void
    {
        if (! $user) {
            return;
        }

        self::writeForModel(
            $user->id,
            self::ACTION_LOGOUT,
            $user,
            'Lietotājs izrakstijas: ' . self::labelFor($user),
            self::SEVERITY_INFO
        );
    }

    /**
     * Uzraksta audita ierakstu, balstoties uz Eloquent modeli.
     */
    public static function writeForModel(
        ?int $userId,
        string $action,
        Model $model,
        string $description,
        ?string $severity = null
    ): void {
        self::write(
            $userId,
            $action,
            class_basename($model),
            (string) $model->getKey(),
            $description,
            $severity ?? self::severityFor($action, $model)
        );
    }

    /**
     * Sagatavo cilvēkam salasāmu objekta nosaukumu auditam.
     */
    public static function labelFor(Model $model): string
    {
        return match (class_basename($model)) {
            'Building' => (string) ($model->building_name ?? ('Ēka #' . $model->getKey())),
            'Room' => trim((string) (($model->room_number ?? 'Telpa #' . $model->getKey()) . ' ' . ($model->room_name ?? ''))),
            'DeviceType' => (string) ($model->type_name ?? ('Ierīces tips #' . $model->getKey())),
            'Device' => trim((string) (($model->code ? '[' . $model->code . '] ' : '') . ($model->name ?? ('Ierīce #' . $model->getKey())))),
            'Repair' => trim((string) (($model->device?->name ?? 'Ierīce') . ' | ' . Str::limit((string) ($model->description ?? 'Remonts #' . $model->getKey()), 70))),
            'User' => (string) ($model->full_name ?? ('Lietotājs #' . $model->getKey())),
            'RepairRequest' => trim((string) (($model->device?->name ?? 'Ierīce') . ' | ' . Str::limit((string) ($model->description ?? 'Remonta pieteikums #' . $model->getKey()), 70))),
            'WriteoffRequest' => trim((string) (($model->device?->name ?? 'Ierīce') . ' | ' . Str::limit((string) ($model->reason ?? 'Norakstīšanas pieteikums #' . $model->getKey()), 70))),
            'DeviceTransfer' => trim((string) (($model->device?->name ?? 'Ierīce') . ' | ' . Str::limit((string) ($model->transfer_reason ?? 'Pārsūtīšanas pieteikums #' . $model->getKey()), 70))),
            default => self::entityLabel(class_basename($model)) . ' #' . $model->getKey(),
        };
    }

    /**
     * Lokalizē darbības tipu.
     */
    public static function actionLabel(string $action): string
    {
        return match (strtoupper($action)) {
            self::ACTION_CREATE => 'Izveide',
            self::ACTION_UPDATE => 'Atjaunosana',
            self::ACTION_DELETE => 'Dzēšana',
            self::ACTION_LOGIN => 'Pieslegsanas',
            self::ACTION_LOGOUT => 'Izrakstisanas',
            self::ACTION_EXPORT => 'Eksports',
            self::ACTION_BACKUP => 'Kopija',
            self::ACTION_RESTORE => 'Atjaunosana no kopijas',
            self::ACTION_VIEW => 'Apskate',
            default => $action,
        };
    }

    /**
     * Lokalizē objektu tipu.
     */
    public static function entityLabel(?string $entityType): string
    {
        $key = self::normalizeEntityKey($entityType);

        return match ($key) {
            'building' => 'Ēka',
            'room' => 'Telpa',
            'device_type' => 'Ierīces tips',
            'device' => 'Ierīce',
            'repair' => 'Remonts',
            'user' => 'Lietotājs',
            'repair_request' => 'Remonta pieteikums',
            'writeoff_request' => 'Norakstīšanas pieteikums',
            'device_transfer' => 'Ierīces pārsūtīšana',
            'database_backup' => 'Datubāzes kopija',
            'backup_setting' => 'Kopiju iestatijumi',
            default => Str::headline((string) $entityType),
        };
    }

    /**
     * Lokalizē audita smaguma līmeni.
     */
    public static function severityLabel(string $severity): string
    {
        return match ($severity) {
            self::SEVERITY_WARNING => 'Bridinajums',
            self::SEVERITY_ERROR => 'Kluda',
            self::SEVERITY_CRITICAL => 'Kritisks',
            default => 'Informācija',
        };
    }

    /**
     * Sagatavo īsu objekta atsauci sarakstu skatam.
     */
    public static function entityReference(?string $entityType, ?string $entityId): string
    {
        $label = self::entityLabel($entityType);

        if (! filled($entityId)) {
            return $label;
        }

        return $label . ' #' . $entityId;
    }

    public static function entityUrl(?string $entityType, ?string $entityId): ?string
    {
        if (! filled($entityId)) {
            return null;
        }

        try {
            return match (self::normalizeEntityKey($entityType)) {
                'device' => route('devices.show', $entityId),
                'repair' => route('repairs.edit', $entityId),
                'repair_request' => route('repair-requests.index', ['q' => $entityId]) . '#repair-request-' . $entityId,
                'writeoff_request' => route('writeoff-requests.index', ['q' => $entityId]) . '#writeoff-request-' . $entityId,
                'device_transfer' => route('device-transfers.index', ['q' => $entityId]) . '#device-transfer-' . $entityId,
                'room' => route('rooms.edit', $entityId),
                'building' => route('buildings.edit', $entityId),
                'device_type' => route('device-types.edit', $entityId),
                'user' => route('users.edit', $entityId),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    public static function localizedDescription(?string $description, ?string $entityType = null): string
    {
        $text = trim((string) $description);

        if ($text === '') {
            return 'Nav apraksta';
        }

        if (preg_match('/^User logged in: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotājs pieslēdzas: ' . $matches['label'];
        }

        if (preg_match('/^User logged out: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotājs izrakstijas: ' . $matches['label'];
        }

        if (preg_match('/^User account deleted: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotāja konts dzēsts: ' . $matches['label'];
        }

        if (preg_match('/^User password changed: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotāja parole nomainīta: ' . $matches['label'];
        }

        if (preg_match('/^Database backup created: (?<label>.+)$/i', $text, $matches)) {
            return 'Datubāzes kopija izveidota: ' . $matches['label'];
        }

        if (preg_match('/^Backup file uploaded from computer: (?<label>.+)$/i', $text, $matches)) {
            return 'Kopijas fails augšupielādēts no datora: ' . $matches['label'];
        }

        if (preg_match('/^Database restored from backup: (?<label>.+)$/i', $text, $matches)) {
            return 'Datubāze atjaunota no kopijas: ' . $matches['label'];
        }

        if (preg_match('/^Backup deleted: (?<label>.+)$/i', $text, $matches)) {
            return 'Rezerves kopija dzēsta: ' . $matches['label'];
        }

        if (preg_match('/^Backup schedule updated: (?<frequency>[^ ]+) at (?<time>.+)$/i', $text, $matches)) {
            return 'Kopiju grafiks atjaunināts: '
                . self::translateValue($matches['frequency'])
                . ' plkst. '
                . trim($matches['time']);
        }

        if (preg_match('/^Repair status changed: (?<states>.+)$/i', $text, $matches)) {
            return 'Remonta statuss mainīts: ' . self::translateArrowText($matches['states']);
        }

        if (preg_match('/^Device status changed: (?<label>.+?) \| (?<states>.+)$/i', $text, $matches)) {
            return 'Ierīces statuss mainīts: ' . $matches['label'] . ' | ' . self::translateArrowText($matches['states']);
        }

        if (preg_match('/^Device status synced from repair #(?<repair>\d+): (?<states>.+)$/i', $text, $matches)) {
            return 'Ierīces statuss saskanots no remonta #' . $matches['repair'] . ': ' . self::translateArrowText($matches['states']);
        }

        if (preg_match('/^Device moved: (?<label>.+?) -> room (?<room>.+)$/i', $text, $matches)) {
            return 'Ierīce pārvietota: ' . $matches['label'] . ' -> telpa ' . trim($matches['room']);
        }

        if (preg_match('/^(?<entity>.+?) updated: (?<label>.+?) \| details: (?<details>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' atjaunināts: '
                . $matches['label']
                . ' | detalas: '
                . self::translateDetails($matches['details']);
        }

        if (preg_match('/^(?<entity>.+?) updated: (?<label>.+?) \| fields: (?<fields>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' atjaunināts: '
                . $matches['label']
                . ' | lauki: '
                . self::translateFieldList($matches['fields']);
        }

        if (preg_match('/^(?<entity>.+?) created: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' izveidots: ' . $matches['label'];
        }

        if (preg_match('/^(?<entity>.+?) updated: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' atjaunināts: ' . $matches['label'];
        }

        if (preg_match('/^(?<entity>.+?) deleted: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' dzēsts: ' . $matches['label'];
        }

        if (preg_match('/^(?<entity>.+?) changed: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' mainīts: ' . $matches['label'];
        }

        $fallbackEntity = $entityType ? self::entityLabel($entityType) : null;
        if ($fallbackEntity && ! str_starts_with($text, $fallbackEntity)) {
            return $text;
        }

        return $text;
    }

    private static function defaultDescription(string $action, Model $model, array $changedFields = []): string
    {
        $entity = self::readableEntityName(class_basename($model));
        $label = self::labelFor($model);

        return match ($action) {
            self::ACTION_CREATE => $entity . ' izveidots: ' . $label,
            self::ACTION_UPDATE => $entity . ' atjaunināts: ' . $label . ($changedFields !== [] ? ' | lauki: ' . self::translateFieldList(implode(', ', $changedFields)) : ''),
            self::ACTION_DELETE => $entity . ' dzēsts: ' . $label,
            default => $entity . ' mainīts: ' . $label,
        };
    }

    private static function detailedUpdateDescription(Model $model, array $changes): string
    {
        $entity = self::readableEntityName(class_basename($model));
        $label = self::labelFor($model);

        if ($changes === []) {
            return $entity . ' atjaunināts: ' . $label;
        }

        $details = collect($changes)
            ->map(function (array $change, string $field) {
                return self::translateFieldName($field) . ': '
                    . self::formatValue($change['old'], $field)
                    . ' -> '
                    . self::formatValue($change['new'], $field);
            })
            ->implode('; ');

        return $entity . ' atjaunināts: ' . $label . ' | detalas: ' . $details;
    }

    private static function severityFor(string $action, ?Model $model = null): string
    {
        if ($model && class_basename($model) === 'Repair') {
            $priority = (string) ($model->priority ?? '');

            if ($priority === 'critical') {
                return self::SEVERITY_CRITICAL;
            }

            if ($priority === 'high') {
                return self::SEVERITY_WARNING;
            }
        }

        return match ($action) {
            self::ACTION_DELETE, self::ACTION_RESTORE => self::SEVERITY_WARNING,
            default => self::SEVERITY_INFO,
        };
    }

    private static function readableEntityName(string $entityType): string
    {
        return self::entityLabel($entityType);
    }

    private static function normalizeAction(string $action): string
    {
        $action = strtoupper($action);
        $allowed = [
            self::ACTION_CREATE,
            self::ACTION_UPDATE,
            self::ACTION_DELETE,
            self::ACTION_LOGIN,
            self::ACTION_LOGOUT,
            self::ACTION_EXPORT,
            self::ACTION_BACKUP,
            self::ACTION_RESTORE,
            self::ACTION_VIEW,
        ];

        return in_array($action, $allowed, true) ? $action : self::ACTION_UPDATE;
    }

    private static function normalizeSeverity(string $severity): string
    {
        $allowed = [
            self::SEVERITY_INFO,
            self::SEVERITY_WARNING,
            self::SEVERITY_ERROR,
            self::SEVERITY_CRITICAL,
        ];

        return in_array($severity, $allowed, true) ? $severity : self::SEVERITY_INFO;
    }

    private static function changesFromState(array $before, array $after, array $ignoredFields = []): array
    {
        $changes = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
            if (in_array($field, $ignoredFields, true)) {
                continue;
            }

            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if (self::formatValue($old, $field) === self::formatValue($new, $field)) {
                continue;
            }

            $changes[$field] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        return $changes;
    }

    private static function formatValue(mixed $value, ?string $field = null): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'ja' : 'ne';
        }

        if ($value === null || $value === '') {
            return 'tukšs';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'masivs';
        }

        return self::translateValue((string) $value, $field);
    }

    private static function translateFieldList(string $fields): string
    {
        return collect(explode(',', $fields))
            ->map(fn (string $field) => self::translateFieldName(trim($field)))
            ->filter()
            ->implode(', ');
    }

    private static function translateDetails(string $details): string
    {
        return collect(explode(';', $details))
            ->map(function (string $entry) {
                $entry = trim($entry);

                if (! preg_match('/^(?<field>[^:]+): (?<old>.+?) -> (?<new>.+)$/', $entry, $matches)) {
                    return $entry;
                }

                $field = trim($matches['field']);

                return self::translateFieldName($field) . ': '
                    . self::translateValue(trim($matches['old']), $field)
                    . ' -> '
                    . self::translateValue(trim($matches['new']), $field);
            })
            ->implode('; ');
    }

    private static function translateArrowText(string $text): string
    {
        if (! str_contains($text, '->')) {
            return self::translateValue(trim($text));
        }

        [$old, $new] = array_map('trim', explode('->', $text, 2));

        return self::translateValue($old) . ' -> ' . self::translateValue($new);
    }

    private static function translateFieldName(string $field): string
    {
        return match (Str::of($field)->snake()->lower()->toString()) {
            'code' => 'kods',
            'name' => 'nosaukums',
            'description' => 'apraksts',
            'status' => 'statuss',
            'device_type_id' => 'ierīces tips',
            'device_id' => 'ierīce',
            'repair_type' => 'remonta tips',
            'priority' => 'prioritāte',
            'start_date' => 'sākuma datums',
            'end_date' => 'beigu datums',
            'cost' => 'izmaksas',
            'vendor_name' => 'pakalpojuma sniedzējs',
            'vendor_contact' => 'kontakts',
            'invoice_number' => 'rēķina numurs',
            'issue_reported_by' => 'izpildītājs',
            'accepted_by' => 'apstiprinātājs',
            'responsible_user_id' => 'atbildīgais lietotājs',
            'reviewed_by_user_id' => 'izskatīja',
            'repair_id' => 'izveidotais remonts',
            'request_id' => 'saiste ar pieteikumu',
            'transfered_to_id' => 'saņēmējs',
            'transfer_reason' => 'nodošanas iemesls',
            'review_notes' => 'izskatīšanas piezīmes',
            'assigned_to_id' => 'piešķirtais lietotājs',
            'building_id' => 'ēka',
            'room_id' => 'telpa',
            'purchase_date' => 'iegādes datums',
            'purchase_price' => 'iegādes cena',
            'warranty_until' => 'garantija līdz',
            'serial_number' => 'sērijas numurs',
            'manufacturer' => 'ražotājs',
            'notes' => 'piezīmes',
            'device_image_url' => 'ierīces attēls',
            'category' => 'kategorija',
            default => str_replace('_', ' ', Str::lower($field)),
        };
    }

    private static function translateValue(string $value, ?string $field = null): string
    {
        $normalized = Str::lower(trim($value));

        $map = [
            'active' => 'Aktīva',
            'repair' => 'Remonta',
            'writeoff' => 'Norakstīta',
            'submitted' => 'Iesniegts',
            'approved' => 'Apstiprināts',
            'rejected' => 'Noraidīts',
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            'internal' => 'Iekšējais',
            'external' => 'Ārējais',
            'low' => 'Zema',
            'medium' => 'Vidēja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
            'daily' => 'katru dienu',
            'weekly' => 'katru nedelu',
            'monthly' => 'katru menesi',
            'manual' => 'manuali',
            'uploaded' => 'augšupielādēts',
            'system' => 'sistēma',
            'info' => 'informācija',
            'warning' => 'brīdinajums',
            'error' => 'kluda',
        ];

        if (array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        if ($field && Str::of($field)->snake()->lower()->toString() === 'run_at') {
            return 'plkst. ' . $value;
        }

        return $value;
    }

    private static function normalizeEntityKey(?string $entityType): string
    {
        return Str::of((string) $entityType)
            ->replace(['-', '/', '\\'], ' ')
            ->snake()
            ->lower()
            ->toString();
    }
}
