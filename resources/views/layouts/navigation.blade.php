<nav x-data="{ open: false }" class="border-b border-gray-200 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 transition hover:opacity-90">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-1 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12 12 3.75 20.25 12M5.25 10.5v8.25h13.5V10.5"/>
                        </svg>
                        <span>Darbvirsma</span>
                    </x-nav-link>
                    <x-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-15 4.5h15m-15 4.5h9M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/>
                        </svg>
                        <span>Ierices</span>
                    </x-nav-link>
                    <x-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                        </svg>
                        <span>Iericu tipi</span>
                    </x-nav-link>
                    <x-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 6.75 1.5-1.5a2.121 2.121 0 1 1 3 3l-7.5 7.5-4.5 1.5 1.5-4.5 6-6Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m13.5 9 3 3"/>
                        </svg>
                        <span>Remonti</span>
                    </x-nav-link>
                    <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                        </svg>
                        <span>Darbinieki</span>
                    </x-nav-link>
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.125a7.5 7.5 0 1 0-6 0"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 20.25v-.75a3 3 0 0 0-3-3h-7.5a3 3 0 0 0-3 3v.75"/>
                        </svg>
                        <span>Lietotaji</span>
                    </x-nav-link>
                    <x-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*') || request()->routeIs('rooms*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21"/>
                        </svg>
                        <span>Ekas un telpas</span>
                    </x-nav-link>
                    <x-nav-link :href="route('device-sets.index')" :active="request()->routeIs('device-sets*') || request()->routeIs('device-set-items*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 7.5 8.25-4.5 8.25 4.5M3.75 7.5V16.5L12 21l8.25-4.5V7.5M12 12l8.25-4.5M12 12v9M12 12 3.75 7.5"/>
                        </svg>
                        <span>Komplekti</span>
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="text-left">
                                <div class="font-semibold">{{ optional(Auth::user()->employee)->full_name ?? 'Lietotajs' }}</div>
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
                                Izrakstities
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
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12 12 3.75 20.25 12M5.25 10.5v8.25h13.5V10.5"/>
                    </svg>
                    <span>Darbvirsma</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-15 4.5h15m-15 4.5h9M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/>
                    </svg>
                    <span>Ierices</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                    </svg>
                    <span>Iericu tipi</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 6.75 1.5-1.5a2.121 2.121 0 1 1 3 3l-7.5 7.5-4.5 1.5 1.5-4.5 6-6Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m13.5 9 3 3"/>
                    </svg>
                    <span>Remonti</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                    </svg>
                    <span>Darbinieki</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.125a7.5 7.5 0 1 0-6 0"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 20.25v-.75a3 3 0 0 0-3-3h-7.5a3 3 0 0 0-3 3v.75"/>
                    </svg>
                    <span>Lietotaji</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*') || request()->routeIs('rooms*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21"/>
                    </svg>
                    <span>Ekas un telpas</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('device-sets.index')" :active="request()->routeIs('device-sets*') || request()->routeIs('device-set-items*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 7.5 8.25-4.5 8.25 4.5M3.75 7.5V16.5L12 21l8.25-4.5V7.5M12 12l8.25-4.5M12 12v9M12 12 3.75 7.5"/>
                    </svg>
                    <span>Komplekti</span>
                </span>
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ optional(Auth::user()->employee)->full_name ?? 'Lietotajs' }}</div>
                <div class="text-sm text-gray-500">{{ optional(Auth::user()->employee)->email ?? '' }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profils</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600">
                        Izrakstities
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
