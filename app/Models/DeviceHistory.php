<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    // Relations
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
