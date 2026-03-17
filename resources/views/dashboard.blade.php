<x-app-layout>
    @php
        $latestInventoryLabel = $latestInventoryAt
            ? \Illuminate\Support\Carbon::parse($latestInventoryAt)->format('d.m.Y H:i')
            : 'Nav datu';
        $coveragePercent = $totalRooms > 0 ? round(($mappedRooms / max($totalRooms, 1)) * 100) : 0;
        $backupTone = ($backupSummary['latest'] ?? null) ? 'emerald' : 'slate';
        $buildingCount = $buildingTree->count();
        $floorCount = $buildingTree->sum('floor_count');
        $singleBuilding = $buildingCount === 1;

        $backupStatus = ($backupSummary['latest'] ?? null)
            ? 'Pedeja kopija ' . optional($backupSummary['latest']->created_at)->format('d.m.Y H:i')
            : 'Rezerves kopija nav izveidota';
    @endphp

    <section class="dash-page">
        <div class="dash-toolbar">
            <div class="dash-toolbar-main">
                <span class="dash-toolbar-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12 12 3.75 20.25 12M5.25 10.5v8.25h13.5V10.5"/>
                    </svg>
                </span>
                <div>
                    <h1 class="dash-toolbar-title">Darbvirsma</h1>
                    <p class="dash-toolbar-subtitle">{{ now()->format('d.m.Y') }} | Pedejais inventars {{ $latestInventoryLabel }}</p>
                </div>
            </div>

            <div class="dash-toolbar-meta">
                <div class="dash-meta-pill dash-meta-pill-sky">
                    <span>Kopa iericu</span>
                    <strong>{{ $totalDevices }}</strong>
                </div>
                <div class="dash-meta-pill dash-meta-pill-emerald">
                    <span>Telpu parklajums</span>
                    <strong>{{ $coveragePercent }}%</strong>
                </div>
                <div class="dash-meta-pill dash-meta-pill-amber">
                    <span>Aktivie remonti</span>
                    <strong>{{ $activeRepairsCount }}</strong>
                </div>
            </div>

            <div class="dash-toolbar-actions">
                <a href="{{ route('devices.create') }}" class="dash-action-btn dash-action-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Pievienot ierici
                </a>
                <a href="{{ route('repairs.create') }}" class="dash-action-btn dash-action-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z"/>
                    </svg>
                    Sakt remontu
                </a>
            </div>
        </div>

        <div class="dash-kpi-grid">
            @foreach ([
                ['label' => 'Aktivas ierices', 'value' => $activeDevices, 'tone' => 'emerald', 'hint' => 'Darba gataviba'],
                ['label' => 'Aktivie remonti', 'value' => $activeRepairsCount, 'tone' => 'sky', 'hint' => 'Gaida vai procesa'],
                ['label' => 'Bojatas ierices', 'value' => $brokenDevices, 'tone' => 'rose', 'hint' => 'Prasa uzmanibu'],
                ['label' => 'Jaunas saja menesi', 'value' => $newThisMonth, 'tone' => 'violet', 'hint' => 'Nesen pievienotas'],
            ] as $card)
                <div class="dash-kpi-card dash-kpi-card-{{ $card['tone'] }}">
                    <div class="dash-kpi-label">{{ $card['label'] }}</div>
                    <div class="dash-kpi-value">{{ $card['value'] }}</div>
                    <p class="dash-kpi-note">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="dash-layout-grid">
            <aside class="dash-column">
                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div class="dash-panel-title-row">
                            <span class="dash-panel-icon dash-panel-icon-sky">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Struktura</p>
                                <h2 class="dash-panel-title">Ekas, stavi un kabineti</h2>
                            </div>
                        </div>
                        <a href="{{ route('buildings.index') }}" class="dash-panel-link">Parvaldit</a>
                    </div>

                    <div class="dash-structure-stats">
                        @unless ($singleBuilding)
                            <div class="dash-structure-stat">
                                <span class="dash-mini-label">Ekas</span>
                                <strong>{{ $buildingCount }}</strong>
                            </div>
                        @endunless
                        <div class="dash-structure-stat">
                            <span class="dash-mini-label">Stavi</span>
                            <strong>{{ $floorCount }}</strong>
                        </div>
                        <div class="dash-structure-stat">
                            <span class="dash-mini-label">Telpas</span>
                            <strong>{{ $totalRooms }}</strong>
                        </div>
                    </div>

                    <div class="dash-structure-scroll">
                        <div class="dash-structure-list">
                            @forelse ($buildingTree as $buildingEntry)
                                @php $building = $buildingEntry['building']; @endphp
                                <details class="dash-structure-card" @if ($loop->first) open @endif>
                                    <summary class="dash-structure-summary">
                                        <div>
                                            <p class="dash-structure-title">{{ $building->building_name }}</p>
                                            <p class="dash-structure-meta">{{ $buildingEntry['rooms_count'] }} telpas | {{ $buildingEntry['device_count'] }} ierices</p>
                                        </div>
                                        <span class="dash-structure-badge">{{ $buildingEntry['floor_count'] }} stavi</span>
                                    </summary>

                                    <div class="dash-floor-list">
                                        @forelse ($buildingEntry['floors'] as $floor)
                                            <details class="dash-floor-card" @if ($loop->first) open @endif>
                                                <summary class="dash-floor-summary">
                                                    <div>
                                                        <p class="dash-floor-title">{{ $floor['floor_label'] }}</p>
                                                        <p class="dash-floor-meta">{{ $floor['room_count'] }} kabineti | {{ $floor['device_count'] }} ierices</p>
                                                    </div>
                                                    <span class="dash-floor-pill">{{ $floor['room_count'] }}</span>
                                                </summary>

                                                <div class="dash-room-list">
                                                    @foreach ($floor['rooms'] as $room)
                                                        <a href="{{ route('rooms.edit', $room) }}" class="dash-room-item">
                                                            <div>
                                                                <p class="dash-room-title">{{ $room->room_number }} @if ($room->room_name) | {{ $room->room_name }} @endif</p>
                                                                <p class="dash-room-meta">
                                                                    {{ $room->devices_count }} ierices
                                                                    @if ($room->employee)
                                                                        | {{ $room->employee->full_name }}
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @empty
                                            <div class="dash-empty-block">Sai ekai vel nav telpu.</div>
                                        @endforelse
                                    </div>
                                </details>
                            @empty
                                <div class="dash-empty-block">Ekas vel nav pievienotas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>

            <main class="dash-content-stack">
                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div class="dash-panel-title-row">
                            <span class="dash-panel-icon dash-panel-icon-amber">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Remonti</p>
                                <h2 class="dash-panel-title">Aktivie remonta procesi</h2>
                            </div>
                        </div>
                        <div class="dash-panel-badges">
                            <span class="dash-inline-badge dash-inline-badge-amber">Gaida {{ $waitingRepairsCount }}</span>
                            <span class="dash-inline-badge dash-inline-badge-sky">Procesa {{ $inProgressRepairsCount }}</span>
                        </div>
                    </div>

                    <div class="dash-activity-list">
                        @forelse ($activeRepairs as $repair)
                            @php
                                $device = $repair->device;
                                $statusClasses = $repair->status === 'in-progress'
                                    ? 'dash-inline-badge dash-inline-badge-sky'
                                    : 'dash-inline-badge dash-inline-badge-amber';
                                $statusLabel = $repair->status === 'in-progress' ? 'Procesa' : 'Gaida';
                                $priorityClasses = match($repair->priority) {
                                    'critical' => 'dash-inline-badge dash-inline-badge-rose',
                                    'high' => 'dash-inline-badge dash-inline-badge-amber',
                                    default => 'dash-inline-badge dash-inline-badge-slate',
                                };
                                $priorityLabel = match($repair->priority) {
                                    'critical' => 'Kritiska',
                                    'high' => 'Augsta',
                                    'low' => 'Zema',
                                    default => 'Videja',
                                };
                            @endphp
                            <a href="{{ route('repairs.edit', $repair) }}" class="dash-activity-item">
                                <div class="dash-activity-main">
                                    <div class="dash-activity-icon dash-activity-icon-{{ $repair->status === 'in-progress' ? 'sky' : 'amber' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="dash-activity-head">
                                            <p class="dash-activity-title">{{ $device?->code ?: 'Ierice' }} | {{ $device?->name ?: 'Nezinama ierice' }}</p>
                                            <span class="{{ $statusClasses }}">{{ $statusLabel }}</span>
                                            <span class="{{ $priorityClasses }}">{{ $priorityLabel }}</span>
                                        </div>
                                        <p class="dash-activity-meta">
                                            {{ $device?->building?->building_name ?: 'Eka nav noradita' }}
                                            @if ($device?->room)
                                                | {{ $device->room->room_number }}
                                            @endif
                                            | pieskirts {{ $repair->assignee?->employee?->full_name ?? 'nav' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="dash-activity-side">
                                    <span>{{ $repair->start_date?->format('d.m.Y') ?? '-' }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="dash-empty-block">Sobrid nav aktivo remontu.</div>
                        @endforelse
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div class="dash-panel-title-row">
                            <span class="dash-panel-icon dash-panel-icon-slate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Notikumi</p>
                                <h2 class="dash-panel-title">Pedejie notikumi</h2>
                            </div>
                        </div>
                        <span class="dash-panel-chip">{{ $recentActivity->count() }} ieraksti</span>
                    </div>

                    <div class="dash-feed-list">
                        @forelse ($recentActivity as $entry)
                            @php
                                $severityClass = match ($entry->severity) {
                                    'critical', 'error' => 'dash-feed-dot dash-feed-dot-rose',
                                    'warning' => 'dash-feed-dot dash-feed-dot-amber',
                                    default => 'dash-feed-dot dash-feed-dot-sky',
                                };
                            @endphp
                            <div class="dash-feed-item">
                                <span class="{{ $severityClass }}"></span>
                                <div>
                                    <p class="dash-feed-title">{{ $entry->localized_description }}</p>
                                    <p class="dash-feed-meta">
                                        {{ $entry->timestamp?->format('d.m.Y H:i') ?? '-' }}
                                        | {{ $entry->user?->employee?->full_name ?? 'Sistema' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="dash-empty-block">Pedejie notikumi vel nav pieejami.</div>
                        @endforelse
                    </div>
                </div>
            </main>

            <aside class="dash-side-stack">
                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div class="dash-panel-title-row">
                            <span class="dash-panel-icon dash-panel-icon-emerald">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M6 3.75h12a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Rezerves kopijas</p>
                                <h2 class="dash-panel-title">Statuss</h2>
                            </div>
                        </div>
                    </div>

                    <div class="dash-status-card dash-status-card-{{ $backupTone }}">
                        <div class="dash-status-head">
                            <span class="dash-status-dot"></span>
                            <strong>{{ $backupStatus }}</strong>
                        </div>
                        <p class="dash-status-text">
                            @if ($backupSummary['enabled'])
                                Automatiskais grafiks ieslegts
                                @if ($backupSummary['next_run_at'])
                                    | nakamais palaidiens {{ $backupSummary['next_run_at']->format('d.m.Y H:i') }}
                                @endif
                            @else
                                Automatiskais grafiks nav ieslegts
                            @endif
                        </p>

                        <div class="dash-status-grid">
                            <div>
                                <span class="dash-status-label">Kopiju skaits</span>
                                <strong>{{ $backupSummary['count'] }}</strong>
                            </div>
                            <div>
                                <span class="dash-status-label">Aktiva kopija</span>
                                <strong>{{ $backupSummary['current'] ? 'Ir' : 'Nav' }}</strong>
                            </div>
                        </div>

                        @if (auth()->user()?->role === 'admin')
                            <a href="{{ route('backups.index') }}" class="dash-inline-link">Atvert kopiju centru</a>
                        @endif
                    </div>
                </div>

            </aside>
        </div>
    </section>
</x-app-layout>
