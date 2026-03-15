<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'enabled',
        'frequency',
        'run_at',
        'weekly_day',
        'monthly_day',
        'last_scheduled_backup_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'weekly_day' => 'integer',
            'monthly_day' => 'integer',
            'last_scheduled_backup_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'enabled' => true,
                'frequency' => 'daily',
                'run_at' => '02:00:00',
                'weekly_day' => 1,
                'monthly_day' => 1,
            ]
        );
    }
}
