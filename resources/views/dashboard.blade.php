<x-app-layout>
    @php
        $latestInventoryLabel = $latestInventoryAt
            ? \Illuminate\Support\Carbon::parse($latestInventoryAt)->format('d.m.Y H:i')
            : 'Nav datu';

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
                        <div class="dash-structure-stat">
                            <span class="dash-mini-label">Ekas</span>
                            <strong>{{ $buildingCount }}</strong>
                        </div>
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
                            <span class="dash-panel-icon dash-panel-icon-violet">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h9"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Kopsavilkums</p>
                                <h2 class="dash-panel-title">Inventars un telpas</h2>
                            </div>
                        </div>
                        <span class="dash-panel-chip">{{ $totalDevices }} ierices</span>
                    </div>

                    <div class="dash-quick-grid">
                        <div class="dash-quick-card dash-quick-card-blue">
                            <p class="dash-quick-label">Telpas ar iericem</p>
                            <div class="dash-quick-value">{{ $mappedRooms }}</div>
                            <p class="dash-quick-note">No {{ $totalRooms }} telpam</p>
                        </div>
                        <div class="dash-quick-card dash-quick-card-amber">
                            <p class="dash-quick-label">Bez telpas</p>
                            <div class="dash-quick-value">{{ $withoutRoom }}</div>
                            <p class="dash-quick-note">Japrecize atrasanas vieta</p>
                        </div>
                        <div class="dash-quick-card dash-quick-card-emerald">
                            <p class="dash-quick-label">Pabeigti menesi</p>
                            <div class="dash-quick-value">{{ $completedRepairsThisMonth }}</div>
                            <p class="dash-quick-note">Videjas izmaksas {{ number_format($averageRepairCost, 2) }} EUR</p>
                        </div>
                    </div>
                </div>

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
                <div class="dash-panel dash-action-panel">
                    <div class="dash-panel-header">
                        <div class="dash-panel-title-row">
                            <span class="dash-panel-icon dash-panel-icon-emerald">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Darbibas</p>
                                <h2 class="dash-panel-title">Atras darbibas</h2>
                            </div>
                        </div>
                    </div>

                    <div class="dash-action-list">
                        <a href="{{ route('devices.index') }}" class="dash-action-item">
                            <span class="dash-action-icon dash-action-icon-slate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Meklet ierices</strong>
                                <small>Atvert inventaru un filtrus</small>
                            </span>
                        </a>
                        <a href="{{ route('devices.create') }}" class="dash-action-item">
                            <span class="dash-action-icon dash-action-icon-emerald">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Pievienot ierici</strong>
                                <small>Jauna inventara vieniba</small>
                            </span>
                        </a>
                        <a href="{{ route('repairs.create') }}" class="dash-action-item">
                            <span class="dash-action-icon dash-action-icon-sky">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Sakt remontu</strong>
                                <small>Atvert jaunu ierakstu</small>
                            </span>
                        </a>
                        <a href="{{ route('buildings.index') }}" class="dash-action-item">
                            <span class="dash-action-icon dash-action-icon-violet">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21H3M6 21V7.5A1.5 1.5 0 0 1 7.5 6h9A1.5 1.5 0 0 1 18 7.5V21"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 12.75h3"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Skatit ekas</strong>
                                <small>Telpas, stavi un kabineti</small>
                            </span>
                        </a>
                    </div>
                </div>

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

                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div class="dash-panel-title-row">
                            <span class="dash-panel-icon dash-panel-icon-amber">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M12 3.75v16.5"/>
                                </svg>
                            </span>
                            <div>
                                <p class="dash-panel-eyebrow">Atrais skats</p>
                                <h2 class="dash-panel-title">Inventara statuss</h2>
                            </div>
                        </div>
                    </div>

                    <div class="dash-mini-grid dash-mini-grid-full">
                        <div class="dash-mini-card">
                            <span class="dash-mini-label">Bez telpas</span>
                            <strong>{{ $withoutRoom }}</strong>
                        </div>
                        <div class="dash-mini-card">
                            <span class="dash-mini-label">Rezerve</span>
                            <strong>{{ $reserveDevices }}</strong>
                        </div>
                        <div class="dash-mini-card">
                            <span class="dash-mini-label">Remonta statuss</span>
                            <strong>{{ $inRepairDevices }}</strong>
                        </div>
                        <div class="dash-mini-card">
                            <span class="dash-mini-label">Pedejais inventars</span>
                            <strong>{{ $latestInventoryAt ? \Illuminate\Support\Carbon::parse($latestInventoryAt)->format('d.m') : '-' }}</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="dash-bottom-grid">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div class="dash-panel-title-row">
                        <span class="dash-panel-icon dash-panel-icon-sky">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-15 4.5h15m-15 4.5h9M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="dash-panel-eyebrow">Inventars</p>
                            <h2 class="dash-panel-title">Pedejas pievienotas ierices</h2>
                        </div>
                    </div>
                    <a href="{{ route('devices.index') }}" class="dash-panel-link">Atvert inventaru</a>
                </div>

                <div class="dash-table-wrap">
                    @if ($recentDevices->isNotEmpty())
                        <table class="dash-table">
                            <thead class="dash-table-head">
                                <tr>
                                    <th>Ierice</th>
                                    <th>Tips</th>
                                    <th>Statuss</th>
                                    <th>Atrasanas vieta</th>
                                    <th>Pievienots</th>
                                    <th>Darbiba</th>
                                </tr>
                            </thead>
                            <tbody class="dash-table-body">
                                @foreach ($recentDevices as $device)
                                    @php
                                        $badgeClasses = match($device->status) {
                                            'active' => 'dash-inline-badge dash-inline-badge-emerald',
                                            'repair' => 'dash-inline-badge dash-inline-badge-sky',
                                            'broken' => 'dash-inline-badge dash-inline-badge-rose',
                                            default => 'dash-inline-badge dash-inline-badge-slate',
                                        };
                                        $statusText = match($device->status) {
                                            'active' => 'Aktiva',
                                            'repair' => 'Remonta',
                                            'broken' => 'Bojata',
                                            'reserve' => 'Rezerve',
                                            'retired' => 'Norakstita',
                                            'kitting' => 'Komplektacija',
                                            default => ucfirst((string) $device->status),
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="dash-device-cell">
                                                @if ($device->deviceImageThumbUrl())
                                                    <img src="{{ $device->deviceImageThumbUrl() }}" alt="Ierices attels" class="dash-device-thumb">
                                                @else
                                                    <div class="dash-device-thumb dash-device-thumb-empty">Nav</div>
                                                @endif
                                                <div>
                                                    <div class="dash-table-cell-strong">{{ $device->name ?: 'Nezinama ierice' }}</div>
                                                    <div class="dash-table-subline">{{ $device->code ?: '-' }} | {{ $device->manufacturer ?: 'Nav razotaja' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $device->type?->type_name ?? 'Nezinams tips' }}</td>
                                        <td><span class="{{ $badgeClasses }}">{{ $statusText }}</span></td>
                                        <td>
                                            @if ($device->room)
                                                {{ $device->room->room_number }}
                                            @elseif ($device->building)
                                                {{ $device->building->building_name }}
                                            @else
                                                Nav piesaistes
                                            @endif
                                        </td>
                                        <td>{{ $device->created_at?->format('d.m.Y H:i') ?? '-' }}</td>
                                        <td><a href="{{ route('devices.edit', $device) }}" class="dash-table-link">Atvert</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="dash-empty-block">Ierices vel nav pievienotas.</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
