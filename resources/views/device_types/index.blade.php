<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Ierīču tipi</h1>
                <p class="text-sm text-gray-500">Tipu klasifikācija un dzīves cikls</p>
            </div>
            <a href="{{ route('device-types.create') }}" class="crud-btn-primary-inline">Pievienot tipu</a>
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
                            <th class="px-4 py-3 text-left">Tipa nosaukums</th>
                            <th class="px-4 py-3 text-left">Kategorija</th>
                            <th class="px-4 py-3 text-left">Kalpošanas gadi</th>
                            <th class="px-4 py-3 text-left">Ikona</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($types as $type)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $type->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $type->type_name }}</td>
                                <td class="px-4 py-3">{{ $type->category }}</td>
                                <td class="px-4 py-3">{{ $type->expected_lifetime_years ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $type->icon_name ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $type->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('device-types.edit', $type) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <form method="POST" action="{{ route('device-types.destroy', $type) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzēst šo tipu?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Ierīču tipu vēl nav.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


