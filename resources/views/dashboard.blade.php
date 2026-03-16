<x-app-layout>
    @php
        $deviceStatusCards = [
            [
                'label' => 'Aktivas ierices',
                'value' => $activeDevices,
                'tone' => 'emerald',
                'hint' => 'Darba gataviba ikdienai',
                'icon' => 'M12 3.75a8.25 8.25 0 1 0 8.25 8.25A8.25 8.25 0 0 0 12 3.75Zm3.36 6.97-4.125 4.125a.75.75 0 0 1-1.06 0L8.64 13.315',
            ],
            [
                'label' => 'Aktivie remonti',
                'value' => $activeRepairsCount,
                'tone' => 'sky',
                'hint' => 'Gaida vai ir procesa',
                'icon' => 'm11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z',
            ],
            [
                'label' => 'Bojatas ierices',
                'value' => $brokenDevices,
                'tone' => 'rose',
                'hint' => 'Prasa uzmanibu vai remontu',
                'icon' => 'M12 9v3.75m0 3.75h.008v.008H12v-.008Zm9-1.508c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z',
            ],
            [
                'label' => 'Jaunas saja menesi',
                'value' => $newThisMonth,
                'tone' => 'violet',
                'hint' => 'Nesen pievienotais inventars',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
            ],
        ];

        $latestInventoryLabel = $latestInventoryAt ? \Illuminate\Support\Carbon::parse($latestInventoryAt)->format('d.m.Y H:i') : 'Nav datu';
        $coveragePercent = $totalRooms > 0 ? round(($mappedRooms / max($totalRooms, 1)) * 100) : 0;
        $backupTone = ($backupSummary['latest'] ?? null) ? 'emerald' : 'slate';
        $backupStatus = ($backupSummary['latest'] ?? null)
            ? 'Pedeja kopija: ' . optional($backupSummary['latest']->created_at)->format('d.m.Y H:i')
            : 'Rezerves kopija vel nav izveidota';
    @endphp

    <section class="dash-page">
        <div class="dash-hero">
            <div class="dash-hero-copy">
                <div class="dash-eyebrow">
                    <span class="dash-eyebrow-dot"></span>
                    IT Stack vadibas centrs
                </div>
                <h1 class="dash-title">Galvena informacijas virsma ikdienas darbam</h1>
                <p class="dash-subtitle">
                    Viena vieta, kur redzet eku strukturu, iericu stavokli, aktivus remontus, pedejas darbibas un sistemas gatavibu talakai ikdienas apkalpei.
                </p>

                <div class="dash-hero-actions">
                    <a href="{{ route('devices.create') }}" class="dash-hero-btn dash-hero-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Pievienot jaunu ierici
                    </a>
                    <a href="{{ route('repairs.create') }}" class="dash-hero-btn dash-hero-btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z"/>
                        </svg>
                        Sakt remontu
                    </a>
                </div>
            </div>

            <div class="dash-hero-side">
                <div class="dash-hero-clock">
                    <p class="dash-mini-label">Sobrid</p>
                    <div class="dash-hero-time">{{ now()->format('d.m.Y') }}</div>
                    <p class="dash-hero-time-sub">{{ now()->format('H:i') }} | inventara ritms viena skatijuma</p>
                </div>

                <div class="dash-hero-pulse">
                    <div class="dash-pulse-item">
                        <span class="dash-pulse-label">Kopa iericu</span>
                        <strong>{{ $totalDevices }}</strong>
                    </div>
                    <div class="dash-pulse-item">
                        <span class="dash-pulse-label">Telpu parklajums</span>
                        <strong>{{ $coveragePercent }}%</strong>
                    </div>
                    <div class="dash-pulse-item">
                        <span class="dash-pulse-label">Pedejais inventars</span>
                        <strong>{{ $latestInventoryLabel }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-kpi-grid">
            @foreach ($deviceStatusCards as $card)
                <div class="dash-kpi-card dash-kpi-card-{{ $card['tone'] }}">
                    <div class="dash-kpi-top">
                        <span class="dash-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                            </svg>
                        </span>
                        <span class="dash-kpi-label">{{ $card['label'] }}</span>
                    </div>
                    <div class="dash-kpi-value">{{ $card['value'] }}</div>
                    <p class="dash-kpi-note">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="dash-layout-grid">
            <aside class="dash-panel dash-structure-panel">
                <div class="dash-panel-header">
                    <div>
                        <p class="dash-panel-eyebrow">Struktura</p>
                        <h2 class="dash-panel-title">Ekas, stavi un kabineti</h2>
                    </div>
                    <a href="{{ route('buildings.index') }}" class="dash-panel-link">Parvaldit ekas</a>
                </div>

                <div class="dash-panel-copy">
                    Atver eku, lai redzetu stavu sadalijumu un katras telpas noslodzi pec iericem.
                </div>

                <div class="dash-structure-list">
                    @forelse ($buildingTree as $buildingEntry)
                        @php
                            $building = $buildingEntry['building'];
                        @endphp
                        <details class="dash-structure-card" @if ($loop->first) open @endif>
                            <summary class="dash-structure-summary">
                                <div>
                                    <p class="dash-structure-title">{{ $building->building_name }}</p>
                                    <p class="dash-structure-meta">
                                        {{ $buildingEntry['rooms_count'] }} telpas | {{ $buildingEntry['device_count'] }} ierices
                                    </p>
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
                                                                | atbildigais {{ $room->employee->full_name }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </details>
                                @empty
                                    <div class="dash-empty-block">Sai ekai vel nav telpu strukturas.</div>
                                @endforelse
                            </div>
                        </details>
                    @empty
                        <div class="dash-empty-block">Ekas vel nav pievienotas.</div>
                    @endforelse
                </div>
            </aside>

            <main class="dash-content-stack">
                <div class="dash-panel dash-story-panel">
                    <div class="dash-panel-header">
                        <div>
                            <p class="dash-panel-eyebrow">Operacionais skats</p>
                            <h2 class="dash-panel-title">Kas svarigs tiesi sobrid</h2>
                        </div>
                        <span class="dash-panel-chip">{{ $totalDevices }} vienibas inventara</span>
                    </div>

                    <div class="dash-story-grid">
                        <div class="dash-story-card dash-story-card-blue">
                            <p class="dash-story-label">Telpas ar iericem</p>
                            <div class="dash-story-value">{{ $mappedRooms }}</div>
                            <p class="dash-story-note">No {{ $totalRooms }} registrētām telpām</p>
                        </div>
                        <div class="dash-story-card dash-story-card-amber">
                            <p class="dash-story-label">Bez piesaistitas telpas</p>
                            <div class="dash-story-value">{{ $withoutRoom }}</div>
                            <p class="dash-story-note">Ierices, kuram japrecize atrasanas vieta</p>
                        </div>
                        <div class="dash-story-card dash-story-card-emerald">
                            <p class="dash-story-label">Pabeigti remonti menesi</p>
                            <div class="dash-story-value">{{ $completedRepairsThisMonth }}</div>
                            <p class="dash-story-note">Ar videjam izmaksam {{ number_format($averageRepairCost, 2) }} EUR</p>
                        </div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div>
                            <p class="dash-panel-eyebrow">Remonti</p>
                            <h2 class="dash-panel-title">Aktivie remonta procesi</h2>
                        </div>
                        <a href="{{ route('repairs.index') }}" class="dash-panel-link">Atvert remonta paneli</a>
                    </div>

                    <div class="dash-activity-list">
                        @forelse ($activeRepairs as $repair)
                            @php
                                $device = $repair->device;
                                $statusClasses = $repair->status === 'in-progress'
                                    ? 'dash-inline-badge dash-inline-badge-sky'
                                    : 'dash-inline-badge dash-inline-badge-amber';
                                $statusLabel = $repair->status === 'in-progress' ? 'Procesa' : 'Gaida';
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
                        <div>
                            <p class="dash-panel-eyebrow">Plusts</p>
                            <h2 class="dash-panel-title">Pedejas darbibas sistemā</h2>
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
                                    <p class="dash-feed-title">{{ $entry->description }}</p>
                                    <p class="dash-feed-meta">
                                        {{ $entry->timestamp?->format('d.m.Y H:i') ?? '-' }}
                                        | {{ $entry->user?->employee?->full_name ?? 'Sistema' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="dash-empty-block">Darbibu plusts vel nav pieejams.</div>
                        @endforelse
                    </div>
                </div>
            </main>

            <aside class="dash-side-stack">
                <div class="dash-panel dash-action-panel">
                    <div class="dash-panel-header">
                        <div>
                            <p class="dash-panel-eyebrow">Atrie soļi</p>
                            <h2 class="dash-panel-title">Darbibas pa labi</h2>
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
                                <small>Atvert visu inventaru un filtrus</small>
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
                                <small>Registrēt jaunu inventara vienibu</small>
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
                                <small>Atvert jaunu remonta ierakstu</small>
                            </span>
                        </a>
                        <a href="{{ route('rooms.index') }}" class="dash-action-item">
                            <span class="dash-action-icon dash-action-icon-violet">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21H3M6 21V7.5A1.5 1.5 0 0 1 7.5 6h9A1.5 1.5 0 0 1 18 7.5V21"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 12.75h3"/>
                                </svg>
                            </span>
                            <span>
                                <strong>Skatit kabinetus</strong>
                                <small>Parvaldit telpu un stavu strukturu</small>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div>
                            <p class="dash-panel-eyebrow">Sistemas statuss</p>
                            <h2 class="dash-panel-title">Rezerves kopijas un veseliba</h2>
                        </div>
                    </div>

                    <div class="dash-status-card dash-status-card-{{ $backupTone }}">
                        <div class="dash-status-head">
                            <span class="dash-status-dot"></span>
                            <strong>{{ $backupStatus }}</strong>
                        </div>
                        <p class="dash-status-text">
                            @if ($backupSummary['enabled'])
                                Automatiskais grafiks ir ieslegts.
                                @if ($backupSummary['next_run_at'])
                                    Nākamais palaidiens {{ $backupSummary['next_run_at']->format('d.m.Y H:i') }}.
                                @endif
                            @else
                                Automatiskais grafiks sobrid nav ieslegts.
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

                    <div class="dash-mini-grid">
                        <div class="dash-mini-card">
                            <span class="dash-mini-label">Rezerve</span>
                            <strong>{{ $reserveDevices }}</strong>
                        </div>
                        <div class="dash-mini-card">
                            <span class="dash-mini-label">Remonta statuss</span>
                            <strong>{{ $inRepairDevices }}</strong>
                        </div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div>
                            <p class="dash-panel-eyebrow">Paligs</p>
                            <h2 class="dash-panel-title">Ko vel var darit talak</h2>
                        </div>
                    </div>

                    <div class="dash-note-list">
                        <div class="dash-note-item">
                            <strong>Sakarto telpas</strong>
                            <p>Ja redzi ierices bez kabineta, vispirms precize telpu piesaistes.</p>
                        </div>
                        <div class="dash-note-item">
                            <strong>Parskati remontus</strong>
                            <p>Atver aktivus remontus un nosledz tos ar faktiskajiem datumiem un izmaksam.</p>
                        </div>
                        <div class="dash-note-item">
                            <strong>Veido atskaites velak</strong>
                            <p>Si virsma jau apkopo galvenos pamatus, uz kuriem var balstit nakamas atskaites un panelus.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="dash-bottom-grid">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <p class="dash-panel-eyebrow">Inventars</p>
                        <h2 class="dash-panel-title">Pedejas pievienotas ierices</h2>
                    </div>
                    <a href="{{ route('devices.index') }}" class="dash-panel-link">Atvert inventaru</a>
                </div>

                <div class="dash-table-wrap">
                    @if ($recentDevices->isNotEmpty())
                        <table class="dash-table">
                            <thead class="dash-table-head">
                                <tr>
                                    <th>Kods</th>
                                    <th>Tips</th>
                                    <th>Statuss</th>
                                    <th>Atrašanas vieta</th>
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
                                            default => ucfirst((string) $device->status),
                                        };
                                    @endphp
                                    <tr>
                                        <td class="dash-table-cell-strong">{{ $device->code ?: '-' }}</td>
                                        <td>{{ $device->type?->type_name ?? 'Nezinams tips' }}</td>
                                        <td><span class="{{ $badgeClasses }}">{{ $statusText }}</span></td>
                                        <td>{{ $device->room?->room_number ?: 'Nav piesaistes' }}</td>
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
