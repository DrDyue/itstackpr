<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserRequestCenterController extends Controller
{
    public function index(Request $request)
    {
        $this->requireRegularUser();

        return redirect()->route('repair-requests.index');
    }

    public function create(Request $request)
    {
        $this->requireRegularUser();

        $type = (string) $request->query('type', '');
        $deviceId = trim((string) $request->query('device_id', ''));
        $params = $deviceId !== '' ? ['device_id' => $deviceId] : [];

        return redirect()->route(match ($type) {
            'writeoff' => 'writeoff-requests.create',
            'transfer' => 'device-transfers.create',
            'repair' => 'repair-requests.create',
            default => 'repair-requests.create',
        }, $params);
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

            return redirect()->route('repair-requests.index')->with('success', 'Remonta pieteikums izveidots.');
        }

        if ($validated['request_type'] === 'writeoff') {
            $writeoffRequest = WriteoffRequest::create([
                'device_id' => $device->id,
                'responsible_user_id' => $user->id,
                'reason' => (string) $validated['reason'],
                'status' => WriteoffRequest::STATUS_SUBMITTED,
            ]);

            AuditTrail::created($user->id, $writeoffRequest);

            return redirect()->route('writeoff-requests.index')->with('success', 'Norakstisanas pieteikums izveidots.');
        }

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => (int) $validated['transfered_to_id'],
            'transfer_reason' => (string) $validated['transfer_reason'],
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $transfer);

        return redirect()->route('device-transfers.index')->with('success', 'Nodosanas pieteikums izveidots.');
    }

    public function edit(string $requestType, int $requestId)
    {
        $user = $this->requireRegularUser();
        [$editableRequest, $config] = $this->editableRequestForUser($user, $requestType, $requestId);
        $editableRequest->loadMissing('device');

        return view('my_requests.edit', [
            'editableRequest' => $editableRequest,
            'requestType' => $requestType,
            'fieldName' => $config['field'],
            'fieldLabel' => $config['label'],
            'fieldValue' => (string) ($editableRequest->{$config['field']} ?? ''),
            'pageTitle' => $config['title'],
            'pageSubtitle' => $config['subtitle'],
            'typeLabel' => $config['type_label'],
            'icon' => $config['icon'],
        ]);
    }

    public function update(Request $request, string $requestType, int $requestId)
    {
        $user = $this->requireRegularUser();
        [$editableRequest, $config] = $this->editableRequestForUser($user, $requestType, $requestId);
        $field = $config['field'];
        $validated = $this->validateInput($request, [
            $field => ['required', 'string'],
        ], [
            $field . '.required' => $config['required_message'],
        ]);

        $before = $editableRequest->only([$field]);
        $editableRequest->update([
            $field => (string) $validated[$field],
        ]);

        AuditTrail::updatedFromState($user->id, $editableRequest, $before, [
            $field => $editableRequest->{$field},
        ]);

        return redirect()->route($config['index_route'])->with('success', $config['updated_message']);
    }

    public function destroy(string $requestType, int $requestId)
    {
        $user = $this->requireRegularUser();
        [$editableRequest, $config] = $this->editableRequestForUser($user, $requestType, $requestId);

        AuditTrail::deleted($user->id, $editableRequest, $config['deleted_audit_message']);
        $editableRequest->delete();

        return redirect()->route($config['index_route'])->with('success', $config['deleted_message']);
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

    private function editableRequestForUser(User $user, string $requestType, int $requestId): array
    {
        $config = $this->editableRequestConfig($requestType);
        /** @var Model $editableRequest */
        $editableRequest = $config['model']::query()
            ->whereKey($requestId)
            ->where('responsible_user_id', $user->id)
            ->firstOrFail();

        abort_unless(($editableRequest->status ?? null) === $config['submitted_status'], 403);

        return [$editableRequest, $config];
    }

    private function editableRequestConfig(string $requestType): array
    {
        return match ($requestType) {
            'repair' => [
                'model' => RepairRequest::class,
                'field' => 'description',
                'label' => 'Apraksts',
                'title' => 'Labot remonta pieteikumu',
                'subtitle' => 'Vari mainit tikai problemu aprakstu, kamer pieteikums vel nav izskatits.',
                'type_label' => 'Remonta pieteikums',
                'icon' => 'repair-request',
                'required_message' => 'Apraksti remonta problemu.',
                'updated_message' => 'Remonta pieteikums atjaunots.',
                'deleted_message' => 'Remonta pieteikums atcelts.',
                'deleted_audit_message' => 'Lietotajs atcela iesniegtu remonta pieteikumu.',
                'index_route' => 'repair-requests.index',
                'submitted_status' => RepairRequest::STATUS_SUBMITTED,
            ],
            'writeoff' => [
                'model' => WriteoffRequest::class,
                'field' => 'reason',
                'label' => 'Iemesls',
                'title' => 'Labot norakstisanas pieteikumu',
                'subtitle' => 'Vari mainit tikai norakstisanas iemeslu, kamer pieteikums vel nav izskatits.',
                'type_label' => 'Norakstisanas pieteikums',
                'icon' => 'writeoff',
                'required_message' => 'Apraksti norakstisanas iemeslu.',
                'updated_message' => 'Norakstisanas pieteikums atjaunots.',
                'deleted_message' => 'Norakstisanas pieteikums atcelts.',
                'deleted_audit_message' => 'Lietotajs atcela iesniegtu norakstisanas pieteikumu.',
                'index_route' => 'writeoff-requests.index',
                'submitted_status' => WriteoffRequest::STATUS_SUBMITTED,
            ],
            'transfer' => [
                'model' => DeviceTransfer::class,
                'field' => 'transfer_reason',
                'label' => 'Nodosanas iemesls',
                'title' => 'Labot nodosanas pieteikumu',
                'subtitle' => 'Vari mainit tikai nodosanas iemeslu, kamer sanemejs vel nav izskatijis pieteikumu.',
                'type_label' => 'Nodosanas pieteikums',
                'icon' => 'transfer',
                'required_message' => 'Apraksti nodosanas iemeslu.',
                'updated_message' => 'Nodosanas pieteikums atjaunots.',
                'deleted_message' => 'Nodosanas pieteikums atcelts.',
                'deleted_audit_message' => 'Lietotajs atcela iesniegtu nodosanas pieteikumu.',
                'index_route' => 'device-transfers.index',
                'submitted_status' => DeviceTransfer::STATUS_SUBMITTED,
            ],
            default => abort(404),
        };
    }
}
