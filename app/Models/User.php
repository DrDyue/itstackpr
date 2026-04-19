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

    public const ROLE_IT_WORKER = 'it_worker';

    public const ROLE_USER = 'user';

    public const VIEW_MODE_ADMIN = 'admin';

    public const VIEW_MODE_USER = 'user';

    public const VIEW_MODE_SESSION_KEY = 'user_view_mode';

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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'password' => 'hashed',
        ];
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

    /**
     * Admins un IT darbinieks sistēmā izmanto vienu paplašināto tiesību kopu.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_IT_WORKER], true);
    }

    /**
     * Projekta biznesa loģikā IT darbinieks tiek apstrādāts kā administrators.
     */
    public function isItWorker(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Nolasa pašreizējo skatīšanās režīmu no sesijas.
     */
    public function currentViewMode(): string
    {
        if (! $this->isAdmin()) {
            return self::VIEW_MODE_USER;
        }

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
     * Vai lietotājs darbojas kā parasts darbinieks.
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
        return $this->canManageRequests()
            || ((int) $device->assigned_to_id === (int) $this->id);
    }

}
