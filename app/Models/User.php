<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * LietotÄja modelis ar lomÄm un skatÄ«ÅanÄs reÅ¾Ä«miem.
 *
 * Å eit glabÄjas gan autentifikÄcijas dati, gan arÄ« noteikumi,
 * kas nosaka, ko lietotÄjs drÄ«kst redzÄ“t admina un lietotÄja skatÄ.
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
     * Scope aktÄ«vo lietotÄju atlasÄ«Åanai.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * IerÄ«ces, kuras lietotÄjs ir izveidojis.
     */
    public function createdDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'created_by');
    }

    /**
     * IerÄ«ces, kas ÅobrÄ«d piesaistÄ«tas lietotÄjam.
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
     * Remontie, kurus Åis lietotÄjs ir pieÅ†Ä“mis un izpilda.
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
     * Admins un IT darbinieks sistÄ“mÄ izmanto vienu paplaÅinÄto tiesÄ«bu kopu.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_IT_WORKER], true);
    }

    /**
     * Projekta biznesa loÄ£ikÄ IT darbinieks tiek apstrÄdÄts kÄ administrators.
     */
    public function isItWorker(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Nolasa paÅreizÄ“jo skatÄ«ÅanÄs reÅ¾Ä«mu no sesijas.
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
     * Vai administrators ÅobrÄ«d strÄdÄ pilnajÄ admina skatÄ.
     */
    public function isInAdminView(): bool
    {
        return $this->isAdmin() && $this->currentViewMode() === self::VIEW_MODE_ADMIN;
    }

    /**
     * Vai lietotÄjs darbojas kÄ parasts darbinieks.
     */
    public function isInUserView(): bool
    {
        return ! $this->isAdmin() || $this->currentViewMode() === self::VIEW_MODE_USER;
    }

    /**
     * CentralizÄ“ts palÄ«gs visÄm admina darbÄ«bu pÄrbaudÄ“m.
     */
    public function canManageRequests(): bool
    {
        return $this->isInAdminView();
    }

    /**
     * Nosaka, vai lietotÄjs drÄ«kst skatÄ«t konkrÄ“to ierÄ«ci.
     */
    public function canViewDevice(Device $device): bool
    {
        return $this->canManageRequests()
            || ((int) $device->assigned_to_id === (int) $this->id);
    }

}
