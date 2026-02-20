<nav x-data="{ open: false }" class="border-b border-gray-200 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 transition hover:opacity-90">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-1 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Darbvirsma</x-nav-link>
                    <x-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">Ierīces</x-nav-link>
                    <x-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types*')">Ierīču tipi</x-nav-link>
                    <x-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">Remonti</x-nav-link>
                    <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees*')">Darbinieki</x-nav-link>
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users*')">Lietotāji</x-nav-link>
                    <x-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*') || request()->routeIs('rooms*')">Ēkas un telpas</x-nav-link>
                    <x-nav-link :href="route('device-sets.index')" :active="request()->routeIs('device-sets*') || request()->routeIs('device-set-items*')">Komplekti</x-nav-link>
                </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="text-left">
                                <div class="font-semibold">{{ optional(Auth::user()->employee)->full_name ?? 'Lietotājs' }}</div>
                                <div class="text-xs text-gray-500">{{ Auth::user()->role }}</div>
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
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="text-red-600 hover:bg-red-50">
                                Izrakstīties
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Darbvirsma</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">Ierīces</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types*')">Ierīču tipi</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">Remonti</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees*')">Darbinieki</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users*')">Lietotāji</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*') || request()->routeIs('rooms*')">Ēkas un telpas</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('device-sets.index')" :active="request()->routeIs('device-sets*') || request()->routeIs('device-set-items*')">Komplekti</x-responsive-nav-link>
        </div>

        <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ optional(Auth::user()->employee)->full_name ?? 'Lietotājs' }}</div>
                <div class="text-sm text-gray-500">{{ optional(Auth::user()->employee)->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profils</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600">
                        Izrakstīties
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
