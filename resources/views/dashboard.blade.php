<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Darbvirsma</h1>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $user->canManageRequests() ? 'Pārskats par ierīcēm, pieteikumiem un remontiem.' : 'Pārskats par tavām ierīcēm un iesniegtajiem pieteikumiem.' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('repair-requests.create') }}" class="crud-btn-primary inline-flex items-center gap-2">Pieteikt remontu</a>
                <a href="{{ route('writeoff-requests.create') }}" class="crud-btn-secondary inline-flex items-center gap-2">Pieteikt norakstisanu</a>
                <a href="{{ route('device-transfers.create') }}" class="crud-btn-secondary inline-flex items-center gap-2">Pieteikt parsutisanu</a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Ierices kopa</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $totalDevices }}</div>
                <div class="mt-2 text-sm text-slate-500">Aktivas: {{ $activeDevices }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Remonta</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $inRepairDevices }}</div>
                <div class="mt-2 text-sm text-slate-500">Norakstitas: {{ $writtenOffDevices }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Gaidamie pieteikumi</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $pendingRepairRequests + $pendingWriteoffRequests + $pendingTransfers }}</div>
                <div class="mt-2 text-sm text-slate-500">Remonts {{ $pendingRepairRequests }}, norakstisana {{ $pendingWriteoffRequests }}, parsutisana {{ $pendingTransfers }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Telpas ar iericem</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $mappedRooms }}</div>
                <div class="mt-2 text-sm text-slate-500">No {{ $totalRooms }} telpam</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-slate-900">Aktivie remonti</h2>
                        @if ($user->canManageRequests())
                            <a href="{{ route('repairs.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-800">Skatit visus</a>
                        @endif
                    </div>

                    <div class="space-y-3">
                        @forelse ($activeRepairs as $repair)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="font-semibold text-slate-900">{{ $repair->device?->name ?? 'Ierice' }}</div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $statusLabels[$repair->status] ?? $repair->status }}</span>
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

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Jaunakas ierices</h2>
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
                                        <td class="px-3 py-2">{{ $device->status }}</td>
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
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Jaunakas darbības</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentActivity as $entry)
                            <div class="rounded-xl border border-slate-200 p-4">
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

                @if ($user->isAdmin())
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Rezerves kopijas</h2>
                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <div>Kopiju skaits: {{ $backupSummary['count'] }}</div>
                            <div>Nakama palaisana: {{ $backupSummary['next_run_at']?->format('d.m.Y H:i') ?: '-' }}</div>
                            <div>Pedeja kopija: {{ $backupSummary['latest']?->name ?? '-' }}</div>
                        </div>
                    </section>
                @endif
            </div>
        </div>

        @if ($user->canManageRequests())
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Ekas un telpas</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($buildingTree as $entry)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="font-semibold text-slate-900">{{ $entry['building']->building_name }}</div>
                            <div class="mt-1 text-sm text-slate-500">Telpas: {{ $entry['rooms_count'] }}, ierices: {{ $entry['device_count'] }}</div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                @foreach ($entry['floors'] as $floor)
                                    <div class="rounded-xl bg-slate-50 p-3">
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
