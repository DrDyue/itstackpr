<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserRequestCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->requireRegularUser();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'type' => trim((string) $request->query('type', '')),
        ];

        $items = $this->requestItems($user)
            ->when($filters['status'] !== '' && in_array($filters['status'], ['submitted', 'approved', 'rejected'], true), fn (Collection $collection) => $collection->where('status', $filters['status']))
            ->when($filters['type'] !== '' && in_array($filters['type'], ['repair', 'writeoff', 'transfer'], true), fn (Collection $collection) => $collection->where('type', $filters['type']))
            ->when($filters['q'] !== '', function (Collection $collection) use ($filters) {
                $term = mb_strtolower($filters['q']);

                return $collection->filter(function (array $item) use ($term) {
                    return str_contains(mb_strtolower($item['device_name']), $term)
                        || str_contains(mb_strtolower($item['device_code']), $term)
                        || str_contains(mb_strtolower($item['summary']), $term)
                        || str_contains(mb_strtolower($item['actor']), $term);
                });
            })
            ->sortByDesc('timestamp')
            ->values();

        return view('my_requests.index', [
            'items' => $this->paginateCollection($items, 12),
            'filters' => $filters,
            'statusLabels' => $this->requestStatusLabels(),
            'typeLabels' => $this->typeLabels(),
            'roomOptions' => $this->roomOptions(),
            'user' => $user,
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->requireRegularUser();
        $devices = $this->availableDevicesForUser($user)->get();
        $selectedDeviceId = (string) $request->query('device_id', '');
        $selectedDevice = $devices->firstWhere('id', (int) $selectedDeviceId);

        return view('my_requests.create', [
            'devices' => $devices,
            'users' => User::active()->whereKeyNot($user->id)->orderBy('full_name')->get(),
            'selectedType' => in_array($request->query('type'), ['repair', 'writeoff', 'transfer'], true) ? (string) $request->query('type') : 'repair',
            'selectedDeviceId' => $selectedDevice?->id ? (string) $selectedDevice->id : '',
            'selectedDeviceLabel' => $selectedDevice
                ? $selectedDevice->name . ' (' . ($selectedDevice->code ?: 'bez koda') . ')'
                : '',
            'user' => $user,
            'deviceOptions' => $devices->map(fn (Device $device) => [
                'value' => (string) $device->id,
                'label' => $device->name . ' (' . ($device->code ?: 'bez koda') . ')',
                'description' => collect([
                    $device->room?->room_number ? 'telpa ' . $device->room->room_number : null,
                    $device->building?->building_name,
                    $device->status === Device::STATUS_REPAIR
                        ? 'remonts: ' . $this->repairStatusLabel($device->activeRepair?->status)
                        : null,
                ])->filter()->implode(' | '),
                'search' => implode(' ', array_filter([
                    $device->name,
                    $device->code,
                    $device->model,
                    $device->room?->room_number,
                    $device->building?->building_name,
                ])),
            ])->values(),
            'recipientOptions' => User::active()->whereKeyNot($user->id)->orderBy('full_name')->get()->map(fn (User $recipient) => [
                'value' => (string) $recipient->id,
                'label' => $recipient->full_name,
                'description' => $recipient->job_title ?: $recipient->email,
                'search' => implode(' ', array_filter([$recipient->full_name, $recipient->job_title, $recipient->email])),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->requireRegularUser();

        $validated = $this->validateInput($request, [
            'request_type' => ['required', Rule::in(['repair', 'writeoff', 'transfer'])],
            'device_id' => ['required', 'exists:devices,id'],
            'description' => [Rule::requiredIf(fn () => $request->input('request_type') === 'repair'), 'nullable', 'string'],
            'reason' => [Rule::requiredIf(fn () => $request->input('request_type') === 'writeoff'), 'nullable', 'string'],
            'transfered_to_id' => [Rule::requiredIf(fn () => $request->input('request_type') === 'transfer'), 'nullable', 'exists:users,id', Rule::notIn([$user->id])],
            'transfer_reason' => [Rule::requiredIf(fn () => $request->input('request_type') === 'transfer'), 'nullable', 'string'],
        ], [
            'request_type.required' => 'Izvelies pieteikuma tipu.',
            'description.required' => 'Apraksti remonta problemu.',
            'reason.required' => 'Apraksti norakstisanas iemeslu.',
            'transfered_to_id.required' => 'Izvelies lietotaju, kam nodot ierici.',
            'transfer_reason.required' => 'Apraksti nodosanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari veidot pieteikumus tikai savai aktivajai iericei.'],
            ]);
        }

        $this->ensureDeviceCanAcceptRequest($device, $validated['request_type']);

        if ($validated['request_type'] === 'repair') {
            $repairRequest = RepairRequest::create([
                'device_id' => $device->id,
                'responsible_user_id' => $user->id,
                'description' => (string) $validated['description'],
                'status' => RepairRequest::STATUS_SUBMITTED,
            ]);

            AuditTrail::created($user->id, $repairRequest);

            return redirect()->route('my-requests.index')->with('success', 'Remonta pieteikums izveidots.');
        }

        if ($validated['request_type'] === 'writeoff') {
            $writeoffRequest = WriteoffRequest::create([
                'device_id' => $device->id,
                'responsible_user_id' => $user->id,
                'reason' => (string) $validated['reason'],
                'status' => WriteoffRequest::STATUS_SUBMITTED,
            ]);

            AuditTrail::created($user->id, $writeoffRequest);

            return redirect()->route('my-requests.index')->with('success', 'Norakstisanas pieteikums izveidots.');
        }

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => (int) $validated['transfered_to_id'],
            'transfer_reason' => (string) $validated['transfer_reason'],
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $transfer);

        return redirect()->route('my-requests.index')->with('success', 'Nodosanas pieteikums izveidots.');
    }

    private function requireRegularUser(): User
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        return $user;
    }

    private function availableDevicesForUser(User $user)
    {
        return Device::query()
            ->where('assigned_to_id', $user->id)
            ->where('status', Device::STATUS_ACTIVE)
            ->with(['building', 'room.building', 'type', 'activeRepair'])
            ->orderBy('name');
    }

    private function ensureDeviceCanAcceptRequest(Device $device, string $requestType): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau notiek remonts (' . $this->repairStatusLabel($device->activeRepair?->status) . '), tapec jaunus pieteikumus veidot nevar.'],
            ]);
        }

        $hasPendingRepair = RepairRequest::query()
            ->where('device_id', $device->id)
            ->where('status', RepairRequest::STATUS_SUBMITTED)
            ->exists();

        $hasPendingWriteoff = WriteoffRequest::query()
            ->where('device_id', $device->id)
            ->where('status', WriteoffRequest::STATUS_SUBMITTED)
            ->exists();

        $hasPendingTransfer = DeviceTransfer::query()
            ->where('device_id', $device->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->exists();

        if ($requestType === 'repair') {
            if ($hasPendingRepair) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums.'],
                ]);
            }

            if ($hasPendingWriteoff) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums, tapec remonta pieteikumu veidot nevar.'],
                ]);
            }

            if ($hasPendingTransfer) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums, tapec remonta pieteikumu veidot nevar.'],
                ]);
            }
        }

        if ($requestType === 'writeoff') {
            if ($hasPendingWriteoff) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums.'],
                ]);
            }

            if ($hasPendingRepair) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums, tapec norakstisanas pieteikumu veidot nevar.'],
                ]);
            }

            if ($hasPendingTransfer) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums, tapec norakstisanas pieteikumu veidot nevar.'],
                ]);
            }
        }

        if ($requestType === 'transfer') {
            if ($hasPendingTransfer) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums.'],
                ]);
            }

            if ($hasPendingRepair) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums, tapec nodosanas pieteikumu veidot nevar.'],
                ]);
            }

            if ($hasPendingWriteoff) {
                throw ValidationException::withMessages([
                    'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums, tapec nodosanas pieteikumu veidot nevar.'],
                ]);
            }
        }
    }

    private function repairStatusLabel(?string $status): string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            default => 'Remonta',
        };
    }

    private function roomOptions(): Collection
    {
        return Room::query()
            ->with('building')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'value' => (string) $room->id,
                'label' => $room->room_number . ($room->room_name ? ' - ' . $room->room_name : ''),
                'description' => collect([
                    $room->building?->building_name,
                    $room->floor_number !== null ? $room->floor_number . '. stavs' : null,
                    $room->department,
                ])->filter()->implode(' | '),
                'search' => implode(' ', array_filter([
                    $room->room_number,
                    $room->room_name,
                    $room->building?->building_name,
                    $room->department,
                    $room->floor_number,
                ])),
            ])
            ->values();
    }

    private function requestItems(User $user): Collection
    {
        $repairs = RepairRequest::query()
            ->with(['device', 'reviewedBy', 'repair'])
            ->where('responsible_user_id', $user->id)
            ->get()
            ->map(fn (RepairRequest $request) => [
                'id' => 'repair-' . $request->id,
                'type' => 'repair',
                'model' => $request,
                'status' => $request->status,
                'timestamp' => $request->created_at?->getTimestamp() ?? 0,
                'created_at' => $request->created_at,
                'device_name' => $request->device?->name ?: 'Ierice nav atrasta',
                'device_code' => $request->device?->code ?: 'bez koda',
                'summary' => $request->description,
                'actor' => $request->reviewedBy?->full_name ?: $user->full_name,
                'meta' => $request->repair
                    ? 'Saistits remonts #' . $request->repair->id . ' | ' . match ($request->repair->status) {
                        'waiting' => 'Gaida',
                        'in-progress' => 'Procesa',
                        'completed' => 'Pabeigts',
                        'cancelled' => 'Atcelts',
                        default => $request->repair->status,
                    }
                    : null,
                'direction' => 'Tevis iesniegts remonta pieteikums',
                'is_incoming' => false,
            ]);

        $writeoffs = WriteoffRequest::query()
            ->with(['device', 'reviewedBy'])
            ->where('responsible_user_id', $user->id)
            ->get()
            ->map(fn (WriteoffRequest $request) => [
                'id' => 'writeoff-' . $request->id,
                'type' => 'writeoff',
                'model' => $request,
                'status' => $request->status,
                'timestamp' => $request->created_at?->getTimestamp() ?? 0,
                'created_at' => $request->created_at,
                'device_name' => $request->device?->name ?: 'Ierice nav atrasta',
                'device_code' => $request->device?->code ?: 'bez koda',
                'summary' => $request->reason,
                'actor' => $request->reviewedBy?->full_name ?: $user->full_name,
                'meta' => $request->review_notes,
                'direction' => 'Tevis iesniegts norakstisanas pieteikums',
                'is_incoming' => false,
            ]);

        $transfers = DeviceTransfer::query()
            ->with(['device.building', 'device.room.building', 'responsibleUser', 'transferTo', 'reviewedBy'])
            ->where(function ($query) use ($user) {
                $query->where('responsible_user_id', $user->id)
                    ->orWhere('transfered_to_id', $user->id);
            })
            ->get()
            ->map(function (DeviceTransfer $transfer) use ($user) {
                $isIncoming = (int) $transfer->transfered_to_id === (int) $user->id && (int) $transfer->responsible_user_id !== (int) $user->id;

                return [
                    'id' => 'transfer-' . $transfer->id,
                    'type' => 'transfer',
                    'model' => $transfer,
                    'status' => $transfer->status,
                    'timestamp' => $transfer->created_at?->getTimestamp() ?? 0,
                    'created_at' => $transfer->created_at,
                    'device_name' => $transfer->device?->name ?: 'Ierice nav atrasta',
                    'device_code' => $transfer->device?->code ?: 'bez koda',
                    'summary' => $transfer->transfer_reason,
                    'actor' => $isIncoming
                        ? ($transfer->responsibleUser?->full_name ?: '-')
                        : ($transfer->transferTo?->full_name ?: '-'),
                    'meta' => $transfer->review_notes,
                    'direction' => $isIncoming
                        ? 'Tev nosutits nodosanas pieteikums'
                        : 'Tevis izveidots nodosanas pieteikums',
                    'is_incoming' => $isIncoming,
                ];
            });

        return $repairs->concat($writeoffs)->concat($transfers);
    }

    private function typeLabels(): array
    {
        return [
            'repair' => 'Remonts',
            'writeoff' => 'Norakstisana',
            'transfer' => 'Nodosana',
        ];
    }

    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage('page');
        $pageItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
