<nav x-data="{ open: false }" class="border-b border-gray-200 bg-white shadow-sm">
    @php
        $moreActive = request()->routeIs('device-types*')
            || request()->routeIs('employees*')
            || request()->routeIs('users*')
            || request()->routeIs('device-sets*')
            || request()->routeIs('device-set-items*')
            || request()->routeIs('backups*')
            || request()->routeIs('audit-log*');
    @endphp
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 justify-between py-3">
            <div class="flex min-w-0 items-center gap-4 lg:gap-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 transition hover:opacity-90">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-1 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12 12 3.75 20.25 12M5.25 10.5v8.25h13.5V10.5"/>
                        </svg>
                        <span>Darbvirsma</span>
                    </x-nav-link>
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v4.5H3.75Zm0 9h7.5v4.5h-7.5Zm10.5 0h6v4.5h-6Z"/>
                        </svg>
                        <span>Skati</span>
                    </x-nav-link>
                    <x-nav-link :href="route('devices.index')" :active="request()->routeIs('devices*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-15 4.5h15m-15 4.5h9M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/>
                        </svg>
                        <span>Ierices</span>
                    </x-nav-link>
                    <x-nav-link :href="route('repairs.index')" :active="request()->routeIs('repairs*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 6.75 1.5-1.5a2.121 2.121 0 1 1 3 3l-7.5 7.5-4.5 1.5 1.5-4.5 6-6Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m13.5 9 3 3"/>
                        </svg>
                        <span>Remonti</span>
                    </x-nav-link>
                    <x-nav-link :href="route('buildings.index')" :active="request()->routeIs('buildings*') || request()->routeIs('rooms*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21"/>
                        </svg>
                        <span>Ekas</span>
                    </x-nav-link>

                    <x-dropdown align="left" width="w-64">
                        <x-slot name="trigger">
                            <button class="{{ $moreActive ? 'inline-flex items-center gap-2 whitespace-nowrap rounded-lg border-l-4 border-blue-600 bg-blue-50 px-2.5 py-2 text-sm font-semibold text-blue-700 transition duration-200' : 'inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-2.5 py-2 text-sm font-medium text-gray-600 transition duration-200 hover:bg-blue-50 hover:text-blue-700' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                                </svg>
                                <span>Vairak</span>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('employees.index')">
                                <span class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                                    </svg>
                                    <span>Darbinieki</span>
                                </span>
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('device-types.index')">
                                <span class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                                    </svg>
                                    <span>Iericu tipi</span>
                                </span>
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('users.index')">
                                <span class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.125a7.5 7.5 0 1 0-6 0"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 20.25v-.75a3 3 0 0 0-3-3h-7.5a3 3 0 0 0-3 3v.75"/>
                                    </svg>
                                    <span>Lietotaji</span>
                                </span>
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('device-sets.index')">
                                <span class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 7.5 8.25-4.5 8.25 4.5M3.75 7.5V16.5L12 21l8.25-4.5V7.5M12 12l8.25-4.5M12 12v9M12 12 3.75 7.5"/>
                                    </svg>
                                    <span>Komplekti</span>
                                </span>
                            </x-dropdown-link>
                            @if (auth()->user()?->role === 'admin')
                                <x-dropdown-link :href="route('backups.index')">
                                    <span class="inline-flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M6 3.75h12a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5"/>
                                        </svg>
                                        <span>Kopijas</span>
                                    </span>
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('audit-log.index')">
                                    <span class="inline-flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6m-6-4.5h6M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v12A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 20.25v-3a2.25 2.25 0 0 0-2.25-2.25h-3A2.25 2.25 0 0 0 8.25 17.25v3"/>
                                        </svg>
                                        <span>Audits</span>
                                    </span>
                                </x-dropdown-link>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <div class="hidden sm:ms-4 sm:flex sm:items-center">
                <x-dropdown align="right" width="w-56">
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
                        <x-dropdown-link :href="route('profile.edit')">
                            <span class="inline-flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                                </svg>
                                <span>Profils</span>
                            </span>
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="text-red-600 hover:bg-red-50">
                                <span class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 15 12m0 0-3 2.25M15 12H3"/>
                                    </svg>
                                    <span>Izrakstities</span>
                                </span>
                            </x-dropdown-link>
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
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12 12 3.75 20.25 12M5.25 10.5v8.25h13.5V10.5"/>
                    </svg>
                    <span>Darbvirsma</span>
                </span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports*')">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v4.5H3.75Zm0 9h7.5v4.5h-7.5Zm10.5 0h6v4.5h-6Z"/>
                    </svg>
                    <span>Skati</span>
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
            @if (auth()->user()?->role === 'admin')
                <x-responsive-nav-link :href="route('backups.index')" :active="request()->routeIs('backups*')">
                    <span class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M6 3.75h12a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5"/>
                        </svg>
                        <span>Kopijas</span>
                    </span>
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('audit-log.index')" :active="request()->routeIs('audit-log*')">
                    <span class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6m-6-4.5h6M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v12A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 20.25v-3a2.25 2.25 0 0 0-2.25-2.25h-3A2.25 2.25 0 0 0 8.25 17.25v3"/>
                        </svg>
                        <span>Audits</span>
                    </span>
                </x-responsive-nav-link>
            @endif
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
                <x-responsive-nav-link :href="route('profile.edit')">
                    <span class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                        </svg>
                        <span>Profils</span>
                    </span>
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600">
                        <span class="inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 15 12m0 0-3 2.25M15 12H3"/>
                            </svg>
                            <span>Izrakstities</span>
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
