<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
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
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function create()
    {
        $user = $this->user();
        abort_unless($user, 403);

        return view('device_transfers.create', [
            'devices' => $this->availableDevicesForUser($user)->get(),
            'users' => User::active()->whereKeyNot($user->id)->orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'transfered_to_id' => ['required', 'exists:users,id', Rule::notIn([$user->id])],
            'transfer_reason' => ['required', 'string'],
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt nodosanu tikai savai piesaistitai iericei.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss parsutisanas pieteikums.'],
            ]);
        }

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
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

        $canReview = (int) $deviceTransfer->transfered_to_id === (int) $reviewer->id;
        abort_unless($canReview, 403);

        if ($deviceTransfer->status !== DeviceTransfer::STATUS_SUBMITTED) {
            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([DeviceTransfer::STATUS_APPROVED, DeviceTransfer::STATUS_REJECTED])],
            'review_notes' => ['nullable', 'string'],
        ]);

        $before = $deviceTransfer->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $deviceTransfer, $reviewer) {
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
            ])->save();
        });

        $after = $deviceTransfer->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($reviewer->id, $deviceTransfer, $before, $after);

        return back()->with('success', 'Ierices parsutisanas pieteikums izskatits');
    }

    private function availableDevicesForUser(User $user): Builder
    {
        return Device::query()
            ->where('assigned_to_id', $user->id)
            ->where('status', Device::STATUS_ACTIVE)
            ->with(['type', 'building', 'room'])
            ->orderBy('name');
    }
}
