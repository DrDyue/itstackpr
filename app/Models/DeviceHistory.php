<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceHistory extends Model
{
    protected $table = 'device_history';

    // у нас нет created_at/updated_at, вместо этого поле timestamp
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'action',
        'field_changed',
        'old_value',
        'new_value',
        'changed_by',
        'timestamp',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
