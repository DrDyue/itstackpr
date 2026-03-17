<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Audita zurnals</h1>
            <p class="mt-2 text-sm text-slate-600">Sistēmas darbību vēsture administratoram.</p>
        </div>

        <form method="GET" action="{{ route('audit-log.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3 xl:grid-cols-6">
            <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Meklet">
            <input type="text" name="action" value="{{ $filters['action'] }}" class="crud-control" placeholder="Action">
            <input type="text" name="severity" value="{{ $filters['severity'] }}" class="crud-control" placeholder="Severity">
            <input type="text" name="entity_type" value="{{ $filters['entity_type'] }}" class="crud-control" placeholder="Entity">
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="crud-control">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="crud-control">
            <div class="xl:col-span-6 flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Filtrēt</button>
                <a href="{{ route('audit-log.index') }}" class="crud-btn-secondary">Notirit</a>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Kopā</div><div class="mt-2 text-3xl font-semibold">{{ $summary['total'] }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Filtrēti</div><div class="mt-2 text-3xl font-semibold">{{ $summary['filtered'] }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Šodien</div><div class="mt-2 text-3xl font-semibold">{{ $summary['today'] }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Kritiski</div><div class="mt-2 text-3xl font-semibold">{{ $summary['critical'] }}</div></div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                            <td class="px-4 py-3">{{ $log->severity }}</td>
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
