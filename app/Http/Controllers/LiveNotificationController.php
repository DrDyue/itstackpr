<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WriteoffRequest;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Ko dara: Pārvalda dzīvos paziņojumus un skaitītājus lietotāja saskarnei.
 *
 * Kā strādā: Apkopo gaidošus pieteikumus, personīgos paziņojumus, ienākošās nodošanas un formatē tos frontend vajadzībām.
 *
 * Kad pielietojas: Kad saskarne prasa aktuālo paziņojumu skaitu, paziņojumu sarakstu vai atzīmē tos kā lasītus.
 */
class LiveNotificationController extends Controller
{
    /**
     * Ko dara: Atzīmē visus paziņojumus kā lasītus atbilstoši lietotāja lomai.
     *
     * Kā strādā: Administrators atzīmē remonta un norakstīšanas pieteikumus kā lasītus. Parasts lietotājs atzīmē nodošanas pieteikumus, ko viņš saņēmis. Rezultāts ir JSON ar lasīšanas laika marķieri.
     *
     * Kad pielietojas: Izsaukšana: POST /live-notifications/mark-all-read | Pieejams: jebkurš autentificēts lietotājs. Scenārijs: Lietotājs klikšķina uz "Atzīmēt visu kā lasītu" vai tas notiek automātiski.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        $markedCount = 0;

        if ($user->canManageRequests()) {
            $markedCount += RepairRequest::query()
                ->where('status', RepairRequest::STATUS_SUBMITTED)
                ->update(['updated_at' => now()]);

            $markedCount += WriteoffRequest::query()
                ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                ->update(['updated_at' => now()]);
        } else {
            $markedCount += DeviceTransfer::query()
                ->where('transfered_to_id', $user->id)
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->update(['updated_at' => now()]);
        }

        // Jaunās funkcijas persistētie paziņojumi tiek dzēsti loģiski:
        // saglabājam `read_at`, lai tie pazustu no lietotāja centra, bet vēsturiski paliktu datubāzē.
        if ($this->featureTableExists('user_notifications')) {
            $markedCount += UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
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
     * Ko dara: Atgriež pašreizējam lietotājam aktuālos paziņojumus JSON formā.
     *
     * Kā strādā: Ja klients sūta X-Notification-Fingerprint galveni ar aktuālo nospiedumu, tiek izpildīti tikai lēti COUNT vaicājumi. Pilnie JOIN vaicājumi tiek izlaisti.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        $fingerprint = $this->fingerprintFor($user);

        if ($request->header('X-Notification-Fingerprint') === $fingerprint) {
            return response()->json(['unchanged' => true]);
        }

        return response()->json([
            'notifications' => $this->notificationsFor($user)->values(),
            'counts' => $this->countsFor($user),
            'fingerprint' => $fingerprint,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Ko dara: Aprēķina lētu nospiedumu no gaidošo pieprasījumu skaita un jaunākā ID.
     *
     * Kā strādā: Izmanto tikai COUNT + MAX vaicājumus — bez eager loading.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    private function fingerprintFor(User $user): string
    {
        $parts = [];

        if ($user->canManageRequests()) {
            $row = User::query()
                ->whereNotNull('password_reset_requested_at')
                ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(id), 0) as max_id')
                ->toBase()
                ->first();
            $latestPasswordRequest = User::query()
                ->whereNotNull('password_reset_requested_at')
                ->max('password_reset_requested_at');
            $parts[] = 'p'.($row->cnt ?? 0).':'.($row->max_id ?? 0).':'.($latestPasswordRequest ?? '-');

            if ($this->featureTableExists('repair_requests')) {
                $row = RepairRequest::query()
                    ->where('status', RepairRequest::STATUS_SUBMITTED)
                    ->where('responsible_user_id', '!=', $user->id)
                    ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(id), 0) as max_id')
                    ->toBase()
                    ->first();
                $parts[] = 'r'.($row->cnt ?? 0).':'.($row->max_id ?? 0);
            }

            if ($this->featureTableExists('writeoff_requests')) {
                $row = WriteoffRequest::query()
                    ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                    ->where('responsible_user_id', '!=', $user->id)
                    ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(id), 0) as max_id')
                    ->toBase()
                    ->first();
                $parts[] = 'w'.($row->cnt ?? 0).':'.($row->max_id ?? 0);
            }

            if ($this->featureTableExists('device_transfers')) {
                $row = DeviceTransfer::query()
                    ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                    ->where('responsible_user_id', '!=', $user->id)
                    ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(id), 0) as max_id')
                    ->toBase()
                    ->first();
                $parts[] = 't'.($row->cnt ?? 0).':'.($row->max_id ?? 0);
            }
        } else {
            if ($this->featureTableExists('device_transfers')) {
                $row = DeviceTransfer::query()
                    ->where('transfered_to_id', $user->id)
                    ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                    ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(id), 0) as max_id')
                    ->toBase()
                    ->first();
                $parts[] = 'i'.($row->cnt ?? 0).':'.($row->max_id ?? 0);
            }
        }

        // Fingerprintā iekļaujam arī personīgos paziņojumus. Tas ļauj frontendam
        // saņemt `unchanged: true`, ja nav jaunu ierakstu, un neveikt pilnu ielādi katrā poll reizē.
        if ($this->featureTableExists('user_notifications')) {
            $row = UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(id), 0) as max_id')
                ->toBase()
                ->first();
            $latest = UserNotification::query()
                ->where('user_id', $user->id)
                ->max('updated_at');
            $parts[] = 'u'.($row->cnt ?? 0).':'.($row->max_id ?? 0).':'.($latest ?? '-');
        }

        return md5(implode('|', $parts));
    }

    /**
     * Ko dara: Izvēlas atbilstošo paziņojumu avotu pēc lietotāja lomas un skata.
     *
     * Kā strādā: Vecā funkcionalitāte ģenerē paziņojumus no gaidošiem pieteikumiem: administratoram tie ir iesniegtie remonta/norakstīšanas pieteikumi, parastam lietotājam — ienākošās nodošanas. Jaunā funkcionalitāte papildus pievieno `personalNotifications()`, kas nāk no `user_notifications` tabulas un rāda jau izskatītu notikumu rezultātus.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    private function notificationsFor(User $user): Collection
    {
        if ($user->canManageRequests()) {
            // Vadītāja paziņojumos apvienojam divas lietas:
            // aktīvos pieteikumus, kas jāizskata, un personīgos paziņojumus par jau notikušiem lēmumiem.
            return $this->managerNotifications($user)
                ->concat($this->personalNotifications($user))
                ->sortByDesc('created_unix')
                ->take(12)
                ->values();
        }

        // Parastam lietotājam svarīgākie ir ienākošie nodošanas pieteikumi,
        // bet klāt pievienojam arī personīgos rezultātu paziņojumus no user_notifications tabulas.
        return $this->incomingTransferNotifications($user)
            ->concat($this->personalNotifications($user))
            ->sortByDesc('created_unix')
            ->take(12)
            ->values();
    }

    /**
     * Ko dara: Atgriež navigācijas badge skaitītājus reāllaika atjaunošanai.
     *
     * Kā strādā: `personal_notifications` ir jaunās paziņojumu funkcijas skaitītājs. Tas netiek jaukts ar `requests_total`, jo `requests_total` joprojām nozīmē gaidošus pieteikumus, kuriem lietotājam vai administratoram jāpieņem lēmums.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    private function countsFor(User $user): array
    {
        if ($user->canManageRequests()) {
            // Administratora badge skaitītāji rāda tikai darāmās lietas:
            // neizskatītos remonta/norakstīšanas pieteikumus un paroles palīdzības pieprasījumus.
            $passwordResetRequests = User::query()
                ->whereNotNull('password_reset_requested_at')
                ->count();

            $repairRequests = $this->featureTableExists('repair_requests')
                ? RepairRequest::query()
                    ->where('status', RepairRequest::STATUS_SUBMITTED)
                    ->where('responsible_user_id', '!=', $user->id)
                    ->count()
                : 0;

            $writeoffRequests = $this->featureTableExists('writeoff_requests')
                ? WriteoffRequest::query()
                    ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                    ->where('responsible_user_id', '!=', $user->id)
                    ->count()
                : 0;

            $deviceTransfers = 0;

            $personalNotifications = $this->featureTableExists('user_notifications')
                ? UserNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count()
                : 0;

            return [
                'requests_total' => $repairRequests + $writeoffRequests + $deviceTransfers,
                'repair_requests' => $repairRequests,
                'writeoff_requests' => $writeoffRequests,
                'device_transfers' => $deviceTransfers,
                'password_reset_requests' => $passwordResetRequests,
                'incoming_transfers' => 0,
                'personal_notifications' => $personalNotifications,
            ];
        }

        // Parastam lietotājam galvenais darba skaitītājs ir ienākoša nodošana,
        // jo tieši par to viņam jāpieņem lēmums.
        $incomingTransfers = $this->featureTableExists('device_transfers')
            ? DeviceTransfer::query()
                ->where('transfered_to_id', $user->id)
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->count()
            : 0;

        $personalNotifications = $this->featureTableExists('user_notifications')
            ? UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count()
            : 0;

        return [
            'requests_total' => $incomingTransfers,
            'repair_requests' => 0,
            'writeoff_requests' => 0,
            'device_transfers' => 0,
            'password_reset_requests' => 0,
            'incoming_transfers' => $incomingTransfers,
            'personal_notifications' => $personalNotifications,
        ];
    }

    /**
     * Ko dara: Nolasa pašreizējā lietotāja nelasītos persistētos paziņojumus.
     *
     * Kā strādā: Šeit tiek formatēti tie paši `user_notifications` ieraksti, ko izveido `UserNotifier`. Rezultāts tiek pielāgots frontend formai, kuru jau izmanto esošie toast paziņojumi: `id`, `type`, `accent`, `title`, `message`, `details`, `url` un `created_unix`.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    private function personalNotifications(User $user): Collection
    {
        if (! $this->featureTableExists('user_notifications')) {
            return collect();
        }

        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (UserNotification $notification) => $this->formatNotification(
                id: 'user-notification:'.$notification->id,
                type: $notification->type,
                accent: $notification->accent,
                title: $notification->title,
                message: $notification->message,
                details: $notification->data ?: [
                    'device_name' => '-',
                    'submitted_by' => 'Sistēma',
                    'submitted_at' => $notification->created_at?->format('d.m.Y H:i') ?: '-',
                    'device_code' => '-',
                    'serial_number' => '-',
                    'device_location' => '-',
                    'reason_label' => 'Paziņojums',
                    'reason_value' => $notification->message,
                    'cta_label' => 'Atvērt',
                ],
                url: $notification->url ?: route('devices.index'),
                createdAt: $notification->created_at,
                actions: [],
            ));
    }

    /**
     * Ko dara: Savāc administratora skatam gaidošos remonta, norakstīšanas un nodošanas pieteikumus.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    private function managerNotifications(User $user): Collection
    {
        $passwordNotifications = User::query()
            ->whereNotNull('password_reset_requested_at')
            ->latest('password_reset_requested_at')
            ->limit(8)
            ->get(['id', 'full_name', 'email', 'phone', 'job_title', 'password_reset_requested_at'])
            ->map(fn (User $requestUser) => $this->formatNotification(
                id: 'password-reset-request:'.$requestUser->id.':'.$requestUser->password_reset_requested_at?->timestamp,
                type: 'password-reset',
                accent: 'violet',
                title: 'Paroles maiņas pieprasījums',
                message: ($requestUser->full_name ?: 'Lietotājs').' lūdz administratora palīdzību paroles maiņai.',
                details: [
                    'primary_label' => 'Lietotājs',
                    'submitter_label' => 'Pieprasītājs',
                    'code_label' => 'E-pasts',
                    'serial_label' => 'Tālrunis',
                    'location_label' => 'Amats',
                    'device_name' => $requestUser->full_name ?: '-',
                    'device_code' => $requestUser->email ?: '-',
                    'serial_number' => $requestUser->phone ?: '-',
                    'device_location' => $requestUser->job_title ?: 'Amats nav norādīts.',
                    'submitted_by' => $requestUser->full_name ?: '-',
                    'reason_label' => 'Pieprasījums',
                    'reason_value' => 'Jāatjauno lietotāja parole administratora pusē.',
                    'wait_label' => $this->waitLabel($requestUser->password_reset_requested_at),
                    'submitted_at' => $requestUser->password_reset_requested_at?->format('d.m.Y H:i') ?: '-',
                    'cta_label' => 'Atvērt lietotāja ierakstu',
                ],
                url: route('users.index', [
                    'password_reset' => 1,
                    'highlight' => $requestUser->full_name,
                    'highlight_mode' => 'contains',
                    'highlight_id' => 'user-'.$requestUser->id,
                ]),
                createdAt: $requestUser->password_reset_requested_at,
                actions: [],
            ));

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
                    title: 'Jauns remonta pieteikums',
                    message: ($request->responsibleUser?->full_name ?: 'Lietotājs').' gaida izskatīšanu.',
                    details: $this->deviceDetails(
                        $request->device,
                        [
                            'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                            'reason_label' => 'Problēma',
                            'reason_value' => $request->description ?: 'Apraksts nav pievienots.',
                            'wait_label' => $this->waitLabel($request->created_at),
                            'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                            'cta_label' => 'Atvērt remonta pieteikumu',
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
                    title: 'Jauns norakstīšanas pieteikums',
                    message: ($request->responsibleUser?->full_name ?: 'Lietotājs').' gaida izskatīšanu.',
                    details: $this->deviceDetails(
                        $request->device,
                        [
                            'submitted_by' => $request->responsibleUser?->full_name ?: '-',
                            'reason_label' => 'Iemesls',
                            'reason_value' => $request->reason ?: 'Iemesls nav pievienots.',
                            'wait_label' => $this->waitLabel($request->created_at),
                            'submitted_at' => $request->created_at?->format('d.m.Y H:i') ?: '-',
                            'cta_label' => 'Atvērt norakstīšanas pieteikumu',
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
                    title: 'Jauns nodošanas pieteikums',
                    message: ($transfer->responsibleUser?->full_name ?: 'Lietotājs').' gaida lēmumu par nodošanas pieteikumu.',
                    details: $this->deviceDetails(
                        $transfer->device,
                        [
                            'submitted_by' => $transfer->responsibleUser?->full_name ?: '-',
                            'recipient' => $transfer->transferTo?->full_name ?: '-',
                            'reason_label' => 'Iemesls',
                            'reason_value' => $transfer->transfer_reason ?: 'Iemesls nav pievienots.',
                            'wait_label' => $this->waitLabel($transfer->created_at),
                            'submitted_at' => $transfer->created_at?->format('d.m.Y H:i') ?: '-',
                            'cta_label' => 'Atvērt nodošanas pieteikumu',
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

        return $passwordNotifications
            ->concat($repairNotifications)
            ->concat($writeoffNotifications)
            ->concat($transferNotifications)
            ->sortByDesc('created_unix')
            ->take(12)
            ->values();
    }

    /**
     * Ko dara: Savāc parastam lietotājam ienākošos nodošanas pieteikumus.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
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
                title: 'Jauns ienākošs nodošanas pieteikums',
                message: 'Tev jāizlemj par ierīces saņemšanu.',
                details: $this->deviceDetails(
                    $transfer->device,
                    [
                        'submitted_by' => $transfer->responsibleUser?->full_name ?: '-',
                        'reason_label' => 'Iemesls',
                        'reason_value' => $transfer->transfer_reason ?: 'Iemesls nav pievienots.',
                        'wait_label' => $this->waitLabel($transfer->created_at),
                        'submitted_at' => $transfer->created_at?->format('d.m.Y H:i') ?: '-',
                        'cta_label' => 'Atvērt nodošanas pieteikumu',
                    ]
                ),
                url: route('device-transfers.index', [
                    'incoming' => 1,
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
     * Ko dara: Vienotā formā sagatavo vienu toast paziņojuma objektu.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
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
     * Ko dara: Apraksta klikšķināmu paziņojuma darbības pogu.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
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
     * Ko dara: Sagatavo ierīces bloku, ko rāda paziņojuma kartītē.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
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
     * Ko dara: Izveido īsu, cilvēkam saprotamu gaidīšanas ilguma tekstu.
     *
     * Kā strādā: Izmanto pieprasījuma datus, modeļus un palīgmetodes, lai sagatavotu vajadzīgo rezultātu vai izpildītu darbību.
     *
     * Kad pielietojas: Kad šai kontroliera plūsmai nepieciešama šīs metodes konkrētā atbildība.
     */
    private function waitLabel($createdAt): string
    {
        if (! $createdAt) {
            return 'tikko ienācis';
        }

        $minutes = max(0, now()->diffInMinutes($createdAt));

        if ($minutes < 1) {
            return 'gaida mazāk par 1 min';
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
