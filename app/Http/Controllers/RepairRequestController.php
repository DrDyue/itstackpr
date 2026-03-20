<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
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
        $availableStatuses = [
            RepairRequest::STATUS_SUBMITTED,
            RepairRequest::STATUS_APPROVED,
            RepairRequest::STATUS_REJECTED,
        ];
        $statusFilterTouched = $request->has('statuses_filter');
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : [RepairRequest::STATUS_SUBMITTED]))
            ->map(fn (mixed $status) => trim((string) $status))
            ->filter(fn (string $status) => in_array($status, $availableStatuses, true))
            ->unique()
            ->values()
            ->all();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'statuses' => $selectedStatuses,
            'has_status_filter' => true,
        ];

        if (! $this->featureTableExists('repair_requests')) {
            return view('repair_requests.index', [
                'requests' => $this->emptyPaginator(),
                'requestSummary' => [
                    'total' => 0,
                    'submitted' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'canReview' => $user->canManageRequests(),
                'featureMessage' => 'Tabula repair_requests sobrid nav pieejama.',
            ]);
        }

        $baseQuery = RepairRequest::query()
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('responsible_user_id', $user->id));

        $requests = (clone $baseQuery)
            ->with(['device.assignedTo', 'responsibleUser', 'reviewedBy', 'repair'])
            ->whereIn('status', $filters['statuses'] === [] ? ['__none__'] : $filters['statuses'])
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
            'requestSummary' => [
                'total' => (clone $baseQuery)->count(),
                'submitted' => (clone $baseQuery)->where('status', RepairRequest::STATUS_SUBMITTED)->count(),
                'approved' => (clone $baseQuery)->where('status', RepairRequest::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', RepairRequest::STATUS_REJECTED)->count(),
            ],
            'filters' => $filters,
            'statuses' => $availableStatuses,
            'statusLabels' => $this->requestStatusLabels(),
            'canReview' => $user->canManageRequests(),
        ]);
    }

    public function create()
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

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
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('repair_requests')) {
            return redirect()->route('repair-requests.index')->with('error', 'Remonta pieteikumus sobrid nevar saglabat, jo tabula repair_requests nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvelies ierici, kurai piesaki remontu.',
            'description.required' => 'Apraksti remonta problemu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt remontu tikai savai piesaistitai iericei.'],
            ]);
        }

        $this->ensureDeviceCanAcceptRepairRequest($device);

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

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([RepairRequest::STATUS_APPROVED, RepairRequest::STATUS_REJECTED])],
        ], [
            'status.required' => 'Izvelies lemumu remonta pieteikumam.',
        ]);

        $repairRequest->loadMissing(['device', 'responsibleUser']);
        $before = $repairRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        $payload = [
            'status' => $validated['status'],
            'reviewed_by_user_id' => $manager->id,
            'review_notes' => null,
        ];

        DB::transaction(function () use ($validated, $repairRequest, $manager, &$payload) {
            if ($validated['status'] === 'approved') {
                $device = $repairRequest->device()->lockForUpdate()->first();

                if (! $device || $device->status === Device::STATUS_WRITEOFF) {
                    throw ValidationException::withMessages([
                        'status' => ['Pieteikumu nevar apstiprinat, jo ierice vairs nav pieejama remontam.'],
                    ]);
                }

                if ($device->repairs()->whereIn('status', ['waiting', 'in-progress'])->exists()) {
                    throw ValidationException::withMessages([
                        'status' => ['Sai iericei jau ir aktivs remonta ieraksts.'],
                    ]);
                }

                $repair = $this->createRepairRecord([
                    'device_id' => $repairRequest->device_id,
                    'issue_reported_by' => $repairRequest->responsible_user_id,
                    'accepted_by' => $manager->id,
                    'description' => $repairRequest->description,
                    'status' => 'waiting',
                    'repair_type' => 'internal',
                    'priority' => 'medium',
                    'start_date' => null,
                    'end_date' => null,
                    'cost' => null,
                    'vendor_name' => null,
                    'vendor_contact' => null,
                    'invoice_number' => null,
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
            ->with(['type', 'building', 'room', 'activeRepair'])
            ->orderBy('name');
    }

    private function ensureDeviceCanAcceptRepairRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau notiek remonts (' . $this->repairStatusLabel($device->activeRepair?->status) . '), tapec jaunu remonta pieteikumu veidot nevar.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums, tapec remonta pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums, tapec remonta pieteikumu veidot nevar.'],
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
}
