<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function createdDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'created_by');
    }

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

    public function assignedRepairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'accepted_by');
    }

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
        return $this->role === self::ROLE_ADMIN;
    }

    public function isItWorker(): bool
    {
        return $this->isAdmin();
    }

    public function canManageRequests(): bool
    {
        return $this->isAdmin();
    }
}
