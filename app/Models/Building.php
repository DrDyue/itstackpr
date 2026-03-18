<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Relations
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
