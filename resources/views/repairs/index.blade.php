<x-app-layout>
    @php
        $columnMeta = [
            'waiting' => [
                'title' => 'Gaida',
                'subtitle' => 'Pieteiktas ierices, kas vel nav panemtas darba.',
                'accent' => 'bg-amber-500',
                'surface' => 'border-amber-200 bg-amber-50',
                'dot' => 'bg-amber-500',
            ],
            'in-progress' => [
                'title' => 'Procesa',
                'subtitle' => 'Aktivie remonti ar izpildi, terminu un izmaksu kontroli.',
                'accent' => 'bg-sky-500',
                'surface' => 'border-sky-200 bg-sky-50',
                'dot' => 'bg-sky-500',
            ],
            'completed' => [
                'title' => 'Pabeigts',
                'subtitle' => 'Noslegti remonti ar saglabatu vesturi un rezultatu.',
                'accent' => 'bg-emerald-500',
                'surface' => 'border-emerald-200 bg-emerald-50',
                'dot' => 'bg-emerald-500',
            ],
        ];

        $buildingNames = $buildings->pluck('building_name', 'id');
        $activeFilterCount = collect($filters)->filter(function ($value, $key) {
            if ($key === 'ownership' && auth()->user()?->role !== 'admin' && $value === 'all-mine') {
                return false;
            }

            return $value !== null && $value !== '';
        })->count();
        $quickTypeFilterQuery = collect($filters)
            ->except('repair_type')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
        $quickOwnershipFilterQuery = collect($filters)
            ->except('ownership')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    @endphp

    <section
        class="repair-shell space-y-6"
        x-data="repairBoard({
            transitionBaseUrl: @js(url('/repairs')),
            csrfToken: @js(csrf_token()),
        })"
    >
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="device-page-title">Remontu parvaldiba</h1>
                <p class="device-page-subtitle">Parskats par iericem, kas gaida remontu, ir procesa vai jau ir pabeigtas.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h.008v.008H8.25V6.75Zm0 5.25h.008v.008H8.25V12Zm0 5.25h.008v.008H8.25v-.008Zm7.5-10.5h.008v.008h-.008V6.75Zm0 5.25h.008v.008h-.008V12Zm0 5.25h.008v.008h-.008v-.008Z"/>
                    </svg>
                </span>
                <a href="{{ route('repairs.create') }}" class="crud-btn-primary-inline inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Pievienot remontu
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-700">Gaida</p>
                        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['waiting'] }}</div>
                        <p class="mt-2 text-sm text-slate-600">Jauni vai vel nepanemti remonta pieteikumi.</p>
                    </div>
                    <div class="rounded-2xl bg-amber-100 p-3 text-amber-700 ring-1 ring-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v4.5l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Procesa</p>
                        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['in_progress'] }}</div>
                        <p class="mt-2 text-sm text-slate-600">Aktivas darbibas ar atbildigajiem un terminu uzraudzibu.</p>
                    </div>
                    <div class="rounded-2xl bg-sky-100 p-3 text-sky-700 ring-1 ring-sky-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-7.5-7.5L20.25 12l-7.5 7.5"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Pabeigts</p>
                        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['completed'] }}</div>
                        <p class="mt-2 text-sm text-slate-600">Noslegti remonti ar saglabatu rezutatu un izmaksam.</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-700 ring-1 ring-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-700">Kopsavilkums</p>
                        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format((float) $stats['active_cost'], 2) }} EUR</div>
                        <p class="mt-2 text-sm text-slate-600">
                            Aktivie remonti:
                            <strong class="text-slate-900">{{ $stats['waiting'] + $stats['in_progress'] }}</strong>
                            @if ($stats['average_days'] !== null)
                                | videji {{ number_format((float) $stats['average_days'], 1) }} dienas
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl bg-violet-100 p-3 text-violet-700 ring-1 ring-violet-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-5">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Atrie filtri</span>

                <a
                    href="{{ route('repairs.index', $quickTypeFilterQuery) }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ $filters['repair_type'] === '' ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' }}"
                >
                    Visi remonti
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filters['repair_type'] === '' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $quickTypeCounts['all'] ?? $repairs->count() }}</span>
                </a>

                <a
                    href="{{ route('repairs.index', array_merge($quickTypeFilterQuery, ['repair_type' => 'internal'])) }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ $filters['repair_type'] === 'internal' ? 'bg-violet-600 text-white ring-violet-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-violet-50 hover:text-violet-800 hover:ring-violet-200' }}"
                >
                    Ieksejais
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filters['repair_type'] === 'internal' ? 'bg-white/20 text-white' : 'bg-violet-100 text-violet-700' }}">{{ $quickTypeCounts['internal'] ?? 0 }}</span>
                </a>

                <a
                    href="{{ route('repairs.index', array_merge($quickTypeFilterQuery, ['repair_type' => 'external'])) }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ $filters['repair_type'] === 'external' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-rose-50 hover:text-rose-800 hover:ring-rose-200' }}"
                >
                    Arejais
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $filters['repair_type'] === 'external' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700' }}">{{ $quickTypeCounts['external'] ?? 0 }}</span>
                </a>
            </div>

            @if (auth()->user()?->role !== 'admin')
                <div class="mb-5 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-5">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Mani remonti</span>

                    <a
                        href="{{ route('repairs.index', array_merge($quickOwnershipFilterQuery, ['ownership' => 'all-mine'])) }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ ($filters['ownership'] ?? 'all-mine') === 'all-mine' ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' }}"
                    >
                        Visi mani
                    </a>

                    <a
                        href="{{ route('repairs.index', array_merge($quickOwnershipFilterQuery, ['ownership' => 'reported-by-me'])) }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ ($filters['ownership'] ?? '') === 'reported-by-me' ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-emerald-50 hover:text-emerald-800 hover:ring-emerald-200' }}"
                    >
                        Manis pieteiktie
                    </a>

                    <a
                        href="{{ route('repairs.index', array_merge($quickOwnershipFilterQuery, ['ownership' => 'assigned-to-me'])) }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ring-1 transition {{ ($filters['ownership'] ?? '') === 'assigned-to-me' ? 'bg-sky-600 text-white ring-sky-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-sky-50 hover:text-sky-800 hover:ring-sky-200' }}"
                    >
                        Man pieskirtie
                    </a>
                </div>
            @endif

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_repeat(4,minmax(0,0.8fr))]">
                <label class="block">
                    <span class="repair-filter-label">Meklesana</span>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                        </span>
                        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ierice, kods, apraksts, telpa, piegadatajs..." class="crud-control pl-11">
                    </div>
                </label>

                <label class="block">
                    <span class="repair-filter-label">Statuss</span>
                    <select name="status" class="crud-control">
                        <option value="">Visi statusi</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="repair-filter-label">Remonta tips</span>
                    <select name="repair_type" class="crud-control">
                        <option value="">Visi tipi</option>
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected($filters['repair_type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="repair-filter-label">Eka</span>
                    <select name="building_id" class="crud-control">
                        <option value="">Visas ekas</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected($filters['building_id'] === (string) $building->id)>{{ $building->building_name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="repair-filter-label">Prioritate</span>
                    <select name="priority" class="crud-control">
                        <option value="">Visas prioritates</option>
                        @foreach ($priorityLabels as $value => $label)
                            <option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-700">
                        <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                        Atrasti ieraksti: {{ $repairs->count() }}
                    </span>

                    @if ($activeFilterCount > 0)
                        <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 font-medium text-amber-800 ring-1 ring-amber-200">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Aktivie filtri: {{ $activeFilterCount }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        Filtret
                    </button>
                    <a href="{{ route('repairs.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                        Notirit
                    </a>
                </div>
            </div>
        </form>

        <div class="grid gap-5 xl:grid-cols-3">
            @foreach ($columns as $status => $items)
                <div
                    class="repair-column-drop rounded-[2rem] border {{ $columnMeta[$status]['surface'] }} p-4 shadow-sm"
                    @dragover.prevent="onDragOver('{{ $status }}')"
                    @dragleave="clearDropTarget('{{ $status }}')"
                    @drop.prevent="handleDrop('{{ $status }}')"
                    :class="dropTargetStatus === '{{ $status }}' ? 'repair-column-drop-active' : ''"
                >
                    <div class="mb-4 border-b border-white/70 pb-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="h-3 w-3 rounded-full {{ $columnMeta[$status]['dot'] }}"></span>
                                <h2 class="text-lg font-semibold text-slate-900">{{ $columnMeta[$status]['title'] }}</h2>
                                <span class="inline-flex min-w-10 justify-center rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $items->count() }}</span>
                            </div>
                            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-600">{{ $columnMeta[$status]['subtitle'] }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @forelse ($items as $repair)
                            @php
                                $device = $repair->device;
                                $room = $device?->room;
                                $building = $device?->building;
                            @endphp

                            <article
                                class="repair-card-draggable rounded-[1.6rem] border border-white/80 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                draggable="true"
                                @dragstart="startDrag({ id: {{ $repair->id }}, status: @js($repair->status), name: @js($device?->name ?: 'Nezinama ierice') }, $event)"
                                @dragend="clearDrag()"
                                :class="draggedRepair && draggedRepair.id === {{ $repair->id }} ? 'repair-card-dragging' : ''"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h.008v.008H8.25V6.75Zm0 5.25h.008v.008H8.25V12Zm0 5.25h.008v.008H8.25v-.008Zm7.5-10.5h.008v.008h-.008V6.75Zm0 5.25h.008v.008h-.008V12Zm0 5.25h.008v.008h-.008v-.008Z"/>
                                                </svg>
                                            </span>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600">
                                                {{ $device?->code ?: 'Ierice' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $typeClasses[$repair->repair_type] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                                @include('repairs.partials.icon', ['name' => $typeIcons[$repair->repair_type] ?? 'wrench', 'class' => 'h-3.5 w-3.5'])
                                                {{ $typeLabels[$repair->repair_type] ?? $repair->repair_type }}
                                            </span>
                                        </div>
                                        <h3 class="mt-3 text-base font-semibold text-slate-900">{{ $device?->name ?: 'Nezinama ierice' }}</h3>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($repair->description, 150) }}</p>
                                    </div>

                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $priorityClasses[$repair->priority] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        @include('repairs.partials.icon', ['name' => $priorityIcons[$repair->priority] ?? 'bars', 'class' => 'h-3.5 w-3.5'])
                                        {{ $priorityLabels[$repair->priority] ?? 'Videja' }}
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-slate-50 px-3 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Atrasanas vieta</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ $building?->building_name ?: 'Eka nav noradita' }}</p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            @if ($room)
                                                {{ $room->floor_number }}. stavs | telpa {{ $room->room_number }}
                                                @if ($room->room_name)
                                                    | {{ $room->room_name }}
                                                @endif
                                            @else
                                                Telpa nav noradita
                                            @endif
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 px-3 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Atbildiba</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ $repair->assignee?->employee?->full_name ?? 'Nav pieskirta' }}</p>
                                        <p class="mt-1 text-sm text-slate-600">Pieteica: {{ $repair->reporter?->full_name ?? $repair->legacyReporter?->employee?->full_name ?? 'Nav zinotaja' }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 {{ $status === 'waiting' ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }}">
                                    <div class="rounded-2xl border border-slate-200 px-3 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Sakts</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ $repair->start_date?->format('d.m.Y') ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 px-3 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                            {{ $status === 'completed' ? 'Pabeigts' : 'Planotais' }}
                                        </p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">
                                            {{ ($status === 'completed' ? $repair->actual_completion : $repair->estimated_completion)?->format('d.m.Y') ?? '-' }}
                                        </p>
                                    </div>
                                    @if ($status !== 'waiting')
                                        <div class="rounded-2xl border border-slate-200 px-3 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Izmaksas</p>
                                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $repair->cost !== null ? number_format((float) $repair->cost, 2) . ' EUR' : '-' }}</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $statusClasses[$repair->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        @include('repairs.partials.icon', ['name' => $statusIcons[$repair->status] ?? 'clock', 'class' => 'h-3.5 w-3.5'])
                                        {{ $statusLabels[$repair->status] ?? $repair->status }}
                                    </span>

                                    @if ($repair->repair_type === 'external' && $repair->vendor_name)
                                        <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">
                                            @include('repairs.partials.icon', ['name' => 'truck', 'class' => 'h-3.5 w-3.5'])
                                            {{ $repair->vendor_name }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <a href="{{ route('repairs.edit', $repair) }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                                        </svg>
                                        Skatit procesu
                                    </a>

                                    @if ($repair->status === 'waiting')
                                        <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                            @csrf
                                            <input type="hidden" name="target_status" value="in-progress">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-7.5-7.5L20.25 12l-7.5 7.5"/>
                                                </svg>
                                                Panemt procesa
                                            </button>
                                        </form>
                                    @elseif ($repair->status === 'in-progress')
                                        <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                            @csrf
                                            <input type="hidden" name="target_status" value="waiting">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                                                </svg>
                                                Atpakal uz gaida
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            @click="submitCompletion({ id: {{ $repair->id }}, name: @js($device?->name ?: 'Nezinama ierice') })"
                                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                        >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                </svg>
                                                Pabeigt
                                        </button>
                                    @elseif ($repair->status === 'completed')
                                        <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                            @csrf
                                            <input type="hidden" name="target_status" value="in-progress">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5 19.5 4.5M9 4.5h10.5V15"/>
                                                </svg>
                                                Atvert atkal
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.6rem] border border-dashed border-slate-200 bg-white/80 px-5 py-10 text-center shadow-sm">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm font-medium text-slate-700">Saja kolonna pagaidam nav ierakstu.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        @if ($cancelledRepairs->isNotEmpty())
            <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Atceltie remonti</h2>
                        <p class="mt-1 text-sm text-slate-600">Saglabati atseviski, lai netrauce galvenajai plusmai.</p>
                    </div>
                    <span class="inline-flex min-w-10 justify-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $cancelledRepairs->count() }}</span>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    @foreach ($cancelledRepairs as $repair)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $repair->device?->code }} | {{ $repair->device?->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($repair->description, 120) }}</p>
                                </div>
                                <a href="{{ route('repairs.edit', $repair) }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                                    </svg>
                                    Skatit
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </section>
</x-app-layout>
