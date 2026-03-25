<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\WriteoffRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->user();
        abort_unless($user, 403);
        $isManager = $user->canManageRequests();

        if (! $isManager) {
            return redirect()->route('devices.index');
        }

        $filters = [
            'floor' => trim((string) $request->query('floor', '')),
            'room_id' => trim((string) $request->query('room_id', '')),
        ];

        $hasDevices = $this->featureTableExists('devices');
        $hasRooms = $this->featureTableExists('rooms');

        $deviceQuery = $hasDevices ? Device::query() : null;
        $repairRequestQuery = Schema::hasTable('repair_requests') ? RepairRequest::query() : null;
        $writeoffRequestQuery = Schema::hasTable('writeoff_requests') ? WriteoffRequest::query() : null;

        if ($deviceQuery) {
            $deviceQuery
                ->when(
                    $filters['floor'] !== '' && ctype_digit($filters['floor']),
                    fn ($query) => $query->whereHas('room', fn ($roomQuery) => $roomQuery->where('floor_number', (int) $filters['floor']))
                )
                ->when(
                    $filters['room_id'] !== '' && ctype_digit($filters['room_id']),
                    fn ($query) => $query->where('room_id', (int) $filters['room_id'])
                );
        }

        $locationRooms = $hasRooms && $isManager
            ? Room::query()
                ->with(['building'])
                ->withCount(['devices'])
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get()
            : collect();

        $locationTree = $locationRooms
            ->groupBy(fn (Room $room) => (string) ($room->floor_number ?? 0))
            ->sortKeys()
            ->map(function ($rooms, $floorKey) use ($filters) {
                return [
                    'id' => (string) $floorKey,
                    'label' => ((int) $floorKey).'. stavs',
                    'room_count' => $rooms->count(),
                    'device_count' => (int) $rooms->sum('devices_count'),
                    'rooms' => $rooms->map(function (Room $room) use ($filters) {
                        return [
                            'id' => $room->id,
                            'room_number' => $room->room_number,
                            'room_name' => $room->room_name,
                            'building_name' => $room->building?->building_name,
                            'department' => $room->department,
                            'device_count' => (int) $room->devices_count,
                            'is_active' => (string) $room->id === $filters['room_id'],
                        ];
                    })->values(),
                    'is_active' => (string) $floorKey === $filters['floor'] && $filters['room_id'] === '',
                ];
            })
            ->values();

        $pendingRepairRequestCount = $repairRequestQuery
            ? (clone $repairRequestQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count()
            : 0;

        $pendingWriteoffRequestCount = $writeoffRequestQuery
            ? (clone $writeoffRequestQuery)->where('status', WriteoffRequest::STATUS_SUBMITTED)->count()
            : 0;

        $dashboardDevices = $deviceQuery
            ? (clone $deviceQuery)
                ->with(['room.building', 'building', 'type', 'assignedTo', 'activeRepair', 'latestRepair'])
                ->withExists([
                    'repairRequests as has_pending_repair_request' => fn ($query) => $query->where('status', RepairRequest::STATUS_SUBMITTED),
                    'writeoffRequests as has_pending_writeoff_request' => fn ($query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED),
                    'transfers as has_pending_transfer_request' => fn ($query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED),
                ])
                ->latest('id')
                ->paginate(12)
                ->withQueryString()
            : $this->emptyPaginator(12);

        $dashboardDeviceStates = collect($dashboardDevices->items())
            ->mapWithKeys(fn (Device $device) => [
                $device->id => [
                    'repairStatusLabel' => $this->visibleRepairStatusLabel($device),
                    'pendingRequestBadge' => $this->pendingRequestBadge($device),
                ],
            ])
            ->all();

        return view('dashboard', [
            'user' => $user,
            'isManager' => $isManager,
            'dashboardDevices' => $dashboardDevices,
            'dashboardDeviceStates' => $dashboardDeviceStates,
            'locationTree' => $locationTree,
            'quickActions' => $this->quickActions(
                $pendingRepairRequestCount,
                $pendingWriteoffRequestCount
            ),
            'filters' => $filters,
        ]);
    }

    private function quickActions(int $pendingRepairRequestCount, int $pendingWriteoffRequestCount): array
    {
        return [
            [
                'label' => 'Jauna ierice',
                'url' => route('devices.create'),
                'icon' => 'plus',
                'class' => 'btn-create',
                'count' => null,
            ],
            [
                'label' => 'Pievienot remontu',
                'url' => route('repairs.create'),
                'icon' => 'repair',
                'class' => 'btn-edit',
                'count' => null,
            ],
            [
                'label' => 'Remonta pieteikumi',
                'url' => route('repair-requests.index'),
                'icon' => 'repair-request',
                'class' => 'btn-view',
                'count' => $pendingRepairRequestCount,
            ],
            [
                'label' => 'Norakstisanas pieteikumi',
                'url' => route('writeoff-requests.index'),
                'icon' => 'writeoff',
                'class' => 'btn-danger',
                'count' => $pendingWriteoffRequestCount,
            ],
        ];
    }

    public function repairStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            default => null,
        };
    }

    public function visibleRepairStatusLabel(Device $device): ?string
    {
        if ($device->status !== Device::STATUS_REPAIR) {
            return null;
        }

        $label = $this->repairStatusLabel($device->activeRepair?->status)
            ?? $this->repairStatusLabel($device->latestRepair?->status);

        return $label ?: 'Gaida';
    }

    public function pendingRequestBadge(Device $device): ?array
    {
        if ((bool) ($device->has_pending_repair_request ?? false)) {
            return [
                'icon' => 'repair-request',
                'label' => 'Apskatit',
                'detail_label' => 'Remonts',
                'class' => 'border-sky-200 bg-sky-50 text-sky-700',
                'url' => $this->requestIndexUrl($device, 'repair'),
            ];
        }

        if ((bool) ($device->has_pending_writeoff_request ?? false)) {
            return [
                'icon' => 'writeoff',
                'label' => 'Apskatit',
                'detail_label' => 'Norakst.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'url' => $this->requestIndexUrl($device, 'writeoff'),
            ];
        }

        if ((bool) ($device->has_pending_transfer_request ?? false)) {
            return [
                'icon' => 'transfer',
                'label' => 'Apskatit',
                'detail_label' => 'Nodosana',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'url' => $this->requestIndexUrl($device, 'transfer'),
            ];
        }

        return null;
    }

    private function requestIndexUrl(Device $device, string $type): ?string
    {
        $params = [
            'q' => $device->code ?: $device->name,
            'statuses_filter' => 1,
            'status' => ['submitted'],
        ];

        return match ($type) {
            'repair' => Route::has('repair-requests.index') ? route('repair-requests.index', $params) : null,
            'writeoff' => Route::has('writeoff-requests.index') ? route('writeoff-requests.index', $params) : null,
            'transfer' => Route::has('device-transfers.index') ? route('device-transfers.index', $params) : null,
            default => null,
        };
    }
}
