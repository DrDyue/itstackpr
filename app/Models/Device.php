<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'assigned_to',
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
        ];
    }

    // Relations
    public function type(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class, 'device_type_id');
    }

    public function device_type(): BelongsTo
    {
        return $this->type();
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DeviceHistory::class);
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class);
    }

    public function sets()
    {
        return $this->belongsToMany(DeviceSet::class, 'device_set_items');
    }
}
