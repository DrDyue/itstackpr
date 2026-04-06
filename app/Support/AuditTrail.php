<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\DeviceType;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
    public const ACTION_ASSIGN = 'ASSIGN';
    public const ACTION_UNASSIGN = 'UNASSIGN';
    public const ACTION_MOVE = 'MOVE';
    public const ACTION_STATUS_CHANGE = 'STATUS_CHANGE';
    public const ACTION_APPROVE = 'APPROVE';
    public const ACTION_REJECT = 'REJECT';
    public const ACTION_SUBMIT = 'SUBMIT';
    public const ACTION_CANCEL = 'CANCEL';
    public const ACTION_TRANSFER = 'TRANSFER';
    public const ACTION_PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    public const ACTION_PROFILE_UPDATE = 'PROFILE_UPDATE';
    public const ACTION_ROOM_ASSIGN = 'ROOM_ASSIGN';
    public const ACTION_BUILDING_ASSIGN = 'BUILDING_ASSIGN';
    public const ACTION_SWITCH_VIEW = 'SWITCH_VIEW';
    public const ACTION_MARK_READ = 'MARK_READ';
    public const ACTION_SEARCH = 'SEARCH';
    public const ACTION_FILTER = 'FILTER';
    public const ACTION_SORT = 'SORT';

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
     * Auditē administratora skata režīma pārslēgšanu.
     */
    public static function switchViewMode(?User $user, string $fromMode, string $toMode): void
    {
        if (! $user) {
            return;
        }

        self::write(
            $user->id,
            self::ACTION_SWITCH_VIEW,
            'ViewMode',
            (string) $user->id,
            'Skata režīms pārslēgts: '.self::translateValue($fromMode).' -> '.self::translateValue($toMode),
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē paziņojumu atzīmēšanu kā lasītus.
     */
    public static function markRead(?User $user, string $description): void
    {
        if (! $user) {
            return;
        }

        self::write(
            $user->id,
            self::ACTION_MARK_READ,
            'NotificationCenter',
            (string) $user->id,
            $description,
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē meklēšanas darbību sarakstos vai tabulās.
     */
    public static function search(?User $user, string $entityType, string $term, ?string $description = null): void
    {
        if (! $user) {
            return;
        }

        self::write(
            $user->id,
            self::ACTION_SEARCH,
            $entityType,
            null,
            $description ?? ('Meklēts '.mb_strtolower(self::entityLabel($entityType)).': '.$term),
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē filtru pielietošanu sarakstiem.
     */
    public static function filter(?User $user, string $entityType, array $filters, ?string $description = null): void
    {
        if (! $user) {
            return;
        }

        $summary = self::filterSummary($filters);
        if ($summary === '') {
            return;
        }

        self::write(
            $user->id,
            self::ACTION_FILTER,
            $entityType,
            null,
            $description ?? ('Filtrēts '.mb_strtolower(self::entityLabel($entityType)).': '.$summary),
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē kārtošanas darbību tabulās.
     */
    public static function sort(?User $user, string $entityType, string $field, string $direction, ?string $description = null): void
    {
        if (! $user) {
            return;
        }

        $directionLabel = strtolower($direction) === 'asc' ? 'augošajā secībā' : 'dilstošajā secībā';

        self::write(
            $user->id,
            self::ACTION_SORT,
            $entityType,
            null,
            $description ?? ('Kārtots '.mb_strtolower(self::entityLabel($entityType)).' pēc '.$field.' '.$directionLabel.'.'),
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē kādas sadaļas apskati.
     */
    public static function viewed(?User $user, string $entityType, ?string $entityId = null, ?string $description = null): void
    {
        if (! $user) {
            return;
        }

        self::write(
            $user->id,
            self::ACTION_VIEW,
            $entityType,
            $entityId,
            $description ?? ('Atvērta sadaļa: '.self::entityLabel($entityType)),
            self::SEVERITY_INFO
        );
    }

    /**
     * Auditē ierīces piešķiršanu lietotājam.
     */
    public static function assign(?int $userId, Model $model, ?User $assignedTo = null, ?string $description = null): void
    {
        $desc = $description ?? sprintf(
            'Ierīce piešķirta: %s → %s',
            self::labelFor($model),
            $assignedTo ? self::labelFor($assignedTo) : 'Nav piešķirts'
        );
        self::writeForModel($userId, self::ACTION_ASSIGN, $model, $desc, self::SEVERITY_INFO);
    }

    /**
     * Auditē ierīces atsaistīšanu no lietotāja.
     */
    public static function unassign(?int $userId, Model $model, ?string $description = null): void
    {
        $desc = $description ?? 'Ierīce atsaistīta: ' . self::labelFor($model);
        self::writeForModel($userId, self::ACTION_UNASSIGN, $model, $desc, self::SEVERITY_INFO);
    }

    /**
     * Auditē ierīces pārvietošanu uz citu telpu.
     */
    public static function move(?int $userId, Model $model, ?string $fromLocation, ?string $toLocation, ?string $description = null): void
    {
        $desc = $description ?? sprintf(
            'Ierīce pārvietota: %s | %s → %s',
            self::labelFor($model),
            $fromLocation ?? 'Nav norādīts',
            $toLocation ?? 'Nav norādīts'
        );
        self::writeForModel($userId, self::ACTION_MOVE, $model, $desc, self::SEVERITY_INFO);
    }

    /**
     * Auditē statusa maiņu.
     */
    public static function statusChange(?int $userId, Model $model, string $oldStatus, string $newStatus, ?string $description = null): void
    {
        $desc = $description ?? sprintf(
            'Statuss mainīts: %s | %s → %s',
            self::labelFor($model),
            self::translateValue($oldStatus),
            self::translateValue($newStatus)
        );
        self::writeForModel($userId, self::ACTION_STATUS_CHANGE, $model, $desc, self::SEVERITY_INFO);
    }

    /**
     * Auditē pieteikuma apstiprināšanu.
     */
    public static function approve(?int $userId, Model $model, ?string $description = null): void
    {
        $desc = $description ?? 'Apstiprināts: ' . self::labelFor($model);
        self::writeForModel($userId, self::ACTION_APPROVE, $model, $desc, self::SEVERITY_INFO);
    }

    /**
     * Auditē pieteikuma noraidīšanu.
     */
    public static function reject(?int $userId, Model $model, ?string $reason = null, ?string $description = null): void
    {
        $desc = $description ?? 'Noraidīts: ' . self::labelFor($model) . ($reason ? ' | Iemesls: ' . $reason : '');
        self::writeForModel($userId, self::ACTION_REJECT, $model, $desc, self::SEVERITY_WARNING);
    }

    /**
     * Auditē pieteikuma iesniegšanu.
     */
    public static function submit(?int $userId, Model $model, ?string $description = null): void
    {
        $desc = $description ?? 'Iesniegts: ' . self::labelFor($model);
        self::writeForModel($userId, self::ACTION_SUBMIT, $model, $desc, self::SEVERITY_INFO);
    }

    /**
     * Auditē pieteikuma atcelšanu.
     */
    public static function cancel(?int $userId, Model $model, ?string $description = null): void
    {
        $desc = $description ?? 'Atcelts: ' . self::labelFor($model);
        self::writeForModel($userId, self::ACTION_CANCEL, $model, $desc, self::SEVERITY_WARNING);
    }

    /**
     * Auditē ierīces nodošanu citam lietotājam.
     */
    public static function transfer(?int $userId, Model $model, ?User $transferTo, ?string $description = null): void
    {
        $desc = $description ?? sprintf(
            'Nodots: %s | %s → %s',
            self::labelFor($model),
            self::labelFor($userId ? \App\Models\User::find($userId) : null) ?? 'Nezināms',
            $transferTo ? self::labelFor($transferTo) : 'Nezināms'
        );
        self::writeForModel($userId, self::ACTION_TRANSFER, $model, $desc, self::SEVERITY_INFO);
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
            self::ACTION_UPDATE => 'Atjaunošana',
            self::ACTION_DELETE => 'Dzēšana',
            self::ACTION_LOGIN => 'Pieslēgšanās',
            self::ACTION_LOGOUT => 'Izrakstīšanās',
            self::ACTION_EXPORT => 'Eksports',
            self::ACTION_BACKUP => 'Kopija',
            self::ACTION_RESTORE => 'Atjaunošana no kopijas',
            self::ACTION_VIEW => 'Apskate',
            self::ACTION_ASSIGN => 'Piešķiršana',
            self::ACTION_UNASSIGN => 'Atsaistīšana',
            self::ACTION_MOVE => 'Pārvietošana',
            self::ACTION_STATUS_CHANGE => 'Statusa maiņa',
            self::ACTION_APPROVE => 'Apstiprināšana',
            self::ACTION_REJECT => 'Noraidīšana',
            self::ACTION_SUBMIT => 'Iesniegšana',
            self::ACTION_CANCEL => 'Atcelšana',
            self::ACTION_TRANSFER => 'Nodošana',
            self::ACTION_PASSWORD_CHANGE => 'Paroles maiņa',
            self::ACTION_PROFILE_UPDATE => 'Profila atjaunošana',
            self::ACTION_ROOM_ASSIGN => 'Telpas piešķiršana',
            self::ACTION_BUILDING_ASSIGN => 'Ēkas piešķiršana',
            self::ACTION_SWITCH_VIEW => 'Skata maiņa',
            self::ACTION_MARK_READ => 'Atzīmēts kā lasīts',
            self::ACTION_SEARCH => 'Meklēšana',
            self::ACTION_FILTER => 'Filtrēšana',
            self::ACTION_SORT => 'Kārtošana',
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
            'audit_log' => 'Audita žurnāls',
            'notification_center' => 'Paziņojumu centrs',
            'view_mode' => 'Skata režīms',
            'database_backup' => 'Datubāzes kopija',
            'backup_setting' => 'Kopiju iestatijumi',
            default => 'Cits objekts',
        };
    }

    /**
     * Pārbauda, vai objekta tips ir zināms un paredzēts rādīšanai auditā.
     */
    public static function isKnownEntityType(?string $entityType): bool
    {
        return in_array(self::normalizeEntityKey($entityType), [
            'building',
            'room',
            'device_type',
            'device',
            'repair',
            'user',
            'repair_request',
            'writeoff_request',
            'device_transfer',
            'audit_log',
            'notification_center',
            'view_mode',
            'database_backup',
            'backup_setting',
        ], true);
    }

    /**
     * Lokalizē audita smaguma līmeni.
     */
    public static function severityLabel(string $severity): string
    {
        return match ($severity) {
            self::SEVERITY_WARNING => 'Brīdinājums',
            self::SEVERITY_ERROR => 'Kļūda',
            self::SEVERITY_CRITICAL => 'Kritiski',
            self::SEVERITY_INFO => 'Informācija',
            default => $severity,
        };
    }

    /**
     * Sagatavo īsu objekta atsauci sarakstu skatam.
     */
    public static function entityReference(?string $entityType, ?string $entityId): string
    {
        $preview = self::entityPreview($entityType, $entityId);

        if (($preview['title'] ?? '') !== '') {
            return (string) $preview['title'];
        }

        return self::entityLabel($entityType);
    }

    public static function entityUrl(?string $entityType, ?string $entityId): ?string
    {
        if (! filled($entityId)) {
            return null;
        }

        try {
            $entity = self::resolveEntityModel($entityType, $entityId);

             if (! $entity) {
                return null;
            }

            return match (self::normalizeEntityKey($entityType)) {
                'device' => route('devices.show', $entityId),
                'repair' => route('repairs.edit', $entityId),
                'repair_request' => route('repair-requests.index', ['q' => $entityId]) . '#repair-request-' . $entityId,
                'writeoff_request' => route('writeoff-requests.index', ['q' => $entityId]) . '#writeoff-request-' . $entityId,
                'device_transfer' => route('device-transfers.index', ['q' => $entityId]) . '#device-transfer-' . $entityId,
                'room' => route('rooms.edit', $entityId),
                'building' => route('buildings.edit', $entityId),
                'device_type' => $entity instanceof DeviceType ? self::deviceTypeIndexUrl($entity) : null,
                'user' => route('users.edit', $entityId),
                'audit_log' => route('audit-log.index', ['highlight_id' => 'audit-log-'.$entityId]),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Sagatavo hover kartītes saturu objektam.
     *
     * @return array{title:string, lines:array<int,string>, exists:bool}
     */
    public static function entityPreview(?string $entityType, ?string $entityId): array
    {
        $label = self::entityLabel($entityType);
        $entity = self::resolveEntityModel($entityType, $entityId);

        if (! $entity) {
            return [
                'title' => $label,
                'lines' => ['Objekts vairs nav pieejams vai ir dzēsts.'],
                'exists' => false,
            ];
        }

        return match (self::normalizeEntityKey($entityType)) {
            'device_type' => [
                'title' => (string) $entity->type_name,
                'lines' => [
                    'Saistītās ierīces: '.(string) $entity->devices()->count(),
                    'Atver tipu sarakstu ar izcelto ierakstu.',
                ],
                'exists' => true,
            ],
            'device' => [
                'title' => trim((string) (($entity->code ? '['.$entity->code.'] ' : '').($entity->name ?? 'Ierīce'))),
                'lines' => array_values(array_filter([
                    $entity->type?->type_name ? 'Tips: '.$entity->type->type_name : null,
                    $entity->assignedTo?->full_name ? 'Piešķirta: '.$entity->assignedTo->full_name : null,
                    $entity->room?->room_name ? 'Telpa: '.$entity->room->room_name : null,
                ])),
                'exists' => true,
            ],
            'user' => [
                'title' => (string) $entity->full_name,
                'lines' => array_values(array_filter([
                    $entity->email ?: null,
                    $entity->role ? 'Loma: '.self::translateValue((string) $entity->role) : null,
                ])),
                'exists' => true,
            ],
            'room' => [
                'title' => trim((string) (($entity->room_name ?? 'Telpa').' '.($entity->room_number ?? ''))),
                'lines' => array_values(array_filter([
                    $entity->building?->building_name ? 'Ēka: '.$entity->building->building_name : null,
                    $entity->floor_number !== null ? 'Stāvs: '.$entity->floor_number : null,
                ])),
                'exists' => true,
            ],
            'building' => [
                'title' => (string) ($entity->building_name ?? $label),
                'lines' => array_values(array_filter([
                    $entity->address ?: null,
                    $entity->city ?: null,
                ])),
                'exists' => true,
            ],
            'repair' => [
                'title' => (string) ($entity->device?->name ?: 'Remonts'),
                'lines' => array_values(array_filter([
                    $entity->status ? 'Statuss: '.self::translateValue((string) $entity->status) : null,
                    $entity->priority ? 'Prioritāte: '.self::translateValue((string) $entity->priority) : null,
                ])),
                'exists' => true,
            ],
            'repair_request' => [
                'title' => (string) ($entity->device?->name ?: 'Remonta pieteikums'),
                'lines' => array_values(array_filter([
                    $entity->responsibleUser?->full_name ? 'Pieteicējs: '.$entity->responsibleUser->full_name : null,
                    $entity->status ? 'Statuss: '.self::translateValue((string) $entity->status) : null,
                ])),
                'exists' => true,
            ],
            'writeoff_request' => [
                'title' => (string) ($entity->device?->name ?: 'Norakstīšanas pieteikums'),
                'lines' => array_values(array_filter([
                    $entity->responsibleUser?->full_name ? 'Pieteicējs: '.$entity->responsibleUser->full_name : null,
                    $entity->status ? 'Statuss: '.self::translateValue((string) $entity->status) : null,
                ])),
                'exists' => true,
            ],
            'device_transfer' => [
                'title' => (string) ($entity->device?->name ?: 'Nodošanas pieteikums'),
                'lines' => array_values(array_filter([
                    $entity->responsibleUser?->full_name ? 'No: '.$entity->responsibleUser->full_name : null,
                    $entity->transferTo?->full_name ? 'Kam: '.$entity->transferTo->full_name : null,
                ])),
                'exists' => true,
            ],
            default => [
                'title' => self::labelFor($entity),
                'lines' => [],
                'exists' => true,
            ],
        };
    }

    public static function localizedDescription(?string $description, ?string $entityType = null): string
    {
        $text = trim((string) $description);

        if ($text === '') {
            return 'Nav apraksta';
        }

        // Jaunas darbības
        if (preg_match('/^Ierīce piešķirta: (?<label>.+) → (?<user>.+)$/i', $text, $matches)) {
            return 'Ierīce piešķirta: ' . $matches['label'] . ' → ' . $matches['user'];
        }

        if (preg_match('/^Ierīce atsaistīta: (?<label>.+)$/i', $text, $matches)) {
            return 'Ierīce atsaistīta: ' . $matches['label'];
        }

        if (preg_match('/^Ierīce pārvietota: (?<label>.+) \| (?<from>.+) → (?<to>.+)$/i', $text, $matches)) {
            return 'Ierīce pārvietota: ' . $matches['label'] . ' | ' . $matches['from'] . ' → ' . $matches['to'];
        }

        if (preg_match('/^Statuss mainīts: (?<label>.+) \| (?<from>.+) → (?<to>.+)$/i', $text, $matches)) {
            return 'Statuss mainīts: ' . $matches['label'] . ' | ' . self::translateValue($matches['from']) . ' → ' . self::translateValue($matches['to']);
        }

        if (preg_match('/^Apstiprināts: (?<label>.+)$/i', $text, $matches)) {
            return 'Apstiprināts: ' . $matches['label'];
        }

        if (preg_match('/^Noraidīts: (?<label>.+)(?: \| Iemesls: (?<reason>.+))?$/i', $text, $matches)) {
            return 'Noraidīts: ' . $matches['label'] . (isset($matches['reason']) ? ' | Iemesls: ' . $matches['reason'] : '');
        }

        if (preg_match('/^Iesniegts: (?<label>.+)$/i', $text, $matches)) {
            return 'Iesniegts: ' . $matches['label'];
        }

        if (preg_match('/^Atcelts: (?<label>.+)$/i', $text, $matches)) {
            return 'Atcelts: ' . $matches['label'];
        }

        if (preg_match('/^Nodots: (?<label>.+) \| (?<from>.+) → (?<to>.+)$/i', $text, $matches)) {
            return 'Nodots: ' . $matches['label'] . ' | ' . $matches['from'] . ' → ' . $matches['to'];
        }

        // Esošās darbības
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

    public static function compactDescription(?string $description, ?string $entityType = null, ?string $action = null): string
    {
        $text = self::localizedDescription($description, $entityType);

        $patterns = [
            '/^.+? izveidots: (?<body>.+)$/u' => 'Izveidots: $1',
            '/^.+? atjaunināts: (?<body>.+)$/u' => 'Atjaunināts: $1',
            '/^.+? dzēsts: (?<body>.+)$/u' => 'Dzēsts: $1',
            '/^.+? mainīts: (?<body>.+)$/u' => 'Mainīts: $1',
            '/^Atvērta sadaļa: (?<body>.+)$/u' => 'Atvērts: $1',
            '/^Audita žurnālā meklēts ieraksts: (?<body>.+)$/u' => 'Meklēts: $1',
            '/^Audita žurnāls filtrēts pēc: (?<body>.+)$/u' => 'Filtri: $1',
            '/^Audita žurnāls sakārtots pēc (?<body>.+)$/u' => 'Kārtošana: $1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $text)) {
                return preg_replace($pattern, $replacement, $text) ?? $text;
            }
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
            self::ACTION_DELETE,
            self::ACTION_RESTORE,
            self::ACTION_REJECT,
            self::ACTION_CANCEL,
            self::ACTION_UNASSIGN => self::SEVERITY_WARNING,
            
            self::ACTION_STATUS_CHANGE,
            self::ACTION_MOVE,
            self::ACTION_ASSIGN,
            self::ACTION_APPROVE,
            self::ACTION_SUBMIT,
            self::ACTION_TRANSFER => self::SEVERITY_INFO,
            
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
            self::ACTION_ASSIGN,
            self::ACTION_UNASSIGN,
            self::ACTION_MOVE,
            self::ACTION_STATUS_CHANGE,
            self::ACTION_APPROVE,
            self::ACTION_REJECT,
            self::ACTION_SUBMIT,
            self::ACTION_CANCEL,
            self::ACTION_TRANSFER,
            self::ACTION_PASSWORD_CHANGE,
            self::ACTION_PROFILE_UPDATE,
            self::ACTION_ROOM_ASSIGN,
            self::ACTION_BUILDING_ASSIGN,
            self::ACTION_SWITCH_VIEW,
            self::ACTION_MARK_READ,
            self::ACTION_SEARCH,
            self::ACTION_FILTER,
            self::ACTION_SORT,
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

    private static function filterSummary(array $filters): string
    {
        return collect($filters)
            ->map(function (mixed $value, mixed $key) {
                $label = self::translateFieldName((string) $key);

                if (is_array($value)) {
                    $items = collect($value)
                        ->filter(fn (mixed $item) => $item !== null && $item !== '')
                        ->map(fn (mixed $item) => self::translateValue((string) $item, (string) $key))
                        ->values()
                        ->all();

                    if ($items === []) {
                        return null;
                    }

                    return $label . ': ' . implode(', ', $items);
                }

                if (is_bool($value)) {
                    return $value ? $label . ': jā' : null;
                }

                if ($value === null || $value === '') {
                    return null;
                }

                return $label . ': ' . self::translateValue((string) $value, (string) $key);
            })
            ->filter()
            ->implode(' | ');
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
            'error' => 'kļūda',
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

    private static function resolveEntityModel(?string $entityType, ?string $entityId): ?Model
    {
        if (! filled($entityId)) {
            return null;
        }

        return match (self::normalizeEntityKey($entityType)) {
            'building' => Building::query()->find($entityId),
            'room' => Room::query()->with('building')->find($entityId),
            'device_type' => DeviceType::query()->find($entityId),
            'device' => Device::query()->with(['type', 'assignedTo', 'room'])->find($entityId),
            'repair' => Repair::query()->with('device')->find($entityId),
            'user' => User::query()->find($entityId),
            'repair_request' => RepairRequest::query()->with(['device', 'responsibleUser'])->find($entityId),
            'writeoff_request' => WriteoffRequest::query()->with(['device', 'responsibleUser'])->find($entityId),
            'device_transfer' => DeviceTransfer::query()->with(['device', 'responsibleUser', 'transferTo'])->find($entityId),
            'audit_log' => AuditLog::query()->find($entityId),
            default => null,
        };
    }

    private static function deviceTypeIndexUrl(DeviceType $deviceType): string
    {
        $position = DeviceType::query()
            ->where(function ($query) use ($deviceType) {
                $query->where('type_name', '<', $deviceType->type_name)
                    ->orWhere(function ($subQuery) use ($deviceType) {
                        $subQuery->where('type_name', $deviceType->type_name)
                            ->where('id', '<=', $deviceType->id);
                    });
            })
            ->count();

        $page = (int) ceil(max($position, 1) / 20);

        return route('device-types.index', [
            'page' => max($page, 1),
            'highlight_id' => 'device-type-'.$deviceType->id,
        ]);
    }
}
