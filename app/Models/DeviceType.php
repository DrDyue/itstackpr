<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = [
        'type_name',
        'category',
        'description',
        'expected_lifetime_years',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relations
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
