<?php

namespace App\Support;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\UserNotification;
use App\Models\WriteoffRequest;
use Illuminate\Support\Facades\Schema;

/**
 * Centralizēta lietotāju paziņojumu izveide.
 *
 * Kontrolieri šo klasi izsauc pēc biznesa notikuma pabeigšanas:
 * pieteikuma izskatīšanas, ierīces piešķiršanas vai remonta statusa maiņas.
 * Tādā veidā paziņojumu tekstu un datu formātu nav jādublē vairākos
 * kontrolieros, un frontend vienmēr saņem vienādas struktūras ierakstus.
 */
class UserNotifier
{
    /**
     * Izveido paziņojumu pieteikuma autoram pēc izskatīšanas.
     *
     * Atbalstītie pieteikumu tipi:
     * - remonta pieteikums;
     * - norakstīšanas pieteikums;
     * - ierīces nodošanas pieteikums.
     *
     * Izsauc no `review()` metodēm attiecīgajos kontrolieros pēc statusa
     * saglabāšanas un audita ieraksta izveides.
     */
    public function requestReviewed(RepairRequest|WriteoffRequest|DeviceTransfer $request, string $status): void
    {
        if (! $this->canStore()) {
            return;
        }

        $request->loadMissing(['device.type', 'device.room.building', 'device.building']);
        $device = $request->device;
        $approved = $status === 'approved';

        if ($request instanceof RepairRequest) {
            $this->notify($request->responsible_user_id, [
                'type' => $approved ? 'repair-approved' : 'repair-rejected',
                'accent' => $approved ? 'emerald' : 'rose',
                'title' => $approved ? 'Remonta pieteikums apstiprināts' : 'Remonta pieteikums noraidīts',
                'message' => $approved ? 'Ierīce nodota remonta plūsmā.' : 'Remonta pieteikums ir noraidīts.',
                'url' => route('repair-requests.index').'#repair-request-'.$request->id,
                'data' => $this->deviceDetails($device, [
                    'reason_label' => 'Pieteikums',
                    'reason_value' => $request->description ?: 'Apraksts nav pievienots.',
                    'submitted_at' => now()->format('d.m.Y H:i'),
                    'cta_label' => 'Atvērt remonta pieteikumu',
                ]),
            ]);

            return;
        }

        if ($request instanceof WriteoffRequest) {
            $this->notify($request->responsible_user_id, [
                'type' => $approved ? 'writeoff-approved' : 'writeoff-rejected',
                'accent' => $approved ? 'emerald' : 'rose',
                'title' => $approved ? 'Norakstīšanas pieteikums apstiprināts' : 'Norakstīšanas pieteikums noraidīts',
                'message' => $approved ? 'Ierīce ir pārcelta uz norakstīšanas statusu.' : 'Norakstīšanas pieteikums ir noraidīts.',
                'url' => route('writeoff-requests.index').'#writeoff-request-'.$request->id,
                'data' => $this->deviceDetails($device, [
                    'reason_label' => 'Iemesls',
                    'reason_value' => $request->reason ?: 'Iemesls nav pievienots.',
                    'submitted_at' => now()->format('d.m.Y H:i'),
                    'cta_label' => 'Atvērt norakstīšanas pieteikumu',
                ]),
            ]);

            return;
        }

        $this->notify($request->responsible_user_id, [
            'type' => $approved ? 'transfer-approved' : 'transfer-rejected',
            'accent' => $approved ? 'emerald' : 'rose',
            'title' => $approved ? 'Nodošanas pieteikums apstiprināts' : 'Nodošanas pieteikums noraidīts',
            'message' => $approved ? 'Saņēmējs apstiprināja ierīces pārņemšanu.' : 'Saņēmējs noraidīja ierīces pārņemšanu.',
            'url' => route('device-transfers.index').'#device-transfer-'.$request->id,
            'data' => $this->deviceDetails($device, [
                'recipient' => $request->transferTo?->full_name,
                'reason_label' => 'Iemesls',
                'reason_value' => $request->transfer_reason ?: 'Iemesls nav pievienots.',
                'submitted_at' => now()->format('d.m.Y H:i'),
                'cta_label' => 'Atvērt nodošanas pieteikumu',
            ]),
        ]);
    }

    /**
     * Paziņo lietotājam, ka viņam piešķirta ierīce.
     *
     * `previousUserId` ļauj neatkārtot paziņojumu, ja forma saglabāta bez
     * reālas atbildīgās personas maiņas. Tas tiek izmantots gan pilnajā ierīces
     * rediģēšanas formā, gan ātrajā "mainīt atbildīgo" darbībā.
     */
    public function deviceAssigned(Device $device, ?int $previousUserId = null): void
    {
        if (! $this->canStore() || ! $device->assigned_to_id) {
            return;
        }

        if ($previousUserId && (int) $previousUserId === (int) $device->assigned_to_id) {
            return;
        }

        $device->loadMissing(['type', 'room.building', 'building', 'assignedTo']);

        $this->notify($device->assigned_to_id, [
            'type' => 'device-assigned',
            'accent' => 'sky',
            'title' => 'Piešķirta jauna ierīce',
            'message' => 'Tev ir piešķirta ierīce: '.($device->name ?: $device->code ?: 'ierīce').'.',
            'url' => route('devices.show', $device),
            'data' => $this->deviceDetails($device, [
                'reason_label' => 'Piešķīrums',
                'reason_value' => 'Ierīce tagad ir piesaistīta tev kā atbildīgajai personai.',
                'submitted_at' => now()->format('d.m.Y H:i'),
                'cta_label' => 'Atvērt ierīces karti',
            ]),
        ]);
    }

    /**
     * Paziņo ierīces lietotājiem par būtisku remonta statusa maiņu.
     *
     * Paziņojums tiek veidots tikai gala lietotājam svarīgos statusos:
     * `in-progress`, `completed`, `cancelled`. Statuss `waiting` netiek sūtīts,
     * jo tas parasti rodas brīdī, kad pieteikums tikko apstiprināts un par to jau
     * tiek izveidots atsevišķs paziņojums.
     */
    public function repairStatusChanged(Repair $repair, string $previousStatus, string $nextStatus): void
    {
        if (! $this->canStore() || ! in_array($nextStatus, ['in-progress', 'completed', 'cancelled'], true)) {
            return;
        }

        $repair->loadMissing(['device.type', 'device.room.building', 'device.building']);
        $device = $repair->device;
        $recipientIds = collect([$repair->issue_reported_by, $device?->assigned_to_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        $labels = [
            'in-progress' => ['Remonts sākts', 'Ierīces remonts ir sākts.', 'sky'],
            'completed' => ['Remonts pabeigts', 'Ierīces remonts ir pabeigts.', 'emerald'],
            'cancelled' => ['Remonts atcelts', 'Ierīces remonts ir atcelts.', 'rose'],
        ];

        [$title, $message, $accent] = $labels[$nextStatus];

        foreach ($recipientIds as $userId) {
            $this->notify($userId, [
                'type' => 'repair-status-'.$nextStatus,
                'accent' => $accent,
                'title' => $title,
                'message' => $message,
                'url' => route('repairs.index', ['repair_modal' => 'edit', 'modal_repair' => $repair->id]),
                'data' => $this->deviceDetails($device, [
                    'reason_label' => 'Statuss',
                    'reason_value' => $this->statusLabel($previousStatus).' -> '.$this->statusLabel($nextStatus),
                    'submitted_at' => now()->format('d.m.Y H:i'),
                    'cta_label' => 'Atvērt remonta ierakstu',
                ]),
            ]);
        }
    }

    /**
     * Ieraksta vienu paziņojumu datubāzē.
     *
     * Šī ir zemākā līmeņa metode servisā. Tā nestrādā ar biznesa modeļiem,
     * bet pieņem jau sagatavotu paziņojuma masīvu un saglabā to `user_notifications`.
     */
    private function notify(?int $userId, array $payload): void
    {
        if (! $userId) {
            return;
        }

        UserNotification::query()->create([
            'user_id' => $userId,
            'type' => $payload['type'],
            'accent' => $payload['accent'] ?? 'sky',
            'title' => $payload['title'],
            'message' => $payload['message'],
            'url' => $payload['url'] ?? null,
            'data' => $payload['data'] ?? [],
        ]);
    }

    /**
     * Sagatavo kopīgo ierīces informācijas bloku paziņojuma kartītei.
     *
     * Frontend šo masīvu izmanto gan toast paziņojumā, gan navigācijas
     * paziņojumu centrā. Vienā vietā tiek normalizēti tukšie lauki, lai skatos
     * nav jādublē fallback teksti.
     */
    private function deviceDetails(?Device $device, array $extra = []): array
    {
        $location = collect([
            $device?->room?->room_number ? 'telpa '.$device->room->room_number : null,
            $device?->room?->room_name,
            $device?->building?->building_name ?: $device?->room?->building?->building_name,
        ])->filter()->implode(' | ');

        return array_merge([
            'device_name' => $device?->name ?: '-',
            'device_code' => $device?->code ?: '-',
            'serial_number' => $device?->serial_number ?: '-',
            'device_meta' => collect([$device?->type?->type_name, $device?->manufacturer, $device?->model])->filter()->implode(' | ') ?: 'Tips un modelis nav norādīts.',
            'device_location' => $location ?: 'Atrašanās vieta nav norādīta.',
            'submitted_by' => 'Sistēma',
        ], $extra);
    }

    /**
     * Pārveido tehnisko remonta statusu lietotājam saprotamā tekstā.
     */
    private function statusLabel(string $status): string
    {
        return match ($status) {
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
            default => $status,
        };
    }

    /**
     * Drošības pārbaude instalācijām, kur migrācijas vēl nav palaistas.
     *
     * Dažās projekta vietās jau ir runtime shēmas pielāgošana vecākām datubāzēm,
     * tāpēc šeit paziņojumu izveide vienkārši tiek izlaista, ja tabula vēl neeksistē.
     */
    private function canStore(): bool
    {
        return Schema::hasTable('user_notifications');
    }
}
