<x-app-layout>
    @php
        $summaryCards = [
            ['label' => 'Notikumi šodien', 'value' => $summary['audit_today'], 'note' => 'Auditora ieraksti', 'tone' => 'sky'],
            ['label' => 'Brīdinājumi 30 dienās', 'value' => $summary['attention_30_days'], 'note' => 'Brīdinājumi, kļūdas un kritiskie ieraksti', 'tone' => 'rose'],
            ['label' => 'Remontu notikumi', 'value' => $summary['repair_events_30_days'], 'note' => $repairScope['label'], 'tone' => 'amber'],
            ['label' => 'Ierīču vēsture', 'value' => $summary['device_history_30_days'], 'note' => 'Izmaiņas pēdējās 30 dienās', 'tone' => 'violet'],
        ];

        $severityClasses = [
            'critical' => 'border-rose-200 bg-rose-50 text-rose-700',
            'error' => 'border-orange-200 bg-orange-50 text-orange-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'info' => 'border-sky-200 bg-sky-50 text-sky-700',
        ];

        $historyActionLabels = [
            'CREATE' => 'Izveide',
            'UPDATE' => 'Atjaunošana',
            'DELETE' => 'Dzēšana',
            'STATUS_CHANGE' => 'Statusa maina',
            'MOVE' => 'Pārvietošana',
            'ASSIGNMENT' => 'Piešķiršana',
            'SET_ADD' => 'Pievienots komplektam',
            'SET_REMOVE' => 'Izņemts no komplekta',
        ];

        $fieldLabels = [
            'status' => 'Statuss',
            'room_id' => 'Telpa',
            'building_id' => 'Ēka',
            'assigned_to' => 'Piešķirts',
            'device_type_id' => 'Ierīces tips',
            'serial_number' => 'Sērijas numurs',
            'name' => 'Nosaukums',
            'model' => 'Modelis',
            'manufacturer' => 'Ražotājs',
            'device_image_url' => 'Attēls',
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-violet-700 ring-1 ring-violet-200">
                        Aktivitātes skats
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Auditora plūsma un izmaiņu vēsture</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Skats uz sistēmas darbību, remonta notikumiem un ierīču vēstures ierakstiem.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('audit-log.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Auditora žurnāls
                    </a>
                    <a href="{{ route('device-history.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Ierīču vēsture
                    </a>
                </div>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                @include('reports.partials.nav')
            </div>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Ātrie rādītāji</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">Aktivitātes un vēstures kopsavilkums</h2>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Notikumu skats</span>
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
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
            <div class="space-y-5 rounded-[2rem] bg-slate-100/80 p-3">
                <div class="grid gap-5 lg:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Darbības</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Kas notiek visbiežāk</h2>
                        </div>
                        <div class="space-y-3">
                            @forelse ($actionBreakdown as $row)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    @php $width = $actionBreakdownMax > 0 ? max(8, (int) round(($row['count'] / $actionBreakdownMax) * 100)) : 8; @endphp
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-500">{{ $row['count'] }}</div>
                                    </div>
                                    <div class="mt-3 h-2.5 rounded-full bg-white ring-1 ring-slate-200">
                                        <div class="h-2.5 rounded-full bg-sky-500" style="width: {{ min(100, $width) }}%;"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Darbību sadalījumam vēl nav datu.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Entītijas</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Par ko notiek darbības</h2>
                        </div>
                        <div class="space-y-3">
                            @forelse ($entityBreakdown as $row)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    @php $width = $entityBreakdownMax > 0 ? max(8, (int) round(($row['count'] / $entityBreakdownMax) * 100)) : 8; @endphp
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-500">{{ $row['count'] }}</div>
                                    </div>
                                    <div class="mt-3 h-2.5 rounded-full bg-white ring-1 ring-slate-200">
                                        <div class="h-2.5 rounded-full bg-violet-500" style="width: {{ min(100, $width) }}%;"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Entītiju sadalījumam vēl nav datu.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Lietotāji</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Aktīvākie autori</h2>
                        </div>
                        <div class="space-y-3">
                            @forelse ($userBreakdown as $row)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    @php $width = $userBreakdownMax > 0 ? max(8, (int) round(($row['count'] / $userBreakdownMax) * 100)) : 8; @endphp
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</div>
                                        <div class="text-sm font-semibold text-slate-500">{{ $row['count'] }}</div>
                                    </div>
                                    <div class="mt-3 h-2.5 rounded-full bg-white ring-1 ring-slate-200">
                                        <div class="h-2.5 rounded-full bg-amber-500" style="width: {{ min(100, $width) }}%;"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Lietotāju sadalījumam vēl nav datu.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Remontu notikumi</p>
                                <h2 class="mt-1 text-xl font-semibold text-slate-900">Pēdējie ieraksti</h2>
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
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Remontu notikumi vēl nav atrasti.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5 border-b border-slate-200 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Ierīču vēsture</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Pēdējās izmaiņas</h2>
                        </div>

                        <div class="space-y-3">
                            @forelse ($deviceHistoryEntries as $entry)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="break-words text-sm font-semibold leading-6 text-slate-900">
                                                {{ $historyActionLabels[$entry->action] ?? str_replace('_', ' ', $entry->action) }}
                                                @if ($entry->device)
                                                    | {{ $entry->device->name }}
                                                @endif
                                                </div>
                                            </div>
                                            @if ($entry->device)
                                                <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">#{{ $entry->device->id }}</span>
                                            @endif
                                        </div>
                                        <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                            <div class="break-words text-sm leading-6 text-slate-600">
                                                @if ($entry->field_changed)
                                                    {{ $fieldLabels[$entry->field_changed] ?? $entry->field_changed }}
                                                    @if ($entry->old_value !== null || $entry->new_value !== null)
                                                        : {{ $entry->old_value ?: '-' }} -> {{ $entry->new_value ?: '-' }}
                                                    @endif
                                                @else
                                                    Izmaiņa bez konkrētas lauka norādes
                                                @endif
                                            </div>
                                        </div>
                                        <div class="break-words text-xs uppercase tracking-[0.18em] text-slate-500">
                                            {{ $entry->timestamp?->format('d.m.Y H:i') ?: '-' }}
                                            @if ($entry->changedBy?->employee)
                                                | {{ $entry->changedBy->employee->full_name }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Ierīču vēstures ieraksti vēl nav atrasti.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5 xl:sticky xl:top-6 xl:self-start rounded-[2rem] bg-violet-50/70 p-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Uzmanība</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Ieraksti ar augstāku svarīgumu</h2>
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
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Paaugstināta svarīguma ieraksti vēl nav atrasti.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
