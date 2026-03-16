<x-app-layout>
    @php
        $priorityLabels = [
            'low' => 'Zema',
            'medium' => 'Videja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];

        $summaryCards = [
            ['label' => 'Kopa remontu', 'value' => $summary['total_repairs'], 'note' => $repairScope['label'], 'tone' => 'sky'],
            ['label' => 'Aktivie remonti', 'value' => $summary['active_repairs'], 'note' => 'Gaida vai procesa', 'tone' => 'amber'],
            ['label' => 'Kavejie', 'value' => $summary['overdue_repairs'], 'note' => 'Prasa prioritaru uzmanibu', 'tone' => 'rose'],
            ['label' => 'Pabeigti saja menesi', 'value' => $summary['completed_this_month'], 'note' => 'Noslegtie darbi', 'tone' => 'emerald'],
            ['label' => 'Videjais ilgums', 'value' => $summary['average_duration'] !== null ? $summary['average_duration'] . ' d.' : '-', 'note' => 'Pabeigto remontu cikls', 'tone' => 'violet'],
            ['label' => 'Videjas izmaksas', 'value' => number_format((float) $summary['average_cost'], 2, '.', ' ') . ' EUR', 'note' => 'Tikai ierakstiem ar cenu', 'tone' => 'slate'],
        ];

        $toneClasses = [
            'sky' => ['card' => 'border-sky-200 bg-sky-50', 'bar' => 'bg-sky-500', 'text' => 'text-sky-700'],
            'emerald' => ['card' => 'border-emerald-200 bg-emerald-50', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
            'amber' => ['card' => 'border-amber-200 bg-amber-50', 'bar' => 'bg-amber-500', 'text' => 'text-amber-700'],
            'rose' => ['card' => 'border-rose-200 bg-rose-50', 'bar' => 'bg-rose-500', 'text' => 'text-rose-700'],
            'violet' => ['card' => 'border-violet-200 bg-violet-50', 'bar' => 'bg-violet-500', 'text' => 'text-violet-700'],
            'orange' => ['card' => 'border-orange-200 bg-orange-50', 'bar' => 'bg-orange-500', 'text' => 'text-orange-700'],
            'slate' => ['card' => 'border-slate-200 bg-slate-50', 'bar' => 'bg-slate-500', 'text' => 'text-slate-700'],
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-amber-50 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-amber-700 ring-1 ring-amber-200">
                        Remontu skats
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Remontu plusts un noslodze</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Skats uz statusiem, prioritatem, terminu kavesanu, lietotaju noslodzi un arejo piegadataju darbu apjomu.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">{{ $repairScope['label'] }}</span>
                    <a href="{{ route('repairs.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Pilna lenta
                    </a>
                    <a href="{{ route('repairs.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Pievienot remontu
                    </a>
                </div>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                @include('reports.partials.nav')
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($summaryCards as $card)
                <div class="rounded-3xl border p-5 shadow-sm {{ $toneClasses[$card['tone']]['card'] }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $toneClasses[$card['tone']]['text'] }}">{{ $card['label'] }}</p>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $card['value'] }}</div>
                    <p class="mt-2 text-sm text-slate-600">{{ $card['note'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.95fr)]">
            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Dinamika</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Atvertie un pabeigtie pa menesiem</h2>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.18em]">
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-700">Atverti</span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Pabeigti</span>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-6">
                        @foreach ($repairTrend as $item)
                            @php
                                $openedHeight = $repairTrendMax > 0 ? max(10, (int) round(($item['opened'] / $repairTrendMax) * 160)) : 10;
                                $completedHeight = $repairTrendMax > 0 ? max(10, (int) round(($item['completed'] / $repairTrendMax) * 160)) : 10;
                            @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-44 items-end justify-center gap-3 rounded-2xl bg-white px-4 py-4">
                                    <div class="w-full rounded-t-2xl bg-sky-500" style="height: {{ $openedHeight }}px;"></div>
                                    <div class="w-full rounded-t-2xl bg-emerald-500" style="height: {{ $completedHeight }}px;"></div>
                                </div>
                                <div class="mt-3 text-center">
                                    <div class="text-sm font-semibold text-slate-900">{{ $item['opened'] }} / {{ $item['completed'] }}</div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Statusi</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Plusts</h2>
                        </div>
                        <div class="space-y-3">
                            @foreach ($statusMetrics as $metric)
                                @php $tone = $toneClasses[$metric['tone']] ?? $toneClasses['slate']; @endphp
                                <div class="rounded-2xl border p-4 {{ $tone['card'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $metric['label'] }}</div>
                                        <div class="text-sm font-semibold {{ $tone['text'] }}">{{ $metric['count'] }}</div>
                                    </div>
                                    <div class="mt-3 h-2.5 rounded-full bg-white/80">
                                        <div class="h-2.5 rounded-full {{ $tone['bar'] }}" style="width: {{ min(100, $metric['share']) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Prioritates</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Saspringums</h2>
                        </div>
                        <div class="space-y-3">
                            @foreach ($priorityMetrics as $metric)
                                @php $tone = $toneClasses[$metric['tone']] ?? $toneClasses['slate']; @endphp
                                <div class="rounded-2xl border p-4 {{ $tone['card'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $metric['label'] }}</div>
                                        <div class="text-sm font-semibold {{ $tone['text'] }}">{{ $metric['count'] }}</div>
                                    </div>
                                    <div class="mt-3 h-2.5 rounded-full bg-white/80">
                                        <div class="h-2.5 rounded-full {{ $tone['bar'] }}" style="width: {{ min(100, $metric['share']) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Veids</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Ieksejais vai arejais</h2>
                        </div>
                        <div class="space-y-3">
                            @foreach ($typeMetrics as $metric)
                                @php $tone = $toneClasses[$metric['tone']] ?? $toneClasses['slate']; @endphp
                                <div class="rounded-2xl border p-4 {{ $tone['card'] }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $metric['label'] }}</div>
                                        <div class="text-sm font-semibold {{ $tone['text'] }}">{{ $metric['count'] }}</div>
                                    </div>
                                    <div class="mt-3 h-2.5 rounded-full bg-white/80">
                                        <div class="h-2.5 rounded-full {{ $tone['bar'] }}" style="width: {{ min(100, $metric['share']) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Termini</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Kavejie remonti</h2>
                        </div>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">{{ $overdueRepairs->count() }} ieraksti</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($overdueRepairs as $repair)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900">Remonts #{{ $repair->id }} | {{ $repair->device?->name ?: 'Ierice nav atrasta' }}</div>
                                        <div class="mt-1 text-sm text-slate-500">
                                            {{ $repair->device?->building?->building_name ?: 'Eka nav piesaistita' }}
                                            @if ($repair->device?->room)
                                                | {{ $repair->device->room->room_number }}
                                            @endif
                                            @if ($repair->assignee?->employee)
                                                | {{ $repair->assignee->employee->full_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800">{{ $priorityLabels[$repair->priority ?: 'medium'] ?? 'Videja' }}</span>
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">
                                            @if ($repair->estimated_completion)
                                                Lidz {{ $repair->estimated_completion->format('d.m.Y') }}
                                            @else
                                                No {{ optional($repair->start_date)->format('d.m.Y') ?: '-' }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $repair->description }}</p>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Kavejie remonti pagaidam nav atrasti.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Papildskaitli</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Kas izcelas</h2>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl bg-rose-50 p-4 ring-1 ring-rose-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Kritiskie atvertie</div>
                            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $summary['critical_open'] }}</div>
                        </div>
                        <div class="rounded-2xl bg-violet-50 p-4 ring-1 ring-violet-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">Arejie atvertie</div>
                            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $summary['external_open'] }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Atskaite uz</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ \Illuminate\Support\Carbon::parse($summary['today'])->format('d.m.Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Noslodze</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Lietotaji ar remontiem</h2>
                    </div>

                    <div class="space-y-3">
                        @forelse ($assigneeLoad as $entry)
                            @php $user = $entry['user']; @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $user->employee?->full_name ?: 'Lietotajs #' . $user->id }}</div>
                                        <div class="mt-1 text-sm text-slate-500">{{ $user->role }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-semibold text-slate-900">{{ $entry['active_repairs_count'] }}</div>
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Aktivi</div>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-sm text-slate-600">
                                    <span>Pabeigti</span>
                                    <span class="font-semibold text-slate-900">{{ $entry['completed_repairs_count'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Noslodzes statistikai vel nav pietiekamu datu.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Piegadataji</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Arejo remontu partneri</h2>
                    </div>

                    <div class="space-y-3">
                        @forelse ($vendorSummary as $vendor)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $vendor['vendor_name'] }}</div>
                                        <div class="mt-1 text-sm text-slate-500">{{ $vendor['completed_count'] }} pabeigti no {{ $vendor['repairs_count'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-slate-900">{{ number_format($vendor['total_cost'], 2, '.', ' ') }} EUR</div>
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Kopa</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Arejo piegadataju statistika vel nav pieejama.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Ierices</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Ierices ar biezakiem remontiem</h2>
                    </div>

                    <div class="space-y-3">
                        @forelse ($topDevices as $device)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $device->name }}</div>
                                        <div class="mt-1 text-sm text-slate-500">
                                            {{ $device->type?->type_name ?: 'Tips nav noradits' }}
                                            @if ($device->code)
                                                | {{ $device->code }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">{{ $device->visible_repairs_count }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Remontu top iericem vel nav datu.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
