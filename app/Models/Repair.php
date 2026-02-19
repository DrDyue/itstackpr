<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repair extends Model
{
    protected $table = 'repairs';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'description',
        'status',
        'repair_type',
        'priority',
        'start_date',
        'estimated_completion',
        'actual_completion',
        'cost',
        'vendor_name',
        'vendor_contact',
        'invoice_number',
        'issue_reported_by',
        'assigned_to',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'estimated_completion' => 'date',
            'actual_completion' => 'date',
            'cost' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // Relations
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issue_reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
