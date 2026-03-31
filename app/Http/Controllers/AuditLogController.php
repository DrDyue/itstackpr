<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AuditTrail;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Audita žurnāla skats ar filtriem un kopsavilkumiem.
 */
class AuditLogController extends Controller
{
    /**
     * Parāda auditā reģistrētās darbības administratoram.
     */
    public function index(Request $request)
    {
        $this->requireAdmin();

        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'severity' => trim((string) $request->query('severity', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'search' => trim((string) $request->query('search', $request->query('q', ''))),
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
                'actorOptions' => collect(),
                'featureMessage' => 'Tabula audit_log šobrīd nav pieejama.',
            ]);
        }

        $logQuery = AuditLog::query()
            ->with(['user'])
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['severity'] !== '', fn ($query) => $query->where('severity', $filters['severity']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('timestamp', '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('timestamp', '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay()))
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

        $actorOptions = AuditLog::query()
            ->with('user')
            ->whereNotNull('user_id')
            ->get()
            ->map(fn (AuditLog $log) => $log->user)
            ->filter()
            ->unique('id')
            ->sortBy('full_name')
            ->values()
            ->map(fn ($user) => [
                'value' => (string) $user->id,
                'label' => $user->full_name,
                'description' => $user->email,
                'search' => implode(' ', array_filter([$user->full_name, $user->email])),
            ]);

        return view('audit_log.index', compact('logs', 'filters', 'summary', 'actionOptions', 'severityOptions', 'actorOptions'));
    }

    /**
     * Atrod audita ierakstu pēc apraksta vai ID filtrētajā sarakstā.
     */
    public function findEntry(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'severity' => trim((string) $request->query('severity', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $logs = AuditLog::query()
            ->with(['user'])
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['severity'] !== '', fn ($query) => $query->where('severity', $filters['severity']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('timestamp', '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('timestamp', '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay()))
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->get(['id', 'description', 'user_id', 'timestamp']);

        $needle = mb_strtolower($search);
        $foundIndex = $logs->search(function (AuditLog $log) use ($needle) {
            $haystack = mb_strtolower(implode(' ', array_filter([
                (string) $log->id,
                $log->description,
                $log->user?->full_name,
                $log->user?->email,
            ])));

            return str_contains($haystack, $needle);
        });

        if ($foundIndex === false) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        return response()->json([
            'found' => true,
            'page' => intdiv((int) $foundIndex, 50) + 1,
            'term' => $search,
            'highlight_id' => 'audit-log-'.$logs->values()[(int) $foundIndex]->id,
        ]);
    }
}
