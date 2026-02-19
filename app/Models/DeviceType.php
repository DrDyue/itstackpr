<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // Relations
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
