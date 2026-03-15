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
            <a href="{{ route('repairs.create') }}" class="crud-btn-primary-inline inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Pievienot remontu
            </a>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="repair-toolbar">
            <div class="repair-search-grid">
                <label class="block">
                    <span class="repair-filter-label">Meklesana</span>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Apraksts, piegadatajs vai rekina numurs" class="crud-control">
                </label>
                <div class="repair-filter-actions">
                    <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        Meklet
                    </button>
                    <a href="{{ route('repairs.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                        Notirit
                    </a>
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
                                        <a href="{{ route('repairs.edit', $repair) }}" class="repair-action repair-action-edit inline-flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5"/>
                                            </svg>
                                            Rediget
                                        </a>
                                        <form method="POST" action="{{ route('repairs.destroy', $repair) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzest so remontu?')" class="repair-action repair-action-delete inline-flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                                Dzest
                                            </button>
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
