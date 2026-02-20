<x-app-layout>
    @php
        $statusLabels = [
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
        $typeLabels = [
            'internal' => 'Iekšējais',
            'external' => '&#256;rējais',
        ];
        $priorityLabels = [
            'low' => 'Zema',
            'medium' => 'Vidēja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Remonti</h1>
                <p class="text-sm text-gray-500">Remontdarbu uzskaite un statuss</p>
            </div>
            <a href="{{ route('repairs.create') }}" class="crud-btn-primary-inline">Pievienot remontu</a>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Meklēt pēc apraksta, piegādātāja vai rēķina numura..." class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Meklēt</button>
                <a href="{{ route('repairs.index') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Notīrīt</a>
            </div>
        </form>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Ierīce</th>
                            <th class="px-4 py-3 text-left">Statuss</th>
                            <th class="px-4 py-3 text-left">Tips</th>
                            <th class="px-4 py-3 text-left">Prioritāte</th>
                            <th class="px-4 py-3 text-left">Sākums</th>
                            <th class="px-4 py-3 text-left">Plānotais beigums</th>
                            <th class="px-4 py-3 text-left">Izmaksas</th>
                            <th class="px-4 py-3 text-left">Piegādātājs</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($repairs as $repair)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">#{{ $repair->id }}</td>
                                <td class="px-4 py-3">{{ $repair->device?->code ?: '' }} {{ $repair->device?->name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $statusLabels[$repair->status] ?? ($repair->status ?: '') }}</td>
                                <td class="px-4 py-3">{{ $typeLabels[$repair->repair_type] ?? ($repair->repair_type ?: '') }}</td>
                                <td class="px-4 py-3">{{ $priorityLabels[$repair->priority] ?? ($repair->priority ?: '') }}</td>
                                <td class="px-4 py-3">{{ $repair->start_date ? \Carbon\Carbon::parse($repair->start_date)->format('d.m.Y') : '' }}</td>
                                <td class="px-4 py-3">{{ $repair->estimated_completion ? \Carbon\Carbon::parse($repair->estimated_completion)->format('d.m.Y') : '' }}</td>
                                <td class="px-4 py-3">{{ $repair->cost !== null ? number_format((float) $repair->cost, 2) . ' EUR' : '' }}</td>
                                <td class="px-4 py-3">{{ $repair->vendor_name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $repair->created_at?->format('d.m.Y H:i') ?: '' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('repairs.edit', $repair) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <form method="POST" action="{{ route('repairs.destroy', $repair) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzēst šo remontu?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">Remontdarbu vēl nav.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


