<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WriteoffRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        abort_unless($user, 403);
        $availableStatuses = [
            WriteoffRequest::STATUS_SUBMITTED,
            WriteoffRequest::STATUS_APPROVED,
            WriteoffRequest::STATUS_REJECTED,
        ];
        $statusFilterTouched = $request->has('statuses_filter');
        $selectedStatuses = collect($request->query('status', $statusFilterTouched ? [] : [WriteoffRequest::STATUS_SUBMITTED]))
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

        if (! $this->featureTableExists('writeoff_requests')) {
            return view('writeoff_requests.index', [
                'requests' => $this->emptyPaginator(),
                'filters' => $filters,
                'statuses' => $availableStatuses,
                'statusLabels' => $this->requestStatusLabels(),
                'canReview' => $user->canManageRequests(),
                'featureMessage' => 'Tabula writeoff_requests sobrid nav pieejama.',
            ]);
        }

        $requests = WriteoffRequest::query()
            ->with(['device.assignedTo', 'responsibleUser', 'reviewedBy'])
            ->when(! $user->canManageRequests(), fn (Builder $query) => $query->where('responsible_user_id', $user->id))
            ->whereIn('status', $filters['statuses'] === [] ? ['__none__'] : $filters['statuses'])
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
        abort_if($user->canManageRequests(), 403);

        if (! $this->featureTableExists('writeoff_requests')) {
            return redirect()->route('writeoff-requests.index')->with('error', 'Norakstisanas pieteikumus sobrid nevar saglabat, jo tabula writeoff_requests nav pieejama.');
        }

        $validated = $this->validateInput($request, [
            'device_id' => ['required', 'exists:devices,id'],
            'reason' => ['required', 'string'],
        ], [
            'device_id.required' => 'Izvelies ierici, kuru velies norakstit.',
            'reason.required' => 'Apraksti norakstisanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari pieteikt norakstisanu tikai savai piesaistitai iericei.'],
            ]);
        }

        $this->ensureDeviceCanAcceptWriteoffRequest($device);

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

        $validated = $this->validateInput($request, [
            'status' => ['required', Rule::in([WriteoffRequest::STATUS_APPROVED, WriteoffRequest::STATUS_REJECTED])],
        ], [
            'status.required' => 'Izvelies lemumu norakstisanas pieteikumam.',
        ]);

        $before = $writeoffRequest->only(['status', 'reviewed_by_user_id', 'review_notes']);

        DB::transaction(function () use ($validated, $writeoffRequest, $manager) {
            $writeoffRequest->update([
                'status' => $validated['status'],
                'reviewed_by_user_id' => $manager->id,
                'review_notes' => null,
            ]);

            if ($validated['status'] !== WriteoffRequest::STATUS_APPROVED) {
                return;
            }

            $device = $writeoffRequest->device()->lockForUpdate()->first();

            if (! $device) {
                throw ValidationException::withMessages([
                    'status' => ['Ierice norakstisanai vairs nav atrasta.'],
                ]);
            }

            if ($device->status !== Device::STATUS_ACTIVE || $device->activeRepair()->exists()) {
                throw ValidationException::withMessages([
                    'status' => ['Norakstit var tikai aktivu ierici bez aktiva remonta procesa.'],
                ]);
            }

            $device->forceFill([
                'status' => Device::STATUS_WRITEOFF,
                'assigned_to_id' => null,
                'building_id' => null,
                'room_id' => null,
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
            ->with(['type', 'building', 'room', 'activeRepair'])
            ->orderBy('name');
    }

    private function ensureDeviceCanAcceptWriteoffRequest(Device $device): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau notiek remonts (' . $this->repairStatusLabel($device->activeRepair?->status) . '), tapec norakstisanas pieteikumu veidot nevar.'],
            ]);
        }

        if (WriteoffRequest::query()->where('device_id', $device->id)->where('status', WriteoffRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss norakstisanas pieteikums.'],
            ]);
        }

        if (RepairRequest::query()->where('device_id', $device->id)->where('status', RepairRequest::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss remonta pieteikums, tapec norakstisanas pieteikumu veidot nevar.'],
            ]);
        }

        if (DeviceTransfer::query()->where('device_id', $device->id)->where('status', DeviceTransfer::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'device_id' => ['Sai iericei jau ir gaidoss nodosanas pieteikums, tapec norakstisanas pieteikumu veidot nevar.'],
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
