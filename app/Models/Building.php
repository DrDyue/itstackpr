<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Visas ierīces, kas šaistītas ar šo ēku.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
