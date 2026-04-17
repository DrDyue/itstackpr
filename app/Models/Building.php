<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Ēkas modelis, kas apvieno telpas un ierīces fiziskā līmenī.
 */
class Building extends Model
{
    protected $table = 'buildings';

    protected $fillable = [
        'building_name',
        'address',
        'city',
        'total_floors',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Visas telpas konkrētajā ēkā.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Visas ierīces, kas saistītas ar šo ēku.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Iegūt visas ēkas ar kešu (1 stundu).
     */
    public static function cached()
    {
        return Cache::remember('buildings_all', 3600, function () {
            return self::orderBy('building_name')->get();
        });
    }

    /**
     * Notīrīt keš pēc izmaiņām.
     */
    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('buildings_all');
        });

        static::deleted(function () {
            Cache::forget('buildings_all');
        });
    }
}
