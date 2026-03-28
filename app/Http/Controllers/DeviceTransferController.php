<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $canManageTransfers = $user->canManageRequests();
        $availableStatuses = [
            DeviceTransfer::STATUS_SUBMITTED,
            DeviceTransfer::STATUS_APPROVED,
            DeviceTransfer::STATUS_REJECTED,
        ];
        $statusFilterTouched = $request->has('statuses_filter');
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : $availableStatuses))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'statuses' => $selectedStatuses === [] ? $availableStatuses : $selectedStatuses,
            'has_status_filter' => true,
        ];

        if (! $this->featureTableExists('device_transfers')) {
            return view('device_transfers.index', [
                'transfers' => $this->emptyPaginator(),
                'transferSummary' => [
                    'total' => 0,
                    'submitted' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'isAdmin' => $canManageTransfers,
                'featureMessage' => 'Tabula device_transfers sobrid nav pieejama.',
            ]);
        }

        $baseQuery = DeviceTransfer::query()
            ->when(! $canManageTransfers, function (Builder $query) use ($user) {
                $query->where(function (Builder $builder) use ($user) {
                    $builder->where('responsible_user_id', $user->id)
                        ->orWhere('transfered_to_id', $user->id);
                });
            });

        $incomingPendingCount = ! $canManageTransfers
            ? (clone $baseQuery)
                ->where('transfered_to_id', $user->id)
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->count()
            : 0;

        $transfers = (clone $baseQuery)
            ->with(['device.building', 'device.room.building', 'responsibleUser', 'transferTo', 'reviewedBy'])
            ->whereIn('status', $filters['statuses'] === [] ? ['__none__'] : $filters['statuses'])
            ->when(! $canManageTransfers, fn (Builder $query) => $query->orderByRaw(
                'case when transfered_to_id = ? and status = ? then 0 else 1 end',
                [$user->id, DeviceTransfer::STATUS_SUBMITTED]
            ))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $builder) use ($term) {
                    $builder->where('transfer_reason', 'like', "%{$term}%")
                        ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('responsibleUser', fn (Builder $userQuery) => $userQuery->where('full_name', 'like', "%{$term}%"))
                        ->orWhereHas('transferTo', fn (Builder $userQuery) => $userQuery->where('full_name', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('device_transfers.index', [
            'transfers' => $transfers,
            'transferSummary' => [
                'total' => (clone $baseQuery)->count(),
                'submitted' => (clone $baseQuery)->where('status', DeviceTransfer::STATUS_SUBMITTED)->count(),
                'approved' => (clone $baseQuery)->where('status', DeviceTransfer::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', DeviceTransfer::STATUS_REJECTED)->count(),
            ],
            'filters' => $filters,
            'statuses' => $availableStatuses,
            'statusLabels' => $this->requestStatusLabels(),
            'isAdmin' => $canManageTransfers,
            'currentUserId' => $user->id,
            'incomingPendingCount' => $incomingPendingCount,
            'roomOptions' => $canManageTransfers
                ? collect()
                : Room::query()
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
                    ->values(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $canManageTransfers = $user->canManageRequests();

        if (! $this->featureTableExists('device_transfers')) {
            return view('device_transfers.create', [
                'devices' => collect(),
                'users' => collect(),
                'deviceOptions' => collect(),
                'recipientOptions' => collect(),
                'featureMessage' => 'Tabula device_transfers sobrid nav pieejama.',
                'isAdmin' => $canManageTransfers,
            ]);
        }

        $devices = $this->availableDevicesForUser($user)->get();
        $recipients = User::active()->whereKeyNot($user->id)->orderBy('full_name')->get();
        $selectedDeviceId = (string) $request->query('device_id', '');
        $selectedDevice = ctype_digit($selectedDeviceId)
            ? $devices->firstWhere('id', (int) $selectedDeviceId)
            : null;

        return view('device_transfers.create', [
            'devices' => $devices,
            'users' => $recipients,
            'deviceOptions' => $this->deviceOptions($devices),
            'recipientOptions' => $this->recipientOptions($recipients),
            'isAdmin' => $canManageTransfers,
            'selectedDeviceId' => $selectedDevice?->id ? (string) $selectedDevice->id : '',
            'selectedDeviceLabel' => $selectedDevice
                ? $selectedDevice->name.' ('.($selectedDevice->code ?: 'bez koda').')'
                : '',
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('device_transfers')) {
            return redirect()->route('device-transfers.index')->with('error', 'Iericu parsutisanas pieteikumus sobrid nevar saglabat, jo tabula device_transfers nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'transfered_to_id' => ['required', 'exists:users,id', Rule::notIn([$user->id])],
            'transfer_reason' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvelies ierici, kuru velies nodot.',
            'transfered_to_id.required' => 'Izvelies sanemeju.',
            'transfer_reason.required' => 'Apraksti parsutisanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => [$user->canManageRequests()
                    ? 'Admins var pieteikt parsutisanu tikai aktivai un pieskirtai iericei.'
                    : 'Vari pieteikt nodosanu tikai savai piesaistitai iericei.'],
            ]);
        }

        $ownerId = $this->transferOwnerId($user, $device);
        if (! $ownerId) {
            throw ValidationException::withMessages([
                'device_id' => ['Izveletajai iericei nav pieskirta atbildiga persona.'],
            ]);
        }

        if ((int) $validated['transfered_to_id'] === (int) $ownerId) {
            throw ValidationException::withMessages([
                'transfered_to_id' => ['Sanemejs nevar but tas pats lietotajs, kam ierice jau ir pieskirta.'],
            ]);
        }

        $this->ensureDeviceCanAcceptTransferRequest($device);

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $ownerId,
            'transfered_to_id' => $validated['transfered_to_id'],
            'transfer_reason' => $validated['transfer_reason'],
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $transfer);

        return redirect()->route('device-transfers.index')->with('success', 'Ierices parsutisanas pieteikums izveidots');
    }

    public function review(Request $request, DeviceTransfer $deviceTransfer)
    {
        $reviewer = $this->user();
        abort_unless($reviewer, 403);

        if (! $this->featureTableExists('device_transfers')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Iericu parsutisanas pieteikumu tabula sobrid nav pieejama.'], 503);
            }

            return back()->with('error', 'Iericu parsutisanas pieteikumu tabula sobrid nav pieejama.');
        }

        $canReview = (int) $deviceTransfer->transfered_to_id === (int) $reviewer->id;
        abort_unless($canReview, 403);

        if ($deviceTransfer->status !== DeviceTransfer::STATUS_SUBMITTED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sis pieteikums jau ir izskatits.'], 409);
            }

            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $keepCurrentRoom = ! $request->exists('keep_current_room') || $request->boolean('keep_current_room');

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED])],
            'keep_current_room' => ['nullable', 'boolean'],
            'room_id' => [
                Rule::requiredIf(fn () => $request->input('status') === DeviceTransfer::STATUS_APPROVED && ! $keepCurrentRoom),
                'nullable',
                'exists:rooms,id',
            ],
        ], [
            'status.required' => 'Izvelies lemumu parsutisanas pieteikumam.',
            'room_id.required' => 'Izvelies telpu, uz kuru novietot ierici.',
        ]);

        $before = $deviceTransfer->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $deviceTransfer, $reviewer, $keepCurrentRoom) {
            $deviceTransfer->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $reviewer->id,
                'review_notes' => null,
            ]);

            if ($validated['status'] !== 'approved') {
                return;
            }

            $device = $deviceTransfer->device()->lockForUpdate()->first();

            if (! $device || $device->status !== Device::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'status' => ['Ierici nevar nodot, jo tas statuss kops pieteikuma izveides ir mainijies.'],
                ]);
            }

            $targetRoom = null;

            if (! $keepCurrentRoom) {
                $targetRoom = filled($validated['room_id'] ?? null)
                    ? Room::query()->find($validated['room_id'])
                    : null;

                if (! $targetRoom) {
                    throw ValidationException::withMessages([
                        'room_id' => ['Izveleta telpa nav atrasta.'],
                    ]);
                }
            }

            $device->forceFill([
                'assigned_to_id' => $deviceTransfer->transfered_to_id,
                'room_id' => $targetRoom?->id ?? $device->room_id,
                'building_id' => $targetRoom?->building_id ?? $device->building_id,
            ]);

            $device->save();
        });

        $after = $deviceTransfer->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($reviewer->id, $deviceTransfer, $before, $after);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ierices parsutisanas pieteikums izskatits',
                'status' => $validated['status'],
                'request_id' => $deviceTransfer->id,
            ]);
        }

        return back()->with('success', 'Ierices parsutisanas pieteikums izskatits');
    }

    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->when($user->canManageRequests(), fn (Builder $query) => $query->whereNotNull('assigned_to_id'))
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('assigned_to_id', $user->id))
            ->where('status', Device::STATUS_ACTIVE)
            ->whereDoesntHave('repairs', fn (Builder $query) => $query->whereIn('status', ['waiting', 'in-progress']))
            ->whereDoesntHave('repairRequests', fn (Builder $query) => $query->where('status', RepairRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('writeoffRequests', fn (Builder $query) => $query->where('status', WriteoffRequest::STATUS_SUBMITTED))
            ->whereDoesntHave('transfers', fn (Builder $query) => $query->where('status', DeviceTransfer::STATUS_SUBMITTED))
            ->with(['type', 'building', 'room', 'assignedTo', 'activeRepair'])
            ->orderBy('name');
    }

    private function deviceOptions($devices)
    {
        return collect($devices)->map(function (Device $device) {
            $description = collect([
                $device->type?->type_name,
                collect([$device->manufacturer, $device->model])->filter()->implode(' '),
                $device->assignedTo?->full_name ? 'paslaik: ' . $device->assignedTo->full_name : null,
                $device->room?->room_number ? 'telpa ' . $device->room->room_number : null,
                $device->building?->building_name,
            ])->filter()->implode(' | ');

            return [
                'value' => (string) $device->id,
                'label' => $device->name . ' (' . ($device->code ?: 'bez koda') . ')',
                'description' => $description,
                'search' => implode(' ', array_filter([
                    $device->name,
                    $device->code,
                    $device->type?->type_name,
                    $device->manufacturer,
                    $device->model,
                    $device->assignedTo?->full_name,
                    $device->room?->room_number,
                    $device->room?->room_name,
                    $device->building?->building_name,
                ])),
            ];
        })->values();
    }

    private function recipientOptions($users)
    {
        return collect($users)->map(fn (User $recipient) => [
            'value' => (string) $recipient->id,
            'label' => $recipient->full_name,
            'description' => $recipient->job_title ?: $recipient->email,
            'search' => implode(' ', array_filter([
                $recipient->full_name,
                $recipient->job_title,
                $recipient->email,
            ])),
        ])->values();
    }

    private function ensureDeviceCanAcceptTransferRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau notiek remonts (' . $this->repairStatusLabel($device->activeRepair?->status) . '), tapec nodosanas pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums, tapec nodosanas pieteikumu veidot nevar.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums, tapec nodosanas pieteikumu veidot nevar.'],
            ]);
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

    private function transferOwnerId(User $actor, Device $device): ?int
    {
        if ($actor->canManageRequests()) {
            return $device->assigned_to_id ? (int) $device->assigned_to_id : null;
        }

        return $actor->id;
    }
}
