<?php

namespace App\Models;

use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'timestamp',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'severity',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        return AuditTrail::localizedDescription($this->description, $this->entity_type);
    }

    public function getLocalizedEntityTypeAttribute(): string
    {
        return AuditTrail::entityLabel($this->entity_type);
    }

    public function getLocalizedActionAttribute(): string
    {
        return AuditTrail::actionLabel($this->action);
    }

    public function getLocalizedSeverityAttribute(): string
    {
        return AuditTrail::severityLabel($this->severity);
    }

    public function getEntityReferenceAttribute(): string
    {
        return AuditTrail::entityReference($this->entity_type, $this->entity_id);
    }

    public function getEntityUrlAttribute(): ?string
    {
        return AuditTrail::entityUrl($this->entity_type, $this->entity_id);
    }
}
