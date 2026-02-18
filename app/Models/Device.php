<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'device_type_id',
        'model',
        'status_id',
        'building_id',
        'room_id',
        'assigned_to',
        'purchase_date',
        'purchase_price',
        'warranty_until',
        'warranty_photo_name',
        'serial_number',
        'manufacturer',
        'notes',
        'device_image_url',
        'created_by',
    ];

    public function type()
    {
        return $this->belongsTo(DeviceType::class, 'device_type_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
