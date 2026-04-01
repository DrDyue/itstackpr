<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Reāllaika paziņojumu API kontrolieris.
 *
 * Frontend polling ceļā no šīs klases saņem toast paziņojumus par jauniem
 * pieprasījumiem, kas jāizskata administratoram vai nodošanas saņēmējam.
 */
class LiveNotificationController extends Controller
{
    /**
     * Atzīmē visus paziņojumus kā lasītus.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);
        $markedCount = 0;

        // Atkarībā no lietotāja lomas, atjaunojam attiecīgos pieprasījumus
        if ($user->canManageRequests()) {
            // Administratoram: atzīmējam remonta un norakstīšanas pieprasījumus
            $markedCount += RepairRequest::query()
                ->where('status', RepairRequest::STATUS_SUBMITTED)
                ->update(['updated_at' => now()]);
            
            $markedCount += WriteoffRequest::query()
                ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                ->update(['updated_at' => now()]);
        } else {
            // Lietotājam: atzīmējam nodošanas pieprasījumus
            $markedCount += DeviceTransfer::query()
                ->where('transfered_to_id', $user->id)
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->update(['updated_at' => now()]);
        }

        if ($markedCount > 0) {
            AuditTrail::markRead(
                $user,
                $user->canManageRequests()
                    ? 'Administrators atzīmēja paziņojumus kā lasītus: '.$markedCount.' ieraksti.'
                    : 'Lietotājs atzīmēja paziņojumus kā lasītus: '.$markedCount.' ieraksti.'
            );
        }

        return response()->json([
            'success' => true,
            'read_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Atgriež pašreizējam lietotājam aktuālos paziņojumus JSON formā.
     */
    public function index(): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        return response()->json([
            'notifications' => $this->notificationsFor($user)->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Izvēlas atbilstošo paziņojumu avotu pēc lietotāja lomas un skata.
     */
    private function notificationsFor(User $user): Collection
    {
        if ($user->canManageRequests()) {
            return $this->managerNotifications($user);
        }

        return $this->incomingTransferNotifications($user);
    }

    /**
     * Savāc administratora skatam gaidošos remonta, norakstīšanas un nodošanas pieteikumus.
     */
    private function managerNotifications(User $user): Collection
    {
        $repairNotifications = $this->featureTableExists('repair_requests')
            ? RepairRequest::query()
                ->with(['device.type', 'device.room', 'device.building', 'responsibleUser'])
                ->where('status', RepairRequest::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (RepairRequest $request) => $this->formatNotification(
                    id: 'repair-request:'.$request->id,
                    type: 'repair',
                    accent: 'sky',
                    title: 'Jauns remonta pieprasījums',
                    message: ($request->responsibleUser?->full_name ?: 'Lietotājs').' gaida izskatīšanu.',
                    details: $this->deviceDetails(
                        $request->device,
                        [
                        'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                        'reason_label' => 'Problēma',
                        'reason_value' => $request->description ?: 'Apraksts nav pievienots.',
                        'wait_label' => $this->waitLabel($request->created_at),
                        'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                        'cta_label' => 'Atvērt remonta pieprasījumu',
                        ]
                    ),
                    url: route('repair-requests.index', [
                        'statuses_filter' => 1,
                        'status' => [RepairRequest::STATUS_SUBMITTED],
                    ]).'#repair-request-'.$request->id,
                    createdAt: $request->created_at,
                    actions: [
                        $this->actionConfig('Apstiprināt', 'approve', route('repair-requests.review', $request), ['status' => RepairRequest::STATUS_APPROVED]),
                        $this->actionConfig('Noraidīt', 'reject', route('repair-requests.review', $request), ['status' => RepairRequest::STATUS_REJECTED]),
                    ],
                ))
            : collect();

        $writeoffNotifications = $this->featureTableExists('writeoff_requests')
            ? WriteoffRequest::query()
                ->with(['device.type', 'device.room', 'device.building', 'responsibleUser'])
                ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (WriteoffRequest $request) => $this->formatNotification(
                    id: 'writeoff-request:'.$request->id,
                    type: 'writeoff',
                    accent: 'rose',
                    title: 'Jauns norakstīšanas pieprasījums',
                    message: ($request->responsibleUser?->full_name ?: 'Lietotājs').' gaida izskatīšanu.',
                    details: $this->deviceDetails(
                        $request->device,
                        [
                        'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                        'reason_label' => 'Iemesls',
                        'reason_value' => $request->reason ?: 'Iemesls nav pievienots.',
                        'wait_label' => $this->waitLabel($request->created_at),
                        'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                        'cta_label' => 'Atvērt norakstīšanas pieprasījumu',
                        ]
                    ),
                    url: route('writeoff-requests.index', [
                        'statuses_filter' => 1,
                        'status' => [WriteoffRequest::STATUS_SUBMITTED],
                    ]).'#writeoff-request-'.$request->id,
                    createdAt: $request->created_at,
                    actions: [
                        $this->actionConfig('Apstiprināt', 'approve', route('writeoff-requests.review', $request), ['status' => WriteoffRequest::STATUS_APPROVED]),
                        $this->actionConfig('Noraidīt', 'reject', route('writeoff-requests.review', $request), ['status' => WriteoffRequest::STATUS_REJECTED]),
                    ],
                ))
            : collect();

        $transferNotifications = $this->featureTableExists('device_transfers')
            ? DeviceTransfer::query()
                ->with(['device.type', 'device.room', 'device.building', 'responsibleUser', 'transferTo'])
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (DeviceTransfer $transfer) => $this->formatNotification(
                    id: 'device-transfer:'.$transfer->id,
                    type: 'transfer',
                    accent: 'emerald',
                    title: 'Jauns nodošanas pieprasījums',
                    message: ($transfer->responsibleUser?->full_name ?: 'Lietotājs').' gaida lēmumu par nodošanu.',
                    details: $this->deviceDetails(
                        $transfer->device,
                        [
                        'submitted_by' => $transfer->responsibleUser?->full_name ?: '-',
                        'recipient' => $transfer->transferTo?->full_name ?: '-',
                        'reason_label' => 'Iemesls',
                        'reason_value' => $transfer->transfer_reason ?: 'Iemesls nav pievienots.',
                        'wait_label' => $this->waitLabel($transfer->created_at),
                        'submitted_at' => $transfer->created_at?->format('d.m.Y H:i') ?: '-',
                        'cta_label' => 'Atvērt nodošanas pieprasījumu',
                        ]
                    ),
                    url: route('device-transfers.index', [
                        'statuses_filter' => 1,
                        'status' => [DeviceTransfer::STATUS_SUBMITTED],
                    ]).'#device-transfer-'.$transfer->id,
                    createdAt: $transfer->created_at,
                    actions: [],
                ))
            : collect();

        return $repairNotifications
            ->concat($writeoffNotifications)
            ->concat($transferNotifications)
            ->sortByDesc('created_unix')
            ->take(12)
            ->values();
    }

    /**
     * Savāc parastam lietotājam ienākošos nodošanas pieprasījumus.
     */
    private function incomingTransferNotifications(User $user): Collection
    {
        if (! $this->featureTableExists('device_transfers')) {
            return collect();
        }

        return DeviceTransfer::query()
            ->with(['device.type', 'device.room', 'device.building', 'responsibleUser'])
            ->where('transfered_to_id', $user->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (DeviceTransfer $transfer) => $this->formatNotification(
                id: 'incoming-transfer:'.$transfer->id,
                type: 'incoming-transfer',
                accent: 'amber',
                title: 'Jauns ienākošs nodošanas pieprasījums',
                message: 'Tev jaizlemj par ierīces saņemšanu.',
                details: $this->deviceDetails(
                    $transfer->device,
                    [
                    'submitted_by' => $transfer->responsibleUser?->full_name ?: '-',
                    'reason_label' => 'Iemesls',
                    'reason_value' => $transfer->transfer_reason ?: 'Iemesls nav pievienots.',
                    'wait_label' => $this->waitLabel($transfer->created_at),
                    'submitted_at' => $transfer->created_at?->format('d.m.Y H:i') ?: '-',
                    'cta_label' => 'Atvērt nodošanas pieprasījumu',
                    ]
                ),
                url: route('device-transfers.index', [
                    'statuses_filter' => 1,
                    'status' => [DeviceTransfer::STATUS_SUBMITTED],
                ]).'#device-transfer-'.$transfer->id,
                createdAt: $transfer->created_at,
                actions: [
                    $this->actionConfig('Apstiprināt', 'approve', route('device-transfers.review', $transfer), ['status' => DeviceTransfer::STATUS_APPROVED]),
                    $this->actionConfig('Noraidīt', 'reject', route('device-transfers.review', $transfer), ['status' => DeviceTransfer::STATUS_REJECTED]),
                ],
            ))
            ->sortByDesc('created_unix')
            ->values();
    }

    /**
     * Vienotā formā sagatavo vienu toast paziņojuma objektu.
     */
    private function formatNotification(
        string $id,
        string $type,
        string $accent,
        string $title,
        string $message,
        array $details,
        string $url,
        $createdAt,
        array $actions = [],
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'accent' => $accent,
            'title' => $title,
            'message' => $message,
            'details' => $details,
            'url' => $url,
            'created_at' => $createdAt?->toIso8601String(),
            'created_unix' => $createdAt?->getTimestamp() ?? 0,
            'actions' => $actions,
        ];
    }

    /**
     * Apraksta klikšķināmu paziņojuma darbības pogu.
     */
    private function actionConfig(string $label, string $tone, string $url, array $payload): array
    {
        return [
            'label' => $label,
            'tone' => $tone,
            'url' => $url,
            'payload' => $payload,
        ];
    }

    /**
     * Sagatavo ierīces bloku, ko rāda paziņojuma kartītē.
     */
    private function deviceDetails(?Device $device, array $extra = []): array
    {
        $manufacturer = trim((string) ($device?->manufacturer ?? ''));
        $model = trim((string) ($device?->model ?? ''));
        $deviceMeta = collect([
            $device?->type?->type_name,
            trim(collect([$manufacturer, $model])->filter()->implode(' ')),
        ])->filter()->implode(' | ');

        $deviceLocation = collect([
            $device?->room?->room_number ? 'telpa '.$device->room->room_number : null,
            $device?->room?->room_name,
            $device?->building?->building_name,
        ])->filter()->implode(' | ');

        return array_merge([
            'device_name' => $device?->name ?: '-',
            'device_code' => $device?->code ?: '-',
            'serial_number' => $device?->serial_number ?: '-',
            'device_meta' => $deviceMeta !== '' ? $deviceMeta : 'Tips un modelis nav norādīts.',
            'device_location' => $deviceLocation !== '' ? $deviceLocation : 'Atrašanās vieta nav norādīta.',
        ], $extra);
    }

    /**
     * Izveido īsu cilvēkam saprotamu gaidīšanas ilguma tekstu.
     */
    private function waitLabel($createdAt): string
    {
        if (! $createdAt) {
            return 'tikko ienacis';
        }

        $minutes = max(0, now()->diffInMinutes($createdAt));

        if ($minutes < 1) {
            return 'gaida mazaku par 1 min';
        }

        if ($minutes < 60) {
            return 'gaida '.$minutes.' min';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            $remainingMinutes = $minutes % 60;

            return 'gaida '.$hours.' h'.($remainingMinutes > 0 ? ' '.$remainingMinutes.' min' : '');
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return 'gaida '.$days.' d'.($remainingHours > 0 ? ' '.$remainingHours.' h' : '');
    }
}
