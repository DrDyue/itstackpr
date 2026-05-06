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
 * Ko dara: Pārvalda parastā lietotāja pieteikumu centru.
 *
 * Kā strādā: Ļauj veidot, labot un atcelt savus remonta, norakstīšanas vai nodošanas pieteikumus, ievērojot ierīces pieejamības nosacījumus.
 *
 * Kad pielietojas: Kad darbinieks strādā ar saviem pieteikumiem bez administratora tiesībām.
 */
class UserRequestCenterController extends Controller
{
    use HasRepairStatusLabels;

    /**
     * Ko dara: Lietotāju nosūta uz remonta pieteikumu sarakstu kā galveno ieejas punktu.
     *
     * Kā strādā: Šī metode vienkārši pāradresē uz remonta pieteikumu sarakstu, jo tas ir primārais pieteikuma tips. Citi pieteikuma veidi (norakstīšana, nodošana) ir pieejami no turienes.
     *
     * Kad pielietojas: Izsaukšana: GET /my-requests | Pieejams: parasts lietotājs (neadministrators). Scenārijs: Lietotājs klikšķina uz "Mani pieteikumi" sānjoslā.
     */
    public function index(Request $request)
    {
        $this->requireRegularUser();

        return redirect()->route('repair-requests.index');
    }

    /**
     * Ko dara: Saglabā jaunu pieprasījumu — remonta, norakstīšanas vai nodošanas.
     *
     * Kā strādā: Validē pieprasījuma tipu un ierīci, pārbauda, vai ierīce jau nav aizņemta ar citu aktīvu pieteikumu, un izveido attiecīgo ierakstu datubāzē. Katrs veids tiek reģistrēts audita žurnālā un piesaistīts aktīvās sesijas lietotājam.
     *
     * Kad pielietojas: Izsaukšana: POST /my-requests | Pieejams: parasts lietotājs (neadministrators). Scenārijs: Lietotājs izvēlas ierīci un pieteikuma tipu "Jauns pieteikums" formā.
     */
    public function store(Request $request)
    {
        $user = $this->requireRegularUser();

        // Viena forma apkalpo trīs pieteikumu veidus, tāpēc katra apraksta lauka
        // obligātums ir atkarīgs no izvēlētā request_type.
        $validated = $this->validateInput($request, [
            'request_type' => ['required', Rule::in(['repair', 'writeoff', 'transfer'])],
            'device_id' => ['required', 'exists:devices,id'],
            'description' => [Rule::requiredIf(fn () => $request->input('request_type') === 'repair'), 'nullable', 'string', 'min:10', 'max:2000'],
            'reason' => [Rule::requiredIf(fn () => $request->input('request_type') === 'writeoff'), 'nullable', 'string', 'min:10', 'max:2000'],
            'transfered_to_id' => [Rule::requiredIf(fn () => $request->input('request_type') === 'transfer'), 'nullable', 'exists:users,id', Rule::notIn([$user->id])],
            'transfer_reason' => [Rule::requiredIf(fn () => $request->input('request_type') === 'transfer'), 'nullable', 'string', 'min:10', 'max:2000'],
        ], [
            'request_type.required' => 'Izvēlies pieteikuma tipu.',
            'description.required' => 'Apraksti remonta problēmu.',
            'description.min' => 'Aprakstam jābūt vismaz 10 rakstzīmēm.',
            'description.max' => 'Apraksts nedrīkst pārsniegt 2000 rakstzīmes.',
            'reason.required' => 'Apraksti norakstīšanas iemeslu.',
            'reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
            'reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
            'transfered_to_id.required' => 'Izvēlies lietotāju, kam nodot ierīci.',
            'transfered_to_id.not_in' => 'Nevar nodot ierīci sev pašam.',
            'transfer_reason.required' => 'Apraksti nodošanas iemeslu.',
            'transfer_reason.min' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
            'transfer_reason.max' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
        ]);

        // Lietotājs drīkst veidot pieteikumu tikai savai aktīvajai ierīcei, kas
        // vēl nav norakstīta un nav paslēpta ar pieejamības nosacījumiem.
        $device = $this->availableDevicesForUser($user)->find($validated['device_id']);
        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['Vari veidot pieteikumus tikai savai aktīvajai ierīcei.'],
            ]);
        }

        // Pirms ieraksta izveides pārbaudām, vai nav cita aktīva pieteikuma,
        // kas bloķē remonta, norakstīšanas vai nodošanas sākšanu.
        $this->ensureDeviceCanAcceptRequest($device, $validated['request_type']);

        // Tālāk pēc pieprasījuma tipa izveidojam konkrētās tabulas ierakstu un
        // lietotāju pārvirzām uz atbilstošo pieteikumu sadaļu.
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
     * Ko dara: Atļauj labot tikai aprakstošo lauku iesniegtam pieprasījumam.
     *
     * Kā strādā: Lietotājs var labot tikai sava pieprasījuma tekstu (aprakstu vai iemeslu), kamēr tas vēl nav izskatīts. Izmaiņas tiek reģistrētas audita žurnālā.
     *
     * Kad pielietojas: Izsaukšana: PATCH /my-requests/{type}/{id} | Pieejams: parasts lietotājs. Scenārijs: Lietotājs klikšķina "Labot" pie iesniegtā pieteikuma un saglabā jauno tekstu.
     */
    public function update(Request $request, string $requestType, int $requestId)
    {
        $user = $this->requireRegularUser();
        [$editableRequest, $config] = $this->editableRequestForUser($user, $requestType, $requestId);
        $field = $config['field'];
        // Katram pieteikuma tipam ir viens rediģējamais teksta lauks;
        // pārējos biznesa laukus pēc iesniegšanas lietotājs vairs nevar mainīt.
        $validated = $this->validateInput($request, [
            $field => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            $field . '.required' => $config['required_message'],
            $field . '.min' => $config['min_message'],
            $field . '.max' => $config['max_message'],
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
     * Ko dara: Atceļ vēl neizskatītu pieprasījumu.
     *
     * Kā strādā: Atrod tikai pašam lietotājam piederošu un vēl neizskatītu pieteikumu, pieraksta atcelšanas/dzēšanas auditu un izdzēš ierakstu.
     *
     * Kad pielietojas: Izsaukšana: DELETE /my-requests/{type}/{id} | Pieejams: parasts lietotājs.
     */
    public function destroy(string $requestType, int $requestId)
    {
        $user = $this->requireRegularUser();
        [$editableRequest, $config] = $this->editableRequestForUser($user, $requestType, $requestId);

        // Atcelšanu auditā pierakstām atsevišķi no fiziskās dzēšanas,
        // lai žurnālā paliek skaidrs biznesa notikums un tehniskā darbība.
        AuditTrail::cancel($user->id, $editableRequest, 'Atcelts pieteikums: '.AuditTrail::labelFor($editableRequest));
        AuditTrail::deleted($user->id, $editableRequest, $config['deleted_audit_message']);
        $editableRequest->delete();

        return redirect()->route($config['index_route'])->with('success', $config['deleted_message']);
    }

    /**
     * Ko dara: Pārbauda, vai aktīvais lietotājs ir parastais lietotājs (nevis administrators/vadītājs).
     *
     * Kā strādā: Ja lietotājs nav autentificēts vai ir administrators, tiek atgriezta kļūda 403. Metode nodrošina, ka tikai parasti lietotāji var veikt darbības šajā kontrolierī.
     *
     * Kad pielietojas: Izsauc no: `index()`, `store()`, `update()`, `destroy()`.
     */
    private function requireRegularUser(): User
    {
        $user = $this->user();
        abort_unless($user, 403);
        abort_if($user->canManageRequests(), 403);

        return $user;
    }

    /**
     * Ko dara: Atgriež vaicājumu ar aktīvajām ierīcēm, kas piešķirtas konkrētajam lietotājam.
     *
     * Kā strādā: Iekļauj tikai aktīvā statusā esošās ierīces ar ielādētām attiecībām, kas nepieciešamas pieprasījumu veidošanas formu aizpildīšanai.
     *
     * Kad pielietojas: Izsauc no: `store()`.
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
     * Ko dara: Pārbauda, vai ierīce var pieņemt jaunu pieprasījumu, un meta izņēmumu, ja nevar.
     *
     * Kā strādā: Bloķē pieprasījuma izveidi, ja ierīce jau ir remontā, vai tai ir kāds cits aktīvs (iesniegts) pieteikums — neatkarīgi no tipa. Katrai pieprasījuma tipa un bloķēšanas stāvokļa kombinācijai tiek atgriezts informatīvs kļūdas ziņojums.
     *
     * Kad pielietojas: Izsauc no: `store()`.
     */
    private function ensureDeviceCanAcceptRequest(Device $device, string $requestType): void
    {
        if ($device->status === Device::STATUS_REPAIR) {
            throw ValidationException::withMessages([
                'device_id' => ['Šai ierīcei jau notiek remonts (' . $this->repairStatusLabel($device->activeRepair?->status) . '), tāpēc jaunus pieteikumus veidot nevar.'],
            ]);
        }

        // Katru pieteikumu tipu pārbaudām atsevišķi, jo kļūdas ziņai jābūt precīzai:
        // lietotājam jāsaprot, kas tieši bloķē nākamās darbības ar ierīci.
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

        // Tālāk katram pieteikuma tipam pārbaudām ne tikai savu dublikātu,
        // bet arī pārējos aktīvos pieteikumus, jo viena ierīce vienlaikus nedrīkst būt vairākās plūsmās.
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

        // Norakstīšana ir gala plūsma, tāpēc tai jāgaida, kamēr nav aktīva remonta vai nodošanas pieteikuma.
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

        // Nodošanu bloķējam, ja ierīcei jau ir iesniegts jebkura veida pieteikums,
        // lai atbildīgais lietotājs nemainītos brīdī, kad par ierīci jau tiek pieņemts cits lēmums.
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
     * Ko dara: Atrod rediģējamo pieprasījumu un pārliecinās, ka tas pieder aktīvajam lietotājam.
     *
     * Kā strādā: Atgriež divdaļīgu masīvu — modeli un konfigurāciju. Ja pieprasījums nepieder lietotājam vai nav iesniegtā statusā, tiek atgriezta kļūda 403.
     *
     * Kad pielietojas: Izsauc no: `update()`, `destroy()`.
     */
    private function editableRequestForUser(User $user, string $requestType, int $requestId): array
    {
        $config = $this->editableRequestConfig($requestType);
        /** @var Model $editableRequest */
        $editableRequest = $config['model']::query()
            ->whereKey($requestId)
            ->where('responsible_user_id', $user->id)
            ->firstOrFail();

        // Labot vai dzēst drīkst tikai vēl neizskatītu pieteikumu;
        // pēc statusa maiņas to kontrolē jau vadītāja lēmums, nevis iesniedzējs.
        abort_unless(($editableRequest->status ?? null) === $config['submitted_status'], 403);

        return [$editableRequest, $config];
    }

    /**
     * Ko dara: Atgriež pieprasījuma tipa konfigurāciju rediģēšanas un atcelšanas darbībām.
     *
     * Kā strādā: Katram tipam (repair, writeoff, transfer) definē: modeli, lauku, ziņojumus, audita tekstu, ikonu un atbilstošo maršrutu. Neatpazīts tips rada 404 kļūdu.
     *
     * Kad pielietojas: Izsauc no: `editableRequestForUser()`.
     */
    private function editableRequestConfig(string $requestType): array
    {
        // Konfigurācija vienā vietā sasaista tipu ar modeli, rediģējamo lauku,
        // tekstiem un maršrutu, lai edit/delete metodes var strādāt kopīgi.
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
                'min_message' => 'Aprakstam jābūt vismaz 10 rakstzīmēm.',
                'max_message' => 'Apraksts nedrīkst pārsniegt 2000 rakstzīmes.',
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
                'min_message' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
                'max_message' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
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
                'min_message' => 'Iemeslam jābūt vismaz 10 rakstzīmēm.',
                'max_message' => 'Iemesls nedrīkst pārsniegt 2000 rakstzīmes.',
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
