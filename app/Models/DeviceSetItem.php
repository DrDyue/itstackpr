<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSetItem extends Model
{
    protected $table = 'device_set_items';

    public $timestamps = false;

    protected $fillable = [
        'device_set_id',
        'device_id',
        'quantity',
        'role',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // Relations
    public function deviceSet(): BelongsTo
    {
        return $this->belongsTo(DeviceSet::class, 'device_set_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
