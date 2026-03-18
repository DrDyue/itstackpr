<nav x-data="{ open: false }" class="border-b border-gray-200 bg-white shadow-sm">
    @php
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $canManageRequests = $user?->canManageRequests() ?? false;
    @endphp

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-4 py-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-2 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Darbvirsma</x-nav-link>
                    <x-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">Ierices</x-nav-link>
                    <x-nav-link :href="route('repair-requests.index')" :active="request()->routeIs('repair-requests*')">Remonta pieteikumi</x-nav-link>
                    <x-nav-link :href="route('writeoff-requests.index')" :active="request()->routeIs('writeoff-requests*')">Norakstisanas pieteikumi</x-nav-link>
                    <x-nav-link :href="route('device-transfers.index')" :active="request()->routeIs('device-transfers*')">Parsutisanas</x-nav-link>
                    @if ($canManageRequests)
                        <x-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">Remonti</x-nav-link>
                        <x-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms*')">Telpas</x-nav-link>
                        <x-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*')">Ekas</x-nav-link>
                        <x-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types*')">Iericu tipi</x-nav-link>
                    @endif
                    @if ($isAdmin)
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users*')">Lietotaji</x-nav-link>
                        <x-nav-link :href="route('audit-log.index')" :active="request()->routeIs('audit-log*')">Audits</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="text-left">
                                <div class="font-semibold">{{ $user?->full_name ?? 'Lietotajs' }}</div>
                                <div class="text-xs text-gray-500">{{ $user?->role ?? '' }}</div>
                            </div>
                            <svg class="ms-2 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profils</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-start text-sm font-medium text-red-600 transition duration-150 ease-in-out hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                                Izrakstities
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="hidden lg:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Darbvirsma</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">Ierices</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('repair-requests.index')" :active="request()->routeIs('repair-requests*')">Remonta pieteikumi</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('writeoff-requests.index')" :active="request()->routeIs('writeoff-requests*')">Norakstisanas pieteikumi</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('device-transfers.index')" :active="request()->routeIs('device-transfers*')">Parsutisanas</x-responsive-nav-link>
            @if ($canManageRequests)
                <x-responsive-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">Remonti</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms*')">Telpas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*')">Ekas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types*')">Iericu tipi</x-responsive-nav-link>
            @endif
            @if ($isAdmin)
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users*')">Lietotaji</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('audit-log.index')" :active="request()->routeIs('audit-log*')">Audits</x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ $user?->full_name ?? 'Lietotajs' }}</div>
                <div class="text-sm text-gray-500">{{ $user?->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">Profils</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Izrakstities</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
