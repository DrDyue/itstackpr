<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ierīces tipa vārdnīcas modelis.
 */
class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = [
        'type_name',
        'category',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Visas ierīces, kurām piešķirts šis tips.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
