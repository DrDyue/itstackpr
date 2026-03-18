<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WriteoffRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'q' => trim((string) $request->query('q', '')),
        ];

        if (! $this->featureTableExists('writeoff_requests')) {
            return view('writeoff_requests.index', [
                'requests' => $this->emptyPaginator(),
                'filters' => $filters,
                'statuses' => [WriteoffRequest::STATUS_SUBMITTED, WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED],
                'canReview' => $user->canManageRequests(),
                'featureMessage' => 'Tabula writeoff_requests sobrid nav pieejama.',
            ]);
        }

        $requests = WriteoffRequest::query()
            ->with(['device.assignedTo', 'responsibleUser', 'reviewedBy'])
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('responsible_user_id', $user->id))
            ->when($filters['status'] !== '' && in_array($filters['status'], [WriteoffRequest::STATUS_SUBMITTED, WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED], true), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $builder) use ($term) {
                    $builder->where('reason', 'like', "%{$term}%")
                        ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('writeoff_requests.index', [
            'requests' => $requests,
            'filters' => $filters,
            'statuses' => [WriteoffRequest::STATUS_SUBMITTED, WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED],
            'canReview' => $user->canManageRequests(),
        ]);
    }

    public function create()
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return view('writeoff_requests.create', [
                'devices' => collect(),
                'featureMessage' => 'Tabula writeoff_requests sobrid nav pieejama.',
            ]);
        }

        return view('writeoff_requests.create', [
            'devices' => $this->availableDevicesForUser($user)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return redirect()->route('writeoff-requests.index')->with('error', 'Norakstisanas pieteikumus sobrid nevar saglabat, jo tabula writeoff_requests nav pieejama.');
        }

        $validated = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'reason' => ['required', 'string'],
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt norakstisanu tikai savai piesaistitai iericei.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums.'],
            ]);
        }

        $writeoffRequest = WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => $validated['reason'],
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $writeoffRequest);

        return redirect()->route('writeoff-requests.index')->with('success', 'Norakstisanas pieteikums nosutits izskatisanai');
    }

    public function review(Request $request, WriteoffRequest $writeoffRequest)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('writeoff_requests')) {
            return back()->with('error', 'Norakstisanas pieteikumu tabula sobrid nav pieejama.');
        }

        if ($writeoffRequest->status !== WriteoffRequest::STATUS_SUBMITTED) {
            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED])],
            'review_notes' => ['nullable', 'string'],
        ]);

        $before = $writeoffRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $writeoffRequest, $manager) {
            $writeoffRequest->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $manager->id,
                'review_notes' => $validated['review_notes'] ?: null,
            ]);

            if ($validated['status'] !== 'approved') {
                return;
            }

            $device = $writeoffRequest->device()->lockForUpdate()->first();

            if (! $device || $device->status === 'repair') {
                throw ValidationException::withMessages([
                    'status' => ['Ierici nevar norakstit, kamer tai ir aktivs remonta process.'],
                ]);
            }

            $device->forceFill([
                'status' => Device::STATUS_WRITEOFF,
                'assigned_to_id' => null,
            ])->save();
        });

        $after = $writeoffRequest->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($manager->id, $writeoffRequest, $before, $after);

        return back()->with('success', 'Norakstisanas pieteikums izskatits');
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
