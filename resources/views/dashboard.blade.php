<x-app-layout>
    @php
        $selectedRoomId = $selectedRoom?->id;
        $selectedScopeLabel = $selectedRoom
            ? (($selectedRoom->building?->building_name ?: '-') . ' / ' . $selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : ''))
            : ($selectedFloor !== '' ? ($selectedFloor . '. stavs') : 'Visas pieejamas ierices');
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="dashboard" size="h-4 w-4" />
                        <span>Galvenais darba skats</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="dashboard" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Darbvirsma</h1>
                            <p class="page-subtitle">
                                Kreisaja puse izvelies stavu un telpu, bet labaja puse uzreiz redzi attiecigas ierices, aktivus remontus un jaunakas darbibas.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="{{ route('repair-requests.create') }}" class="btn-create">
                        <x-icon name="repair-request" size="h-4 w-4" />
                        <span>Pieteikt remontu</span>
                    </a>
                    <a href="{{ route('writeoff-requests.create') }}" class="btn-danger">
                        <x-icon name="writeoff" size="h-4 w-4" />
                        <span>Pieteikt norakstisanu</span>
                    </a>
                    <a href="{{ route('device-transfers.create') }}" class="btn-view">
                        <x-icon name="transfer" size="h-4 w-4" />
                        <span>Pieteikt parsutisanu</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="dash-workspace-grid">
            <aside class="dash-location-panel">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <x-icon name="room" size="h-5 w-5" class="text-emerald-600" />
                            <span>Stavi un telpas</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Atver stavu un izvelies telpu, lai filtrs uzreiz paradas iericu saraksta.
                        </p>
                    </div>
                    <a href="{{ route('dashboard') }}" class="btn-clear">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Visas</span>
                    </a>
                </div>

                <div class="mt-4 rounded-[1.35rem] border border-sky-200 bg-sky-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">Aktivais filtrs</div>
                    <div class="mt-2 text-sm font-medium text-sky-900">{{ $selectedScopeLabel }}</div>
                </div>

                <div class="dash-room-tree">
                    @forelse ($locationTree as $floor)
                        <details class="dash-floor-card" @if ($selectedFloor === (string) $floor['id']) open @endif>
                            <summary class="dash-floor-summary">
                                <div>
                                    <div class="dash-floor-title">{{ $floor['label'] }}</div>
                                    <div class="dash-floor-sub">Telpas {{ $floor['room_count'] }}, ierices {{ $floor['device_count'] }}</div>
                                </div>
                                <span class="dash-floor-badge">{{ $floor['device_count'] }}</span>
                            </summary>

                            <div class="px-3 pb-3">
                                <a href="{{ route('dashboard', ['floor' => $floor['id']]) }}" class="dash-floor-filter {{ $selectedFloor === (string) $floor['id'] && ! $selectedRoom ? 'dash-floor-filter-active' : '' }}">
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Skatit visa stava ierices</span>
                                </a>

                                <div class="dash-room-list">
                                    @foreach ($floor['rooms'] as $room)
                                        <a
                                            href="{{ route('dashboard', ['floor' => $floor['id'], 'room' => $room['id']]) }}"
                                            class="dash-room-link {{ (int) $selectedRoomId === (int) $room['id'] ? 'dash-room-link-active' : '' }}"
                                        >
                                            <div class="dash-room-name">
                                                <span>{{ $room['room_number'] }}</span>
                                                @if ($room['room_name'])
                                                    <span class="text-slate-500">- {{ $room['room_name'] }}</span>
                                                @endif
                                            </div>
                                            <div class="dash-room-meta">
                                                <span>{{ $room['building_name'] ?: 'Bez ekas' }}</span>
                                                <span>{{ $room['device_count'] }} ierices</span>
                                                @if ($room['department'])
                                                    <span>{{ $room['department'] }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="dash-empty-block">Pieejamu telpu paslaik nav.</div>
                    @endforelse
                </div>
            </aside>

            <div class="dash-main-stack">
                <section class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <x-icon name="device" size="h-5 w-5" class="text-sky-600" />
                                <span>Ierices</span>
                            </h2>
                            <p class="mt-2 text-sm text-slate-600">
                                {{ $selectedRoom ? 'Filtrets pec izveletas telpas.' : ($selectedFloor !== '' ? 'Filtrets pec izveleta stava.' : 'Redzams pilns pieejamais iericu saraksts.') }}
                            </p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            Atrastas ierices: <strong class="text-slate-900">{{ $dashboardDevices->count() }}</strong>
                        </div>
                    </div>

                    <div class="dash-device-list">
                        @forelse ($dashboardDevices as $device)
                            <a href="{{ route('devices.show', $device) }}" class="dash-device-card">
                                <div class="dash-device-head">
                                    <div>
                                        <div class="dash-device-title">{{ $device->name }}</div>
                                        <div class="dash-device-subtitle">
                                            {{ $device->code ?: 'Bez koda' }} | {{ $device->type?->type_name ?: 'Bez tipa' }} | {{ $device->model }}
                                        </div>
                                    </div>
                                    <x-status-pill context="device" :value="$device->status" />
                                </div>

                                <div class="dash-device-grid">
                                    <div class="dash-device-metric">
                                        <div class="dash-device-label">Atrasanas vieta</div>
                                        <div class="dash-device-value">{{ $device->building?->building_name ?: '-' }} / {{ $device->room?->room_number ?: '-' }}</div>
                                    </div>
                                    <div class="dash-device-metric">
                                        <div class="dash-device-label">Pieskirta</div>
                                        <div class="dash-device-value">{{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                                    </div>
                                    <div class="dash-device-metric">
                                        <div class="dash-device-label">Serijas numurs</div>
                                        <div class="dash-device-value">{{ $device->serial_number ?: '-' }}</div>
                                    </div>
                                    <div class="dash-device-metric">
                                        <div class="dash-device-label">Stavoklis</div>
                                        <div class="dash-device-value">
                                            @if ($device->activeRepair)
                                                Aktivs remonts: {{ $device->activeRepair->description ?: 'Procesa' }}
                                            @else
                                                Bez aktiva remonta
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="dash-empty-block">Saja skata ierices netika atrastas.</div>
                        @endforelse
                    </div>
                </section>

                <div class="dash-split-grid">
                    <section class="surface-card">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <x-icon name="repair" size="h-5 w-5" class="text-amber-600" />
                                <span>Aktivie remonti</span>
                            </h2>
                            @if ($user->canManageRequests())
                                <a href="{{ route('repairs.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-800">Skatit visus</a>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @forelse ($activeRepairs as $repair)
                                <div class="surface-card-muted">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="font-semibold text-slate-900">{{ $repair->device?->name ?? 'Ierice' }}</div>
                                        <x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" />
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">{{ $repair->description }}</div>
                                    <div class="mt-2 text-sm text-slate-500">
                                        Pieteica: {{ $repair->reporter?->full_name ?? '-' }}
                                        | Apstiprinaja: {{ $repair->acceptedBy?->full_name ?? '-' }}
                                    </div>
                                </div>
                            @empty
                                <div class="dash-empty-block">Aktivu remontu paslaik nav.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="surface-card">
                        <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                            <x-icon name="audit" size="h-5 w-5" class="text-violet-600" />
                            <span>Jaunakas darbibas</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($recentActivity as $entry)
                                <div class="surface-card-muted">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-medium text-slate-900">{{ $entry->localized_entity_type }}</div>
                                        <div class="text-xs text-slate-500">{{ $entry->timestamp?->format('d.m.Y H:i') }}</div>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">{{ $entry->localized_description }}</div>
                                    <div class="mt-2 text-xs text-slate-500">{{ $entry->user?->full_name ?? 'Sistema' }}</div>
                                </div>
                            @empty
                                <div class="dash-empty-block">Darbibu ierakstu paslaik nav.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
