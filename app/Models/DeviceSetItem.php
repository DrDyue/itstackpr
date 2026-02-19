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
        'role',
        'description',
    ];

    // Relations
    public function set(): BelongsTo
    {
        return $this->belongsTo(DeviceSet::class, 'device_set_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
