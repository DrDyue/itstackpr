<x-app-layout>
    @php
        $formatDateTime = function ($value, string $fallback = '-'): string {
            return $value ? $value->timezone(config('app.timezone'))->format('d.m.Y H:i:s') : $fallback;
        };

        $severityTone = fn (string $severity) => match ($severity) {
            'critical' => 'bg-rose-100 text-rose-700 ring-rose-200',
            'error' => 'bg-orange-100 text-orange-700 ring-orange-200',
            'warning' => 'bg-amber-100 text-amber-700 ring-amber-200',
            default => 'bg-sky-100 text-sky-700 ring-sky-200',
        };

        $severityLabel = fn (string $severity) => \App\Support\AuditTrail::severityLabel($severity);

        $actionTone = fn (string $action) => match ($action) {
            'DELETE' => 'bg-rose-100 text-rose-700',
            'RESTORE' => 'bg-amber-100 text-amber-700',
            'LOGIN', 'LOGOUT' => 'bg-emerald-100 text-emerald-700',
            'BACKUP', 'EXPORT' => 'bg-violet-100 text-violet-700',
            default => 'bg-slate-100 text-slate-700',
        };

        $actionLabel = fn (string $action) => \App\Support\AuditTrail::actionLabel($action);

        $queryLink = function (array $overrides = []) use ($filters): string {
            return route('audit-log.index', array_filter(array_merge($filters, $overrides), fn ($value) => $value !== ''));
        };
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Audita zurnals</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Parsiets notikumu zurnals ar lietotajiem, darbibam, entitijam un svariguma pakapi.
                </p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="flex h-full flex-col justify-between rounded-[1.75rem] border border-sky-100 bg-sky-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Visi ieraksti</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    </div>
                </div>
                <p class="mt-5 rounded-2xl bg-white/80 px-3 py-2 text-sm text-slate-600 ring-1 ring-sky-100">Filtra atrasti: {{ $summary['filtered'] }}</p>
            </div>

            <div class="flex h-full flex-col justify-between rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Sodien</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $summary['today'] }}</p>
                    </div>
                </div>
                <p class="mt-5 rounded-2xl bg-white/80 px-3 py-2 text-sm text-slate-600 ring-1 ring-emerald-100">Tikai siodienas notikumi.</p>
            </div>

            <div class="flex h-full flex-col justify-between rounded-[1.75rem] border border-violet-100 bg-violet-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Aktivi lietotaji</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $summary['active_users'] }}</p>
                    </div>
                </div>
                <p class="mt-5 rounded-2xl bg-white/80 px-3 py-2 text-sm text-slate-600 ring-1 ring-violet-100">Unikāli lietotāji auditā.</p>
            </div>

            <div class="flex h-full flex-col justify-between rounded-[1.75rem] border border-amber-100 bg-amber-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 2.25-2.25m0 0L21 6.75m-2.25-2.25V15a3 3 0 0 1-3 3h-9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m7.5 17.25-2.25 2.25m0 0L3 17.25m2.25 2.25V9a3 3 0 0 1 3-3h9"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pedejais notikums</p>
                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-900">{{ $formatDateTime($summary['latest']?->timestamp, 'Nav datu') }}</p>
                    </div>
                </div>
                <p class="mt-5 rounded-2xl bg-white/80 px-3 py-2 text-sm text-slate-600 ring-1 ring-amber-100">{{ $summary['latest'] ? $actionLabel($summary['latest']->action) : 'Nav notikumu' }}</p>
            </div>
        </div>

        <div id="audit-history" class="mb-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Filtri un meklešana</h2>
                    <p class="mt-1 text-sm text-slate-500">Atrodi notikumus pec darbibas, entitijas, svariguma, datuma vai apraksta.</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">Atrasti ieraksti: {{ $summary['filtered'] }}</div>
            </div>

            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ $queryLink(['severity' => '']) }}" class="user-role-chip {{ $filters['severity'] === '' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Visi svarigumi</a>
                <a href="{{ $queryLink(['severity' => 'info']) }}" class="user-role-chip {{ $filters['severity'] === 'info' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Informacija</a>
                <a href="{{ $queryLink(['severity' => 'warning']) }}" class="user-role-chip {{ $filters['severity'] === 'warning' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Bridinajums</a>
                <a href="{{ $queryLink(['severity' => 'error']) }}" class="user-role-chip {{ $filters['severity'] === 'error' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Kluda</a>
                <a href="{{ $queryLink(['severity' => 'critical']) }}" class="user-role-chip {{ $filters['severity'] === 'critical' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Kritisks</a>
            </div>

            <form method="GET" action="{{ route('audit-log.index') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_auto]">
                <label class="block">
                    <span class="user-filter-label">Meklet</span>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Apraksts, entitija vai ID" class="crud-control pl-10">
                    </div>
                </label>
                <label class="block">
                    <span class="user-filter-label">Darbiba</span>
                    <select name="action" class="crud-control">
                        <option value="">Visas</option>
                        @foreach (['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'EXPORT', 'BACKUP', 'RESTORE', 'VIEW'] as $action)
                            <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="user-filter-label">Svarigums</span>
                    <select name="severity" class="crud-control">
                        <option value="">Visi</option>
                        @foreach (['info', 'warning', 'error', 'critical'] as $severity)
                            <option value="{{ $severity }}" @selected($filters['severity'] === $severity)>{{ $severityLabel($severity) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="user-filter-label">Entitija</span>
                    <select name="entity_type" class="crud-control">
                        <option value="">Visas</option>
                        @foreach ($entityTypes as $entityType)
                            <option value="{{ $entityType }}" @selected($filters['entity_type'] === $entityType)>{{ \App\Support\AuditTrail::entityLabel($entityType) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="user-filter-label">No datuma</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="user-filter-label">Lidz datumam</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="crud-control">
                </label>
                <div class="flex items-end gap-2">
                    <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        <span>Filtrēt</span>
                    </button>
                    <a href="{{ route('audit-log.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                        <span>Notirit</span>
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-[0.18em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Laiks</th>
                            <th class="px-4 py-3 text-left">Lietotajs</th>
                            <th class="px-4 py-3 text-left">Darbiba</th>
                            <th class="px-4 py-3 text-left">Tabula</th>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Svarigums</th>
                            <th class="px-4 py-3 text-left">Apraksts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ $formatDateTime($log->timestamp) }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ $log->id ? 'Ieraksts #' . $log->id : '-' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ $log->user?->employee?->full_name ?? 'Sistema / nezinams' }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ $log->user_id ? 'Lietotaja ID ' . $log->user_id : 'Bez lietotaja' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="{{ $actionTone($log->action) }} inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold">
                                        @if ($log->action === 'UPDATE')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 2.25-2.25m0 0L21 6.75m-2.25-2.25V15a3 3 0 0 1-3 3h-9"/><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 17.25-2.25 2.25m0 0L3 17.25m2.25 2.25V9a3 3 0 0 1 3-3h9"/></svg>
                                        @elseif ($log->action === 'DELETE')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.11 0 0 0-7.5 0"/></svg>
                                        @elseif ($log->action === 'CREATE')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        @elseif ($log->action === 'LOGIN')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 15l3-3m0 0-3-3m3 3H9"/></svg>
                                        @elseif ($log->action === 'LOGOUT')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0 3-3m-3 3 3 3"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                                        @endif
                                        <span>{{ $actionLabel($log->action) }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ $log->localized_entity_type }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $log->entity_id ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="{{ $severityTone($log->severity) }} inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold ring-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            @if ($log->severity === 'critical')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2.25 2.25 0 0 0 1.93 3.375h16.5A2.25 2.25 0 0 0 22.18 18L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z"/>
                                            @elseif ($log->severity === 'error')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75a8.25 8.25 0 1 0 0 16.5 8.25 8.25 0 0 0 0-16.5Z"/>
                                            @elseif ($log->severity === 'warning')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2.25 2.25 0 0 0 1.93 3.375h16.5A2.25 2.25 0 0 0 22.18 18L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25 12 11.25v5.25h.75m-3.75 0h6a2.25 2.25 0 0 0 2.25-2.25v-6A2.25 2.25 0 0 0 15 6h-6A2.25 2.25 0 0 0 6.75 8.25v6A2.25 2.25 0 0 0 9 16.5Z"/>
                                            @endif
                                        </svg>
                                        <span>{{ $severityLabel($log->severity) }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="max-w-xl leading-6 text-slate-700">{{ $log->localized_description }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">Zurnals ir tukss vai pec sietiem nekas netika atrasts.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($logs->hasPages())
            <div class="mt-5">{{ $logs->links() }}</div>
        @endif
    </section>
</x-app-layout>
