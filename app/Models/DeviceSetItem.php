<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSetItem extends Model
{
    protected $table = 'device_set_items';

    public const UPDATED_AT = null;
    public $timestamps = true; // created_at есть

    protected $fillable = [
        'device_set_id',
        'device_id',
        'quantity',
    ];

    public function set()
    {
        return $this->belongsTo(DeviceSet::class, 'device_set_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
