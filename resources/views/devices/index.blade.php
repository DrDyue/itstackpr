<x-app-layout>
    @php
        $statusLabels = [
            'active' => 'Aktīva',
            'reserve' => 'Rezervē',
            'broken' => 'Bojāta',
            'repair' => 'Remontā',
            'retired' => 'Norakstīta',
            'kitting' => 'Komplektācijā',
        ];
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Ierīces</h1>
                <p class="text-sm text-gray-500">Inventāra vienību saraksts</p>
            </div>
            <a href="{{ route('devices.create') }}" class="crud-btn-primary-inline">Pievienot ierīci</a>
        </div>

        <form method="GET" action="{{ route('devices.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Meklēt pēc koda, nosaukuma vai sērijas numura..." class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Meklēt</button>
                <a href="{{ route('devices.index') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Notīrīt</a>
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
                            <th class="px-4 py-3 text-left">Kods</th>
                            <th class="px-4 py-3 text-left">Nosaukums</th>
                            <th class="px-4 py-3 text-left">Tips</th>
                            <th class="px-4 py-3 text-left">Statuss</th>
                            <th class="px-4 py-3 text-left">&#274;ka</th>
                            <th class="px-4 py-3 text-left">Telpa</th>
                            <th class="px-4 py-3 text-left">Sērijas Nr.</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($devices as $device)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $device->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $device->code ?: '' }}</td>
                                <td class="px-4 py-3">{{ $device->name }}</td>
                                <td class="px-4 py-3">{{ $device->type?->type_name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $statusLabels[$device->status] ?? $device->status }}</td>
                                <td class="px-4 py-3">{{ $device->building?->building_name ?: '' }}</td>
                                <td class="px-4 py-3">{{ $device->room?->room_number ?: '' }}</td>
                                <td class="px-4 py-3">{{ $device->serial_number ?: '' }}</td>
                                <td class="px-4 py-3">{{ $device->created_at?->format('d.m.Y H:i') ?: '' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('devices.edit', $device) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <form method="POST" action="{{ route('devices.destroy', $device) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzēst šo ierīci?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">Ierīces vēl nav pievienotas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


