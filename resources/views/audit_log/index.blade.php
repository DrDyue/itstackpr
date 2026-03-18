<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="audit" size="h-4 w-4" /><span>Administratora modulis</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet"><x-icon name="audit" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Audita zurnals</h1>
                            <p class="page-subtitle">Sistemas darbibas vesture administratoram ar atriem filtriem un kopsavilkuma kartitem.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('audit-log.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Meklet">
            <input type="text" name="action" value="{{ $filters['action'] }}" class="crud-control" placeholder="Action">
            <input type="text" name="severity" value="{{ $filters['severity'] }}" class="crud-control" placeholder="Severity">
            <input type="text" name="entity_type" value="{{ $filters['entity_type'] }}" class="crud-control" placeholder="Entity">
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="crud-control">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="crud-control">
            <div class="toolbar-actions xl:col-span-6">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Filtret</span></button>
                <a href="{{ route('audit-log.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="metric-card metric-card-soft-sky">
                <div class="metric-head"><div class="metric-icon"><x-icon name="audit" size="h-5 w-5" /></div><div class="metric-label">Kopa</div></div>
                <div class="metric-value">{{ $summary['total'] }}</div>
            </div>
            <div class="metric-card metric-card-soft-emerald">
                <div class="metric-head"><div class="metric-icon"><x-icon name="search" size="h-5 w-5" /></div><div class="metric-label">Filtreti</div></div>
                <div class="metric-value">{{ $summary['filtered'] }}</div>
            </div>
            <div class="metric-card metric-card-soft-amber">
                <div class="metric-head"><div class="metric-icon"><x-icon name="calendar" size="h-5 w-5" /></div><div class="metric-label">Sodien</div></div>
                <div class="metric-value">{{ $summary['today'] }}</div>
            </div>
            <div class="metric-card metric-card-soft-rose">
                <div class="metric-head"><div class="metric-icon"><x-icon name="x-circle" size="h-5 w-5" /></div><div class="metric-label">Kritiski</div></div>
                <div class="metric-value">{{ $summary['critical'] }}</div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Laiks</th>
                        <th class="px-4 py-3">Lietotajs</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Entity</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Apraksts</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $log->timestamp?->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $log->user?->full_name ?: 'Sistema' }}</td>
                            <td class="px-4 py-3">{{ $log->action }}</td>
                            <td class="px-4 py-3">{{ $log->entity_type }}</td>
                            <td class="px-4 py-3">
                                <span class="status-pill {{ $log->severity === 'critical' ? 'status-pill-danger' : ($log->severity === 'error' ? 'status-pill-warning' : 'status-pill-neutral') }}">{{ $log->severity }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->localized_description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Audita ierakstu nav.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </section>
</x-app-layout>

