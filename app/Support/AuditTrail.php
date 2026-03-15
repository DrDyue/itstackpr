<?php

namespace App\Support;

use App\Models\AuditLog;
use Throwable;

class AuditTrail
{
    public static function write(
        ?int $userId,
        string $action,
        string $entityType,
        ?string $entityId,
        string $description,
        string $severity = 'info'
    ): void {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'severity' => $severity,
            ]);
        } catch (Throwable) {
            // Backup actions should still succeed if audit persistence is temporarily unavailable.
        }
    }
}
