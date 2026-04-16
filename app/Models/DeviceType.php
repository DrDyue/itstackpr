<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Ierīces tipa vārdnīcas modelis.
 */
class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = [
        'type_name',
    ];
    public $timestamps = false;

    /**
     * Visas ierīces, kurām piešķirts šis tips.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Iegūt visus ierīces tipus ar kešu (1 stundu).
     */
    public static function cached()
    {
        return Cache::remember('device_types_all', 3600, function () {
            return self::orderBy('type_name')->get();
        });
    }

    /**
     * Notīrīt keš pēc izmaiņām.
     */
    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('device_types_all');
        });

        static::deleted(function () {
            Cache::forget('device_types_all');
        });
    }
}
