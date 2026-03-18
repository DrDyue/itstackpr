<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="room" size="h-4 w-4" /><span>Telpu parvaldiba</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-slate"><x-icon name="room" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Telpas</h1>
                            <p class="page-subtitle">Telpu saraksts ar atbildigajiem lietotajiem un piesaisti ekam.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('rooms.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauna telpa</span></a>
            </div>
        </div>

        <form method="GET" action="{{ route('rooms.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-5">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Telpa, eka, nodala, atbildigais...">
            </label>
            <label class="block">
                <span class="crud-label">Eka</span>
                <select name="building_id" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected($filters['building_id'] == $building->id)>{{ $building->building_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Nodala</span>
                <select name="department" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected($filters['department'] === $department)>{{ $department }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Atbildigais</span>
                <select name="user_id" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($responsibleUsers as $responsibleUser)
                        <option value="{{ $responsibleUser->id }}" @selected($filters['user_id'] == $responsibleUser->id)>{{ $responsibleUser->full_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Ierices telpa</span>
                <select name="has_devices" class="crud-control">
                    <option value="">Visas</option>
                    <option value="1" @selected($filters['has_devices'] === '1')>Ar iericem</option>
                    <option value="0" @selected($filters['has_devices'] === '0')>Bez iericem</option>
                </select>
            </label>
            <div class="toolbar-actions md:col-span-5">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('rooms.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Eka', 'value' => $filters['building_id'] !== '' ? optional($buildings->firstWhere('id', (int) $filters['building_id']))->building_name : null],
                ['label' => 'Nodala', 'value' => $filters['department']],
                ['label' => 'Atbildigais', 'value' => $filters['user_id'] !== '' ? optional($responsibleUsers->firstWhere('id', (int) $filters['user_id']))->full_name : null],
                ['label' => 'Ierices telpa', 'value' => $filters['has_devices'] === '1' ? 'Ar iericem' : ($filters['has_devices'] === '0' ? 'Bez iericem' : null)],
            ]"
            :clear-url="route('rooms.index')"
        />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Eka</th>
                        <th class="px-4 py-3">Stavs</th>
                        <th class="px-4 py-3">Numurs</th>
                        <th class="px-4 py-3">Nosaukums</th>
                        <th class="px-4 py-3">Nodala</th>
                        <th class="px-4 py-3">Atbildigais</th>
                        <th class="px-4 py-3">Ierices</th>
                        <th class="px-4 py-3">Piezimes</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $room->building?->building_name }}</td>
                            <td class="px-4 py-3">{{ $room->floor_number }}</td>
                            <td class="px-4 py-3">{{ $room->room_number }}</td>
                            <td class="px-4 py-3">{{ $room->room_name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $room->department ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $room->user?->full_name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $room->devices_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $room->notes ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('rooms.edit', $room) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediget</span></a>
                                    <form method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('Dzest so telpu?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger"><x-icon name="trash" size="h-4 w-4" /><span>Dzest</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">Telpas vel nav pievienotas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $rooms->links() }}
    </section>
</x-app-layout>

