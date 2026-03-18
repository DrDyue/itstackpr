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

        $requests = RepairRequest::query()
            ->with(['device.assignedUser', 'responsibleUser', 'reviewedBy', 'repair'])
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('responsible_user_id', $user->id))
            ->when($filters['status'] !== '' && in_array($filters['status'], ['pending', 'approved', 'denied'], true), fn (Builder $query) => $query->where('status', $filters['status']))
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
            'statuses' => ['pending', 'approved', 'denied'],
            'canReview' => $user->canManageRequests(),
            'reviewUsers' => User::active()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_IT_WORKER])
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    public function create()
    {
        $user = $this->user();
        abort_unless($user, 403);

        return view('repair_requests.create', [
            'devices' => $this->availableDevicesForUser($user)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);

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

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums.'],
            ]);
        }

        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        AuditTrail::created($user->id, $repairRequest);

        return redirect()->route('repair-requests.index')->with('success', 'Remonta pieteikums nosutits izskatisanai');
    }

    public function review(Request $request, RepairRequest $repairRequest)
    {
        $manager = $this->requireManager();

        if ($repairRequest->status !== 'pending') {
            return back()->with('error', 'Sis pieteikums jau ir izskatits.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'denied'])],
            'review_notes' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'repair_type' => ['nullable', Rule::in(['internal', 'external'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
        ]);

        $repairRequest->loadMissing(['device', 'responsibleUser']);
        $before = $repairRequest->only(['status', 'reviewed_by_user_id', 'repair_id', 'review_notes']);

        $payload = [
            'status' => $validated['status'],
            'reviewed_by_user_id' => $manager->id,
            'review_notes' => $validated['review_notes'] ?: null,
        ];

        DB::transaction(function () use ($validated, $repairRequest, $manager, &$payload) {
            if ($validated['status'] === 'approved') {
                $device = $repairRequest->device()->lockForUpdate()->first();

                if (! $device || $device->status === 'written_off') {
                    throw ValidationException::withMessages([
                        'status' => ['Pieteikumu nevar apstiprinat, jo ierice vairs nav pieejama remontam.'],
                    ]);
                }

                $repair = Repair::create([
                    'device_id' => $repairRequest->device_id,
                    'reported_by_user_id' => $repairRequest->responsible_user_id,
                    'assigned_to_user_id' => $validated['assigned_to_user_id'] ?: $manager->id,
                    'accepted_by_user_id' => $manager->id,
                    'description' => $repairRequest->description,
                    'status' => 'waiting',
                    'device_status_before_repair' => $this->normalizeRepairRestoreStatus($device->status),
                    'repair_type' => $validated['repair_type'] ?: 'internal',
                    'priority' => $validated['priority'] ?: 'medium',
                ]);

                $device->forceFill(['status' => 'repair'])->save();
                $payload['repair_id'] = $repair->id;
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
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['written_off', 'repair'])
            ->with(['type', 'building', 'room'])
            ->orderBy('name');
    }

    private function normalizeRepairRestoreStatus(?string $status): string
    {
        return in_array($status, ['active', 'reserve', 'broken', 'kitting'], true) ? $status : 'active';
    }
}
