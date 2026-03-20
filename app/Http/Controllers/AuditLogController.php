<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AuditTrail;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAdmin();

        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'severity' => trim((string) $request->query('severity', '')),
            'entity_type' => trim((string) $request->query('entity_type', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'q' => trim((string) $request->query('q', '')),
        ];

        if (! $this->featureTableExists('audit_log')) {
            return view('audit_log.index', [
                'logs' => $this->emptyPaginator(50),
                'filters' => $filters,
                'summary' => [
                    'total' => 0,
                    'today' => 0,
                    'critical' => 0,
                ],
                'actionOptions' => collect(),
                'severityOptions' => collect(),
                'entityOptions' => collect(),
                'featureMessage' => 'Tabula audit_log sobrid nav pieejama.',
            ]);
        }

        $logQuery = AuditLog::query()
            ->with(['user'])
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['severity'] !== '', fn ($query) => $query->where('severity', $filters['severity']))
            ->when($filters['entity_type'] !== '', fn ($query) => $query->where('entity_type', $filters['entity_type']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('timestamp', '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('timestamp', '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay()))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $term = $filters['q'];

                $query->where(function ($auditQuery) use ($term) {
                    $auditQuery->where('description', 'like', '%' . $term . '%')
                        ->orWhere('entity_type', 'like', '%' . $term . '%')
                        ->orWhere('entity_id', 'like', '%' . $term . '%');
                });
            })
            ->orderByDesc('timestamp')
            ->orderByDesc('id');

        $logs = (clone $logQuery)->paginate(50)->withQueryString();

        $summary = [
            'total' => AuditLog::count(),
            'today' => AuditLog::query()->where('timestamp', '>=', now()->startOfDay())->count(),
            'critical' => AuditLog::query()->where('severity', 'critical')->count(),
        ];

        $actionOptions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action) => [
                'value' => $action,
                'label' => AuditTrail::actionLabel($action),
                'search' => AuditTrail::actionLabel($action) . ' ' . $action,
            ]);

        $severityOptions = AuditLog::query()
            ->select('severity')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity')
            ->map(fn (string $severity) => [
                'value' => $severity,
                'label' => AuditTrail::severityLabel($severity),
                'search' => AuditTrail::severityLabel($severity) . ' ' . $severity,
            ]);

        $entityOptions = AuditLog::query()
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type')
            ->map(fn (string $entityType) => [
                'value' => $entityType,
                'label' => AuditTrail::entityLabel($entityType),
                'search' => AuditTrail::entityLabel($entityType) . ' ' . $entityType,
            ]);

        return view('audit_log.index', compact('logs', 'filters', 'summary', 'actionOptions', 'severityOptions', 'entityOptions'));
    }
}
