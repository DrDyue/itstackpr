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
            // Backup actions should still succeed if audit persistence is temporarily unavailable.
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
            'User logged in: ' . self::labelFor($user),
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
            'User logged out: ' . self::labelFor($user),
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
            'Building' => (string) ($model->building_name ?? ('Building #' . $model->getKey())),
            'Room' => trim((string) (($model->room_number ?? 'Room #' . $model->getKey()) . ' ' . ($model->room_name ?? ''))),
            'DeviceType' => (string) ($model->type_name ?? ('Device type #' . $model->getKey())),
            'Device' => trim((string) (($model->code ? '[' . $model->code . '] ' : '') . ($model->name ?? ('Device #' . $model->getKey())))),
            'Repair' => trim((string) (($model->device?->name ?? 'Device') . ' | ' . Str::limit((string) ($model->description ?? 'Repair #' . $model->getKey()), 70))),
            'Employee' => (string) ($model->full_name ?? ('Employee #' . $model->getKey())),
            'User' => (string) ($model->employee?->full_name ?? ('User #' . $model->getKey())),
            'DeviceSet' => (string) ($model->set_name ?? $model->name ?? ('Device set #' . $model->getKey())),
            'DeviceSetItem' => trim((string) (($model->deviceSet?->set_name ?? 'Set #' . ($model->device_set_id ?? $model->getKey())) . ' | ' . ($model->device?->name ?? 'Device #' . ($model->device_id ?? '')))),
            default => class_basename($model) . ' #' . $model->getKey(),
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

    private static function defaultDescription(string $action, Model $model, array $changedFields = []): string
    {
        $entity = self::readableEntityName(class_basename($model));
        $label = self::labelFor($model);

        return match ($action) {
            self::ACTION_CREATE => $entity . ' created: ' . $label,
            self::ACTION_UPDATE => $entity . ' updated: ' . $label . ($changedFields !== [] ? ' | fields: ' . implode(', ', $changedFields) : ''),
            self::ACTION_DELETE => $entity . ' deleted: ' . $label,
            default => $entity . ' changed: ' . $label,
        };
    }

    private static function detailedUpdateDescription(Model $model, array $changes): string
    {
        $entity = self::readableEntityName(class_basename($model));
        $label = self::labelFor($model);

        if ($changes === []) {
            return $entity . ' updated: ' . $label;
        }

        $details = collect($changes)
            ->map(function (array $change, string $field) {
                return $field . ': ' . self::formatValue($change['old']) . ' -> ' . self::formatValue($change['new']);
            })
            ->implode('; ');

        return $entity . ' updated: ' . $label . ' | details: ' . $details;
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
        return str_replace('_', ' ', Str::headline($entityType));
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

            if (self::formatValue($old) === self::formatValue($new)) {
                continue;
            }

            $changes[$field] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        return $changes;
    }

    private static function formatValue(mixed $value): string
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

        return (string) $value;
    }
}
