<x-app-layout>
    @php
        $cityOptions = collect($cities)->map(fn ($city) => [
            'value' => (string) $city,
            'label' => (string) $city,
            'description' => 'Filtrs pec pilsetas',
            'search' => (string) $city,
        ])->values();
    @endphp
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="building" size="h-4 w-4" /><span>Infrastruktura</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-slate"><x-icon name="building" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Ekas</h1>
                            <p class="page-subtitle">Eku saraksts un pamata dati ar atru pieeju telpu parvaldibai.</p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="{{ route('rooms.create') }}" class="btn-view"><x-icon name="room" size="h-4 w-4" /><span>Pievienot telpu</span></a>
                    <a href="{{ route('buildings.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Pievienot eku</span></a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('buildings.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-2">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, adrese vai piezimes...">
            </label>
            <label class="block">
                <span class="crud-label">Pilseta</span>
                <x-searchable-select
                    name="city"
                    query-name="city_query"
                    identifier="building-city-filter"
                    :options="$cityOptions"
                    :selected="$filters['city']"
                    :query="$filters['city']"
                    placeholder="Izvelies pilsetu"
                    empty-message="Neviena pilseta neatbilst meklejumam."
                />
            </label>
            <div class="toolbar-actions md:col-span-2">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('buildings.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Pilseta', 'value' => $filters['city']],
            ]"
            :clear-url="route('buildings.index')"
        />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Nosaukums</th>
                            <th class="px-4 py-3 text-left">Resursi</th>
                            <th class="px-4 py-3 text-left">Pilseta</th>
                            <th class="px-4 py-3 text-left">Adrese</th>
                            <th class="px-4 py-3 text-left">Stavu skaits</th>
                            <th class="px-4 py-3 text-left">Piezimes</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($buildings as $building)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $building->building_name }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>Telpas: {{ $building->rooms_count }}</div>
                                    <div>Ierices: {{ $building->devices_count }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $building->city ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $building->address ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $building->total_floors ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $building->notes ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $building->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('buildings.edit', $building) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediget</span></a>
                                        <form method="POST" action="{{ route('buildings.destroy', $building) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzest so eku?')" class="btn-danger"><x-icon name="trash" size="h-4 w-4" /><span>Dzest</span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">Ekas vel nav pievienotas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $buildings->links() }}
    </section>
</x-app-layout>
