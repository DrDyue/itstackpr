<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="repair" size="h-4 w-4" /><span>Serviss</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="repair" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Remonti</h1>
                            <p class="page-subtitle">{{ $canManageRepairs ? 'Faktiskie remontdarbi pec apstiprinatiem pieteikumiem.' : 'Tavu iericu remontu statuss.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageRepairs)
                    <a href="{{ route('repairs.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns remonts</span></a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-4">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <select name="status" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Prioritate</span>
                <select name="priority" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <select name="repair_type" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($repairTypes as $repairType)
                        <option value="{{ $repairType }}" @selected($filters['repair_type'] === $repairType)>{{ $typeLabels[$repairType] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="toolbar-actions md:col-span-4">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('repairs.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Ierice</th>
                        <th class="px-4 py-3">Apraksts</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Prioritate</th>
                        <th class="px-4 py-3">Apstiprinaja</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($repairs as $repair)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $repair->device?->name ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $repair->device?->code ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $repair->description }}</td>
                            <td class="px-4 py-3"><span class="status-pill {{ $repair->status === 'completed' ? 'status-pill-success' : ($repair->status === 'cancelled' ? 'status-pill-danger' : 'status-pill-warning') }}">{{ $statusLabels[$repair->status] ?? $repair->status }}</span></td>
                            <td class="px-4 py-3"><span class="status-pill {{ $repair->priority === 'critical' ? 'status-pill-danger' : ($repair->priority === 'high' ? 'status-pill-warning' : 'status-pill-neutral') }}">{{ $priorityLabels[$repair->priority] ?? $repair->priority }}</span></td>
                            <td class="px-4 py-3">{{ $repair->acceptedBy?->full_name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($canManageRepairs)
                                    <a href="{{ route('repairs.edit', $repair) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediget</span></a>
                                @else
                                    <span class="text-slate-400">Tikai apskate</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Remonti nav atrasti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $repairs->links() }}
    </section>
</x-app-layout>

