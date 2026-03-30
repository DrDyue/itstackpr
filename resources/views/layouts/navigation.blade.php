{{--
    Layout daļa: Augšējā navigācija.
    Atbildiba: parāda galvenās sistēmas sadaļas atkarībā no lietotāja lomas un izvēlētā skata režīma.
    Kāpēc tas ir svarīgi:
    1. Adminam šeit parādās pārvaldības sadaļas un view mode pārslēgs.
    2. Lietotājam šeit paliek tikai tās sadaļas, kuras viņš drīkst izmantot.
    3. Navigācijā tiek rādīti arī gaidošo pieteikumu indikatori un ātrās šaites.
--}}
<nav x-data="{ open: false }" class="app-main-nav sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur">
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
        $primaryNavigationItems = array_values(array_filter([
            $canManageRequests ? ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Darbvirsma', 'icon' => 'dashboard'] : null,
            ['route' => 'devices.index', 'pattern' => 'devices*', 'label' => 'Ierīces', 'icon' => 'device'],
        ]));
        $requestNavigationItems = [
            ['route' => 'repair-requests.index', 'pattern' => 'repair-requests*', 'label' => 'Remonta pieteikumi', 'icon' => 'repair-request'],
            ['route' => 'writeoff-requests.index', 'pattern' => 'writeoff-requests*', 'label' => 'Norakstīšanas pieteikumi', 'icon' => 'writeoff'],
            ['route' => 'device-transfers.index', 'pattern' => 'device-transfers*', 'label' => 'Pārsūtīšanas pieteikumi', 'icon' => 'transfer', 'pending_review_count' => $incomingTransferReviewCount],
        ];
        $requestReviewNavigationItems = $canManageRequests ? collect($requestNavigationItems)->take(2)->values()->all() : $requestNavigationItems;
        $requestHistoryNavigationItems = $canManageRequests ? collect($requestNavigationItems)->slice(2)->values()->all() : [];
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

    <div class="mx-auto max-w-[92rem] px-4 sm:px-6 lg:px-8">
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
                                @if ($incomingTransferReviewCount > 0)
                                    <span title="Jaizskata: {{ $incomingTransferReviewCount }}" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                        <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                        <span>{{ $incomingTransferReviewCount }}</span>
                                    </span>
                                @endif
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                    </x-slot>

                    <x-slot name="content">
                            @foreach ($requestReviewNavigationItems as $item)
                                <x-dropdown-link :href="route($item['route'])">
                                    <x-icon :name="$item['icon']" size="h-4 w-4" />
                                    <span>{{ $item['label'] }}</span>
                                    @if (($item['pending_review_count'] ?? 0) > 0)
                                        <span title="Jaizskata: {{ $item['pending_review_count'] }}" class="ml-auto inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                            <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                            <span>{{ $item['pending_review_count'] }}</span>
                                        </span>
                                    @endif
                                </x-dropdown-link>
                            @endforeach
                            @if ($requestHistoryNavigationItems !== [])
                                <div class="mx-4 my-2 h-px bg-slate-200"></div>
                                @foreach ($requestHistoryNavigationItems as $item)
                                    <x-dropdown-link :href="route($item['route'])">
                                        <x-icon :name="$item['icon']" size="h-4 w-4" />
                                        <span>{{ $item['label'] }}</span>
                                    </x-dropdown-link>
                                @endforeach
                            @endif
                        </x-slot>
                    </x-dropdown>

                    @if ($usersNavigationItem)
                        <x-nav-link :href="route($usersNavigationItem['route'])" :active="request()->routeIs($usersNavigationItem['pattern'])">
                            <x-icon :name="$usersNavigationItem['icon']" size="h-4 w-4" />
                            <span>{{ $usersNavigationItem['label'] }}</span>
                        </x-nav-link>
                    @endif

                    @if ($lessImportantNavigationItems !== [])
                        <x-dropdown align="left" width="w-72">
                            <x-slot name="trigger">
                                <button class="{{ $lessImportantGroupActive
                                    ? 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl bg-sky-50 px-3.5 py-2.5 text-sm font-semibold text-sky-800 ring-1 ring-sky-200 shadow-sm transition duration-200'
                                    : 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Vairak</span>
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pb-2 pt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    Mazak svarigais
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

                    <x-dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        <button class="inline-flex min-w-0 max-w-[17rem] items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white shadow-sm">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->full_name ?? 'L', 0, 1)) }}
                            </div>
                            <div class="min-w-0 text-left leading-tight">
                                <div class="truncate font-semibold text-slate-900">{{ $user?->full_name ?? 'Lietotājs' }}</div>
                                <div class="mt-1 inline-flex max-w-full items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    <span class="truncate">{{ $isAdmin ? ($canManageRequests ? 'admin view' : 'user view') : ($user?->role ?? '') }}</span>
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
                        <x-dropdown-link :href="route('profile.edit')">
                            <x-icon name="profile" size="h-4 w-4" />
                            <span>Profils</span>
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-4 py-2.5 text-start text-sm font-medium text-rose-600 transition duration-150 ease-in-out hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                <x-icon name="logout" size="h-4 w-4" />
                                <span>Izrakstīties</span>
                            </button>
                        </form>
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
                    <div class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Skata rezims</div>
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
                        <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                            <span class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2.5">
                                    <x-icon :name="$item['icon']" size="h-5 w-5" />
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                @if (($item['pending_review_count'] ?? 0) > 0)
                                    <span title="Jaizskata: {{ $item['pending_review_count'] }}" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                        <x-icon name="exclamation-triangle" size="h-3 w-3" />
                                        <span>{{ $item['pending_review_count'] }}</span>
                                    </span>
                                @endif
                            </span>
                        </x-responsive-nav-link>
                    @endforeach
                    @if ($requestHistoryNavigationItems !== [])
                        <div class="mx-3 my-2 h-px bg-slate-200"></div>
                        @foreach ($requestHistoryNavigationItems as $item)
                            <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                                <span class="inline-flex items-center gap-2.5">
                                    <x-icon :name="$item['icon']" size="h-5 w-5" />
                                    <span>{{ $item['label'] }}</span>
                                </span>
                            </x-responsive-nav-link>
                        @endforeach
                    @endif
                </div>
            </div>

            @if ($usersNavigationItem)
                <x-responsive-nav-link :href="route($usersNavigationItem['route'])" :active="request()->routeIs($usersNavigationItem['pattern'])">
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon :name="$usersNavigationItem['icon']" size="h-5 w-5" />
                        <span>{{ $usersNavigationItem['label'] }}</span>
                    </span>
                </x-responsive-nav-link>
            @endif

            @if ($lessImportantNavigationItems !== [])
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2">
                    <div class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Vairak</div>
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
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon name="profile" size="h-5 w-5" />
                        <span>Profils</span>
                    </span>
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        <span class="inline-flex items-center gap-2.5">
                            <x-icon name="logout" size="h-5 w-5" />
                            <span>Izrakstīties</span>
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
