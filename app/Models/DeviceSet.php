<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSet extends Model
{
    protected $table = 'device_sets';

    protected $fillable = [
        'set_name',
        'set_code',
        'status',
        'room_id',
        'assigned_to',
        'notes',
        'created_by',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function items()
    {
        return $this->hasMany(DeviceSetItem::class, 'device_set_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
