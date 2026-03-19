<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="dashboard" size="h-4 w-4" />
                        <span>{{ $isManager ? 'Galvenais darba skats' : 'Tavs darba skats' }}</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="dashboard" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Darbvirsma</h1>
                            <p class="page-subtitle">
                                @if ($isManager)
                                    Vienuviet redzi telpu strukturu, ierices, aktivus remontus un jaunakas darbibas.
                                @else
                                    Vienuviet redzi savas ierices un ar tam saistitos pieteikumus.
                                @endif
                            </p>
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

        @if (! $isManager)
            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <section class="surface-card p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <x-icon name="repair-request" size="h-5 w-5" class="text-sky-600" />
                                <span>Pedejie pieteikumi</span>
                            </h2>
                            <p class="mt-2 text-sm text-slate-600">
                                Te redzi jaunakos remonta, norakstisanas un nodosanas pieteikumus, kas saistiti ar tevi.
                            </p>
                        </div>
                        <a href="{{ route('my-requests.index') }}" class="btn-view">
                            <x-icon name="view" size="h-4 w-4" />
                            <span>Visi pieteikumi</span>
                        </a>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Kopa</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $userRequestSummary['total'] }}</div>
                        </div>
                        <div class="rounded-[1.25rem] border border-sky-200 bg-sky-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">Gaida</div>
                            <div class="mt-2 text-2xl font-semibold text-sky-900">{{ $userRequestSummary['submitted'] }}</div>
                        </div>
                        <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Apstiprinati</div>
                            <div class="mt-2 text-2xl font-semibold text-emerald-900">{{ $userRequestSummary['approved'] }}</div>
                        </div>
                        <div class="rounded-[1.25rem] border border-rose-200 bg-rose-50 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-700">Noraiditi</div>
                            <div class="mt-2 text-2xl font-semibold text-rose-900">{{ $userRequestSummary['rejected'] }}</div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($recentUserRequests as $item)
                            <div class="surface-card-muted">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $item['type'] }}</div>
                                        <div class="mt-1 text-sm text-slate-600">{{ $item['device_name'] }}</div>
                                    </div>
                                    <x-status-pill context="request" :value="$item['status']" />
                                </div>
                                <div class="mt-3 text-sm text-slate-600">{{ $item['summary'] }}</div>
                                @if (! empty($item['meta']))
                                    <div class="mt-2 text-xs text-slate-500">{{ $item['meta'] }}</div>
                                @endif
                                <div class="mt-3 text-xs text-slate-500">{{ $item['created_at']?->format('d.m.Y H:i') ?: '-' }}</div>
                            </div>
                        @empty
                            <div class="dash-empty-block">Pieteikumu vesture paslaik vel nav.</div>
                        @endforelse
                    </div>
                </section>

                <section class="surface-card p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <x-icon name="device" size="h-5 w-5" class="text-emerald-600" />
                                <span>Manas ierices</span>
                            </h2>
                            <p class="mt-2 text-sm text-slate-600">
                                Atver ierices kartiti, piesaki remontu, norakstisanu vai atjaunini telpu.
                            </p>
                        </div>
                        <a href="{{ route('devices.index') }}" class="btn-view">
                            <x-icon name="view" size="h-4 w-4" />
                            <span>Visas manas ierices</span>
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($dashboardDevices as $device)
                            <div class="surface-card-muted">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $device->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $device->code ?: 'Bez koda' }} | {{ $device->type?->type_name ?: 'Bez tipa' }}</div>
                                    </div>
                                    <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                                </div>
                                <div class="mt-3 grid gap-3 text-sm text-slate-600 md:grid-cols-2">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Atrasanas vieta</div>
                                        <div class="mt-1">{{ $device->building?->building_name ?: 'Bez ekas' }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $device->room?->room_number ?: 'Telpa nav noradita' }}
                                            @if ($device->room?->room_name)
                                                | {{ $device->room->room_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Stavoklis</div>
                                        <div class="mt-1">
                                            @if ($device->activeRepair)
                                                Remonts: {{ ['waiting' => 'Gaida', 'in-progress' => 'Procesa', 'completed' => 'Pabeigts', 'cancelled' => 'Atcelts'][$device->activeRepair->status] ?? 'Remonta' }}
                                            @else
                                                Pieejama darbam
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $device->model ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('devices.show', $device) }}" class="btn-view">
                                        <x-icon name="view" size="h-4 w-4" />
                                        <span>Apskatit</span>
                                    </a>
                                    <a href="{{ route('my-requests.create', ['type' => 'repair', 'device_id' => $device->id]) }}" class="btn-edit">
                                        <x-icon name="repair" size="h-4 w-4" />
                                        <span>Pieteikt remontu</span>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="dash-empty-block">Tev paslaik nav pieskirtu iericu.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @else
            <div class="dash-workspace-grid">
                <aside class="dash-location-panel">
                    <div class="flex items-start justify-between gap-3">
                        <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <x-icon name="room" size="h-5 w-5" class="text-emerald-600" />
                            <span>Stavi un telpas</span>
                        </div>
                        <a href="{{ route('devices.index') }}" class="btn-clear">
                            <x-icon name="view" size="h-4 w-4" />
                            <span>Visas ierices</span>
                        </a>
                    </div>

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
                                    <a href="{{ route('devices.index', ['floor' => $floor['id']]) }}" class="dash-floor-filter">
                                        <x-icon name="view" size="h-4 w-4" />
                                        <span>Atvert iericu tabulu</span>
                                    </a>

                                    <div class="dash-room-list">
                                        @foreach ($floor['rooms'] as $room)
                                            <a
                                                href="{{ route('devices.index', ['floor' => $floor['id'], 'room_id' => $room['id']]) }}"
                                                class="dash-room-link"
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
                                    Kompakts parskats par jaunakajam pieejamajam iericem.
                                </p>
                            </div>
                            <a href="{{ route('devices.index') }}" class="btn-view">
                                <x-icon name="view" size="h-4 w-4" />
                                <span>Iericu tabula</span>
                            </a>
                        </div>

                        <div class="mt-5 overflow-x-auto rounded-[1.5rem] border border-slate-200 bg-white">
                            <table class="dash-table">
                                <thead class="dash-table-head">
                                    <tr>
                                        <th>Kods</th>
                                        <th>Ierice</th>
                                        <th>Atrasanas vieta</th>
                                        <th>Pieskirta</th>
                                        <th>Statuss</th>
                                        <th>Darbibas</th>
                                    </tr>
                                </thead>
                                <tbody class="dash-table-body">
                                    @forelse ($dashboardDevices as $device)
                                        <tr>
                                            <td>
                                                <div class="dash-table-cell-strong">{{ $device->code ?: '-' }}</div>
                                                <div class="dash-table-subline">{{ $device->type?->type_name ?: 'Bez tipa' }}</div>
                                            </td>
                                            <td>
                                                <a href="{{ route('devices.show', $device) }}" class="dash-table-link">{{ $device->name }}</a>
                                                <div class="dash-table-subline">{{ $device->model ?: '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="dash-table-cell-strong">{{ $device->building?->building_name ?: '-' }}</div>
                                                <div class="dash-table-subline">
                                                    {{ $device->room?->room_number ?: '-' }}
                                                    @if ($device->room?->room_name)
                                                        | {{ $device->room->room_name }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dash-table-cell-strong">{{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                                                <div class="dash-table-subline">{{ $device->serial_number ?: 'Bez serijas numura' }}</div>
                                            </td>
                                            <td>
                                                <x-status-pill context="device" :value="$device->status" />
                                                <div class="dash-table-subline">
                                                    @if ($device->activeRepair)
                                                        Aktivs remonts
                                                    @else
                                                        Bez aktiva remonta
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('devices.show', $device) }}" class="btn-view">
                                                    <x-icon name="view" size="h-4 w-4" />
                                                    <span>Skatit</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ierices pagaidam nav pieejamas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
                                            Izpilda: {{ $repair->executor?->full_name ?? '-' }}
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
        @endif
    </section>
</x-app-layout>
