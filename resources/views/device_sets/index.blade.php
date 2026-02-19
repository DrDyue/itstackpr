<x-app-layout>
    @php
        $statusLabels = [
            'draft' => 'Melnraksts',
            'active' => 'Aktīvs',
            'returned' => 'Atgriezts',
            'archived' => 'Arhivēts',
        ];
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Komplekti</h1>
                <p class="text-sm text-gray-500">Ierīču komplektu pārvaldība</p>
            </div>
            <a href="{{ route('device-sets.create') }}" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Pievienot komplektu</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nosaukums</th>
                            <th class="px-4 py-3 text-left">Statuss</th>
                            <th class="px-4 py-3 text-left">Telpa</th>
                            <th class="px-4 py-3 text-left">Atbildīgais</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sets as $set)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $set->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $set->set_name }}</td>
                                <td class="px-4 py-3">{{ $statusLabels[$set->status] ?? $set->status }}</td>
                                <td class="px-4 py-3">{{ $set->room?->room_number ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $set->assigned_to ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $set->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <a href="{{ route('device-sets.edit', $set) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <a href="{{ route('device-sets.edit', $set) }}#add-device-form" class="text-emerald-600 hover:text-emerald-700">Pievienot ierīci</a>
                                        <form method="POST" action="{{ route('device-sets.destroy', $set) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzēst šo komplektu?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Komplekti vēl nav pievienoti.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>
