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
        $filters = $filters ?? ['q' => '', 'code' => '', 'room' => '', 'status' => '', 'type' => ''];
        $baseFilters = array_filter($filters, fn ($value) => $value !== '');
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ selectedCount: 0 }">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Ierices</h1>
                <p class="mt-1 text-sm text-slate-500">Parskatama inventara tabula ar atriem filtriem un precizu meklejumu.</p>
            </div>
            <a href="{{ route('devices.create') }}" class="crud-btn-primary-inline">Pievienot ierici</a>
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
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Meklet
                        </button>
                        <a href="{{ route('devices.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                            Notirit
                        </a>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="min-w-24 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Statusi</span>
                        <a
                            href="{{ route('devices.index', array_merge($baseFilters, ['status' => null])) }}"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ $filters['status'] === '' ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' }}"
                        >
                            Visi statusi
                            <span class="rounded-full bg-black/10 px-2 py-0.5 text-xs {{ $filters['status'] === '' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $types->sum('devices_count') }}</span>
                        </a>
                        @foreach ($statusOptions as $option)
                            <a
                                href="{{ route('devices.index', array_merge($baseFilters, ['status' => $option['value']])) }}"
                                class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ $filters['status'] === $option['value'] ? 'bg-sky-600 text-white ring-sky-600' : 'bg-white text-slate-700 ring-slate-300 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-800' }}"
                            >
                                {{ $option['label'] }}
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $filters['status'] === $option['value'] ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $option['count'] }}</span>
                            </a>
                        @endforeach
                    </div>

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
                        <p class="text-sm text-slate-500">Filtri sinhronizejas ar statusiem un tipu tabulu.</p>
                    </div>
                    <form id="device-bulk-form" method="POST" action="{{ route('devices.bulk-update') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Darbiba ar atzimetajam</label>
                            <select name="action" class="crud-control min-w-[180px]">
                                <option value="">Izvelies darbibu</option>
                                <option value="status">Mainit statusu</option>
                                <option value="room">Parvietot uz telpu</option>
                                <option value="set">Pievienot komplektacijai</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jaunais statuss</label>
                            <select name="target_status" class="crud-control min-w-[170px]">
                                <option value="">Neizvelets</option>
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Telpa</label>
                            <select name="target_room_id" class="crud-control min-w-[180px]">
                                <option value="">Neizveleta</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Komplektacija</label>
                            <select name="target_set_id" class="crud-control min-w-[180px]">
                                <option value="">Neizveleta</option>
                                @foreach ($deviceSets as $deviceSet)
                                    <option value="{{ $deviceSet->id }}">{{ $deviceSet->set_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Pielietot
                        </button>
                        <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600" x-text="`Atzimetas: ${selectedCount}`"></span>
                    </form>
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
                            <th class="px-4 py-4 text-left">
                                <input type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                    @change="document.querySelectorAll('.device-bulk-checkbox').forEach(el => { el.checked = $event.target.checked; }); selectedCount = $event.target.checked ? document.querySelectorAll('.device-bulk-checkbox').length : 0;">
                            </th>
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
                                <td class="px-4 py-4">
                                    <input type="checkbox" form="device-bulk-form" name="device_ids[]" value="{{ $device->id }}" class="device-bulk-checkbox rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                        @change="selectedCount = document.querySelectorAll('.device-bulk-checkbox:checked').length">
                                </td>
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
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <a href="{{ route('devices.show', $device) }}" class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-medium text-emerald-700 transition hover:bg-emerald-100">Apskatit</a>
                                        <a href="{{ route('devices.edit', $device) }}" class="inline-flex items-center rounded-2xl border border-sky-200 bg-sky-50 px-3 py-1.5 font-medium text-sky-700 transition hover:bg-sky-100">Rediget</a>
                                        <form method="POST" action="{{ route('devices.destroy', $device) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzest so ierici?')" class="inline-flex items-center rounded-2xl border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-700 transition hover:bg-rose-100">Dzest</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-md">
                                        <div class="mb-3 text-lg font-semibold text-slate-900">Neviena ierice neatbilst atlasitajiem filtriem</div>
                                        <p class="text-sm text-slate-500">Pamegini notirit filtrus vai pamainit kodu, telpu un statusu kombinaciju.</p>
                                        <div class="mt-5">
                                            <a href="{{ route('devices.index') }}" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
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
