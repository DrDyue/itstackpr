<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ierīces tipa vārdnīcas modelis.
 */
class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = [
        'type_name',
    ];
    public $timestamps = false;

    /**
     * Visas ierīces, kurām piešķirts šis tips.
     */
    public function devices(): HasMany
    {
        // Šo relāciju izmanto ne tikai tipu detalizācijai, bet arī drošai dzēšanas kontrolei:
        // ja tipam vēl ir ierīces, kontrolieris tipu neļauj dzēst.
        return $this->hasMany(Device::class);
    }

}
