<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

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
            // Rezerves kopijam un citam kritiskam darbibam nav jauzluzt, ja audits uz bridi nav pieejams.
        }
    }

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

    public static function login(?User $user): void
    {
        if (! $user) {
            return;
        }

        self::writeForModel(
            $user->id,
            self::ACTION_LOGIN,
            $user,
            'Lietotajs piesledzas: ' . self::labelFor($user),
            self::SEVERITY_INFO
        );
    }

    public static function logout(?User $user): void
    {
        if (! $user) {
            return;
        }

        self::writeForModel(
            $user->id,
            self::ACTION_LOGOUT,
            $user,
            'Lietotajs izrakstijas: ' . self::labelFor($user),
            self::SEVERITY_INFO
        );
    }

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

    public static function labelFor(Model $model): string
    {
        return match (class_basename($model)) {
            'Building' => (string) ($model->building_name ?? ('Eka #' . $model->getKey())),
            'Room' => trim((string) (($model->room_number ?? 'Telpa #' . $model->getKey()) . ' ' . ($model->room_name ?? ''))),
            'DeviceType' => (string) ($model->type_name ?? ('Ierices tips #' . $model->getKey())),
            'Device' => trim((string) (($model->code ? '[' . $model->code . '] ' : '') . ($model->name ?? ('Ierice #' . $model->getKey())))),
            'Repair' => trim((string) (($model->device?->name ?? 'Ierice') . ' | ' . Str::limit((string) ($model->description ?? 'Remonts #' . $model->getKey()), 70))),
            'User' => (string) ($model->full_name ?? ('Lietotajs #' . $model->getKey())),
            'RepairRequest' => trim((string) (($model->device?->name ?? 'Ierice') . ' | ' . Str::limit((string) ($model->description ?? 'Remonta pieteikums #' . $model->getKey()), 70))),
            'WriteoffRequest' => trim((string) (($model->device?->name ?? 'Ierice') . ' | ' . Str::limit((string) ($model->reason ?? 'Norakstisanas pieteikums #' . $model->getKey()), 70))),
            'DeviceTransfer' => trim((string) (($model->device?->name ?? 'Ierice') . ' | ' . Str::limit((string) ($model->transfer_reason ?? 'Parsutisanas pieteikums #' . $model->getKey()), 70))),
            'DeviceSet' => (string) ($model->set_name ?? $model->name ?? ('Komplekts #' . $model->getKey())),
            'DeviceSetItem' => trim((string) (($model->deviceSet?->set_name ?? 'Komplekts #' . ($model->device_set_id ?? $model->getKey())) . ' | ' . ($model->device?->name ?? 'Ierice #' . ($model->device_id ?? '')))),
            default => self::entityLabel(class_basename($model)) . ' #' . $model->getKey(),
        };
    }

    public static function actionLabel(string $action): string
    {
        return match (strtoupper($action)) {
            self::ACTION_CREATE => 'Izveide',
            self::ACTION_UPDATE => 'Atjaunosana',
            self::ACTION_DELETE => 'Dzesana',
            self::ACTION_LOGIN => 'Pieslegsanas',
            self::ACTION_LOGOUT => 'Izrakstisanas',
            self::ACTION_EXPORT => 'Eksports',
            self::ACTION_BACKUP => 'Kopija',
            self::ACTION_RESTORE => 'Atjaunosana no kopijas',
            self::ACTION_VIEW => 'Apskate',
            default => $action,
        };
    }

    public static function entityLabel(?string $entityType): string
    {
        $key = self::normalizeEntityKey($entityType);

        return match ($key) {
            'building' => 'Eka',
            'room' => 'Telpa',
            'device_type' => 'Ierices tips',
            'device' => 'Ierice',
            'repair' => 'Remonts',
            'user' => 'Lietotajs',
            'repair_request' => 'Remonta pieteikums',
            'writeoff_request' => 'Norakstisanas pieteikums',
            'device_transfer' => 'Ierices parsutisana',
            'device_set' => 'Komplekts',
            'device_set_item' => 'Komplekta ieraksts',
            'database_backup' => 'Datubazes kopija',
            'backup_setting' => 'Kopiju iestatijumi',
            default => Str::headline((string) $entityType),
        };
    }

    public static function severityLabel(string $severity): string
    {
        return match ($severity) {
            self::SEVERITY_WARNING => 'Bridinajums',
            self::SEVERITY_ERROR => 'Kluda',
            self::SEVERITY_CRITICAL => 'Kritisks',
            default => 'Informacija',
        };
    }

    public static function localizedDescription(?string $description, ?string $entityType = null): string
    {
        $text = trim((string) $description);

        if ($text === '') {
            return 'Nav apraksta';
        }

        if (preg_match('/^User logged in: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotajs piesledzas: ' . $matches['label'];
        }

        if (preg_match('/^User logged out: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotajs izrakstijas: ' . $matches['label'];
        }

        if (preg_match('/^User account deleted: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotaja konts dzests: ' . $matches['label'];
        }

        if (preg_match('/^User password changed: (?<label>.+)$/i', $text, $matches)) {
            return 'Lietotaja parole nomainita: ' . $matches['label'];
        }

        if (preg_match('/^Database backup created: (?<label>.+)$/i', $text, $matches)) {
            return 'Datubazes kopija izveidota: ' . $matches['label'];
        }

        if (preg_match('/^Backup file uploaded from computer: (?<label>.+)$/i', $text, $matches)) {
            return 'Kopijas fails augshupladets no datora: ' . $matches['label'];
        }

        if (preg_match('/^Database restored from backup: (?<label>.+)$/i', $text, $matches)) {
            return 'Datubaze atjaunota no kopijas: ' . $matches['label'];
        }

        if (preg_match('/^Backup deleted: (?<label>.+)$/i', $text, $matches)) {
            return 'Rezerves kopija dzesta: ' . $matches['label'];
        }

        if (preg_match('/^Backup schedule updated: (?<frequency>[^ ]+) at (?<time>.+)$/i', $text, $matches)) {
            return 'Kopiju grafiks atjauninats: '
                . self::translateValue($matches['frequency'])
                . ' plkst. '
                . trim($matches['time']);
        }

        if (preg_match('/^Repair status changed: (?<states>.+)$/i', $text, $matches)) {
            return 'Remonta statuss mainits: ' . self::translateArrowText($matches['states']);
        }

        if (preg_match('/^Device status changed: (?<label>.+?) \| (?<states>.+)$/i', $text, $matches)) {
            return 'Ierices statuss mainits: ' . $matches['label'] . ' | ' . self::translateArrowText($matches['states']);
        }

        if (preg_match('/^Device status synced from repair #(?<repair>\d+): (?<states>.+)$/i', $text, $matches)) {
            return 'Ierices statuss saskanots no remonta #' . $matches['repair'] . ': ' . self::translateArrowText($matches['states']);
        }

        if (preg_match('/^Device moved: (?<label>.+?) -> room (?<room>.+)$/i', $text, $matches)) {
            return 'Ierice parvietota: ' . $matches['label'] . ' -> telpa ' . trim($matches['room']);
        }

        if (preg_match('/^Device added to set: (?<label>.+?) -> (?<set>.+)$/i', $text, $matches)) {
            return 'Ierice pievienota komplektam: ' . $matches['label'] . ' -> ' . trim($matches['set']);
        }

        if (preg_match('/^(?<entity>.+?) updated: (?<label>.+?) \| details: (?<details>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' atjauninats: '
                . $matches['label']
                . ' | detalas: '
                . self::translateDetails($matches['details']);
        }

        if (preg_match('/^(?<entity>.+?) updated: (?<label>.+?) \| fields: (?<fields>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' atjauninats: '
                . $matches['label']
                . ' | lauki: '
                . self::translateFieldList($matches['fields']);
        }

        if (preg_match('/^(?<entity>.+?) created: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' izveidots: ' . $matches['label'];
        }

        if (preg_match('/^(?<entity>.+?) updated: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' atjauninats: ' . $matches['label'];
        }

        if (preg_match('/^(?<entity>.+?) deleted: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' dzests: ' . $matches['label'];
        }

        if (preg_match('/^(?<entity>.+?) changed: (?<label>.+)$/i', $text, $matches)) {
            return self::entityLabel($matches['entity']) . ' mainits: ' . $matches['label'];
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
            self::ACTION_UPDATE => $entity . ' atjauninats: ' . $label . ($changedFields !== [] ? ' | lauki: ' . self::translateFieldList(implode(', ', $changedFields)) : ''),
            self::ACTION_DELETE => $entity . ' dzests: ' . $label,
            default => $entity . ' mainits: ' . $label,
        };
    }

    private static function detailedUpdateDescription(Model $model, array $changes): string
    {
        $entity = self::readableEntityName(class_basename($model));
        $label = self::labelFor($model);

        if ($changes === []) {
            return $entity . ' atjauninats: ' . $label;
        }

        $details = collect($changes)
            ->map(function (array $change, string $field) {
                return self::translateFieldName($field) . ': '
                    . self::formatValue($change['old'], $field)
                    . ' -> '
                    . self::formatValue($change['new'], $field);
            })
            ->implode('; ');

        return $entity . ' atjauninats: ' . $label . ' | detalas: ' . $details;
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
            return 'tukss';
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
            'device_type_id' => 'ierices tips',
            'device_id' => 'ierice',
            'repair_type' => 'remonta tips',
            'priority' => 'prioritate',
            'start_date' => 'sakuma datums',
            'end_date' => 'beigu datums',
            'cost' => 'izmaksas',
            'vendor_name' => 'pakalpojuma sniedzejs',
            'vendor_contact' => 'kontakts',
            'invoice_number' => 'rekina numurs',
            'issue_reported_by' => 'pieteicejs',
            'accepted_by' => 'apstiprinatajs',
            'responsible_user_id' => 'atbildigais lietotajs',
            'reviewed_by_user_id' => 'izskatija',
            'repair_id' => 'izveidotais remonts',
            'request_id' => 'saiste ar pieteikumu',
            'transfered_to_id' => 'saemejs',
            'transfer_reason' => 'nodosanas iemesls',
            'review_notes' => 'izskatisanas piezimes',
            'assigned_to_id' => 'pieskirtais lietotajs',
            'building_id' => 'eka',
            'room_id' => 'telpa',
            'purchase_date' => 'iegades datums',
            'purchase_price' => 'iegades cena',
            'warranty_until' => 'garantija lidz',
            'warranty_photo_name' => 'garantijas foto',
            'serial_number' => 'serijas numurs',
            'manufacturer' => 'razotajs',
            'notes' => 'piezimes',
            'device_image_url' => 'ierices attels',
            'category' => 'kategorija',
            'expected_lifetime_years' => 'paredzamais lietosanas ilgums',
            'quantity' => 'daudzums',
            'role' => 'loma',
            default => str_replace('_', ' ', Str::lower($field)),
        };
    }

    private static function translateValue(string $value, ?string $field = null): string
    {
        $normalized = Str::lower(trim($value));

        $map = [
            'active' => 'Aktiva',
            'repair' => 'Remonta',
            'writeoff' => 'Norakstita',
            'submitted' => 'Iesniegts',
            'approved' => 'Apstiprinats',
            'rejected' => 'Noraidits',
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            'internal' => 'Ieksejais',
            'external' => 'Arejais',
            'low' => 'Zema',
            'medium' => 'Videja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
            'daily' => 'katru dienu',
            'weekly' => 'katru nedelu',
            'monthly' => 'katru menesi',
            'manual' => 'manuali',
            'uploaded' => 'augshupladets',
            'system' => 'sistema',
            'info' => 'informacija',
            'warning' => 'bridinajums',
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
