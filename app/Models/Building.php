<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ä’kas modelis, kas apvieno telpas un ierÄ«ces fiziskÄ lÄ«menÄ«.
 */
class Building extends Model
{
    protected $table = 'buildings';

    protected $fillable = [
        'building_name',
        'address',
        'city',
        'total_floors',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Visas telpas konkrÄ“tajÄ Ä“kÄ.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Visas ierÄ«ces, kas saistÄ«tas ar Åo Ä“ku.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

}
