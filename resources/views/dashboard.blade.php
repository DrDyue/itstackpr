<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="dashboard" size="h-4 w-4" />
                        <span>Galvenais parskats</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="dashboard" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Darbvirsma</h1>
                            <p class="page-subtitle">
                                {{ $user->canManageRequests() ? 'Parskats par iericem, pieteikumiem un remontiem.' : 'Parskats par tavajam iericem un iesniegtajiem pieteikumiem.' }}
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

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card metric-card-soft-sky">
                <div class="metric-head">
                    <div class="metric-icon"><x-icon name="device" size="h-5 w-5" /></div>
                    <div class="metric-label">Ierices kopa</div>
                </div>
                <div class="metric-value">{{ $totalDevices }}</div>
                <div class="metric-note">Aktivas: {{ $activeDevices }}</div>
            </div>
            <div class="metric-card metric-card-soft-amber">
                <div class="metric-head">
                    <div class="metric-icon"><x-icon name="repair" size="h-5 w-5" /></div>
                    <div class="metric-label">Remonta</div>
                </div>
                <div class="metric-value">{{ $inRepairDevices }}</div>
                <div class="metric-note">Norakstitas: {{ $writtenOffDevices }}</div>
            </div>
            <div class="metric-card metric-card-soft-rose">
                <div class="metric-head">
                    <div class="metric-icon"><x-icon name="repair-request" size="h-5 w-5" /></div>
                    <div class="metric-label">Gaidamie pieteikumi</div>
                </div>
                <div class="metric-value">{{ $pendingRepairRequests + $pendingWriteoffRequests + $pendingTransfers }}</div>
                <div class="metric-note">Remonts {{ $pendingRepairRequests }}, norakstisana {{ $pendingWriteoffRequests }}, parsutisana {{ $pendingTransfers }}</div>
            </div>
            <div class="metric-card metric-card-soft-emerald">
                <div class="metric-head">
                    <div class="metric-icon"><x-icon name="room" size="h-5 w-5" /></div>
                    <div class="metric-label">Telpas ar iericem</div>
                </div>
                <div class="metric-value">{{ $mappedRooms }}</div>
                <div class="metric-note">No {{ $totalRooms }} telpam</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
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
                            <p class="text-sm text-slate-500">Aktivu remontu paslaik nav.</p>
                        @endforelse
                    </div>
                </section>

                <section class="surface-card">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="device" size="h-5 w-5" class="text-sky-600" />
                        <span>Jaunakas ierices</span>
                    </h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-slate-500">
                                    <th class="px-3 py-2">Kods</th>
                                    <th class="px-3 py-2">Nosaukums</th>
                                    <th class="px-3 py-2">Statuss</th>
                                    <th class="px-3 py-2">Pieskirta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentDevices as $device)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-3 py-2">{{ $device->code ?: '-' }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('devices.show', $device) }}" class="font-medium text-slate-900 hover:text-blue-700">{{ $device->name }}</a>
                                        </td>
                                        <td class="px-3 py-2"><x-status-pill context="device" :value="$device->status" /></td>
                                        <td class="px-3 py-2">{{ $device->assignedTo?->full_name ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-slate-500">Ierices vel nav pievienotas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
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
                            <p class="text-sm text-slate-500">Audita ierakstu pagaidam nav.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        @if ($user->canManageRequests())
            <section class="surface-card">
                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                    <x-icon name="building" size="h-5 w-5" class="text-emerald-600" />
                    <span>Ekas un telpas</span>
                </h2>
                <div class="mt-4 space-y-4">
                    @forelse ($buildingTree as $entry)
                        <div class="surface-card-muted">
                            <div class="font-semibold text-slate-900">{{ $entry['building']->building_name }}</div>
                            <div class="mt-1 text-sm text-slate-500">Telpas: {{ $entry['rooms_count'] }}, ierices: {{ $entry['device_count'] }}</div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                @foreach ($entry['floors'] as $floor)
                                    <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200">
                                        <div class="font-medium text-slate-800">{{ $floor['floor_label'] }}</div>
                                        <div class="mt-1 text-sm text-slate-500">Telpas {{ $floor['room_count'] }}, ierices {{ $floor['device_count'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Eku dati pagaidam nav pieejami.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </section>
</x-app-layout>

