<x-app-layout>
    @php
        $summaryCards = [
            ['label' => 'Notikumi sodien', 'value' => $summary['audit_today'], 'note' => 'Auditora ieraksti', 'tone' => 'sky'],
            ['label' => 'Bridinajumi 30 dienas', 'value' => $summary['attention_30_days'], 'note' => 'Bridinajumi, kludas un kritiskie', 'tone' => 'rose'],
            ['label' => 'Remontu notikumi', 'value' => $summary['repair_events_30_days'], 'note' => $repairScope['label'], 'tone' => 'amber'],
            ['label' => 'Iericu vesture', 'value' => $summary['device_history_30_days'], 'note' => 'Izmainas pedejas 30 dienas', 'tone' => 'violet'],
        ];

        $severityClasses = [
            'critical' => 'border-rose-200 bg-rose-50 text-rose-700',
            'error' => 'border-orange-200 bg-orange-50 text-orange-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'info' => 'border-sky-200 bg-sky-50 text-sky-700',
        ];

        $historyActionLabels = [
            'CREATE' => 'Izveide',
            'UPDATE' => 'Atjaunosana',
            'DELETE' => 'Dzesana',
            'STATUS_CHANGE' => 'Statusa maina',
            'MOVE' => 'Parvietosana',
            'ASSIGNMENT' => 'Pieskirsana',
            'SET_ADD' => 'Pievienots komplektam',
            'SET_REMOVE' => 'Iznemts no komplekta',
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-violet-50 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-violet-700 ring-1 ring-violet-200">
                        Aktivitates skats
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Auditora plusts un izmainu vesture</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Skats uz sistemas darbibu, remonta notikumiem un iericu vestures ierakstiem.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('audit-log.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Auditora zurnals
                    </a>
                    <a href="{{ route('device-history.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Iericu vesture
                    </a>
                </div>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                @include('reports.partials.nav')
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                @php
                    $cardClass = match ($card['tone']) {
                        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
                        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
                        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
                        default => 'border-violet-200 bg-violet-50 text-violet-700',
                    };
                @endphp
                <div class="rounded-3xl border p-5 shadow-sm {{ $cardClass }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em]">{{ $card['label'] }}</p>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $card['value'] }}</div>
                    <p class="mt-2 text-sm text-slate-600">{{ $card['note'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
            <div class="space-y-5">
                <div class="grid gap-5 lg:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Darbibas</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Kas notiek visbiezak</h2>
                        </div>
                        <div class="space-y-3">
                            @forelse ($actionBreakdown as $row)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-500">{{ $row['count'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Darbibu sadalijumam vel nav datu.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Entitates</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Par ko notiek darbibas</h2>
                        </div>
                        <div class="space-y-3">
                            @forelse ($entityBreakdown as $row)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-500">{{ $row['count'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Entitasu sadalijumam vel nav datu.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Lietotaji</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Aktivakie autori</h2>
                        </div>
                        <div class="space-y-3">
                            @forelse ($userBreakdown as $row)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-500">{{ $row['count'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Lietotaju sadalijumam vel nav datu.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Remontu notikumi</p>
                                <h2 class="mt-1 text-xl font-semibold text-slate-900">Pedejie ieraksti</h2>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800 ring-1 ring-amber-200">{{ $repairScope['label'] }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($repairEntries as $entry)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $entry->localized_description }}</div>
                                            <div class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500">
                                                {{ $entry->timestamp?->format('d.m.Y H:i') ?: '-' }}
                                                @if ($entry->user?->employee)
                                                    | {{ $entry->user->employee->full_name }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">#{{ $entry->entity_id }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Remontu notikumi vel nav atrasti.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Iericu vesture</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Pedejas izmainas</h2>
                        </div>

                        <div class="space-y-3">
                            @forelse ($deviceHistoryEntries as $entry)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">
                                                {{ $historyActionLabels[$entry->action] ?? str_replace('_', ' ', $entry->action) }}
                                                @if ($entry->device)
                                                    | {{ $entry->device->name }}
                                                @endif
                                            </div>
                                            <div class="mt-2 text-sm text-slate-600">
                                                @if ($entry->field_changed)
                                                    {{ $entry->field_changed }}
                                                    @if ($entry->old_value !== null || $entry->new_value !== null)
                                                        : {{ $entry->old_value ?: '-' }} -> {{ $entry->new_value ?: '-' }}
                                                    @endif
                                                @else
                                                    Izmaina bez konkretas lauka norades
                                                @endif
                                            </div>
                                            <div class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500">
                                                {{ $entry->timestamp?->format('d.m.Y H:i') ?: '-' }}
                                                @if ($entry->changedBy?->employee)
                                                    | {{ $entry->changedBy->employee->full_name }}
                                                @endif
                                            </div>
                                        </div>
                                        @if ($entry->device)
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">#{{ $entry->device->id }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Iericu vestures ieraksti vel nav atrasti.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Uzmaniba</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Ieraksti ar augstaku svarigumu</h2>
                    </div>

                    <div class="space-y-3">
                        @forelse ($attentionEntries as $entry)
                            @php $severity = $severityClasses[$entry->severity] ?? $severityClasses['info']; @endphp
                            <div class="rounded-3xl border p-4 {{ $severity }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $entry->localized_description }}</div>
                                        <div class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500">
                                            {{ $entry->timestamp?->format('d.m.Y H:i') ?: '-' }}
                                            @if ($entry->user?->employee)
                                                | {{ $entry->user->employee->full_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold ring-1 ring-black/5">{{ strtoupper($entry->severity) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Paaugstinata svariguma ieraksti vel nav atrasti.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
