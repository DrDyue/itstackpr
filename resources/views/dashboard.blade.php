<x-app-layout>
    @php
        $latestInventoryLabel = $latestInventoryAt
            ? \Illuminate\Support\Carbon::parse($latestInventoryAt)->format('d.m.Y H:i')
            : 'Nav datu';

        $backupStatus = ($backupSummary['latest'] ?? null)
            ? 'Pedeja kopija ' . optional($backupSummary['latest']->created_at)->format('d.m.Y H:i')
            : 'Rezerves kopija nav izveidota';
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Darbvirsma</h1>
            <p class="mt-2 text-sm text-slate-600">
                Svarigakais par iericem, remontiem un rezerves kopijam.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices kopa</div>
                <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $totalDevices }}</div>
            </div>

            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Aktivie remonti</div>
                <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $activeRepairsCount }}</div>
            </div>

            <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Bojatas ierices</div>
                <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $brokenDevices }}</div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pedejais inventars</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $latestInventoryLabel }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)]">
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Aktivie remonti</h2>
                    <p class="mt-1 text-sm text-slate-500">Kas sobrid notiek.</p>
                </div>

                <div class="p-4">
                    @forelse ($activeRepairs as $repair)
                        @php
                            $device = $repair->device;
                            $statusClass = $repair->status === 'in-progress'
                                ? 'bg-sky-100 text-sky-800 ring-sky-200'
                                : 'bg-amber-100 text-amber-800 ring-amber-200';
                            $statusLabel = $repair->status === 'in-progress' ? 'Procesa' : 'Gaida';
                        @endphp

                        <a href="{{ route('repairs.edit', $repair) }}" class="mb-3 flex items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100 last:mb-0">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $device?->code ?: 'Ierice' }} | {{ $device?->name ?: 'Nezinama ierice' }}
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-slate-600">
                                    {{ $repair->assignee?->employee?->full_name ?? 'Nav pieskirta atbildiga' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $repair->start_date?->format('d.m.Y') ?? '-' }}
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-sky-700">Atvert</span>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                            Aktivu remontu nav.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Rezerves kopijas</h2>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Statuss</div>
                            <div class="mt-2 text-sm font-semibold text-slate-900">{{ $backupStatus }}</div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kopiju skaits</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $backupSummary['count'] }}</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nakamais starts</div>
                                <div class="mt-2 text-sm font-semibold text-slate-900">
                                    {{ $backupSummary['next_run_at'] ? $backupSummary['next_run_at']->format('d.m.Y H:i') : 'Izslegts' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Pedejie notikumi</h2>
                    </div>
                    <div class="space-y-3 p-4">
                        @forelse ($recentActivity as $entry)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-sm font-medium text-slate-900">{{ $entry->localized_description }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $entry->timestamp?->format('d.m.Y H:i') ?? '-' }}
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                                Notikumu nav.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Pedejas pievienotas ierices</h2>
            </div>

            <div class="overflow-x-auto">
                @if ($recentDevices->isNotEmpty())
                    <table class="min-w-full text-sm text-slate-700">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-4 py-4 text-left">Ierice</th>
                                <th class="px-4 py-4 text-left">Tips</th>
                                <th class="px-4 py-4 text-left">Statuss</th>
                                <th class="px-4 py-4 text-left">Telpa</th>
                                <th class="px-4 py-4 text-left">Pievienots</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recentDevices as $device)
                                @php
                                    $badgeClasses = match($device->status) {
                                        'active' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                        'repair' => 'bg-sky-100 text-sky-800 ring-sky-200',
                                        'broken' => 'bg-rose-100 text-rose-800 ring-rose-200',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ $device->name ?: 'Nezinama ierice' }}</div>
                                        <div class="text-xs text-slate-500">{{ $device->code ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4">{{ $device->type?->type_name ?? '-' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badgeClasses }}">
                                            {{ $device->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">{{ $device->room?->room_number ?: ($device->building?->building_name ?: '-') }}</td>
                                    <td class="px-4 py-4">{{ $device->created_at?->format('d.m.Y H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-6 text-sm text-slate-500">Ierices vel nav pievienotas.</div>
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
