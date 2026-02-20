<x-app-layout>
    <section class="dash-page">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Darbvirsma</h1>
                <p class="dash-subtitle">Parskats par IT inventaru, {{ now()->format('d.m.Y H:i') }}</p>
            </div>
            <a href="{{ route('devices.create') }}" class="dash-add-btn">Pievienot ierici</a>
        </div>

        <div class="dash-kpi-grid">
            <div class="dash-kpi-card">
                <p class="dash-kpi-label">Kopa iericu</p>
                <p class="dash-kpi-value">{{ $totalDevices }}</p>
            </div>
            <div class="dash-kpi-card">
                <p class="dash-kpi-label">Aktivas</p>
                <p class="dash-kpi-value dash-kpi-value-active">{{ $activeDevices }}</p>
            </div>
            <div class="dash-kpi-card">
                <p class="dash-kpi-label">Remonta vai bojatas</p>
                <p class="dash-kpi-value dash-kpi-value-warn">{{ $inRepairDevices + $brokenDevices }}</p>
            </div>
            <div class="dash-kpi-card">
                <p class="dash-kpi-label">Jaunas menesi</p>
                <p class="dash-kpi-value dash-kpi-value-info">{{ $newThisMonth }}</p>
            </div>
        </div>

        <div class="dash-shortcuts-grid">
            <a href="{{ route('device-types.index') }}" class="dash-shortcut-card">
                <p class="dash-shortcut-title">Iericu tipi</p>
                <p class="dash-shortcut-text">Klasifikators jaunu tipu pievienosanai</p>
            </a>
            <a href="{{ route('employees.index') }}" class="dash-shortcut-card">
                <p class="dash-shortcut-title">Darbinieki</p>
                <p class="dash-shortcut-text">Personu katalogs telpam un atbildibam</p>
            </a>
            <a href="{{ route('users.index') }}" class="dash-shortcut-card">
                <p class="dash-shortcut-title">Lietotaji</p>
                <p class="dash-shortcut-text">Sistemas konti, kas piesaistiti darbiniekiem</p>
            </a>
            <a href="{{ route('rooms.index') }}" class="dash-shortcut-card">
                <p class="dash-shortcut-title">Telpas</p>
                <p class="dash-shortcut-text">Eku un kabinetu struktura</p>
            </a>
        </div>

        <div class="dash-main-grid">
            <div class="dash-panel dash-panel-buildings">
                <div class="dash-panel-header">
                    <h2 class="dash-panel-title">Ekas</h2>
                    <a href="{{ route('buildings.index') }}" class="dash-panel-link">Skatit visas</a>
                </div>
                <div class="dash-panel-body-list">
                    @forelse($buildings->take(8) as $building)
                        <a href="{{ route('buildings.edit', $building) }}" class="dash-list-item">
                            <div class="dash-list-item-head">
                                <p class="dash-list-item-title">{{ $building->building_name }}</p>
                                <span class="dash-list-item-meta">{{ $building->rooms_count ?? 0 }} telpas</span>
                            </div>
                            <p class="dash-list-item-text">Stavi: {{ $building->total_floors ?? 1 }}</p>
                        </a>
                    @empty
                        <p class="dash-empty-text">Ekas vel nav pievienotas.</p>
                    @endforelse
                </div>
            </div>

            <div class="dash-panel dash-panel-hot">
                <div class="dash-panel-header">
                    <h2 class="dash-panel-title">Karstie punkti</h2>
                    <span class="dash-panel-hint">Pedejie statusa atjauninajumi</span>
                </div>

                <div class="dash-hot-stats">
                    <div class="dash-hot-stat dash-hot-stat-blue">
                        <p class="dash-hot-label">Remonta</p>
                        <p class="dash-hot-value">{{ $inRepairDevices }}</p>
                    </div>
                    <div class="dash-hot-stat dash-hot-stat-red">
                        <p class="dash-hot-label">Bojatas</p>
                        <p class="dash-hot-value">{{ $brokenDevices }}</p>
                    </div>
                    <div class="dash-hot-stat dash-hot-stat-amber">
                        <p class="dash-hot-label">Bez telpas</p>
                        <p class="dash-hot-value">{{ $withoutRoom }}</p>
                    </div>
                </div>

                <div class="dash-panel-body-list">
                    @forelse($hotDevices as $device)
                        @php
                            $badgeClasses = $device->status === 'broken' ? 'dash-badge dash-badge-red' : 'dash-badge dash-badge-blue';
                            $statusText = $device->status === 'broken' ? 'Bojata' : 'Remonta';
                        @endphp
                        <a href="{{ route('devices.edit', $device) }}" class="dash-list-item dash-list-item-row">
                            <div>
                                <p class="dash-list-item-title">{{ $device->type?->type_name ?? 'Nezinams tips' }}</p>
                                <p class="dash-list-item-text">{{ $device->code }} | {{ optional($device->room)->room_number ?? 'Telpa nav noradita' }}</p>
                            </div>
                            <span class="{{ $badgeClasses }}">{{ $statusText }}</span>
                        </a>
                    @empty
                        <p class="dash-empty-text">Sobrid nav iericu ar kritisku statusu.</p>
                    @endforelse
                </div>
            </div>

            <div class="dash-panel dash-panel-stats">
                <div class="dash-panel-header">
                    <h2 class="dash-panel-title">Statistika</h2>
                </div>
                <div class="dash-panel-body-list">
                    <div class="dash-stat-row">
                        <span class="dash-stat-label">Aktivas</span>
                        <span class="dash-stat-value">{{ $activeDevices }}</span>
                    </div>
                    <div class="dash-stat-row">
                        <span class="dash-stat-label">Remonta</span>
                        <span class="dash-stat-value">{{ $inRepairDevices }}</span>
                    </div>
                    <div class="dash-stat-row">
                        <span class="dash-stat-label">Bojatas</span>
                        <span class="dash-stat-value">{{ $brokenDevices }}</span>
                    </div>
                    <div class="dash-stat-row">
                        <span class="dash-stat-label">Ekas</span>
                        <span class="dash-stat-value">{{ $buildings->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2 class="dash-panel-title">Jaunakas ierices</h2>
                <a href="{{ route('devices.index') }}" class="dash-panel-link">Atvert inventaru</a>
            </div>

            @if($recentDevices->isNotEmpty())
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead class="dash-table-head">
                            <tr>
                                <th>Kods</th>
                                <th>Tips</th>
                                <th>Statuss</th>
                                <th>Atrasanas vieta</th>
                                <th>Darbibas</th>
                            </tr>
                        </thead>
                        <tbody class="dash-table-body">
                            @foreach($recentDevices as $device)
                                @php
                                    $statusText = match($device->status) {
                                        'active' => 'Aktiva',
                                        'broken' => 'Bojata',
                                        'repair' => 'Remonta',
                                        default => ucfirst((string) $device->status),
                                    };
                                    $badgeClasses = match($device->status) {
                                        'active' => 'dash-badge dash-badge-green',
                                        'broken' => 'dash-badge dash-badge-red',
                                        'repair' => 'dash-badge dash-badge-blue',
                                        default => 'dash-badge dash-badge-gray',
                                    };
                                @endphp
                                <tr>
                                    <td class="dash-table-cell-strong">{{ $device->code }}</td>
                                    <td>{{ $device->type?->type_name ?? 'Nezinams tips' }}</td>
                                    <td><span class="{{ $badgeClasses }}">{{ $statusText }}</span></td>
                                    <td>{{ optional($device->room)->room_number ?? 'Nav noradita' }}</td>
                                    <td><a href="{{ route('devices.edit', $device) }}" class="dash-table-link">Rediget</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="dash-empty-block">Ierices vel nav pievienotas.</div>
            @endif
        </div>
    </section>
</x-app-layout>
