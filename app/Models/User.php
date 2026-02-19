<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'password',
        'role',
        'is_active',
        'last_login',
        'created_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'created_by');
    }

    public function deviceHistories(): HasMany
    {
        return $this->hasMany(DeviceHistory::class, 'changed_by');
    }

    public function repairsReported(): HasMany
    {
        return $this->hasMany(Repair::class, 'issue_reported_by');
    }

    public function repairsAssigned(): HasMany
    {
        return $this->hasMany(Repair::class, 'assigned_to');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
