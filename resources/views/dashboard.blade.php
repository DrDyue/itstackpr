{{--
    Lapa: Admina darbvirsma.
    Atbildība: parāda administratoram vienkopus galvenos darba rīkus, telpu koku un kompaktu ierīču tabulu.
    Datu avots: DashboardController@index.
    Galvenās daļas:
    1. Hero zona ar ātrajām darbībām.
    2. Kreisā kolonna ar stāvu un telpu filtru koku.
    3. Labā kolonna ar ierīču tabulu un statusu priekšskatījumiem.
--}}
<x-app-layout>
    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="dashboard" size="h-4 w-4" />
                            <span>Galvenais darba skats</span>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">
                            <x-icon name="device" size="h-3.5 w-3.5" />
                            <span>Ierīces: {{ $dashboardDeviceCount }}</span>
                        </span>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="dashboard" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Darbvirsma</h1>
                            <p class="page-subtitle">Vienuviet redzi telpu struktūru, ātri filtrē ierīces un pārvaldi galvenās darbības.</p>
                        </div>
                    </div>
                </div>

                <div class="page-actions">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="{{ $action['class'] }}">
                            <x-icon :name="$action['icon']" size="h-4 w-4" />
                            <span>{{ $action['label'] }}</span>
                            @if ($action['count'] !== null)
                                <span class="dashboard-action-badge">{{ $action['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dash-workspace-grid" x-data="dashboardFilter">
            @php
                $activeFloor = $locationTree->firstWhere('id', $filters['floor']);
                $activeRoom = $activeFloor
                    ? collect($activeFloor['rooms'] ?? [])->firstWhere('id', (int) $filters['room_id'])
                    : null;
                $activeFilterLabel = $activeRoom
                    ? trim(implode(' ', array_filter([
                        'Telpa:',
                        $activeRoom['room_number'] ?? null,
                        $activeRoom['room_name'] ?? null,
                    ])))
                    : ($activeFloor ? 'Stāvs: ' . ($activeFloor['label'] ?? $filters['floor']) : null);
            @endphp
            <aside class="dash-location-panel">
                <div class="dash-location-panel-head">
                    <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <x-icon name="room" size="h-5 w-5" class="text-emerald-600" />
                        <span>Stāvi un telpas</span>
                    </div>

                    <button type="button" @click="clearFilters()" class="dash-location-clear-btn" :disabled="!currentFilters.floor && !currentFilters.room_id">
                        <x-icon name="x-circle" size="h-4 w-4" />
                        <span>Atcelt filtrus</span>
                    </button>
                </div>

                @if ($activeFilterLabel)
                    <div class="dash-location-active-filter">
                        <x-icon name="filter" size="h-4 w-4" />
                        <span>{{ $activeFilterLabel }}</span>
                    </div>
                @endif

                <div class="dash-room-tree">
                    @forelse ($locationTree as $floor)
                        <details class="dash-floor-card" @if ($loop->first) open @endif>
                            <summary class="dash-floor-summary">
                                <div>
                                    <div class="dash-floor-title">{{ $floor['label'] }}</div>
                                    <div class="dash-floor-sub">Telpas {{ $floor['room_count'] }}</div>
                                </div>
                                <span class="dash-floor-badge">{{ $floor['device_count'] }}</span>
                            </summary>

                            <div class="px-3 pb-3">
                                <button
                                    type="button"
                                    @click="filterByFloor('{{ $floor['id'] }}')"
                                    data-floor="{{ $floor['id'] }}"
                                    class="dash-floor-filter {{ $floor['is_active'] ? 'dash-floor-filter-active' : '' }}"
                                >
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Filtrēt šī stāva ierīces</span>
                                </button>

                                <div class="dash-room-list">
                                    @foreach ($floor['rooms'] as $room)
                                        <button
                                            type="button"
                                            @click="filterByRoom('{{ $room['id'] }}', '{{ $floor['id'] }}')"
                                            data-room="{{ $room['id'] }}"
                                            data-floor="{{ $floor['id'] }}"
                                            class="dash-room-link {{ $room['is_active'] ? 'dash-room-link-active' : '' }}"
                                        >
                                            <div class="dash-room-name">
                                                <span>{{ $room['room_number'] }}</span>
                                                @if ($room['room_name'])
                                                    <span class="text-slate-500">- {{ $room['room_name'] }}</span>
                                                @endif
                                            </div>
                                            <div class="dash-room-meta">
                                                <span>{{ $room['building_name'] ?: 'Bez ēkas' }}</span>
                                                <span>{{ $room['device_count'] }} ierīces</span>
                                                @if ($room['department'])
                                                    <span>{{ $room['department'] }}</span>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="dash-empty-block">Pieejamu telpu pašlaik nav.</div>
                    @endforelse
                </div>
            </aside>

            <div class="dash-main-stack">
                <section class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <x-icon name="device" size="h-5 w-5" class="text-sky-600" />
                                <span>Ierīces</span>
                            </h2>
                            <p class="mt-2 text-sm text-slate-600">
                                Pārskats par visām ierīcēm, sakārtots pēc jaunākajiem ierakstiem.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($filters['floor'] !== '' || $filters['room_id'] !== '')
                                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">
                                    <x-icon name="room" size="h-3.5 w-3.5" />
                                    <span>{{ $filters['room_id'] !== '' ? 'Telpas filtrs ieslēgts' : 'Stāva filtrs ieslēgts' }}</span>
                                </div>
                                <button type="button" @click="clearFilters()" class="btn-clear">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>Notīrīt filtru</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    @include('dashboard.devices-table', [
                        'dashboardDevices' => $dashboardDevices,
                        'dashboardDeviceCount' => $dashboardDeviceCount,
                        'dashboardDeviceStates' => $dashboardDeviceStates,
                        'filters' => $filters,
                    ])
                </section>
            </div>
        </div>
    </section>

    <script>
        const dashboardFilter = {
            isLoading: false,
            currentFilters: {
                floor: @js($filters['floor']),
                room_id: @js($filters['room_id']),
            },
            async fetchDevices(params = {}) {
                this.isLoading = true;
                const filters = { floor: '', room_id: '', ...this.currentFilters, ...params };

                try {
                    const queryString = new URLSearchParams(filters).toString();
                    const url = '{{ route("dashboard.devices") }}' + (queryString ? '?' + queryString : '');
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.querySelector('#dashboard-devices-table');

                    if (newTable) {
                        document.querySelector('#dashboard-devices-table').outerHTML = newTable.outerHTML;
                    }
                } catch (error) {
                    console.error('Failed to fetch devices:', error);
                } finally {
                    this.isLoading = false;
                }
            },
            filterByFloor(floorId) {
                this.currentFilters = { floor: floorId, room_id: '' };
                this.updateActiveState(floorId, null);
                this.fetchDevices();
            },
            filterByRoom(roomId, floorId) {
                this.currentFilters = { floor: floorId, room_id: roomId };
                this.updateActiveState(floorId, roomId);
                this.fetchDevices();
            },
            clearFilters() {
                this.currentFilters = { floor: '', room_id: '' };
                this.updateActiveState(null, null);
                this.fetchDevices();
            },
            updateActiveState(floorId, roomId) {
                document.querySelectorAll('.dash-floor-filter').forEach(el => {
                    el.classList.remove('dash-floor-filter-active');
                });
                document.querySelectorAll('.dash-room-link').forEach(el => {
                    el.classList.remove('dash-room-link-active');
                });

                if (roomId) {
                    const roomButton = document.querySelector(`.dash-room-link[data-room="${roomId}"]`);
                    if (roomButton) roomButton.classList.add('dash-room-link-active');
                } else if (floorId) {
                    const floorButton = document.querySelector(`.dash-floor-filter[data-floor="${floorId}"]`);
                    if (floorButton) floorButton.classList.add('dash-floor-filter-active');
                }
            }
        };
    </script>
</x-app-layout>
