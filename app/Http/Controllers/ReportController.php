<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceType;
use App\Models\Repair;
use App\Models\Room;
use App\Models\User;
use App\Support\AuditTrail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $visibleRepairs = $this->visibleRepairsQuery();
        $activeRepairs = (clone $visibleRepairs)->whereIn('status', ['waiting', 'in-progress'])->count();
        $overdueRepairs = $this->overdueRepairsQuery()->count();

        return view('reports.index', [
            'summary' => [
                'total_devices' => Device::count(),
                'broken_devices' => Device::where('status', 'broken')->count(),
                'devices_without_room' => Device::whereNull('room_id')->count(),
                'active_repairs' => $activeRepairs,
                'overdue_repairs' => $overdueRepairs,
                'completed_repairs_this_month' => (clone $visibleRepairs)->where('status', 'completed')
                    ->whereNotNull('actual_completion')
                    ->where('actual_completion', '>=', now()->startOfMonth())
                    ->count(),
                'audit_today' => AuditLog::where('timestamp', '>=', now()->startOfDay())->count(),
                'warning_activity' => AuditLog::where('timestamp', '>=', now()->subDays(30))
                    ->whereIn('severity', ['warning', 'error', 'critical'])
                    ->count(),
            ],
            'repairScope' => $this->repairScopeMeta(),
        ]);
    }

    public function devices(): View
    {
        $totalDevices = Device::count();
        $totalRooms = Room::count();
        $roomsWithDevices = Room::has('devices')->count();
        $months = $this->monthBuckets(6);

        $deviceTrendSource = Device::query()
            ->where('created_at', '>=', $months->first())
            ->get(['created_at']);

        $deviceTrend = $months->map(function (CarbonImmutable $month) use ($deviceTrendSource) {
            return [
                'label' => $this->monthLabel($month),
                'count' => $deviceTrendSource->filter(
                    fn (Device $device) => $device->created_at?->format('Y-m') === $month->format('Y-m')
                )->count(),
            ];
        })->values();

        $statusMetrics = collect($this->deviceStatusMeta())
            ->map(function (array $meta, string $status) use ($totalDevices) {
                $count = Device::where('status', $status)->count();

                return array_merge($meta, [
                    'status' => $status,
                    'count' => $count,
                    'share' => $totalDevices > 0 ? (int) round(($count / $totalDevices) * 100) : 0,
                ]);
            })
            ->values();

        $buildingBreakdown = Building::query()
            ->withCount([
                'rooms',
                'devices',
                'devices as active_devices_count' => fn (Builder $query) => $query->where('status', 'active'),
                'devices as repair_devices_count' => fn (Builder $query) => $query->where('status', 'repair'),
                'devices as broken_devices_count' => fn (Builder $query) => $query->where('status', 'broken'),
            ])
            ->orderByDesc('devices_count')
            ->limit(8)
            ->get()
            ->filter(fn (Building $building) => $building->devices_count > 0)
            ->values();

        $typeBreakdown = DeviceType::query()
            ->withCount('devices')
            ->orderByDesc('devices_count')
            ->limit(8)
            ->get()
            ->filter(fn (DeviceType $type) => $type->devices_count > 0)
            ->values();

        $topRooms = Room::query()
            ->with('building')
            ->withCount('devices')
            ->orderByDesc('devices_count')
            ->limit(8)
            ->get()
            ->filter(fn (Room $room) => $room->devices_count > 0)
            ->values();

        $problemDevices = Device::query()
            ->with(['type', 'building', 'room', 'activeRepair'])
            ->where(function (Builder $query) {
                $query->whereIn('status', ['broken', 'repair'])
                    ->orWhereNull('room_id')
                    ->orWhereNull('device_image_url');
            })
            ->latest('created_at')
            ->limit(24)
            ->get()
            ->sortBy(function (Device $device) {
                return match ($device->status) {
                    'broken' => 0,
                    'repair' => 1,
                    default => 2,
                };
            })
            ->take(12)
            ->values();

        return view('reports.devices', [
            'summary' => [
                'total_devices' => $totalDevices,
                'devices_without_room' => Device::whereNull('room_id')->count(),
                'rooms_with_devices' => $roomsWithDevices,
                'rooms_without_devices' => max($totalRooms - $roomsWithDevices, 0),
                'coverage_percent' => $totalRooms > 0 ? (int) round(($roomsWithDevices / $totalRooms) * 100) : 0,
            ],
            'statusMetrics' => $statusMetrics,
            'deviceTrend' => $deviceTrend,
            'deviceTrendMax' => max(1, $deviceTrend->max('count')),
            'buildingBreakdown' => $buildingBreakdown,
            'typeBreakdown' => $typeBreakdown,
            'topRooms' => $topRooms,
            'problemDevices' => $problemDevices,
        ]);
    }

    public function repairs(): View
    {
        $visibleRepairs = $this->visibleRepairsQuery();
        $totalRepairs = (clone $visibleRepairs)->count();
        $months = $this->monthBuckets(6);
        $today = now()->toDateString();

        $repairTrendSource = (clone $visibleRepairs)
            ->where(function (Builder $query) use ($months) {
                $query->where('start_date', '>=', $months->first()->toDateString())
                    ->orWhere('actual_completion', '>=', $months->first()->toDateString())
                    ->orWhere('created_at', '>=', $months->first());
            })
            ->get(['start_date', 'actual_completion', 'created_at']);

        $repairTrend = $months->map(function (CarbonImmutable $month) use ($repairTrendSource) {
            $key = $month->format('Y-m');

            return [
                'label' => $this->monthLabel($month),
                'opened' => $repairTrendSource->filter(function (Repair $repair) use ($key) {
                    $date = $repair->start_date ?? $repair->created_at;

                    return $date?->format('Y-m') === $key;
                })->count(),
                'completed' => $repairTrendSource->filter(
                    fn (Repair $repair) => $repair->actual_completion?->format('Y-m') === $key
                )->count(),
            ];
        })->values();

        $statusMetrics = collect($this->repairStatusMeta())
            ->map(function (array $meta, string $status) use ($totalRepairs, $visibleRepairs) {
                $count = (clone $visibleRepairs)->where('status', $status)->count();

                return array_merge($meta, [
                    'status' => $status,
                    'count' => $count,
                    'share' => $totalRepairs > 0 ? (int) round(($count / $totalRepairs) * 100) : 0,
                ]);
            })
            ->values();

        $priorityMetrics = collect($this->repairPriorityMeta())
            ->map(function (array $meta, string $priority) use ($totalRepairs, $visibleRepairs) {
                $count = (clone $visibleRepairs)->where('priority', $priority)->count();

                return array_merge($meta, [
                    'priority' => $priority,
                    'count' => $count,
                    'share' => $totalRepairs > 0 ? (int) round(($count / $totalRepairs) * 100) : 0,
                ]);
            })
            ->values();

        $typeMetrics = collect($this->repairTypeMeta())
            ->map(function (array $meta, string $type) use ($totalRepairs, $visibleRepairs) {
                $count = (clone $visibleRepairs)->where('repair_type', $type)->count();

                return array_merge($meta, [
                    'repair_type' => $type,
                    'count' => $count,
                    'share' => $totalRepairs > 0 ? (int) round(($count / $totalRepairs) * 100) : 0,
                ]);
            })
            ->values();

        $overdueRepairs = $this->overdueRepairsQuery()
            ->with(['device.building', 'device.room', 'assignee.employee'])
            ->orderBy('estimated_completion')
            ->orderBy('start_date')
            ->limit(12)
            ->get();

        $completedDurationSource = (clone $visibleRepairs)
            ->where('status', 'completed')
            ->whereNotNull('start_date')
            ->whereNotNull('actual_completion')
            ->get(['start_date', 'actual_completion']);

        $averageDuration = $completedDurationSource
            ->map(fn (Repair $repair) => $repair->start_date?->diffInDays($repair->actual_completion))
            ->filter(fn ($days) => $days !== null)
            ->avg();

        $assigneeLoadSource = (clone $visibleRepairs)
            ->whereNotNull('assigned_to')
            ->get(['assigned_to', 'status']);

        $assigneeUsers = User::query()
            ->with('employee')
            ->whereIn('id', $assigneeLoadSource->pluck('assigned_to')->filter()->unique())
            ->get()
            ->keyBy('id');

        $assigneeLoad = $assigneeLoadSource
            ->groupBy('assigned_to')
            ->map(function (Collection $items, string $assigneeId) use ($assigneeUsers) {
                $user = $assigneeUsers->get((int) $assigneeId);

                if (! $user) {
                    return null;
                }

                return [
                    'user' => $user,
                    'active_repairs_count' => $items->whereIn('status', ['waiting', 'in-progress'])->count(),
                    'completed_repairs_count' => $items->where('status', 'completed')->count(),
                ];
            })
            ->filter()
            ->sortByDesc('active_repairs_count')
            ->take(8)
            ->values();

        $vendorSummary = (clone $visibleRepairs)
            ->where('repair_type', 'external')
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '')
            ->get(['vendor_name', 'cost', 'status'])
            ->groupBy(fn (Repair $repair) => trim((string) $repair->vendor_name))
            ->map(function ($items, string $vendorName) {
                return [
                    'vendor_name' => $vendorName,
                    'repairs_count' => $items->count(),
                    'completed_count' => $items->where('status', 'completed')->count(),
                    'total_cost' => (float) $items->sum(fn (Repair $repair) => (float) ($repair->cost ?? 0)),
                ];
            })
            ->sortByDesc('repairs_count')
            ->take(8)
            ->values();

        $topDeviceCounts = (clone $visibleRepairs)
            ->whereNotNull('device_id')
            ->get(['device_id'])
            ->groupBy('device_id')
            ->map(fn (Collection $items, string $deviceId) => [
                'device_id' => (int) $deviceId,
                'repairs_count' => $items->count(),
            ])
            ->sortByDesc('repairs_count')
            ->take(8)
            ->values();

        $topDevicesLookup = Device::query()
            ->with('type')
            ->whereIn('id', $topDeviceCounts->pluck('device_id'))
            ->get()
            ->keyBy('id');

        $topDevices = $topDeviceCounts
            ->map(function (array $row) use ($topDevicesLookup) {
                $device = $topDevicesLookup->get($row['device_id']);

                if (! $device) {
                    return null;
                }

                $device->visible_repairs_count = $row['repairs_count'];

                return $device;
            })
            ->filter()
            ->values();

        return view('reports.repairs', [
            'summary' => [
                'total_repairs' => $totalRepairs,
                'active_repairs' => (clone $visibleRepairs)->whereIn('status', ['waiting', 'in-progress'])->count(),
                'overdue_repairs' => $overdueRepairs->count(),
                'completed_this_month' => (clone $visibleRepairs)->where('status', 'completed')
                    ->whereNotNull('actual_completion')
                    ->where('actual_completion', '>=', now()->startOfMonth())
                    ->count(),
                'average_duration' => $averageDuration !== null ? round((float) $averageDuration, 1) : null,
                'average_cost' => round((float) ((clone $visibleRepairs)->whereNotNull('cost')->avg('cost') ?? 0), 2),
                'critical_open' => (clone $visibleRepairs)->whereIn('status', ['waiting', 'in-progress'])->where('priority', 'critical')->count(),
                'external_open' => (clone $visibleRepairs)->whereIn('status', ['waiting', 'in-progress'])->where('repair_type', 'external')->count(),
                'today' => $today,
            ],
            'statusMetrics' => $statusMetrics,
            'priorityMetrics' => $priorityMetrics,
            'typeMetrics' => $typeMetrics,
            'repairTrend' => $repairTrend,
            'repairTrendMax' => max(1, $repairTrend->map(fn (array $row) => max($row['opened'], $row['completed']))->max()),
            'overdueRepairs' => $overdueRepairs,
            'assigneeLoad' => $assigneeLoad,
            'vendorSummary' => $vendorSummary,
            'topDevices' => $topDevices,
            'repairScope' => $this->repairScopeMeta(),
        ]);
    }

    public function activity(): View
    {
        $recentStart = now()->subDays(30);
        $visibleRepairIds = $this->visibleRepairIds();
        $auditRecent = AuditLog::query()
            ->with('user.employee')
            ->where('timestamp', '>=', $recentStart)
            ->orderByDesc('timestamp')
            ->get();

        $actionBreakdown = $auditRecent
            ->groupBy('action')
            ->map(fn ($items, string $action) => [
                'action' => $action,
                'label' => AuditTrail::actionLabel($action),
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $entityBreakdown = $auditRecent
            ->groupBy('entity_type')
            ->map(fn ($items, string $entityType) => [
                'entity_type' => $entityType,
                'label' => AuditTrail::entityLabel($entityType),
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $userBreakdown = $auditRecent
            ->groupBy(fn (AuditLog $log) => (string) ($log->user_id ?? 'system'))
            ->map(function ($items, string $userKey) {
                /** @var AuditLog $first */
                $first = $items->first();

                return [
                    'label' => $first?->user?->employee?->full_name ?? 'Sistema',
                    'count' => $items->count(),
                    'user_key' => $userKey,
                ];
            })
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $attentionEntries = AuditLog::query()
            ->with('user.employee')
            ->whereIn('severity', ['warning', 'error', 'critical'])
            ->latest('timestamp')
            ->limit(12)
            ->get();

        $repairEntries = AuditLog::query()
            ->with('user.employee')
            ->where('entity_type', 'Repair')
            ->when($visibleRepairIds !== null, function (Builder $query) use ($visibleRepairIds) {
                $query->whereIn('entity_id', $visibleRepairIds->map(fn (int $id) => (string) $id)->all());
            })
            ->latest('timestamp')
            ->limit(10)
            ->get();

        $deviceHistoryEntries = DeviceHistory::query()
            ->with(['device', 'changedBy.employee'])
            ->latest('timestamp')
            ->limit(10)
            ->get();

        return view('reports.activity', [
            'summary' => [
                'audit_today' => AuditLog::where('timestamp', '>=', now()->startOfDay())->count(),
                'attention_30_days' => AuditLog::where('timestamp', '>=', $recentStart)
                    ->whereIn('severity', ['warning', 'error', 'critical'])
                    ->count(),
                'repair_events_30_days' => AuditLog::where('timestamp', '>=', $recentStart)
                    ->where('entity_type', 'Repair')
                    ->when($visibleRepairIds !== null, function (Builder $query) use ($visibleRepairIds) {
                        $query->whereIn('entity_id', $visibleRepairIds->map(fn (int $id) => (string) $id)->all());
                    })
                    ->count(),
                'device_history_30_days' => DeviceHistory::where('timestamp', '>=', $recentStart)->count(),
            ],
            'actionBreakdown' => $actionBreakdown,
            'entityBreakdown' => $entityBreakdown,
            'userBreakdown' => $userBreakdown,
            'attentionEntries' => $attentionEntries,
            'repairEntries' => $repairEntries,
            'deviceHistoryEntries' => $deviceHistoryEntries,
            'repairScope' => $this->repairScopeMeta(),
        ]);
    }

    private function overdueRepairsQuery(): Builder
    {
        $today = now()->toDateString();
        $staleDate = now()->subDays(14)->toDateString();

        return $this->visibleRepairsQuery()
            ->whereIn('status', ['waiting', 'in-progress'])
            ->where(function (Builder $query) use ($today, $staleDate) {
                $query->where(function (Builder $datedQuery) use ($today) {
                    $datedQuery->whereNotNull('estimated_completion')
                        ->whereDate('estimated_completion', '<', $today);
                })->orWhere(function (Builder $staleQuery) use ($staleDate) {
                    $staleQuery->whereNull('estimated_completion')
                        ->whereDate('start_date', '<', $staleDate);
                });
            });
    }

    private function visibleRepairsQuery(): Builder
    {
        $query = Repair::query();
        $user = auth()->user();

        if (! $user || $user->role === 'admin') {
            return $query;
        }

        $employeeId = $user->employee_id;

        return $query->where(function (Builder $builder) use ($user, $employeeId) {
            $builder->where('assigned_to', $user->id)
                ->orWhere(function (Builder $reporterBuilder) use ($user, $employeeId) {
                    if ($this->supportsReportedEmployeeColumn() && $employeeId !== null) {
                        $reporterBuilder->where('reported_employee_id', $employeeId);

                        return;
                    }

                    $reporterBuilder->where('issue_reported_by', $user->id);
                });
        });
    }

    private function visibleRepairIds(): ?Collection
    {
        $user = auth()->user();

        if (! $user || $user->role === 'admin') {
            return null;
        }

        return $this->visibleRepairsQuery()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function supportsReportedEmployeeColumn(): bool
    {
        static $hasColumn;

        return $hasColumn ??= Schema::hasColumn('repairs', 'reported_employee_id');
    }

    private function repairScopeMeta(): array
    {
        if (auth()->user()?->role === 'admin') {
            return [
                'label' => 'Visi remonti',
                'description' => 'Statistika no visiem remonta ierakstiem sistema.',
            ];
        }

        return [
            'label' => 'Man pieejamie remonti',
            'description' => 'Skats apvieno man pieskirtos un manus pieteiktos remontus.',
        ];
    }

    private function monthBuckets(int $months = 6)
    {
        return collect(range($months - 1, 0))
            ->map(fn (int $offset) => CarbonImmutable::now()->startOfMonth()->subMonths($offset))
            ->values();
    }

    private function monthLabel(CarbonImmutable $month): string
    {
        $labels = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Dec',
        ];

        return ($labels[(int) $month->format('n')] ?? $month->format('m')) . ' ' . $month->format('Y');
    }

    private function deviceStatusMeta(): array
    {
        return [
            'active' => ['label' => 'Aktivas', 'tone' => 'emerald'],
            'repair' => ['label' => 'Remonta', 'tone' => 'sky'],
            'broken' => ['label' => 'Bojatas', 'tone' => 'rose'],
            'reserve' => ['label' => 'Rezerve', 'tone' => 'amber'],
            'kitting' => ['label' => 'Komplektacija', 'tone' => 'violet'],
            'retired' => ['label' => 'Norakstitas', 'tone' => 'slate'],
        ];
    }

    private function repairStatusMeta(): array
    {
        return [
            'waiting' => ['label' => 'Gaida', 'tone' => 'amber'],
            'in-progress' => ['label' => 'Procesa', 'tone' => 'sky'],
            'completed' => ['label' => 'Pabeigts', 'tone' => 'emerald'],
            'cancelled' => ['label' => 'Atcelts', 'tone' => 'slate'],
        ];
    }

    private function repairPriorityMeta(): array
    {
        return [
            'low' => ['label' => 'Zema', 'tone' => 'slate'],
            'medium' => ['label' => 'Videja', 'tone' => 'amber'],
            'high' => ['label' => 'Augsta', 'tone' => 'orange'],
            'critical' => ['label' => 'Kritiska', 'tone' => 'rose'],
        ];
    }

    private function repairTypeMeta(): array
    {
        return [
            'internal' => ['label' => 'Ieksejais', 'tone' => 'violet'],
            'external' => ['label' => 'Arejais', 'tone' => 'rose'],
        ];
    }
}
