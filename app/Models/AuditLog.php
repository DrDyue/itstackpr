<?php

namespace App\Models;

use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audita žurnāla ieraksts.
 *
 * Šis modelis glabā sistēmas notikumu vēsturi komisijas un administrēšanas vajadzībām.
 */
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

    /**
     * Lietotājs, kurš veica reģistrēto darbību.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lokalizēts apraksts cilvēkam saprotamā formā.
     */
    public function getLocalizedDescriptionAttribute(): string
    {
        // Frontend un Blade skati lieto šo accessor, lai neanalizētu izejas audit text paši.
        // Visa lokalizācija un "cilvēkam saprotamā" pārtulkošana paliek centralizēti AuditTrail klasē.
        return AuditTrail::localizedDescription($this->description, $this->entity_type);
    }

    public function getCompactDescriptionAttribute(): string
    {
        return AuditTrail::compactDescription($this->description, $this->entity_type, $this->action);
    }

    /**
     * Strukturētas izmaiņas auditam: lauks, vecā vērtība un jaunā vērtība.
     *
     * @return array<int, array{field:string, old:string, new:string}>
     */
    public function getChangeDetailsAttribute(): array
    {
        return AuditTrail::changeDetails($this->description, $this->entity_type);
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
        // Vēsturiskais audita ieraksts pēc vajadzības var atgriezt saiti uz pašreizējo entītijas vietu sistēmā,
        // ja vien attiecīgais ieraksts joprojām eksistē.
        return AuditTrail::entityUrl($this->entity_type, $this->entity_id);
    }
}
