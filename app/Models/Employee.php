<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $table = 'employees';

    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'job_title',
        'is_active',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // Relations
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
