<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = [
        'building_id',
        'floor_number',
        'room_number',
        'room_name',
        'employee_id',
        'department',
        'notes',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
