<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
                    'filtered' => 0,
                    'today' => 0,
                    'critical' => 0,
                    'active_users' => 0,
                    'latest' => null,
                ],
                'entityTypes' => collect(),
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
            'filtered' => (clone $logQuery)->count(),
            'today' => AuditLog::query()->where('timestamp', '>=', now()->startOfDay())->count(),
            'critical' => AuditLog::query()->where('severity', 'critical')->count(),
            'active_users' => AuditLog::query()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'latest' => AuditLog::query()->latest('timestamp')->first(),
        ];

        $entityTypes = AuditLog::query()
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type');

        return view('audit_log.index', compact('logs', 'filters', 'summary', 'entityTypes'));
    }
}
