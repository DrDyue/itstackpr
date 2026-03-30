<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Remonta izpildes ieraksts.
 *
 * Atšķirībā no remonta pieprasījuma šis modelis apraksta pašu darbu,
 * kas tiek veikts ar ierīci pēc apstiprināšanas.
 */
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

    /**
     * Ierīce, kurai tiek veikts remonts.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Lietotājs, kurš sākotnēji pieteica problēmu.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issue_reported_by');
    }

    public function executor(): BelongsTo
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

    /**
     * Apstiprinātājs tiek ņemts no remonta ieraksta vai no šaistītā pieprasījuma.
     */
    public function getApprovalActorAttribute(): ?User
    {
        if ($this->relationLoaded('acceptedBy') && $this->acceptedBy) {
            return $this->acceptedBy;
        }

        if ($this->relationLoaded('request') && $this->request?->relationLoaded('reviewedBy') && $this->request->reviewedBy) {
            return $this->request->reviewedBy;
        }

        return null;
    }

    /**
     * Ērts lasāms apstiprinātāja vārds priekš skatījumiem un tooltipiem.
     */
    public function getApprovalActorNameAttribute(): ?string
    {
        if ($this->approval_actor?->full_name) {
            return $this->approval_actor->full_name;
        }

        if ($this->accepted_by) {
            return User::query()->whereKey($this->accepted_by)->value('full_name');
        }

        if ($this->request?->reviewed_by_user_id) {
            return User::query()->whereKey($this->request->reviewed_by_user_id)->value('full_name');
        }

        return null;
    }

    /**
     * Pieprasījums, no kura izveidots remonts.
     */
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
