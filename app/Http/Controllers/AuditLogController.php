<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditTrail;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Audita žurnāla skats ar filtrēšanu, kārtošanu un manuālu ierakstu meklēšanu.
 */
class AuditLogController extends Controller
{
    /**
     * Parāda auditam reģistrētās darbības administratoram.
     */
    public function index(Request $request)
    {
        $this->requireAdmin();
        $user = $this->user();

        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'entity_type' => trim((string) $request->query('entity_type', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'lookup' => trim((string) $request->query('lookup', $request->query('search', $request->query('q', '')))),
            'sort' => trim((string) $request->query('sort', 'timestamp')),
            'direction' => trim((string) $request->query('direction', 'desc')),
            'severities' => collect((array) $request->query('severity', []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];

        if (! $request->ajax()) {
            AuditTrail::viewed($user, 'AuditLog', null, 'Atvērts audita žurnāls.');
        }

        if (! $this->featureTableExists('audit_log')) {
            return view('audit_log.index', [
                'logs' => collect(),
                'filters' => $filters,
                'summary' => [
                    'total' => 0,
                    'today' => 0,
                    'info' => 0,
                    'warning' => 0,
                    'error' => 0,
                    'critical' => 0,
                ],
                'actionOptions' => collect(),
                'severityOptions' => collect(),
                'actorOptions' => collect(),
                'entityOptions' => collect(),
                'featureMessage' => 'Tabula audit_log šobrīd nav pieejama.',
            ]);
        }

        $baseLogQuery = AuditLog::query()
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['severities'] !== [], fn ($query) => $query->whereIn('severity', $filters['severities']))
            ->when($filters['entity_type'] !== '', fn ($query) => $query->where('entity_type', $filters['entity_type']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('timestamp', '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('timestamp', '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay()));

        $logQuery = (clone $baseLogQuery)->with('user:id,full_name,email');

        $sortField = $filters['sort'];
        $sortDirection = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        switch ($sortField) {
            case 'timestamp':
            case 'time':
                $logQuery->orderBy('timestamp', $sortDirection)->orderBy('id', $sortDirection);
                break;
            case 'user':
            case 'user_id':
                $logQuery->leftJoin('users', 'audit_log.user_id', '=', 'users.id')
                    ->select('audit_log.*')
                    ->orderByRaw('COALESCE(users.full_name, "Sistēma") '.$sortDirection)
                    ->orderBy('timestamp', 'desc');
                break;
            case 'action':
                $logQuery->orderBy('action', $sortDirection)->orderBy('timestamp', 'desc');
                break;
            case 'entity_type':
            case 'object':
                $logQuery->orderBy('entity_type', $sortDirection)->orderBy('timestamp', 'desc');
                break;
            case 'severity':
                $logQuery->orderByRaw(
                    'CASE severity
                        WHEN "critical" THEN 1
                        WHEN "error" THEN 2
                        WHEN "warning" THEN 3
                        ELSE 4
                    END '.$sortDirection
                )->orderBy('timestamp', 'desc');
                break;
            case 'description':
                $logQuery->orderBy('description', $sortDirection)->orderBy('timestamp', 'desc');
                break;
            default:
                $logQuery->orderBy('timestamp', 'desc')->orderBy('id', 'desc');
        }

        $logs = (clone $logQuery)->get(['id', 'timestamp', 'user_id', 'action', 'entity_type', 'entity_id', 'description', 'severity']);

        $summaryRow = AuditLog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN timestamp >= ? THEN 1 ELSE 0 END) as today', [now()->startOfDay()])
            ->selectRaw('SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as info', ['info'])
            ->selectRaw('SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as warning', ['warning'])
            ->selectRaw('SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as error', ['error'])
            ->selectRaw('SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) as critical', ['critical'])
            ->first();

        $summary = [
            'total' => (int) ($summaryRow->total ?? 0),
            'today' => (int) ($summaryRow->today ?? 0),
            'info' => (int) ($summaryRow->info ?? 0),
            'warning' => (int) ($summaryRow->warning ?? 0),
            'error' => (int) ($summaryRow->error ?? 0),
            'critical' => (int) ($summaryRow->critical ?? 0),
        ];

        $actionOptions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action) => [
                'value' => $action,
                'label' => AuditTrail::actionLabel($action),
                'search' => AuditTrail::actionLabel($action).' '.$action,
            ]);

        $severityOptions = AuditLog::query()
            ->select('severity')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity')
            ->map(fn (string $severity) => [
                'value' => $severity,
                'label' => AuditTrail::severityLabel($severity),
                'search' => AuditTrail::severityLabel($severity).' '.$severity,
            ]);

        $actorIds = AuditLog::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $actorOptions = User::query()
            ->whereIn('id', $actorIds)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email'])
            ->map(fn (User $userOption) => [
                'value' => (string) $userOption->id,
                'label' => $userOption->full_name,
                'description' => $userOption->email,
                'search' => implode(' ', array_filter([$userOption->full_name, $userOption->email])),
            ]);

        $entityOptions = AuditLog::query()
            ->select('entity_type')
            ->distinct()
            ->pluck('entity_type')
            ->filter(fn (string $entityType) => AuditTrail::isKnownEntityType($entityType))
            ->map(fn (string $entityType) => [
                'value' => $entityType,
                'label' => AuditTrail::entityLabel($entityType),
                'search' => AuditTrail::entityLabel($entityType).' '.$entityType,
            ])
            ->sortBy('label')
            ->values();

        $this->auditAuditLogInteractions($request, $user, $filters);

        return view('audit_log.index', compact(
            'logs',
            'filters',
            'summary',
            'actionOptions',
            'severityOptions',
            'actorOptions',
            'entityOptions'
        ));
    }

    /**
     * Atrod audita ierakstu pēc ID, apraksta, darbības vai lietotāja.
     */
    public function findEntry(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $user = $this->user();

        $search = trim((string) $request->query('lookup', $request->query('search', $request->query('q', ''))));
        if ($search === '') {
            return response()->json(['found' => false, 'page' => 1]);
        }

        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'entity_type' => trim((string) $request->query('entity_type', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'severities' => collect((array) $request->query('severity', []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];

        $logs = AuditLog::query()
            ->with('user:id,full_name,email')
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['severities'] !== [], fn ($query) => $query->whereIn('severity', $filters['severities']))
            ->when($filters['entity_type'] !== '', fn ($query) => $query->where('entity_type', $filters['entity_type']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('timestamp', '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('timestamp', '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay()))
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->get(['id', 'description', 'action', 'entity_type', 'severity', 'user_id', 'timestamp']);

        $needle = mb_strtolower($search);
        $foundIndex = $logs->search(function (AuditLog $log) use ($needle) {
            $haystack = mb_strtolower(implode(' ', array_filter([
                (string) $log->id,
                $log->description,
                $log->localized_description,
                $log->localized_action,
                $log->localized_entity_type,
                $log->localized_severity,
                $log->user?->full_name,
                $log->user?->email,
            ])));

            return str_contains($haystack, $needle);
        });

        if ($foundIndex === false) {
            return response()->json(['found' => false, 'page' => 1]);
        }

        AuditTrail::search($user, 'AuditLog', $search, 'Audita žurnālā meklēts ieraksts: '.$search);

        return response()->json([
            'found' => true,
            'page' => 1,
            'term' => $search,
            'highlight_id' => 'audit-log-'.$logs->values()[(int) $foundIndex]->id,
        ]);
    }

    /**
     * Reģistrē audita saraksta filtrēšanas un kārtošanas darbības.
     *
     * @param  array<string, mixed>  $filters
     */
    private function auditAuditLogInteractions(Request $request, User $user, array $filters): void
    {
        $activeFilters = array_filter([
            'darbība' => $filters['action'] ?: null,
            'objekts' => $filters['entity_type'] ?: null,
            'lietotājs' => $filters['user_id'] ?: null,
            'no datuma' => $filters['date_from'] ?: null,
            'līdz datumam' => $filters['date_to'] ?: null,
            'svarīgums' => ! empty($filters['severities']) ? implode(', ', $filters['severities']) : null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($activeFilters !== []) {
            AuditTrail::filter(
                $user,
                'AuditLog',
                $activeFilters,
                'Audita žurnāls filtrēts pēc: '.implode(', ', array_map(
                    fn ($label, $value) => $label.' — '.$value,
                    array_keys($activeFilters),
                    array_values($activeFilters),
                ))
            );
        }

        $sortField = trim((string) $request->query('sort', ''));
        $sortDirection = trim((string) $request->query('direction', ''));

        if ($sortField !== '' && ! ($sortField === 'timestamp' && $sortDirection === 'desc')) {
            AuditTrail::sort(
                $user,
                'AuditLog',
                $sortField,
                $sortDirection === 'asc' ? 'asc' : 'desc',
                'Audita žurnāls sakārtots pēc '.$sortField.' '.($sortDirection === 'asc' ? 'augošā' : 'dilstošā').' secībā.'
            );
        }
    }
}
