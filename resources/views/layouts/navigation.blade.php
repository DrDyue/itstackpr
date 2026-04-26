{{--
    Layout daļa: Augšējā navigācija.
    Atbildība: parāda galvenās sistēmas sadaļas atkarībā no lietotāja lomas un izvēlētā skata režīma.
    Kāpēc tas ir svarīgi:
    1. Adminam šeit parādās pārvaldības sadaļas un skata režīma pārslēgs.
    2. Lietotājam šeit paliek tikai tās sadaļas, kuras viņš drīkst izmantot.
    3. Navigācijā tiek rādīti arī gaidošo pieteikumu indikatori un ātrās saites.
--}}
@php
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $canManageRequests = $user?->canManageRequests() ?? false;
        $currentViewMode = $user?->currentViewMode() ?? \App\Models\User::VIEW_MODE_USER;
        $incomingTransferReviewCount = ! $canManageRequests && $user
            ? \App\Models\DeviceTransfer::query()
                ->where('transfered_to_id', $user->id)
                ->where('status', \App\Models\DeviceTransfer::STATUS_SUBMITTED)
                ->count()
            : 0;
        $passwordResetRequestCount = $canManageRequests
            ? \App\Models\User::query()->whereNotNull('password_reset_requested_at')->count()
            : 0;
        $pendingRepairRequestCount = $canManageRequests && $user
            ? \App\Models\RepairRequest::query()
                ->where('status', \App\Models\RepairRequest::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->count()
            : 0;
        $pendingWriteoffRequestCount = $canManageRequests && $user
            ? \App\Models\WriteoffRequest::query()
                ->where('status', \App\Models\WriteoffRequest::STATUS_SUBMITTED)
                ->where('responsible_user_id', '!=', $user->id)
                ->count()
            : 0;
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
        $primaryNavigationItems = array_values(array_filter([
            $canManageRequests ? ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Darbvirsma', 'icon' => 'dashboard'] : null,
            ['route' => 'devices.index', 'pattern' => 'devices*', 'label' => 'Ierīces', 'icon' => 'device'],
        ]));
        $requestNavigationItems = [
            ['route' => 'repair-requests.index', 'pattern' => 'repair-requests*', 'label' => 'Remonta pieteikumi', 'icon' => 'repair-request'],
            ['route' => 'writeoff-requests.index', 'pattern' => 'writeoff-requests*', 'label' => 'Norakstīšanas pieteikumi', 'icon' => 'writeoff'],
            ['route' => 'device-transfers.index', 'pattern' => 'device-transfers*', 'label' => 'Nodošanas pieteikumi', 'icon' => 'transfer'],
        ];
        $requestReviewNavigationItems = $canManageRequests ? collect($requestNavigationItems)->take(2)->values()->all() : $requestNavigationItems;
        $requestHistoryNavigationItems = $canManageRequests ? collect($requestNavigationItems)->slice(2)->values()->all() : [];
        $secondaryNavigationItems = [];
        $lessImportantNavigationItems = [];
        $repairsNavigationItem = null;
        $usersNavigationItem = null;
        $requestGroupActive = collect($requestNavigationItems)->contains(fn (array $item) => request()->routeIs($item['pattern']));
        $lessImportantGroupActive = false;

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

        if ($lessImportantNavigationItems !== []) {
            $lessImportantGroupActive = collect($lessImportantNavigationItems)->contains(
                fn (array $item) => request()->routeIs($item['pattern'])
            );
        }
@endphp
<nav
    x-data="{
        open: false,
        requestCounts: {
            requests_total: 0,
            repair_requests: 0,
            writeoff_requests: 0,
            device_transfers: 0,
            password_reset_requests: 0,
            incoming_transfers: 0,
        },
        syncCounts(counts = {}) {
            this.requestCounts = {
                requests_total: Number(counts?.requests_total || 0),
                repair_requests: Number(counts?.repair_requests || 0),
                writeoff_requests: Number(counts?.writeoff_requests || 0),
                device_transfers: Number(counts?.device_transfers || 0),
                password_reset_requests: Number(counts?.password_reset_requests || 0),
                incoming_transfers: Number(counts?.incoming_transfers || 0),
            };
        },
    }"
    x-init='syncCounts(@json($initialNavCounts))'
    @nav-counts-updated.window="syncCounts($event.detail)"
    class="app-main-nav sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur"
>

    <div class="mx-auto max-w-[120rem] px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route($canManageRequests ? 'dashboard' : 'devices.index') }}" class="flex min-w-0 items-center gap-3">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-1.5 xl:flex xl:flex-nowrap">
                    @foreach ($primaryNavigationItems as $item)
                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                            <x-icon :name="$item['icon']" size="h-4 w-4" />
                            <span>{{ $item['label'] }}</span>
                        </x-nav-link>
                    @endforeach

                    @if ($repairsNavigationItem)
                        <x-nav-link :href="route($repairsNavigationItem['route'])" :active="request()->routeIs($repairsNavigationItem['pattern'])">
                            <x-icon :name="$repairsNavigationItem['icon']" size="h-4 w-4" />
                            <span>{{ $repairsNavigationItem['label'] }}</span>
                        </x-nav-link>
                    @endif

                    <x-dropdown align="left" width="w-72">
                        <x-slot name="trigger">
                            <button class="{{ $requestGroupActive
                                ? 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl bg-sky-50 px-3.5 py-2.5 text-sm font-semibold text-sky-800 ring-1 ring-sky-200 shadow-sm transition duration-200'
                                : 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900' }}">
                                <x-icon name="repair-request" size="h-4 w-4" />
                                <span>Pieteikumi</span>
                                <span
                                    x-cloak
                                    x-show="requestCounts.requests_total > 0"
                                    title="Jāizskata"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm"
                                >
                                    <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                    <span x-text="requestCounts.requests_total">{{ $requestGroupCount }}</span>
                                </span>
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                    </x-slot>

                    <x-slot name="content">
                            @foreach ($requestReviewNavigationItems as $item)
                                @php
                                    $itemCountKey = match ($item['route']) {
                                        'repair-requests.index' => 'repair_requests',
                                        'writeoff-requests.index' => 'writeoff_requests',
                                        'device-transfers.index' => $canManageRequests ? 'device_transfers' : 'incoming_transfers',
                                        default => null,
                                    };
                                    $itemCount = match ($item['route']) {
                                        'repair-requests.index' => $pendingRepairRequestCount,
                                        'writeoff-requests.index' => $pendingWriteoffRequestCount,
                                        'device-transfers.index' => $canManageRequests ? $pendingTransferRequestCount : $incomingTransferReviewCount,
                                        default => 0,
                                    };
                                @endphp
                                <x-dropdown-link :href="route($item['route'])">
                                    <x-icon :name="$item['icon']" size="h-4 w-4" />
                                    <span>{{ $item['label'] }}</span>
                                    @if ($itemCountKey)
                                        <span x-cloak x-show="requestCounts.{{ $itemCountKey }} > 0" title="Jāizskata" class="ml-auto inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                            <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                            <span x-text="requestCounts.{{ $itemCountKey }}">{{ $itemCount }}</span>
                                        </span>
                                    @endif
                                </x-dropdown-link>
                            @endforeach
                            @if ($requestHistoryNavigationItems !== [])
                                <div class="mx-4 my-2 h-px bg-slate-200"></div>
                                @foreach ($requestHistoryNavigationItems as $item)
                                    @php
                                        $itemCountKey = $item['route'] === 'device-transfers.index' ? 'device_transfers' : null;
                                        $itemCount = $item['route'] === 'device-transfers.index' ? $pendingTransferRequestCount : 0;
                                    @endphp
                                    <x-dropdown-link :href="route($item['route'])">
                                        <x-icon :name="$item['icon']" size="h-4 w-4" />
                                        <span>{{ $item['label'] }}</span>
                                        @if ($itemCountKey)
                                            <span x-cloak x-show="requestCounts.{{ $itemCountKey }} > 0" class="ml-auto inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                                <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                                <span x-text="requestCounts.{{ $itemCountKey }}">{{ $itemCount }}</span>
                                            </span>
                                        @endif
                                    </x-dropdown-link>
                                @endforeach
                            @endif
                        </x-slot>
                    </x-dropdown>

                    @if ($usersNavigationItem)
                        <x-nav-link :href="route($usersNavigationItem['route'])" :active="request()->routeIs($usersNavigationItem['pattern'])">
                            <x-icon :name="$usersNavigationItem['icon']" size="h-4 w-4" />
                            <span>{{ $usersNavigationItem['label'] }}</span>
                            <span x-cloak x-show="requestCounts.password_reset_requests > 0" title="Paroles maiņas pieprasījumi" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                    <x-icon name="key" size="h-3 w-3" />
                                    <span x-text="requestCounts.password_reset_requests">{{ $passwordResetRequestCount }}</span>
                                </span>
                        </x-nav-link>
                    @endif

                    @foreach ($secondaryNavigationItems as $item)
                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                            <x-icon :name="$item['icon']" size="h-4 w-4" />
                            <span>{{ $item['label'] }}</span>
                        </x-nav-link>
                    @endforeach

                    @if ($lessImportantNavigationItems !== [])
                        <x-dropdown align="left" width="w-72">
                            <x-slot name="trigger">
                                <button class="{{ $lessImportantGroupActive
                                    ? 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl bg-sky-50 px-3.5 py-2.5 text-sm font-semibold text-sky-800 ring-1 ring-sky-200 shadow-sm transition duration-200'
                                    : 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Vairāk</span>
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    Mazāk svarīgās
                                </div>
                                @foreach ($lessImportantNavigationItems as $item)
                                    <x-dropdown-link :href="route($item['route'])">
                                        <x-icon :name="$item['icon']" size="h-4 w-4" />
                                        <span>{{ $item['label'] }}</span>
                                    </x-dropdown-link>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @endif

                </div>
            </div>

            <div class="hidden min-w-0 sm:flex sm:items-center">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($isAdmin)
                        <form method="POST" action="{{ route('view-mode.update') }}" class="hidden lg:block">
                            @csrf
                            <div class="inline-flex items-center rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                                <label class="cursor-pointer">
                                    <input type="radio" name="mode" value="{{ \App\Models\User::VIEW_MODE_ADMIN }}" class="sr-only" onchange="this.form.submit()" @checked($currentViewMode === \App\Models\User::VIEW_MODE_ADMIN)>
                                    <span class="{{ $currentViewMode === \App\Models\User::VIEW_MODE_ADMIN
                                        ? 'inline-flex min-w-[6.5rem] items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white'
                                        : 'inline-flex min-w-[6.5rem] items-center justify-center rounded-xl px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 transition hover:bg-slate-100 hover:text-slate-900' }}">
                                        Admins
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="mode" value="{{ \App\Models\User::VIEW_MODE_USER }}" class="sr-only" onchange="this.form.submit()" @checked($currentViewMode === \App\Models\User::VIEW_MODE_USER)>
                                    <span class="{{ $currentViewMode === \App\Models\User::VIEW_MODE_USER
                                        ? 'inline-flex min-w-[6.5rem] items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white'
                                        : 'inline-flex min-w-[6.5rem] items-center justify-center rounded-xl px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 transition hover:bg-slate-100 hover:text-slate-900' }}">
                                        Lietotājs
                                    </span>
                                </label>
                            </div>
                        </form>
                    @endif

                    {{-- Paziņojumu centrs --}}
                    @php
                        $showNotificationPreviewCards = true;
                        // Jaunā paziņojumu funkcija glabā personīgos notikumus tabulā `user_notifications`.
                        // Šeit tos pieskaitām zvaniņa emblēmai, lai lietotājs redzētu ne tikai gaidošus
                        // pieteikumus, bet arī rezultātus: apstiprināts, noraidīts, piešķirta ierīce, remonts pabeigts.
                        $personalNotificationCount = \Illuminate\Support\Facades\Schema::hasTable('user_notifications') && $user
                            ? \App\Models\UserNotification::query()
                                ->where('user_id', $user->id)
                                ->whereNull('read_at')
                                ->count()
                            : 0;
                        $pendingNotificationsCount = $canManageRequests
                            ? (\App\Models\RepairRequest::query()->where('status', \App\Models\RepairRequest::STATUS_SUBMITTED)->count()
                                + \App\Models\WriteoffRequest::query()->where('status', \App\Models\WriteoffRequest::STATUS_SUBMITTED)->count()
                                + \App\Models\DeviceTransfer::query()->where('status', \App\Models\DeviceTransfer::STATUS_SUBMITTED)->count()
                                + $passwordResetRequestCount
                                + $personalNotificationCount)
                            : $incomingTransferReviewCount + $personalNotificationCount;
                    @endphp
                    <div
                        x-data="navNotificationCenter({
                            initialCount: {{ $pendingNotificationsCount }},
                            initialCounts: @js($initialNavCounts),
                            endpoint: '{{ route('live-notifications.index') }}',
                            markReadUrl: '{{ route('notifications.mark-all-read') }}',
                            csrfToken: '{{ csrf_token() }}',
                            storageKey: 'nav-notification-center:{{ $user?->id ?? 'guest' }}:{{ $canManageRequests ? 'manager' : 'user' }}',
                        })"
                        class="relative"
                    >
                        <button
                            @click="open = ! open"
                            @click.outside="open = false"
                            class="relative inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-sky-200 hover:from-sky-50 hover:to-white hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500"
                        >
                            <x-icon name="mail" size="h-5 w-5" />
                            <span class="hidden sm:inline">Paziņojumi</span>
                            <span
                                x-cloak
                                x-show="unreadCount > 0"
                                x-transition:enter="transition ease-out duration-250"
                                x-transition:enter-start="opacity-0 scale-75 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-180"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-75 -translate-y-1"
                                class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-gradient-to-r from-rose-500 to-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm"
                            >
                                <span x-text="Math.min(unreadCount, 99)"></span>
                            </span>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                            class="absolute right-0 z-50 mt-2 w-96 origin-top-right rounded-3xl border border-slate-200 bg-white shadow-2xl ring-1 ring-black/5 focus:outline-none"
                            x-cloak
                        >
                            <div class="rounded-t-3xl border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                                <div
                                    x-cloak
                                    x-show="readFeedbackVisible"
                                    x-transition.opacity.scale
                                    class="mb-3 inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                                >
                                    <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                    <span>Atzīmēts kā lasīts</span>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900">Paziņojumu centrs</h3>
                                <p class="mt-1 text-xs text-slate-500">Reāllaika paziņojumi par pieprasījumiem</p>
                            </div>

                            <div class="max-h-[28rem] overflow-y-auto p-2">
                                @php
                                    // Personīgie paziņojumi tiek rādīti virs lomas specifiskajiem pieteikumiem.
                                    // Tas nodrošina, ka lietotājs uzreiz redz jaunāko rezultātu par savām ierīcēm.
                                    $personalNotifications = \Illuminate\Support\Facades\Schema::hasTable('user_notifications') && $user
                                        ? \App\Models\UserNotification::query()
                                            ->where('user_id', $user->id)
                                            ->whereNull('read_at')
                                            ->latest('id')
                                            ->limit(5)
                                            ->get()
                                        : collect();
                                @endphp

                                @if ($personalNotifications->count() > 0)
                                    <div class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Mani paziņojumi</div>
                                    @foreach ($personalNotifications as $notification)
                                        <a href="{{ $notification->url ?: route('devices.index') }}" class="pending-review-card group flex items-start gap-3 rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-sky-200 hover:bg-sky-50">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 ring-1 ring-sky-200">
                                                <x-icon name="mail" size="h-5 w-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                                                    <span class="shrink-0 text-[10px] text-slate-400">{{ $notification->created_at?->diffForHumans(short: true) }}</span>
                                                </div>
                                                <p class="truncate text-xs text-slate-600">{{ $notification->message }}</p>
                                                <p class="truncate text-xs text-slate-500">{{ data_get($notification->data, 'device_name', 'Sistēmas paziņojums') }}</p>
                                            </div>
                                        </a>
                                    @endforeach
                                @endif

                                @if ($canManageRequests)
                                    @php
                                        $pendingRepairs = \App\Models\RepairRequest::query()
                                            ->with(['device', 'responsibleUser'])
                                            ->where('status', \App\Models\RepairRequest::STATUS_SUBMITTED)
                                            ->latest('id')
                                            ->limit(5)
                                            ->get();
                                        $pendingWriteoffs = \App\Models\WriteoffRequest::query()
                                            ->with(['device', 'responsibleUser'])
                                            ->where('status', \App\Models\WriteoffRequest::STATUS_SUBMITTED)
                                            ->latest('id')
                                            ->limit(5)
                                            ->get();
                                        $pendingPasswordResets = \App\Models\User::query()
                                            ->whereNotNull('password_reset_requested_at')
                                            ->latest('password_reset_requested_at')
                                            ->limit(5)
                                            ->get(['id', 'full_name', 'email', 'phone', 'job_title', 'password_reset_requested_at']);
                                    @endphp

                                    @if ($showNotificationPreviewCards && $pendingPasswordResets->count() > 0)
                                        <div class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Paroles maiņas pieprasījumi</div>
                                        @foreach ($pendingPasswordResets as $requestUser)
                                            <a href="{{ route('users.index', ['password_reset' => 1, 'highlight' => $requestUser->full_name, 'highlight_mode' => 'contains', 'highlight_id' => 'user-'.$requestUser->id]) }}" class="pending-review-card group flex items-start gap-3 rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-amber-200 hover:bg-amber-50">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                                    <x-icon name="key" size="h-5 w-5" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $requestUser->full_name ?: 'Lietotājs' }}</p>
                                                        <span class="shrink-0 text-[10px] text-slate-400">{{ $requestUser->password_reset_requested_at?->diffForHumans(short: true) }}</span>
                                                    </div>
                                                    <p class="truncate text-xs text-slate-600">{{ $requestUser->email ?: 'E-pasts nav norādīts' }}</p>
                                                    <p class="truncate text-xs text-slate-500">Administratoram jāiestata jauna parole.</p>
                                                </div>
                                            </a>
                                        @endforeach
                                    @endif

                                    @if ($showNotificationPreviewCards && $pendingRepairs->count() > 0)
                                        <div class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta pieprasījumi</div>
                                        @foreach ($pendingRepairs as $request)
                                            <a href="{{ route('repair-requests.index', ['statuses_filter' => 1, 'status' => ['submitted']]) }}#repair-request-{{ $request->id }}" class="pending-review-card group flex items-start gap-3 rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-amber-200 hover:bg-amber-50">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                                    <x-icon name="repair-request" size="h-5 w-5" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $request->responsibleUser?->full_name ?: 'Lietotājs' }}</p>
                                                        <span class="text-[10px] text-slate-400">{{ $request->created_at?->diffForHumans(short: true) }}</span>
                                                    </div>
                                                    <p class="truncate text-xs text-slate-600">{{ $request->device?->name ?: 'Ierīce' }} · {{ $request->device?->code ?: 'Bez koda' }}</p>
                                                    <p class="truncate text-xs text-slate-500">{{ Str::limit($request->description, 60) ?: 'Apraksts nav pievienots' }}</p>
                                                </div>
                                            </a>
                                        @endforeach
                                    @endif

                                    @if ($showNotificationPreviewCards && $pendingWriteoffs->count() > 0)
                                        <div class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Norakstīšanas pieprasījumi</div>
                                        @foreach ($pendingWriteoffs as $request)
                                            <a href="{{ route('writeoff-requests.index', ['statuses_filter' => 1, 'status' => ['submitted']]) }}#writeoff-request-{{ $request->id }}" class="pending-review-card group flex items-start gap-3 rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-rose-200 hover:bg-rose-50">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 ring-1 ring-rose-200">
                                                    <x-icon name="writeoff" size="h-5 w-5" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $request->responsibleUser?->full_name ?: 'Lietotājs' }}</p>
                                                        <span class="text-[10px] text-slate-400">{{ $request->created_at?->diffForHumans(short: true) }}</span>
                                                    </div>
                                                    <p class="truncate text-xs text-slate-600">{{ $request->device?->name ?: 'Ierīce' }} · {{ $request->device?->code ?: 'Bez koda' }}</p>
                                                    <p class="truncate text-xs text-slate-500">{{ Str::limit($request->reason, 60) ?: 'Iemesls nav pievienots' }}</p>
                                                </div>
                                            </a>
                                        @endforeach
                                    @endif

                                    @if (! $showNotificationPreviewCards && ($pendingRepairs->count() > 0 || $pendingWriteoffs->count() > 0 || $pendingPasswordResets->count() > 0))
                                        <div class="px-3 py-6 text-center">
                                            <p class="text-sm font-semibold text-slate-900">Aktīvie pieprasījumi ir pieejami pārskata lapās</p>
                                            <p class="mt-1 text-xs text-slate-500">Šajā formā atstātas tikai ātrās pārejas uz pieteikumu sadaļām.</p>
                                        </div>
                                    @endif

                                    @if ($pendingRepairs->count() === 0 && $pendingWriteoffs->count() === 0 && $pendingPasswordResets->count() === 0 && $personalNotifications->count() === 0)
                                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                <x-icon name="check-circle" size="h-7 w-7" />
                                            </div>
                                            <p class="text-sm font-semibold text-slate-900">Viss kārtībā!</p>
                                            <p class="text-xs text-slate-500">Nav jaunu pieprasījumu</p>
                                        </div>
                                    @endif
                                @else
                                    @if ($incomingTransferReviewCount > 0)
                                        @php
                                            $pendingTransfers = \App\Models\DeviceTransfer::query()
                                                ->with(['device', 'responsibleUser'])
                                                ->where('transfered_to_id', auth()->id())
                                                ->where('status', \App\Models\DeviceTransfer::STATUS_SUBMITTED)
                                                ->latest('id')
                                                ->limit(5)
                                                ->get();
                                        @endphp
                                        <div class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ienākošās nodošanas</div>
                                        @foreach ($pendingTransfers as $transfer)
                                            <a href="{{ route('device-transfers.index', ['incoming' => 1]) }}#device-transfer-{{ $transfer->id }}" class="pending-review-card group flex items-start gap-3 rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-emerald-200 hover:bg-emerald-50">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                                                    <x-icon name="transfer" size="h-5 w-5" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $transfer->responsibleUser?->full_name ?: 'Lietotājs' }}</p>
                                                        <span class="text-[10px] text-slate-400">{{ $transfer->created_at?->diffForHumans(short: true) }}</span>
                                                    </div>
                                                    <p class="truncate text-xs text-slate-600">{{ $transfer->device?->name ?: 'Ierīce' }} · {{ $transfer->device?->code ?: 'Bez koda' }}</p>
                                                    <p class="truncate text-xs text-slate-500">{{ Str::limit($transfer->transfer_reason, 60) ?: 'Nodošanas iemesls' }}</p>
                                                </div>
                                            </a>
                                        @endforeach
                                    @elseif ($personalNotifications->count() === 0)
                                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                <x-icon name="check-circle" size="h-7 w-7" />
                                            </div>
                                            <p class="text-sm font-semibold text-slate-900">Viss kārtībā!</p>
                                            <p class="text-xs text-slate-500">Nav jaunu paziņojumu</p>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="rounded-b-3xl border-t border-slate-100 bg-slate-50/50 px-5 py-3">
                                @if (! $canManageRequests)
                                    <a href="{{ route('device-transfers.index') }}" class="flex items-center justify-between text-sm font-semibold text-sky-700 transition hover:text-sky-900">
                                        <span>Skatīt visus nodošanas pieteikumus</span>
                                        <x-icon name="transfer" size="h-4 w-4" />
                                    </a>
                                @endif
                                @if ($pendingNotificationsCount > 0)
                                    <button
                                        type="button"
                                        @click="markAllAsRead()"
                                        x-cloak
                                        x-show="unreadCount > 0"
                                        x-transition.opacity
                                        :disabled="markingAllRead"
                                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800 disabled:cursor-wait disabled:opacity-70"
                                    >
                                        <x-icon name="check-circle" size="h-4 w-4" />
                                        Atzīmēt kā lasītu
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <x-dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        <button class="inline-flex min-w-0 max-w-[17rem] items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white shadow-sm">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->full_name ?? 'L', 0, 1)) }}
                            </div>
                            <div class="min-w-0 text-left leading-tight">
                                <div class="truncate font-semibold text-slate-900">{{ $user?->full_name ?? 'Lietotājs' }}</div>
                                <div class="mt-1 inline-flex max-w-full items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    <span class="truncate">{{ $isAdmin ? ($canManageRequests ? 'admina skats' : 'darbinieka skats') : ($user?->role ?? '') }}</span>
                                </div>
                            </div>
                            <svg class="ms-1 h-4 w-4 fill-current text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 pb-2 pt-3">
                            <div class="mb-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Izskats</div>
                            <div class="theme-choice-group">
                                <button type="button" class="theme-choice-btn" data-theme-choice data-theme-value="light" aria-pressed="false">
                                    <x-icon name="sun" size="h-4 w-4" />
                                    <span>Gaišā tēma</span>
                                </button>
                                <button type="button" class="theme-choice-btn" data-theme-choice data-theme-value="dark" aria-pressed="false">
                                    <x-icon name="moon" size="h-4 w-4" />
                                    <span>Tumšā tēma</span>
                                </button>
                            </div>
                        </div>
                        <div class="mx-4 my-2 h-px bg-slate-200"></div>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 rounded-xl px-4 py-2.5 text-start text-sm font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-50 hover:text-slate-900 focus:bg-slate-50 focus:text-slate-900 focus:outline-none"
                            @click="$dispatch('open-modal', 'profile-modal')"
                        >
                            <x-icon name="profile" size="h-4 w-4" />
                            <span>Profils</span>
                        </button>
                        @if ($isAdmin)
                            <button
                                type="button"
                                class="flex w-full items-center gap-2.5 rounded-xl px-4 py-2.5 text-start text-sm font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-50 hover:text-slate-900 focus:bg-slate-50 focus:text-slate-900 focus:outline-none"
                                @click="$dispatch('open-modal', 'profile-settings-modal')"
                            >
                                <x-icon name="settings" size="h-4 w-4" />
                                <span>Iestatījumi</span>
                            </button>
                        @endif
                        <x-post-action-button
                            :action="route('logout')"
                            button-class="flex w-full items-center gap-2.5 rounded-xl px-4 py-2.5 text-start text-sm font-medium text-rose-600 transition duration-150 ease-in-out hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"
                        >
                            <x-icon name="logout" size="h-4 w-4" />
                            <span>Izrakstīties</span>
                        </x-post-action-button>
                    </x-slot>
                </x-dropdown>
                </div>
            </div>

            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-2.5 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="app-mobile-nav-panel hidden border-t border-slate-200 bg-white xl:hidden">
        <div class="space-y-2 px-4 pb-4 pt-3">
            @if ($isAdmin)
                <form method="POST" action="{{ route('view-mode.update') }}" class="mb-3 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                    @csrf
                    <div class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Skata režīms</div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="{{ \App\Models\User::VIEW_MODE_ADMIN }}" class="sr-only" onchange="this.form.submit()" @checked($currentViewMode === \App\Models\User::VIEW_MODE_ADMIN)>
                            <span class="{{ $currentViewMode === \App\Models\User::VIEW_MODE_ADMIN
                                ? 'flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white'
                                : 'flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-600 ring-1 ring-slate-200' }}">
                                Admins
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="{{ \App\Models\User::VIEW_MODE_USER }}" class="sr-only" onchange="this.form.submit()" @checked($currentViewMode === \App\Models\User::VIEW_MODE_USER)>
                            <span class="{{ $currentViewMode === \App\Models\User::VIEW_MODE_USER
                                ? 'flex items-center justify-center rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white'
                                : 'flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-600 ring-1 ring-slate-200' }}">
                                Lietotājs
                            </span>
                        </label>
                    </div>
                </form>
            @endif

            @foreach ($primaryNavigationItems as $item)
                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon :name="$item['icon']" size="h-5 w-5" />
                        <span>{{ $item['label'] }}</span>
                    </span>
                </x-responsive-nav-link>
            @endforeach

            @if ($repairsNavigationItem)
                <x-responsive-nav-link :href="route($repairsNavigationItem['route'])" :active="request()->routeIs($repairsNavigationItem['pattern'])">
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon :name="$repairsNavigationItem['icon']" size="h-5 w-5" />
                        <span>{{ $repairsNavigationItem['label'] }}</span>
                    </span>
                </x-responsive-nav-link>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2">
                <div class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pieteikumi</div>
                <div class="space-y-1">
                    @foreach ($requestReviewNavigationItems as $item)
                        @php
                            $itemCountKey = match ($item['route']) {
                                'repair-requests.index' => 'repair_requests',
                                'writeoff-requests.index' => 'writeoff_requests',
                                'device-transfers.index' => $canManageRequests ? 'device_transfers' : 'incoming_transfers',
                                default => null,
                            };
                            $itemCount = match ($item['route']) {
                                'repair-requests.index' => $pendingRepairRequestCount,
                                'writeoff-requests.index' => $pendingWriteoffRequestCount,
                                'device-transfers.index' => $canManageRequests ? $pendingTransferRequestCount : $incomingTransferReviewCount,
                                default => 0,
                            };
                        @endphp
                        <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                            <span class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2.5">
                                    <x-icon :name="$item['icon']" size="h-5 w-5" />
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                @if ($itemCountKey)
                                    <span x-cloak x-show="requestCounts.{{ $itemCountKey }} > 0" title="Jāizskata" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                        <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                        <span x-text="requestCounts.{{ $itemCountKey }}">{{ $itemCount }}</span>
                                    </span>
                                @endif
                            </span>
                        </x-responsive-nav-link>
                    @endforeach
                    @if ($requestHistoryNavigationItems !== [])
                        <div class="mx-3 my-2 h-px bg-slate-200"></div>
                        @foreach ($requestHistoryNavigationItems as $item)
                            @php
                                $itemCountKey = $item['route'] === 'device-transfers.index' ? 'device_transfers' : null;
                                $itemCount = $item['route'] === 'device-transfers.index' ? $pendingTransferRequestCount : 0;
                            @endphp
                            <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-2.5">
                                        <x-icon :name="$item['icon']" size="h-5 w-5" />
                                        <span>{{ $item['label'] }}</span>
                                    </span>
                                    @if ($itemCountKey)
                                        <span x-cloak x-show="requestCounts.{{ $itemCountKey }} > 0" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                            <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                            <span x-text="requestCounts.{{ $itemCountKey }}">{{ $itemCount }}</span>
                                        </span>
                                    @endif
                                </span>
                            </x-responsive-nav-link>
                        @endforeach
                    @endif
                </div>
            </div>

            @if ($usersNavigationItem)
                <x-responsive-nav-link :href="route($usersNavigationItem['route'])" :active="request()->routeIs($usersNavigationItem['pattern'])">
                    <span class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2.5">
                            <x-icon :name="$usersNavigationItem['icon']" size="h-5 w-5" />
                            <span>{{ $usersNavigationItem['label'] }}</span>
                        </span>
                        <span x-cloak x-show="requestCounts.password_reset_requests > 0" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                <x-icon name="key" size="h-3 w-3" />
                                <span x-text="requestCounts.password_reset_requests">{{ $passwordResetRequestCount }}</span>
                            </span>
                    </span>
                </x-responsive-nav-link>
            @endif

            @foreach ($secondaryNavigationItems as $item)
                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon :name="$item['icon']" size="h-5 w-5" />
                        <span>{{ $item['label'] }}</span>
                    </span>
                </x-responsive-nav-link>
            @endforeach

            @if ($lessImportantNavigationItems !== [])
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2">
                    <div class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Vairāk</div>
                    <div class="space-y-1">
                        @foreach ($lessImportantNavigationItems as $item)
                            <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                                <span class="inline-flex items-center gap-2.5">
                                    <x-icon :name="$item['icon']" size="h-5 w-5" />
                                    <span>{{ $item['label'] }}</span>
                                </span>
                            </x-responsive-nav-link>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="border-t border-slate-200 pb-4 pt-4">
            <div class="px-4">
                <div class="text-base font-semibold text-slate-900">{{ $user?->full_name ?? 'Lietotājs' }}</div>
                <div class="text-sm text-slate-500">{{ $user?->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-2 px-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <div class="mb-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Izskats</div>
                    <div class="theme-choice-group">
                        <button type="button" class="theme-choice-btn" data-theme-choice data-theme-value="light" aria-pressed="false">
                            <x-icon name="sun" size="h-4 w-4" />
                            <span>Gaišā tēma</span>
                        </button>
                        <button type="button" class="theme-choice-btn" data-theme-choice data-theme-value="dark" aria-pressed="false">
                            <x-icon name="moon" size="h-4 w-4" />
                            <span>Tumšā tēma</span>
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="block w-full whitespace-nowrap rounded-2xl px-4 py-3 text-start text-base font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-50 hover:shadow-sm hover:text-slate-900"
                    @click="open = false; $dispatch('open-modal', 'profile-modal')"
                >
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon name="profile" size="h-5 w-5" />
                        <span>Profils</span>
                    </span>
                </button>
                @if ($isAdmin)
                    <button
                        type="button"
                        class="block w-full whitespace-nowrap rounded-2xl px-4 py-3 text-start text-base font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-50 hover:shadow-sm hover:text-slate-900"
                        @click="open = false; $dispatch('open-modal', 'profile-settings-modal')"
                    >
                        <span class="inline-flex items-center gap-2.5">
                            <x-icon name="settings" size="h-5 w-5" />
                            <span>Iestatījumi</span>
                        </span>
                    </button>
                @endif
                <x-post-action-button
                    :action="route('logout')"
                    button-class="block w-full whitespace-nowrap rounded-2xl px-4 py-3 text-start text-base font-medium text-rose-600 transition duration-150 ease-in-out hover:bg-rose-50 hover:shadow-sm hover:text-rose-700"
                >
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon name="logout" size="h-5 w-5" />
                        <span>Izrakstīties</span>
                    </span>
                </x-post-action-button>
            </div>
        </div>
    </div>
</nav>
