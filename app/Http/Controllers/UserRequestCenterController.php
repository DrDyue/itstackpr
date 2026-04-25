<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasRepairStatusLabels;
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

/**
 * Vienotais lietotāja pieprasījumu ieejas punkts.
 *
 * Praktiski tas darbojas kā novirzītājs un kopīga vieta labošanai/atcelšanai,
 * kamēr galvenās plūsmas dzīvo atsevišķos kontrolieros.
 */
class UserRequestCenterController extends Controller
{
    use HasRepairStatusLabels;

    /**
     * Lietotāju nosūta uz remonta pieteikumu sarakstu kā galveno ieejas punktu.
     */
    public function index(Request $request)
    {
        $this->requireRegularUser();

        return redirect()->route('repair-requests.index');
    }

    /**
     * Saglabā jaunu pieprasījumu — remonta, norakstīšanas vai nodošanas.
     *
     * Validē pieprasījuma tipu un ierīci, pārbauda, vai ierīce jau nav aizņemta
     * ar citu aktīvu pieteikumu, un izveido attiecīgo ierakstu datubāzē.
     * Katrs veids tiek reģistrēts audita žurnālā un piesaistīts aktīvās sesijas lietotājam.
     */
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
            'request_type.required' => 'Izvēlies pieteikuma tipu.',
            'description.required' => 'Apraksti remonta problēmu.',
            'reason.required' => 'Apraksti norakstīšanas iemeslu.',
            'transfered_to_id.required' => 'Izvēlies lietotāju, kam nodot ierīci.',
            'transfer_reason.required' => 'Apraksti nodošanas iemeslu.',
        ]);

        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari veidot pieteikumus tikai savai aktīvajai ierīcei.'],
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
            AuditTrail::submit($user->id, $repairRequest, 'Iesniegts remonta pieteikums: '.AuditTrail::labelFor($repairRequest));

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
            AuditTrail::submit($user->id, $writeoffRequest, 'Iesniegts norakstīšanas pieteikums: '.AuditTrail::labelFor($writeoffRequest));

            return redirect()->route('writeoff-requests.index')->with('success', 'Norakstīšanas pieteikums izveidots.');
        }

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => (int) $validated['transfered_to_id'],
            'transfer_reason' => (string) $validated['transfer_reason'],
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        AuditTrail::created($user->id, $transfer);
        AuditTrail::submit($user->id, $transfer, 'Iesniegts ierīces nodošanas pieteikums: '.AuditTrail::labelFor($transfer));

        return redirect()->route('device-transfers.index')->with('success', 'Nodošanas pieteikums izveidots.');
    }

    /**
     * Atļauj labot tikai aprakstošo lauku iesniegtam pieprasījumam.
     *
     * Lietotājs var labot tikai sava pieprasījuma tekstu (aprakstu vai iemeslu),
     * kamēr tas vēl nav izskatīts. Izmaiņas tiek reģistrētas audita žurnālā.
     */
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

    /**
     * Atceļ vēl neizskatītu pieprasījumu.
     */
    public function destroy(string $requestType, int $requestId)
    {
        $user = $this->requireRegularUser();
        [$editableRequest, $config] = $this->editableRequestForUser($user, $requestType, $requestId);

        AuditTrail::cancel($user->id, $editableRequest, 'Atcelts pieteikums: '.AuditTrail::labelFor($editableRequest));
        AuditTrail::deleted($user->id, $editableRequest, $config['deleted_audit_message']);
        $editableRequest->delete();

        return redirect()->route($config['index_route'])->with('success', $config['deleted_message']);
    }

    /**
     * Pārbauda, vai aktīvais lietotājs ir parastais lietotājs (nevis administrators/vadītājs).
     *
     * Ja lietotājs nav autentificēts vai ir administrators, tiek atgriezta kļūda 403.
     * Metode nodrošina, ka tikai parasti lietotāji var veikt darbības šajā kontrolierī.
     */
    private function requireRegularUser(): User
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        return $user;
    }

    /**
     * Atgriež vaicājumu ar aktīvajām ierīcēm, kas piešķirtas konkrētajam lietotājam.
     *
     * Iekļauj tikai aktīvā statusā esošās ierīces ar ielādētām attiecībām,
     * kas nepieciešamas pieprasījumu veidošanas formu aizpildīšanai.
     */
    private function availableDevicesForUser(User $user)
    {
        return Device::query()
            ->where('assigned_to_id', $user->id)
            ->where('status', Device::STATUS_ACTIVE)
            ->with(['building', 'room.building', 'type', 'activeRepair'])
            ->orderBy('name');
    }

    /**
     * Pārbauda, vai ierīce var pieņemt jaunu pieprasījumu, un meta izņēmumu, ja nevar.
     *
     * Bloķē pieprasījuma izveidi, ja ierīce jau ir remontā, vai tai ir
     * kāds cits aktīvs (iesniegts) pieteikums — neatkarīgi no tipa.
     * Katrai pieprasījuma tipa un bloķēšanas stāvokļa kombinācijai tiek
     * atgriezts informatīvs kļūdas ziņojums.
     */
    private function ensureDeviceCanAcceptRequest(Device $device, string $requestType): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau notiek remonts (' . $this->repairStatusLabel($device->activeRepair?->status) . '), tāpēc jaunus pieteikumus veidot nevar.'],
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
                    'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums.'],
                ]);
            }

            if ($hasPendingWriteoff) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums, tāpēc remonta pieteikumu veidot nevar.'],
                ]);
            }

            if ($hasPendingTransfer) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums, tāpēc remonta pieteikumu veidot nevar.'],
                ]);
            }
        }

        if ($requestType === 'writeoff') {
            if ($hasPendingWriteoff) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums.'],
                ]);
            }

            if ($hasPendingRepair) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums, tāpēc norakstīšanas pieteikumu veidot nevar.'],
                ]);
            }

            if ($hasPendingTransfer) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums, tāpēc norakstīšanas pieteikumu veidot nevar.'],
                ]);
            }
        }

        if ($requestType === 'transfer') {
            if ($hasPendingTransfer) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs nodošanas pieteikums.'],
                ]);
            }

            if ($hasPendingRepair) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs remonta pieteikums, tāpēc nodošanas pieteikumu veidot nevar.'],
                ]);
            }

            if ($hasPendingWriteoff) {
                throw ValidationException::withMessages([
                    'device_id' => ['Šai ierīcei jau ir gaidošs norakstīšanas pieteikums, tāpēc nodošanas pieteikumu veidot nevar.'],
                ]);
            }
        }
    }



    /**
     * Atrod rediģējamo pieprasījumu un pārliecinās, ka tas pieder aktīvajam lietotājam.
     *
     * Atgriež divdaļīgu masīvu — modeli un konfigurāciju. Ja pieprasījums
     * nepieder lietotājam vai nav iesniegtā statusā, tiek atgriezta kļūda 403.
     */
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

    /**
     * Atgriež pieprasījuma tipa konfigurāciju rediģēšanas un atcelšanas darbībām.
     *
     * Katram tipam (repair, writeoff, transfer) definē: modeli, lauku, ziņojumus,
     * audita tekstu, ikonu un atbilstošo maršrutu. Neatpazīts tips rada 404 kļūdu.
     */
    private function editableRequestConfig(string $requestType): array
    {
        return match ($requestType) {
            'repair' => [
                'model' => RepairRequest::class,
                'field' => 'description',
                'label' => 'Apraksts',
                'title' => 'Labot remonta pieteikumu',
                'subtitle' => 'Vari mainīt tikai problēmu aprakstu, kamēr pieteikums vēl nav izskatīts.',
                'type_label' => 'Remonta pieteikums',
                'icon' => 'repair-request',
                'required_message' => 'Apraksti remonta problēmu.',
                'updated_message' => 'Remonta pieteikums atjaunots.',
                'deleted_message' => 'Remonta pieteikums atcelts.',
                'deleted_audit_message' => 'Lietotājs atcēla iesniegtu remonta pieteikumu.',
                'index_route' => 'repair-requests.index',
                'submitted_status' => RepairRequest::STATUS_SUBMITTED,
            ],
            'writeoff' => [
                'model' => WriteoffRequest::class,
                'field' => 'reason',
                'label' => 'Iemesls',
                'title' => 'Labot norakstīšanas pieteikumu',
                'subtitle' => 'Vari mainīt tikai norakstīšanas iemeslu, kamēr pieteikums vēl nav izskatīts.',
                'type_label' => 'Norakstīšanas pieteikums',
                'icon' => 'writeoff',
                'required_message' => 'Apraksti norakstīšanas iemeslu.',
                'updated_message' => 'Norakstīšanas pieteikums atjaunots.',
                'deleted_message' => 'Norakstīšanas pieteikums atcelts.',
                'deleted_audit_message' => 'Lietotājs atcēla iesniegtu norakstīšanas pieteikumu.',
                'index_route' => 'writeoff-requests.index',
                'submitted_status' => WriteoffRequest::STATUS_SUBMITTED,
            ],
            'transfer' => [
                'model' => DeviceTransfer::class,
                'field' => 'transfer_reason',
                'label' => 'Nodošanas iemesls',
                'title' => 'Labot nodošanas pieteikumu',
                'subtitle' => 'Vari mainīt tikai nodošanas iemeslu, kamēr saņēmējs vēl nav izskatījis pieteikumu.',
                'type_label' => 'Nodošanas pieteikums',
                'icon' => 'transfer',
                'required_message' => 'Apraksti nodošanas iemeslu.',
                'updated_message' => 'Nodošanas pieteikums atjaunots.',
                'deleted_message' => 'Nodošanas pieteikums atcelts.',
                'deleted_audit_message' => 'Lietotājs atcēla iesniegtu nodošanas pieteikumu.',
                'index_route' => 'device-transfers.index',
                'submitted_status' => DeviceTransfer::STATUS_SUBMITTED,
            ],
            default => abort(404),
        };
    }
}
