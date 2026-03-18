<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTransfer extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'device_id',
        'responsible_user_id',
        'transfered_to_id',
        'transfer_reason',
        'status',
        'reviewed_by_user_id',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function transferTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transfered_to_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getTransferToUserIdAttribute(): mixed
    {
        return $this->attributes['transfered_to_id'] ?? $this->attributes['transfer_to_user_id'] ?? null;
    }

    public function setTransferToUserIdAttribute(mixed $value): void
    {
        $this->attributes['transfered_to_id'] = $value;
        $this->attributes['transfer_to_user_id'] = $value;
    }
}
