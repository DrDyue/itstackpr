<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Komplektu pozīcijas</h1>
                <p class="text-sm text-gray-500">Ierīces, kas pievienotas komplektiem</p>
            </div>
            <a href="{{ route('device-set-items.create') }}" class="crud-btn-primary-inline">Pievienot pozīciju</a>
        </div>

        <form method="GET" action="{{ route('device-set-items.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <input type="text" name="device_set_id" value="{{ $deviceSetId }}" placeholder="Filtrs pēc komplekta ID" class="w-full max-w-xs rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Filtrēt</button>
                <a href="{{ route('device-set-items.index') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Notīrīt</a>
            </div>
        </form>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Komplekts</th>
                            <th class="px-4 py-3 text-left">Ierīce</th>
                            <th class="px-4 py-3 text-left">Daudzums</th>
                            <th class="px-4 py-3 text-left">Loma</th>
                            <th class="px-4 py-3 text-left">Apraksts</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $item->id }}</td>
                                <td class="px-4 py-3">{{ $item->deviceSet?->set_name ?? $item->deviceSet?->name }}</td>
                                <td class="px-4 py-3">{{ $item->device?->name }} ({{ $item->device?->code }})</td>
                                <td class="px-4 py-3">{{ $item->quantity ?? 1 }}</td>
                                <td class="px-4 py-3">{{ $item->role ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $item->description ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $item->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('device-set-items.edit', $item) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <form method="POST" action="{{ route('device-set-items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Noņemt šo pozīciju?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Pozīciju vēl nav.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


