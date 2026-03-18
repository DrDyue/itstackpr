<?php

namespace App\Models;

use App\Support\DeviceAssetManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REPAIR = 'repair';
    public const STATUS_WRITEOFF = 'writeoff';

    protected $table = 'devices';

    protected $fillable = [
        'code',
        'name',
        'device_type_id',
        'model',
        'status',
        'building_id',
        'room_id',
        'assigned_to_id',
        'purchase_date',
        'purchase_price',
        'warranty_until',
        'serial_number',
        'manufacturer',
        'notes',
        'device_image_url',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_until' => 'date',
            'purchase_price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class, 'device_type_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->assignedTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class);
    }

    public function activeRepair(): HasOne
    {
        return $this->hasOne(Repair::class)
            ->whereIn('status', ['waiting', 'in-progress'])
            ->latestOfMany('id');
    }

    public function repairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class);
    }

    public function writeoffRequests(): HasMany
    {
        return $this->hasMany(WriteoffRequest::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(DeviceTransfer::class);
    }

    public function deviceImageUrl(): ?string
    {
        return app(DeviceAssetManager::class)->url($this->device_image_url);
    }

    public function deviceImageThumbUrl(): ?string
    {
        return app(DeviceAssetManager::class)->thumbUrl($this->device_image_url);
    }

    public function getAssignedUserIdAttribute(): mixed
    {
        return $this->attributes['assigned_to_id'] ?? $this->attributes['assigned_user_id'] ?? null;
    }

    public function setAssignedUserIdAttribute(mixed $value): void
    {
        $this->attributes['assigned_to_id'] = $value;
        $this->attributes['assigned_user_id'] = $value;
    }

    public function getStatusAttribute(?string $value): string
    {
        return self::normalizeStatus($value);
    }

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = self::normalizeStatus($value);
    }

    public static function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'repair' => self::STATUS_REPAIR,
            'writeoff', 'written_off' => self::STATUS_WRITEOFF,
            default => self::STATUS_ACTIVE,
        };
    }
}
