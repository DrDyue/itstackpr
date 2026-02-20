<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Telpas</h1>
                <p class="text-sm text-gray-500">Telpu saraksts pa ēkām</p>
            </div>
            <a href="{{ route('rooms.create') }}" class="crud-btn-primary-inline">Pievienot telpu</a>
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
                            <th class="px-4 py-3 text-left">&#274;ka</th>
                            <th class="px-4 py-3 text-left">Stāvs</th>
                            <th class="px-4 py-3 text-left">Telpas Nr.</th>
                            <th class="px-4 py-3 text-left">Nosaukums</th>
                            <th class="px-4 py-3 text-left">Atbildīgais</th>
                            <th class="px-4 py-3 text-left">Nodaļa</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rooms as $room)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $room->id }}</td>
                                <td class="px-4 py-3">{{ $room->building?->building_name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $room->floor_number }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $room->room_number }}</td>
                                <td class="px-4 py-3">{{ $room->room_name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $room->employee?->full_name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $room->department ?: '' }}</td>
                                <td class="px-4 py-3">{{ $room->created_at?->format('d.m.Y H:i') ?: '' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('rooms.edit', $room) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <form method="POST" action="{{ route('rooms.destroy', $room) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzēst šo telpu?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">Telpas vēl nav pievienotas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


