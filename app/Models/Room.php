<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Telpas modelis.
 *
 * Telpa ir piešaiste gan fiziskai atrašanās vietai, gan arī noliktavas loģikai.
 */
class Room extends Model
{
    protected $table = 'rooms';

    protected $fillable = [
        'building_id',
        'floor_number',
        'room_number',
        'room_name',
        'user_id',
        'department',
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
     * Ēka, kurā atrodas telpa.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Lietotājs, kurš atbild par telpu, ja tāds piešķirts.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ierīces, kas novietotas šajā telpā.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
