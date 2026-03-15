<x-app-layout>
    @php
        $statusClasses = [
            'active' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'reserve' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'broken' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'repair' => 'bg-sky-100 text-sky-800 ring-sky-200',
            'retired' => 'bg-slate-200 text-slate-700 ring-slate-300',
            'kitting' => 'bg-violet-100 text-violet-800 ring-violet-200',
        ];
        $statusLabels = [
            'active' => 'Aktiva',
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'retired' => 'Norakstita',
            'kitting' => 'Komplektacija',
        ];
        $filters = $filters ?? ['q' => '', 'code' => '', 'room' => '', 'type' => ''];
        $baseFilters = array_filter($filters, fn ($value) => $value !== '');
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Ierices</h1>
                <p class="mt-1 text-sm text-slate-500">Parskatama inventara tabula ar atriem filtriem un precizu meklejumu.</p>
            </div>
            <a href="{{ route('devices.create') }}" class="crud-btn-primary-inline inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Pievienot ierici
            </a>
        </div>

        <div class="mb-5 overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Filtri</p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-900">Atrodi vajadzigo ierici bez liekiem klikskjiem</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200">
                            Atrastas: {{ $devices->total() }}
                        </span>
                        <span class="rounded-full bg-sky-100 px-3 py-1 font-medium text-sky-800 ring-1 ring-sky-200">
                            Aktivie filtri: {{ $activeFilterCount }}
                        </span>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('devices.index') }}" class="space-y-5 px-5 py-5 sm:px-6">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(220px,0.8fr)_minmax(220px,0.8fr)_auto]">
                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Briva meklesana</span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Nosaukums, razotajs, modelis vai serijas numurs"
                            class="w-full rounded-2xl border-slate-300 bg-white/90 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Meklet pec koda</span>
                        <input
                            type="text"
                            name="code"
                            value="{{ $filters['code'] }}"
                            placeholder="Piem. LDZ-0008"
                            class="w-full rounded-2xl border-slate-300 bg-white/90 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Meklet pec telpas</span>
                        <input
                            type="text"
                            name="room"
                            value="{{ $filters['room'] }}"
                            placeholder="Telpas numurs vai nosaukums"
                            class="w-full rounded-2xl border-slate-300 bg-white/90 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                    </label>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                            Meklet
                        </button>
                        <a href="{{ route('devices.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Notirit
                        </a>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="min-w-24 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tipi</span>
                        <a
                            href="{{ route('devices.index', array_merge($baseFilters, ['type' => null])) }}"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ $filters['type'] === '' ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' }}"
                        >
                            Visi tipi
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $filters['type'] === '' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $types->sum('devices_count') }}</span>
                        </a>
                        @foreach ($types as $type)
                            <a
                                href="{{ route('devices.index', array_merge($baseFilters, ['type' => $type->id])) }}"
                                class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ (string) $filters['type'] === (string) $type->id ? 'bg-amber-500 text-white ring-amber-500' : 'bg-white text-slate-700 ring-slate-300 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-800' }}"
                            >
                                {{ $type->type_name }}
                                <span class="rounded-full px-2 py-0.5 text-xs {{ (string) $filters['type'] === (string) $type->id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $type->devices_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Iericu saraksts</h3>
                        <p class="text-sm text-slate-500">Filtri sinhronizejas ar tipu tabulu.</p>
                    </div>
                </div>
                @if ($activeFilterCount > 0)
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium text-slate-600">
                        @foreach ($filters as $key => $value)
                            @continue($value === '')
                            <span class="rounded-full bg-slate-100 px-3 py-1">
                                {{ strtoupper($key) }}: {{ $value }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-700">
                    <thead class="bg-slate-50 text-xs uppercase tracking-[0.18em] text-slate-500">
                        <tr>
                            <th class="px-4 py-4 text-left">ID</th>
                            <th class="px-4 py-4 text-left">Kods</th>
                            <th class="px-4 py-4 text-left">Nosaukums</th>
                            <th class="px-4 py-4 text-left">Tips</th>
                            <th class="px-4 py-4 text-left">Statuss</th>
                            <th class="px-4 py-4 text-left">Modelis</th>
                            <th class="px-4 py-4 text-left">Telpa</th>
                            <th class="px-4 py-4 text-left">Serijas Nr.</th>
                            <th class="px-4 py-4 text-left">Izveidots</th>
                            <th class="px-4 py-4 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($devices as $device)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-4 font-medium text-slate-500">#{{ $device->id }}</td>
                                <td class="px-4 py-4">
                                    <span class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($device->deviceImageThumbUrl())
                                            <img src="{{ $device->deviceImageThumbUrl() }}" alt="Ierices thumbnail" class="h-11 w-11 rounded-2xl object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-xs font-semibold text-slate-400 ring-1 ring-slate-200">Nav</div>
                                        @endif
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $device->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $device->manufacturer ?: 'Nav razotaja' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">{{ $device->type?->type_name ?: '-' }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$device->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        {{ $statusLabels[$device->status] ?? $device->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">{{ $device->model ?: '-' }}</td>
                                <td class="px-4 py-4">
                                    <div>{{ $device->room?->room_number ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $device->room?->room_name ?: ($device->building?->building_name ?: '') }}</div>
                                </td>
                                <td class="px-4 py-4">{{ $device->serial_number ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-4">
                                    <div x-data="{ open: false }" class="relative">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Darbibas
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            x-transition
                                            @click.outside="open = false"
                                            class="absolute right-0 z-20 mt-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-lg"
                                        >
                                            <a href="{{ route('devices.show', $device) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                                                </svg>
                                                Apskatit
                                            </a>
                                            <a href="{{ route('devices.edit', $device) }}" class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-sky-700 transition hover:bg-sky-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5"/>
                                                </svg>
                                                Rediget
                                            </a>

                                            @if ($device->activeRepair)
                                                <a href="{{ route('repairs.edit', $device->activeRepair) }}" class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 3.17a1 1 0 0 1 1.16 0l8 5.5a1 1 0 0 1 .42.82v5.02a1 1 0 0 1-.42.82l-8 5.5a1 1 0 0 1-1.16 0l-8-5.5a1 1 0 0 1-.42-.82V9.5a1 1 0 0 1 .42-.82l8-5.5Z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>
                                                    </svg>
                                                    Skatit remontu
                                                </a>
                                            @elseif (! in_array($device->status, ['repair', 'retired'], true))
                                                <a href="{{ route('repairs.create', ['device_id' => $device->id]) }}" class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-violet-700 transition hover:bg-violet-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9m-9 6h9m-9 6h9M4.5 6h.75v.75H4.5V6Zm0 6h.75v.75H4.5V12Zm0 6h.75v.75H4.5V18Z"/>
                                                    </svg>
                                                    Atdot remonta
                                                </a>
                                            @endif

                                            <form method="POST" action="{{ route('devices.destroy', $device) }}" class="mt-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Dzest so ierici?')" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-medium text-rose-700 transition hover:bg-rose-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                    </svg>
                                                    Dzest
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-md">
                                        <div class="mb-3 text-lg font-semibold text-slate-900">Neviena ierice neatbilst atlasitajiem filtriem</div>
                                        <p class="text-sm text-slate-500">Pamegini notirit filtrus vai pamainit meklejuma nosacijumus.</p>
                                        <div class="mt-5">
                                            <a href="{{ route('devices.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                </svg>
                                                Atiestatit filtrus
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($devices->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $devices->links() }}
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
