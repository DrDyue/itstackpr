<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceHistory extends Model
{
    protected $table = 'device_history';

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

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
