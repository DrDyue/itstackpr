<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * IerÄ«ces tipa vÄrdnÄ«cas modelis.
 */
class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = [
        'type_name',
    ];
    public $timestamps = false;

    /**
     * Visas ierÄ«ces, kurÄm pieÅÄ·irts Åis tips.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

}
