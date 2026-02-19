<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $table = 'buildings';

    public $timestamps = false;

    protected $fillable = [
        'building_name',
        'address',
        'city',
        'total_floors',
        'notes',
    ];

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
