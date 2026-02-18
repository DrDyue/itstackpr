<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    protected $table = 'device_types';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'category',
        'icon_name',
        'description',
        'expected_lifetime_years',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class, 'device_type_id');
    }
}
