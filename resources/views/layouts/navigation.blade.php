<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur">
    @php
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $canManageRequests = $user?->canManageRequests() ?? false;
        $navigationItems = [
            ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Darbvirsma', 'icon' => 'dashboard'],
            ['route' => 'devices.index', 'pattern' => 'devices*', 'label' => 'Ierices', 'icon' => 'device'],
            ['route' => 'repair-requests.index', 'pattern' => 'repair-requests*', 'label' => 'Remonta pieteikumi', 'icon' => 'repair-request'],
            ['route' => 'writeoff-requests.index', 'pattern' => 'writeoff-requests*', 'label' => 'Norakstisana', 'icon' => 'writeoff'],
            ['route' => 'device-transfers.index', 'pattern' => 'device-transfers*', 'label' => 'Parsutisanas', 'icon' => 'transfer'],
        ];

        if ($canManageRequests) {
            array_push(
                $navigationItems,
                ['route' => 'repairs.index', 'pattern' => 'repairs*', 'label' => 'Remonti', 'icon' => 'repair'],
                ['route' => 'rooms.index', 'pattern' => 'rooms*', 'label' => 'Telpas', 'icon' => 'room'],
                ['route' => 'buildings.index', 'pattern' => 'buildings*', 'label' => 'Ekas', 'icon' => 'building'],
                ['route' => 'device-types.index', 'pattern' => 'device-types*', 'label' => 'Iericu tipi', 'icon' => 'tag'],
            );
        }

        if ($isAdmin) {
            array_push(
                $navigationItems,
                ['route' => 'users.index', 'pattern' => 'users*', 'label' => 'Lietotaji', 'icon' => 'users'],
                ['route' => 'audit-log.index', 'pattern' => 'audit-log*', 'label' => 'Audits', 'icon' => 'audit'],
            );
        }
    @endphp

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <x-application-logo />
                </a>

                <div class="hidden flex-wrap items-center gap-2 xl:flex">
                    @foreach ($navigationItems as $item)
                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                            <x-icon :name="$item['icon']" size="h-4 w-4" />
                            <span>{{ $item['label'] }}</span>
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white shadow-sm">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->full_name ?? 'L', 0, 1)) }}
                            </div>
                            <div class="text-left leading-tight">
                                <div class="font-semibold text-slate-900">{{ $user?->full_name ?? 'Lietotajs' }}</div>
                                <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $user?->role ?? '' }}</div>
                            </div>
                            <svg class="ms-1 h-4 w-4 fill-current text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            <x-icon name="profile" size="h-4 w-4" />
                            <span>Profils</span>
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-4 py-2.5 text-start text-sm font-medium text-rose-600 transition duration-150 ease-in-out hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                <x-icon name="logout" size="h-4 w-4" />
                                <span>Izrakstities</span>
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
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

    <div :class="{'block': open, 'hidden': !open}" class="hidden border-t border-slate-200 bg-white xl:hidden">
        <div class="space-y-2 px-4 pb-4 pt-3">
            @foreach ($navigationItems as $item)
                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">
                    <span class="inline-flex items-center gap-2.5">
                        <x-icon :name="$item['icon']" size="h-5 w-5" />
                        <span>{{ $item['label'] }}</span>
                    </span>
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="border-t border-slate-200 pb-4 pt-4">
            <div class="px-4">
                <div class="text-base font-semibold text-slate-900">{{ $user?->full_name ?? 'Lietotajs' }}</div>
                <div class="text-sm text-slate-500">{{ $user?->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-2 px-4">
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
                            <span>Izrakstities</span>
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

