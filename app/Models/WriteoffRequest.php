<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lietotāja pieteikums ierīces norakstīšanai.
 */
class WriteoffRequest extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'device_id',
        'responsible_user_id',
        'reason',
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

    /**
     * Ierīce, kurai pieteikta norakstīšana.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Lietotājs, kurš pieteikumu iesniedza.
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Admins, kurš norakstīšanas pieprasījumu izskatīja.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
