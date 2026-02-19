<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceSet extends Model
{
    protected $table = 'device_sets';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // Relations
    public function items(): HasMany
    {
        return $this->hasMany(DeviceSetItem::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_set_items');
    }
}
