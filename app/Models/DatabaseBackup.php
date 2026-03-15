<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackup extends Model
{
    protected $fillable = [
        'name',
        'disk',
        'file_path',
        'format',
        'database_connection',
        'database_driver',
        'database_name',
        'trigger_type',
        'creator_type',
        'created_by_user_id',
        'file_size_bytes',
        'duration_ms',
        'total_tables',
        'total_rows',
        'is_current',
        'restore_count',
        'last_restored_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'duration_ms' => 'integer',
            'total_tables' => 'integer',
            'total_rows' => 'integer',
            'is_current' => 'boolean',
            'restore_count' => 'integer',
            'last_restored_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
