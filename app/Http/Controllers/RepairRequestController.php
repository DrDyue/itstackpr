<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RepairRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'q' => trim((string) $request->query('q', '')),
        ];

        if (! $this->featureTableExists('repair_requests')) {
            return view('repair_requests.index', [
                'requests' => $this->emptyPaginator(),
                'filters' => $filters,
                'statuses' => [RepairRequest::STATUS_SUBMITTED, RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED],
                'canReview' => $user->canManageRequests(),
                'featureMessage' => 'Tabula repair_requests sobrid nav pieejama.',
            ]);
        }

        $requests = RepairRequest::query()
            ->with(['device.assignedTo', 'responsibleUser', 'reviewedBy', 'repair'])
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('responsible_user_id', $user->id))
            ->when($filters['status'] !== '' && in_array($filters['status'], [RepairRequest::STATUS_SUBMITTED, RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED], true), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['q'];

                $query->where(function (Builder $builder) use ($term) {
                    $builder->where('description', 'like', "%{$term}%")
                        ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('responsibleUser', fn (Builder $userQuery) => $userQuery->where('full_name', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('repair_requests.index', [
            'requests' => $requests,
            'filters' => $filters,
            'statuses' => [RepairRequest::STATUS_SUBMITTED, RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED],
            'canReview' => $user->canManageRequests(),
        ]);
    }

    public function create()
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('repair_requests')) {
            return view('repair_requests.create', [
                'devices' => collect(),
                'featureMessage' => 'Tabula repair_requests sobrid nav pieejama.',
            ]);
        }

        return view('repair_requests.create', [
            'devices' => $this->availableDevicesForUser($user)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

        if (! $this->featureTableExists('repair_requests')) {
            return redirect()->route('repair-requests.index')->with('error', 'Remonta pieteikumus sobrid nevar saglabat, jo tabula repair_requests nav pieejama.');
        }

        $validated = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt remontu tikai savai piesaistitai iericei.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums.'],
            ]);
        }

        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => $validated['description'],
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $repairRequest);

        return redirect()->route('repair-requests.index')->with('success', 'Remonta pieteikums nosutits izskatisanai');
    }

    public function review(Request $request, RepairRequest $repairRequest)
    {
        $manager = $this->requireManager();

        if (! $this->featureTableExists('repair_requests')) {
            return back()->with('error', 'Remonta pieteikumu tabula sobrid nav pieejama.');
        }

        if ($repairRequest->status !== RepairRequest::STATUS_SUBMITTED) {
            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED])],
            'review_notes' => ['nullable', 'string'],
            'repair_type' => ['nullable', Rule::in(['internal', 'external'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
        ]);

        $repairRequest->loadMissing(['device', 'responsibleUser']);
        $before = $repairRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        $payload = [
            'status' => $validated['status'],
            'reviewed_by_user_id' => $manager->id,
            'review_notes' => $validated['review_notes'] ?: null,
        ];

        DB::transaction(function () use ($validated, $repairRequest, $manager, &$payload) {
            if ($validated['status'] === 'approved') {
                $device = $repairRequest->device()->lockForUpdate()->first();

                if (! $device || $device->status === Device::STATUS_WRITEOFF) {
                    throw ValidationException::withMessages([
                        'status' => ['Pieteikumu nevar apstiprinat, jo ierice vairs nav pieejama remontam.'],
                    ]);
                }

                $repair = Repair::create([
                    'device_id' => $repairRequest->device_id,
                    'issue_reported_by' => $repairRequest->responsible_user_id,
                    'accepted_by' => $manager->id,
                    'description' => $repairRequest->description,
                    'status' => 'waiting',
                    'repair_type' => $validated['repair_type'] ?: 'internal',
                    'priority' => $validated['priority'] ?: 'medium',
                    'request_id' => $repairRequest->id,
                ]);

                $device->forceFill(['status' => 'repair'])->save();

                if (array_key_exists('repair_id', $repairRequest->getAttributes())) {
                    $payload['repair_id'] = $repair->id;
                }
            }

            $repairRequest->update($payload);
        });

        $after = $repairRequest->fresh()->only(array_keys($before));
        AuditTrail::updatedFromState($manager->id, $repairRequest, $before, $after);

        return back()->with('success', 'Remonta pieteikums izskatits');
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
