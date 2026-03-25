<x-app-layout>
    <section class="app-shell">
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
                            <span>Ierices: {{ $dashboardDevices->total() }}</span>
                        </span>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="dashboard" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Darbvirsma</h1>
                            <p class="page-subtitle">Vienuviet redzi telpu strukturu, atri filtre ierices un parvaldi galvenas darbibas.</p>
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

        <div class="dash-workspace-grid">
            <aside class="dash-location-panel">
                <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <x-icon name="room" size="h-5 w-5" class="text-emerald-600" />
                    <span>Stavi un telpas</span>
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
                                <a
                                    href="{{ route('dashboard', ['floor' => $floor['id']]) }}"
                                    class="dash-floor-filter {{ $floor['is_active'] ? 'dash-floor-filter-active' : '' }}"
                                >
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Filtre si stava ierices</span>
                                </a>

                                <div class="dash-room-list">
                                    @foreach ($floor['rooms'] as $room)
                                        <a
                                            href="{{ route('dashboard', ['floor' => $floor['id'], 'room_id' => $room['id']]) }}"
                                            class="dash-room-link {{ $room['is_active'] ? 'dash-room-link-active' : '' }}"
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
                                Parskats par visam iericem, sakartots pec jaunakajiem ierakstiem.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($filters['floor'] !== '' || $filters['room_id'] !== '')
                                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">
                                    <x-icon name="room" size="h-3.5 w-3.5" />
                                    <span>{{ $filters['room_id'] !== '' ? 'Telpas filtrs ieslegts' : 'Stava filtrs ieslegts' }}</span>
                                </div>
                                <a href="{{ route('dashboard') }}" class="btn-clear">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>Notirit filtru</span>
                                </a>
                            @endif
                        </div>
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
                                    @php
                                        $manufacturer = trim((string) ($device->manufacturer ?? ''));
                                        $model = trim((string) ($device->model ?? ''));
                                        $typeName = $device->type?->type_name ?: 'Bez tipa';
                                        $brandModel = $model !== ''
                                            ? (
                                                $manufacturer !== '' && ! \Illuminate\Support\Str::startsWith(
                                                    mb_strtolower($model),
                                                    mb_strtolower($manufacturer)
                                                )
                                                    ? trim($manufacturer . ' ' . $model)
                                                    : $model
                                            )
                                            : ($manufacturer !== '' ? $manufacturer : 'Razotajs un modelis nav noradits');
                                        $assignedJobTitle = $device->assignedTo?->job_title ?: 'Nav amata';
                                        $roomLabel = collect([
                                            $device->room?->room_number,
                                            $device->room?->room_name,
                                        ])->filter()->implode(' | ');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="dash-table-cell-strong">{{ $device->code ?: '-' }}</div>
                                            <div class="dash-table-subline">{{ $device->serial_number ?: 'Nav serijas numura' }}</div>
                                        </td>
                                        <td>
                                            <a href="{{ route('devices.show', $device) }}" class="dash-table-link">{{ $device->name }}</a>
                                            <div class="dash-table-subline">{{ $typeName }}</div>
                                            <div class="dash-table-subline dash-table-subline-wrap">{{ $brandModel }}</div>
                                        </td>
                                        <td>
                                            <div class="dash-table-cell-strong dash-table-nowrap">{{ $device->building?->building_name ?: '-' }}</div>
                                            <div class="dash-table-subline">{{ $roomLabel !== '' ? $roomLabel : 'Telpa nav noradita' }}</div>
                                        </td>
                                        <td>
                                            <div class="dash-table-cell-strong">{{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                                            <div class="dash-table-subline">{{ $assignedJobTitle }}</div>
                                        </td>
                                        <td>
                                            <x-status-pill context="device" :value="$device->status" />
                                            @if ($device->status === \App\Models\Device::STATUS_REPAIR)
                                                <div class="device-repair-state-chip mt-2">
                                                    <x-icon name="repair" size="h-3.5 w-3.5" />
                                                    <span>{{ ['waiting' => 'Gaida', 'in-progress' => 'Procesa'][$device->activeRepair?->status] ?? 'Remonta' }}</span>
                                                </div>
                                            @else
                                                <div class="dash-table-subline">Bez gaidosa remonta</div>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('devices.show', $device) }}" class="btn-view dash-table-action-btn">
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

                    @if ($dashboardDevices->hasPages())
                        @php
                            $currentPage = $dashboardDevices->currentPage();
                            $lastPage = $dashboardDevices->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp
                        <div class="dashboard-pagination">
                            <div class="dashboard-pagination-meta">
                                <span>Kopa {{ $dashboardDevices->total() }} ierices</span>
                                <span>Lapa {{ $currentPage }} no {{ $lastPage }}</span>
                            </div>

                            <div class="dashboard-pagination-links">
                                @if ($dashboardDevices->onFirstPage())
                                    <span class="dashboard-pagination-btn dashboard-pagination-btn-disabled">Iepriekseja</span>
                                @else
                                    <a href="{{ $dashboardDevices->previousPageUrl() }}" class="dashboard-pagination-btn">Iepriekseja</a>
                                @endif

                                @for ($page = $startPage; $page <= $endPage; $page++)
                                    @if ($page === $currentPage)
                                        <span class="dashboard-pagination-btn dashboard-pagination-btn-active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $dashboardDevices->url($page) }}" class="dashboard-pagination-btn">{{ $page }}</a>
                                    @endif
                                @endfor

                                @if ($dashboardDevices->hasMorePages())
                                    <a href="{{ $dashboardDevices->nextPageUrl() }}" class="dashboard-pagination-btn">Nakama</a>
                                @else
                                    <span class="dashboard-pagination-btn dashboard-pagination-btn-disabled">Nakama</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </section>
</x-app-layout>
