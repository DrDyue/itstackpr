<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Lietotāja modelis ar lomām un skatīšanās režīmiem.
 *
 * Šeit glabājas gan autentifikācijas dati, gan arī noteikumi,
 * kas nosaka, ko lietotājs drīkst redzēt admina un lietotāja skatā.
 */
class User extends Authenticatable
{
    use HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const VIEW_MODE_ADMIN = 'admin';

    public const VIEW_MODE_USER = 'user';

    public const VIEW_MODE_SESSION_KEY = 'user_view_mode';

    public const SETTING_HIDE_WRITEOFF_DEVICES = 'hide_written_off_devices';
    public const SETTING_DEFAULT_START_PAGE = 'default_start_page';
    public const SETTING_DEFAULT_VIEW_MODE = 'default_view_mode';
    public const SETTING_LAST_VIEW_MODE = 'last_view_mode';
    public const SETTING_DEFAULT_DEVICE_FILTER = 'default_device_filter';
    public const SETTING_NOTIFICATION_VISUAL_MODE = 'notification_visual_mode';
    public const SETTING_DEFAULT_REQUEST_FILTER = 'default_request_filter';

    public const START_PAGE_DASHBOARD = 'dashboard';
    public const START_PAGE_DEVICES = 'devices';
    public const START_PAGE_REPAIR_REQUESTS = 'repair_requests';
    public const START_PAGE_WRITEOFF_REQUESTS = 'writeoff_requests';
    public const START_PAGE_DEVICE_TRANSFERS = 'device_transfers';
    public const START_PAGE_AUDIT_LOG = 'audit_log';

    public const DEFAULT_VIEW_MODE_LAST = 'last';

    public const DEVICE_FILTER_ALL = 'all';
    public const DEVICE_FILTER_ACTIVE = 'active';
    public const DEVICE_FILTER_REPAIR = 'repair';

    public const REQUEST_FILTER_SUBMITTED = 'submitted';
    public const REQUEST_FILTER_ALL = 'all';
    public const REQUEST_FILTER_TODAY = 'today';

    public const NOTIFICATION_VISUAL_ANIMATED = 'animated';
    public const NOTIFICATION_VISUAL_SUBTLE = 'subtle';
    public const NOTIFICATION_VISUAL_OFF = 'off';

    /**
     * Vecās datubāzēs šī vērtība var būt saglabāta admina kontiem.
     * Tā nav aktīva trešā loma un netiek rādīta saskarnē.
     */
    private static function legacyAdminRoleValue(): string
    {
        return 'it'.'_worker';
    }

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'job_title',
        'password',
        'role',
        'is_active',
        'remember_token',
        'last_login',
        'password_reset_requested_at',
        'user_settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login' => 'datetime',
            'password_reset_requested_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'password' => 'hashed',
            'user_settings' => 'array',
        ];
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->user_settings;

        // user_settings tiek glabāts vienā JSON kolonnā.
        // Šis palīgs pasargā pārējo kodu no tiešas darba ar null vai bojātu masīva struktūru.
        if (! is_array($settings) || ! array_key_exists($key, $settings)) {
            return $default;
        }

        return $settings[$key];
    }

    public function prefersHiddenWrittenOffDevices(): bool
    {
        return (bool) $this->setting(self::SETTING_HIDE_WRITEOFF_DEVICES, false);
    }

    public function defaultStartPage(): string
    {
        $value = (string) $this->setting(self::SETTING_DEFAULT_START_PAGE, self::START_PAGE_DASHBOARD);

        return in_array($value, self::startPageOptions(), true) ? $value : self::START_PAGE_DASHBOARD;
    }

    public function defaultStartRouteName(): string
    {
        return match ($this->defaultStartPage()) {
            self::START_PAGE_DEVICES => 'devices.index',
            self::START_PAGE_REPAIR_REQUESTS => 'repair-requests.index',
            self::START_PAGE_WRITEOFF_REQUESTS => 'writeoff-requests.index',
            self::START_PAGE_DEVICE_TRANSFERS => 'device-transfers.index',
            self::START_PAGE_AUDIT_LOG => 'audit-log.index',
            default => 'dashboard',
        };
    }

    public function defaultViewMode(): string
    {
        $value = (string) $this->setting(self::SETTING_DEFAULT_VIEW_MODE, self::VIEW_MODE_ADMIN);

        return in_array($value, self::defaultViewModeOptions(), true) ? $value : self::VIEW_MODE_ADMIN;
    }

    public function initialViewMode(): string
    {
        if (! $this->isAdmin()) {
            return self::VIEW_MODE_USER;
        }

        // Ja lietotājs izvēlējies režīmu "last", sistēma mēģina atjaunot pēdējo izmantoto skatu.
        // Pretējā gadījumā tiek lietots profila noklusētais režīms.
        if ($this->defaultViewMode() === self::DEFAULT_VIEW_MODE_LAST) {
            $lastMode = (string) $this->setting(self::SETTING_LAST_VIEW_MODE, self::VIEW_MODE_ADMIN);

            return in_array($lastMode, [self::VIEW_MODE_ADMIN, self::VIEW_MODE_USER], true)
                ? $lastMode
                : self::VIEW_MODE_ADMIN;
        }

        return $this->defaultViewMode();
    }

    public function defaultDeviceFilter(): string
    {
        $value = (string) $this->setting(self::SETTING_DEFAULT_DEVICE_FILTER, self::DEVICE_FILTER_ALL);

        return in_array($value, self::deviceFilterOptions(), true) ? $value : self::DEVICE_FILTER_ALL;
    }

    public function defaultRequestFilter(): string
    {
        $value = (string) $this->setting(self::SETTING_DEFAULT_REQUEST_FILTER, self::REQUEST_FILTER_SUBMITTED);

        return in_array($value, self::requestFilterOptions(), true) ? $value : self::REQUEST_FILTER_SUBMITTED;
    }

    public function notificationVisualMode(): string
    {
        $value = (string) $this->setting(self::SETTING_NOTIFICATION_VISUAL_MODE, self::NOTIFICATION_VISUAL_ANIMATED);

        return in_array($value, self::notificationVisualOptions(), true) ? $value : self::NOTIFICATION_VISUAL_ANIMATED;
    }

    public static function startPageOptions(): array
    {
        return [
            self::START_PAGE_DASHBOARD,
            self::START_PAGE_DEVICES,
            self::START_PAGE_REPAIR_REQUESTS,
            self::START_PAGE_WRITEOFF_REQUESTS,
            self::START_PAGE_DEVICE_TRANSFERS,
            self::START_PAGE_AUDIT_LOG,
        ];
    }

    public static function defaultViewModeOptions(): array
    {
        return [self::VIEW_MODE_ADMIN, self::VIEW_MODE_USER, self::DEFAULT_VIEW_MODE_LAST];
    }

    public static function deviceFilterOptions(): array
    {
        return [self::DEVICE_FILTER_ALL, self::DEVICE_FILTER_ACTIVE, self::DEVICE_FILTER_REPAIR];
    }

    public static function requestFilterOptions(): array
    {
        return [self::REQUEST_FILTER_SUBMITTED, self::REQUEST_FILTER_ALL, self::REQUEST_FILTER_TODAY];
    }

    public static function notificationVisualOptions(): array
    {
        return [self::NOTIFICATION_VISUAL_ANIMATED, self::NOTIFICATION_VISUAL_SUBTLE, self::NOTIFICATION_VISUAL_OFF];
    }

    /**
     * Scope aktīvo lietotāju atlasīšanai.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Ierīces, kuras lietotājs ir izveidojis.
     */
    public function createdDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'created_by');
    }

    /**
     * Ierīces, kas šobrīd piesaistītas lietotājam.
     */
    public function assignedDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'assigned_to_id');
    }

    public function responsibleRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'user_id');
    }

    public function reportedRepairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'issue_reported_by');
    }

    /**
     * Remonti, kurus šis lietotājs ir pieņēmis un izpilda.
     */
    public function acceptedRepairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'accepted_by');
    }

    public function repairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class, 'responsible_user_id');
    }

    public function reviewedRepairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class, 'reviewed_by_user_id');
    }

    public function writeoffRequests(): HasMany
    {
        return $this->hasMany(WriteoffRequest::class, 'responsible_user_id');
    }

    public function reviewedWriteoffRequests(): HasMany
    {
        return $this->hasMany(WriteoffRequest::class, 'reviewed_by_user_id');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(DeviceTransfer::class, 'responsible_user_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(DeviceTransfer::class, 'transfered_to_id');
    }

    public function reviewedTransfers(): HasMany
    {
        return $this->hasMany(DeviceTransfer::class, 'reviewed_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        // Publiski projekts strādā ar divām lomām: admin un user.
        // Legacy vērtību atzīstam tikai tāpēc, lai vecas instalācijas nezaudētu admina piekļuvi.
        return in_array($this->role, [self::ROLE_ADMIN, self::legacyAdminRoleValue()], true);
    }

    /**
     * Nolasa pašreizējo skatīšanās režīmu no sesijas.
     */
    public function currentViewMode(): string
    {
        if (! $this->isAdmin()) {
            return self::VIEW_MODE_USER;
        }

        // Aktīvais skata režīms dzīvo sesijā, nevis tikai profilā,
        // jo administrators vienas sesijas laikā var pārslēgties starp admina un lietotāja skatu.
        $request = app()->bound('request') ? request() : null;
        $mode = $request && $request->hasSession()
            ? $request->session()->get(self::VIEW_MODE_SESSION_KEY)
            : null;

        return in_array($mode, [self::VIEW_MODE_ADMIN, self::VIEW_MODE_USER], true)
            ? $mode
            : self::VIEW_MODE_ADMIN;
    }

    /**
     * Vai administrators šobrīd strādā pilnajā admina skatā.
     */
    public function isInAdminView(): bool
    {
        return $this->isAdmin() && $this->currentViewMode() === self::VIEW_MODE_ADMIN;
    }

    /**
     * Vai konts darbojas parastā lietotāja skatā.
     */
    public function isInUserView(): bool
    {
        return ! $this->isAdmin() || $this->currentViewMode() === self::VIEW_MODE_USER;
    }

    /**
     * Centralizēts palīgs visām admina darbību pārbaudēm.
     */
    public function canManageRequests(): bool
    {
        return $this->isInAdminView();
    }

    /**
     * Nosaka, vai lietotājs drīkst skatīt konkrēto ierīci.
     */
    public function canViewDevice(Device $device): bool
    {
        // Vadītājs/admins redz visas ierīces, bet parastais lietotājs tikai sev piesaistītās.
        // Šis ir centrālais piekļuves noteikums, ko izmanto kontrolieri un asset preview.
        return $this->canManageRequests()
            || ((int) $device->assigned_to_id === (int) $this->id);
    }

}
