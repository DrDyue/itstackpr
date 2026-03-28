<?php

namespace App\Http\Controllers;

use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class LiveNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = $this->user();
        abort_unless($user, 403);

        return response()->json([
            'notifications' => $this->notificationsFor($user)->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function notificationsFor(User $user): Collection
    {
        if ($user->canManageRequests()) {
            return $this->managerNotifications($user);
        }

        return $this->incomingTransferNotifications($user);
    }

    private function managerNotifications(User $user): Collection
    {
        $repairNotifications = $this->featureTableExists('repair_requests')
            ? RepairRequest::query()
                ->with(['device', 'responsibleUser'])
                ->where('status', RepairRequest::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (RepairRequest $request) => $this->formatNotification(
                    id: 'repair-request:'.$request->id,
                    type: 'repair',
                    accent: 'sky',
                    title: 'Jauns remonta pieprasijums',
                    message: trim(($request->responsibleUser?->full_name ?: 'Lietotajs').' iesniedza remontu iericei '.($request->device?->name ?: 'bez nosaukuma').'.'),
                    url: route('repair-requests.index', [
                        'statuses_filter' => 1,
                        'status' => [RepairRequest::STATUS_SUBMITTED],
                    ], false).'#repair-request-'.$request->id,
                    createdAt: $request->created_at,
                    actions: [
                        $this->actionConfig('Apstiprinat', 'approve', route('repair-requests.review', $request), ['status' => RepairRequest::STATUS_APPROVED]),
                        $this->actionConfig('Noraidit', 'reject', route('repair-requests.review', $request), ['status' => RepairRequest::STATUS_REJECTED]),
                    ],
                ))
            : collect();

        $writeoffNotifications = $this->featureTableExists('writeoff_requests')
            ? WriteoffRequest::query()
                ->with(['device', 'responsibleUser'])
                ->where('status', WriteoffRequest::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (WriteoffRequest $request) => $this->formatNotification(
                    id: 'writeoff-request:'.$request->id,
                    type: 'writeoff',
                    accent: 'rose',
                    title: 'Jauns norakstisanas pieprasijums',
                    message: trim(($request->responsibleUser?->full_name ?: 'Lietotajs').' iesniedza norakstisanu iericei '.($request->device?->name ?: 'bez nosaukuma').'.'),
                    url: route('writeoff-requests.index', [
                        'statuses_filter' => 1,
                        'status' => [WriteoffRequest::STATUS_SUBMITTED],
                    ], false).'#writeoff-request-'.$request->id,
                    createdAt: $request->created_at,
                    actions: [
                        $this->actionConfig('Apstiprinat', 'approve', route('writeoff-requests.review', $request), ['status' => WriteoffRequest::STATUS_APPROVED]),
                        $this->actionConfig('Noraidit', 'reject', route('writeoff-requests.review', $request), ['status' => WriteoffRequest::STATUS_REJECTED]),
                    ],
                ))
            : collect();

        $transferNotifications = $this->featureTableExists('device_transfers')
            ? DeviceTransfer::query()
                ->with(['device', 'responsibleUser', 'transferTo'])
                ->where('status', DeviceTransfer::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (DeviceTransfer $transfer) => $this->formatNotification(
                    id: 'device-transfer:'.$transfer->id,
                    type: 'transfer',
                    accent: 'emerald',
                    title: 'Jauns nodosanas pieprasijums',
                    message: trim(($transfer->responsibleUser?->full_name ?: 'Lietotajs').' velas nodot ierici '.($transfer->device?->name ?: 'bez nosaukuma').($transfer->transferTo?->full_name ? ' lietotajam '.$transfer->transferTo->full_name : '').'.'),
                    url: route('device-transfers.index', [
                        'statuses_filter' => 1,
                        'status' => [DeviceTransfer::STATUS_SUBMITTED],
                    ], false).'#device-transfer-'.$transfer->id,
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

    private function incomingTransferNotifications(User $user): Collection
    {
        if (! $this->featureTableExists('device_transfers')) {
            return collect();
        }

        return DeviceTransfer::query()
            ->with(['device', 'responsibleUser'])
            ->where('transfered_to_id', $user->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (DeviceTransfer $transfer) => $this->formatNotification(
                id: 'incoming-transfer:'.$transfer->id,
                type: 'incoming-transfer',
                accent: 'amber',
                title: 'Jauns ienakoss nodosanas pieprasijums',
                message: trim(($transfer->responsibleUser?->full_name ?: 'Lietotajs').' velas tev nodot ierici '.($transfer->device?->name ?: 'bez nosaukuma').'.'),
                url: route('device-transfers.index', [
                    'statuses_filter' => 1,
                    'status' => [DeviceTransfer::STATUS_SUBMITTED],
                ], false).'#device-transfer-'.$transfer->id,
                createdAt: $transfer->created_at,
                actions: [
                    $this->actionConfig('Apstiprinat', 'approve', route('device-transfers.review', $transfer), ['status' => DeviceTransfer::STATUS_APPROVED]),
                    $this->actionConfig('Noraidit', 'reject', route('device-transfers.review', $transfer), ['status' => DeviceTransfer::STATUS_REJECTED]),
                ],
            ))
            ->sortByDesc('created_unix')
            ->values();
    }

    private function formatNotification(
        string $id,
        string $type,
        string $accent,
        string $title,
        string $message,
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
            'url' => $url,
            'created_at' => $createdAt?->toIso8601String(),
            'created_unix' => $createdAt?->getTimestamp() ?? 0,
            'actions' => $actions,
        ];
    }

    private function actionConfig(string $label, string $tone, string $url, array $payload): array
    {
        return [
            'label' => $label,
            'tone' => $tone,
            'url' => $url,
            'payload' => $payload,
        ];
    }
}
