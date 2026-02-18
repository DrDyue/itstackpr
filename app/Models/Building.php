<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
