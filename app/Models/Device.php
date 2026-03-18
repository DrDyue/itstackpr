<?php

namespace App\Models;

use App\Support\DeviceAssetManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $table = 'devices';

    protected $fillable = [
        'code',
        'name',
        'device_type_id',
        'model',
        'status',
        'building_id',
        'room_id',
        'assigned_user_id',
        'purchase_date',
        'purchase_price',
        'warranty_until',
        'warranty_photo_name',
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
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

    public function sets()
    {
        return $this->belongsToMany(DeviceSet::class, 'device_set_items')
            ->withPivot(['quantity', 'role', 'description']);
    }

    public function deviceSetItems(): HasMany
    {
        return $this->hasMany(DeviceSetItem::class);
    }

    public function deviceImageUrl(): ?string
    {
        return app(DeviceAssetManager::class)->url($this->device_image_url);
    }

    public function warrantyImageUrl(): ?string
    {
        return app(DeviceAssetManager::class)->url($this->warranty_photo_name);
    }

    public function deviceImageThumbUrl(): ?string
    {
        return app(DeviceAssetManager::class)->thumbUrl($this->device_image_url);
    }

    public function warrantyImageThumbUrl(): ?string
    {
        return app(DeviceAssetManager::class)->thumbUrl($this->warranty_photo_name);
    }
}
