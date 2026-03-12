<x-app-layout>
    @php
        $statusLabels = [
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
        $statusClasses = [
            'waiting' => 'bg-amber-100 text-amber-800',
            'in-progress' => 'bg-sky-100 text-sky-800',
            'completed' => 'bg-emerald-100 text-emerald-800',
            'cancelled' => 'bg-slate-100 text-slate-700',
        ];
        $typeLabels = [
            'internal' => 'Ieksejais',
            'external' => 'Arejais',
        ];
        $typeClasses = [
            'internal' => 'bg-violet-100 text-violet-800',
            'external' => 'bg-rose-100 text-rose-800',
        ];
        $priorityLabels = [
            'low' => 'Zema',
            'medium' => 'Videja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
    @endphp

    <section class="repair-shell">
        <div class="repair-header">
            <div>
                <h1 class="device-page-title">Remonti</h1>
                <p class="device-page-subtitle">Ieksejo un arejo remontu uzskaite ar statusiem un terminiem.</p>
            </div>
            <a href="{{ route('repairs.create') }}" class="crud-btn-primary-inline">Pievienot remontu</a>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="repair-toolbar">
            <div class="repair-search-grid">
                <label class="block">
                    <span class="repair-filter-label">Meklesana</span>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Apraksts, piegadatajs vai rekina numurs" class="crud-control">
                </label>
                <div class="repair-filter-actions">
                    <button type="submit" class="crud-btn-primary">Meklet</button>
                    <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Notirit</a>
                </div>
            </div>
        </form>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="repair-table-wrap">
            <div class="overflow-x-auto">
                <table class="repair-table">
                    <thead class="repair-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Ierice</th>
                            <th class="px-4 py-3 text-left">Statuss</th>
                            <th class="px-4 py-3 text-left">Tips</th>
                            <th class="px-4 py-3 text-left">Prioritate</th>
                            <th class="px-4 py-3 text-left">Sakums</th>
                            <th class="px-4 py-3 text-left">Planotais beigums</th>
                            <th class="px-4 py-3 text-left">Izmaksas</th>
                            <th class="px-4 py-3 text-left">Piegadatajs</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="repair-table-body">
                        @forelse ($repairs as $repair)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-500">#{{ $repair->id }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $repair->device?->code ?: '' }} {{ $repair->device?->name ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="repair-badge {{ $statusClasses[$repair->status ?? 'waiting'] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabels[$repair->status] ?? ($repair->status ?: '') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="repair-badge {{ $typeClasses[$repair->repair_type] ?? 'bg-slate-100 text-slate-700' }}">{{ $typeLabels[$repair->repair_type] ?? ($repair->repair_type ?: '') }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $priorityLabels[$repair->priority] ?? ($repair->priority ?: '') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $repair->start_date?->format('d.m.Y') ?: '' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $repair->estimated_completion?->format('d.m.Y') ?: '' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $repair->cost !== null ? number_format((float) $repair->cost, 2) . ' EUR' : '' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $repair->vendor_name ?: ($repair->repair_type === 'internal' ? 'Ieksejais' : '') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $repair->created_at?->format('d.m.Y H:i') ?: '' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="repair-actions-row">
                                        <a href="{{ route('repairs.edit', $repair) }}" class="repair-action repair-action-edit">Rediget</a>
                                        <form method="POST" action="{{ route('repairs.destroy', $repair) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzest so remontu?')" class="repair-action repair-action-delete">Dzest</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-10 text-center text-sm text-slate-500">Remontdarbu vel nav.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>
