<?php

namespace App\Support;

use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WriteoffRequest;
use Illuminate\Support\Facades\Schema;

class NavigationViewData
{
    public function data(): array
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $canManageRequests = $user?->canManageRequests() ?? false;
        $currentViewMode = $user?->currentViewMode() ?? User::VIEW_MODE_USER;

        $incomingTransferReviewCount = $this->incomingTransferReviewCount($user, $canManageRequests);
        $passwordResetRequestCount = $this->passwordResetRequestCount($canManageRequests);
        $pendingRepairRequestCount = $this->pendingRepairRequestCount($user, $canManageRequests);
        $pendingWriteoffRequestCount = $this->pendingWriteoffRequestCount($user, $canManageRequests);
        $pendingTransferRequestCount = 0;

        $requestGroupCount = $canManageRequests
            ? $pendingRepairRequestCount + $pendingWriteoffRequestCount + $pendingTransferRequestCount
            : $incomingTransferReviewCount;

        $initialNavCounts = [
            'requests_total' => $requestGroupCount,
            'repair_requests' => $pendingRepairRequestCount,
            'writeoff_requests' => $pendingWriteoffRequestCount,
            'device_transfers' => $pendingTransferRequestCount,
            'password_reset_requests' => $passwordResetRequestCount,
            'incoming_transfers' => $incomingTransferReviewCount,
        ];

        $navigationItems = $this->navigationItems($isAdmin, $canManageRequests);
        $personalNotifications = $this->personalNotifications($user);
        $personalNotificationCount = $this->personalNotificationCount($user);
        $pendingNotificationsCount = $canManageRequests
            ? RepairRequest::query()->where('status', RepairRequest::STATUS_SUBMITTED)->count()
                + WriteoffRequest::query()->where('status', WriteoffRequest::STATUS_SUBMITTED)->count()
                + DeviceTransfer::query()->where('status', DeviceTransfer::STATUS_SUBMITTED)->count()
                + $passwordResetRequestCount
                + $personalNotificationCount
            : $incomingTransferReviewCount + $personalNotificationCount;

        return array_merge($navigationItems, [
            'user' => $user,
            'userInitial' => mb_strtoupper(mb_substr((string) ($user?->full_name ?: 'L'), 0, 1)),
            'isAdmin' => $isAdmin,
            'canManageRequests' => $canManageRequests,
            'currentViewMode' => $currentViewMode,
            'incomingTransferReviewCount' => $incomingTransferReviewCount,
            'passwordResetRequestCount' => $passwordResetRequestCount,
            'pendingRepairRequestCount' => $pendingRepairRequestCount,
            'pendingWriteoffRequestCount' => $pendingWriteoffRequestCount,
            'pendingTransferRequestCount' => $pendingTransferRequestCount,
            'requestGroupCount' => $requestGroupCount,
            'initialNavCounts' => $initialNavCounts,
            'showNotificationPreviewCards' => true,
            'personalNotificationCount' => $personalNotificationCount,
            'pendingNotificationsCount' => $pendingNotificationsCount,
            'personalNotifications' => $personalNotifications,
            'pendingRepairs' => $this->pendingRepairs($canManageRequests),
            'pendingWriteoffs' => $this->pendingWriteoffs($canManageRequests),
            'pendingPasswordResets' => $this->pendingPasswordResets($canManageRequests),
            'pendingTransfers' => $this->pendingTransfers($user, $canManageRequests, $incomingTransferReviewCount),
        ]);
    }

    private function incomingTransferReviewCount(?User $user, bool $canManageRequests): int
    {
        if ($canManageRequests || ! $user) {
            return 0;
        }

        return DeviceTransfer::query()
            ->where('transfered_to_id', $user->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->count();
    }

    private function passwordResetRequestCount(bool $canManageRequests): int
    {
        return $canManageRequests
            ? User::query()->whereNotNull('password_reset_requested_at')->count()
            : 0;
    }

    private function pendingRepairRequestCount(?User $user, bool $canManageRequests): int
    {
        if (! $canManageRequests || ! $user) {
            return 0;
        }

        return RepairRequest::query()
            ->where('status', RepairRequest::STATUS_SUBMITTED)
            ->where('responsible_user_id', '!=', $user->id)
            ->count();
    }

    private function pendingWriteoffRequestCount(?User $user, bool $canManageRequests): int
    {
        if (! $canManageRequests || ! $user) {
            return 0;
        }

        return WriteoffRequest::query()
            ->where('status', WriteoffRequest::STATUS_SUBMITTED)
            ->where('responsible_user_id', '!=', $user->id)
            ->count();
    }

    private function personalNotifications(?User $user)
    {
        if (! $this->hasUserNotificationsTable() || ! $user) {
            return collect();
        }

        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    private function personalNotificationCount(?User $user): int
    {
        if (! $this->hasUserNotificationsTable() || ! $user) {
            return 0;
        }

        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    private function pendingRepairs(bool $canManageRequests)
    {
        if (! $canManageRequests) {
            return collect();
        }

        return RepairRequest::query()
            ->with(['device', 'responsibleUser'])
            ->where('status', RepairRequest::STATUS_SUBMITTED)
            ->latest('id')
            ->limit(5)
            ->get();
    }

    private function pendingWriteoffs(bool $canManageRequests)
    {
        if (! $canManageRequests) {
            return collect();
        }

        return WriteoffRequest::query()
            ->with(['device', 'responsibleUser'])
            ->where('status', WriteoffRequest::STATUS_SUBMITTED)
            ->latest('id')
            ->limit(5)
            ->get();
    }

    private function pendingPasswordResets(bool $canManageRequests)
    {
        if (! $canManageRequests) {
            return collect();
        }

        return User::query()
            ->whereNotNull('password_reset_requested_at')
            ->latest('password_reset_requested_at')
            ->limit(5)
            ->get(['id', 'full_name', 'email', 'phone', 'job_title', 'password_reset_requested_at']);
    }

    private function pendingTransfers(?User $user, bool $canManageRequests, int $incomingTransferReviewCount)
    {
        if ($canManageRequests || ! $user || $incomingTransferReviewCount <= 0) {
            return collect();
        }

        return DeviceTransfer::query()
            ->with(['device', 'responsibleUser'])
            ->where('transfered_to_id', $user->id)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->latest('id')
            ->limit(5)
            ->get();
    }

    private function navigationItems(bool $isAdmin, bool $canManageRequests): array
    {
        $primaryNavigationItems = array_values(array_filter([
            $canManageRequests ? ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Darbvirsma', 'icon' => 'dashboard'] : null,
            ['route' => 'devices.index', 'pattern' => 'devices*', 'label' => 'Ierīces', 'icon' => 'device'],
        ]));
        $requestNavigationItems = [
            ['route' => 'repair-requests.index', 'pattern' => 'repair-requests*', 'label' => 'Remonta pieteikumi', 'icon' => 'repair-request'],
            ['route' => 'writeoff-requests.index', 'pattern' => 'writeoff-requests*', 'label' => 'Norakstīšanas pieteikumi', 'icon' => 'writeoff'],
            ['route' => 'device-transfers.index', 'pattern' => 'device-transfers*', 'label' => 'Nodošanas pieteikumi', 'icon' => 'transfer'],
        ];
        $lessImportantNavigationItems = [];
        $repairsNavigationItem = null;
        $usersNavigationItem = null;

        if ($canManageRequests) {
            $repairsNavigationItem = ['route' => 'repairs.index', 'pattern' => 'repairs*', 'label' => 'Remonti', 'icon' => 'repair'];
            $lessImportantNavigationItems = [
                ['route' => 'rooms.index', 'pattern' => 'rooms*', 'label' => 'Telpas', 'icon' => 'room'],
                ['route' => 'buildings.index', 'pattern' => 'buildings*', 'label' => 'Ēkas', 'icon' => 'building'],
                ['route' => 'device-types.index', 'pattern' => 'device-types*', 'label' => 'Ierīču tipi', 'icon' => 'tag'],
            ];
        }

        if ($isAdmin && $canManageRequests) {
            $usersNavigationItem = ['route' => 'users.index', 'pattern' => 'users*', 'label' => 'Lietotāji', 'icon' => 'users'];
            $lessImportantNavigationItems[] = ['route' => 'audit-log.index', 'pattern' => 'audit-log*', 'label' => 'Audits', 'icon' => 'audit'];
        }

        return [
            'primaryNavigationItems' => $primaryNavigationItems,
            'requestNavigationItems' => $requestNavigationItems,
            'requestReviewNavigationItems' => $canManageRequests ? collect($requestNavigationItems)->take(2)->values()->all() : $requestNavigationItems,
            'requestHistoryNavigationItems' => $canManageRequests ? collect($requestNavigationItems)->slice(2)->values()->all() : [],
            'secondaryNavigationItems' => [],
            'lessImportantNavigationItems' => $lessImportantNavigationItems,
            'repairsNavigationItem' => $repairsNavigationItem,
            'usersNavigationItem' => $usersNavigationItem,
            'requestGroupActive' => collect($requestNavigationItems)->contains(fn (array $item) => request()->routeIs($item['pattern'])),
            'lessImportantGroupActive' => $lessImportantNavigationItems !== []
                && collect($lessImportantNavigationItems)->contains(fn (array $item) => request()->routeIs($item['pattern'])),
        ];
    }

    private function hasUserNotificationsTable(): bool
    {
        return Schema::hasTable('user_notifications');
    }
}
