<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Room;
use App\Models\User;
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

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'q' => trim((string) $request->query('q', '')),
        ];

        if (! $this->featureTableExists('device_transfers')) {
            return view('device_transfers.index', [
                'transfers' => $this->emptyPaginator(),
                'filters' => $filters,
                'statuses' => [DeviceTransfer::STATUS_SUBMITTED, DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED],
                'statusLabels' => $this->requestStatusLabels(),
                'isAdmin' => $user->isAdmin(),
                'featureMessage' => 'Tabula device_transfers sobrid nav pieejama.',
            ]);
        }

        $transfers = DeviceTransfer::query()
            ->with(['device', 'responsibleUser', 'transferTo', 'reviewedBy'])
            ->when(! $user->isAdmin(), function (Builder $query) use ($user) {
                $query->where(function (Builder $builder) use ($user) {
                    $builder->where('responsible_user_id', $user->id)
                        ->orWhere('transfered_to_id', $user->id);
                });
            })
            ->when($filters['status'] !== '' && in_array($filters['status'], [DeviceTransfer::STATUS_SUBMITTED, DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED], true), fn (Builder $query) => $query->where('status', $filters['status']))
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
            'filters' => $filters,
            'statuses' => [DeviceTransfer::STATUS_SUBMITTED, DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED],
            'statusLabels' => $this->requestStatusLabels(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function create()
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('device_transfers')) {
            return view('device_transfers.create', [
                'devices' => collect(),
                'users' => collect(),
                'featureMessage' => 'Tabula device_transfers sobrid nav pieejama.',
                'isAdmin' => $user->isAdmin(),
            ]);
        }

        return view('device_transfers.create', [
            'devices' => $this->availableDevicesForUser($user)->get(),
            'users' => User::active()->whereKeyNot($user->id)->orderBy('full_name')->get(),
            'isAdmin' => $user->isAdmin(),
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
                'device_id' => [$user->isAdmin()
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

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss parsutisanas pieteikums.'],
            ]);
        }

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
            return back()->with('error', 'Iericu parsutisanas pieteikumu tabula sobrid nav pieejama.');
        }

        $canReview = (int) $deviceTransfer->transfered_to_id === (int) $reviewer->id;
        abort_unless($canReview, 403);

        if ($deviceTransfer->status !== DeviceTransfer::STATUS_SUBMITTED) {
            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED])],
            'review_notes' => ['nullable', 'string'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'keep_current_room' => ['nullable', 'boolean'],
        ], [
            'status.required' => 'Izvelies lemumu parsutisanas pieteikumam.',
        ]);

        if (
            $validated['status'] === DeviceTransfer::STATUS_APPROVED
            && ! $request->boolean('keep_current_room')
            && blank($validated['room_id'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'room_id' => ['Izvelies jauno telpu vai atzime, ka ierice paliek esosaja telpa.'],
            ]);
        }

        $before = $deviceTransfer->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $deviceTransfer, $reviewer, $request) {
            $deviceTransfer->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $reviewer->id,
                'review_notes' => $validated['review_notes'] ?: null,
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

            $device->forceFill([
                'assigned_to_id' => $deviceTransfer->transfered_to_id,
            ]);

            if (! $request->boolean('keep_current_room') && filled($validated['room_id'] ?? null)) {
                $room = Room::query()->find($validated['room_id']);
                $device->forceFill([
                    'room_id' => $room?->id,
                    'building_id' => $room?->building_id,
                ]);
            }

            $device->save();
        });

        $after = $deviceTransfer->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($reviewer->id, $deviceTransfer, $before, $after);

        return back()->with('success', 'Ierices parsutisanas pieteikums izskatits');
    }

    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->when($user->isAdmin(), fn (Builder $query) => $query->whereNotNull('assigned_to_id'))
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('assigned_to_id', $user->id))
            ->where('status', Device::STATUS_ACTIVE)
            ->with(['type', 'building', 'room', 'assignedTo'])
            ->orderBy('name');
    }

    private function transferOwnerId(User $actor, Device $device): ?int
    {
        if ($actor->isAdmin()) {
            return $device->assigned_to_id ? (int) $device->assigned_to_id : null;
        }

        return $actor->id;
    }
}
