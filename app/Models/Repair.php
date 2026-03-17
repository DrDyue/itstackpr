<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Repair extends Model
{
    protected $table = 'repairs';

    protected $fillable = [
        'device_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'accepted_by_user_id',
        'description',
        'status',
        'device_status_before_repair',
        'repair_type',
        'priority',
        'start_date',
        'estimated_completion',
        'actual_completion',
        'diagnosis',
        'resolution_notes',
        'cost',
        'vendor_name',
        'vendor_contact',
        'invoice_number',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'estimated_completion' => 'date',
            'actual_completion' => 'date',
            'cost' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function request(): HasOne
    {
        return $this->hasOne(RepairRequest::class, 'repair_id');
    }
}
