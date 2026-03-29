<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Lietotāja pieteikums remontam.
 *
 * Šis ir starpposms starp problēmas paziņošanu un faktisku remonta ierakstu.
 */
class RepairRequest extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'device_id',
        'responsible_user_id',
        'description',
        'status',
        'reviewed_by_user_id',
        'repair_id',
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
     * Ierīce, kurai tiek pieteikts remonts.
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
     * Admins, kurš pieteikumu apstiprināja vai noraidīja.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Remonta ieraksts, kas radās pēc apstiprināšanas.
     */
    public function repair(): HasOne
    {
        return $this->hasOne(Repair::class, 'request_id');
    }
}
