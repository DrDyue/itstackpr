<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repair extends Model
{
    protected $table = 'repairs';

    protected $fillable = [
        'device_id',
        'description',
        'status',
        'repair_type',
        'priority',
        'start_date',
        'end_date',
        'cost',
        'vendor_name',
        'vendor_contact',
        'invoice_number',
        'issue_reported_by',
        'accepted_by',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
        return $this->belongsTo(User::class, 'issue_reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RepairRequest::class, 'request_id');
    }

    public function getReportedByUserIdAttribute(): mixed
    {
        return $this->attributes['issue_reported_by'] ?? $this->attributes['reported_by_user_id'] ?? null;
    }

    public function setReportedByUserIdAttribute(mixed $value): void
    {
        $this->attributes['issue_reported_by'] = $value;
        $this->attributes['reported_by_user_id'] = $value;
    }

    public function getAcceptedByUserIdAttribute(): mixed
    {
        return $this->attributes['accepted_by'] ?? $this->attributes['accepted_by_user_id'] ?? null;
    }

    public function setAcceptedByUserIdAttribute(mixed $value): void
    {
        $this->attributes['accepted_by'] = $value;
        $this->attributes['accepted_by_user_id'] = $value;
    }

    public function getActualCompletionAttribute(): mixed
    {
        return $this->attributes['end_date'] ?? $this->attributes['actual_completion'] ?? null;
    }

    public function setActualCompletionAttribute(mixed $value): void
    {
        $this->attributes['end_date'] = $value;
        $this->attributes['actual_completion'] = $value;
    }
}
