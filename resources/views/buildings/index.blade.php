{{--
    Lapa: Ēku saraksts.
    Atbildība: rāda visas ēkas, kurās sistēmā tiek organizētas telpas un ierīces.
    Datu avots: BuildingController@index.
    Galvenās daļas:
    1. Hero un darbības.
    2. Filtri pēc meklēšanas un pilsētas.
    3. Ēku tabula ar telpu un ierīču skaitu.
--}}
<x-app-layout>
    @php
        $cityOptions = collect($cities)->map(fn ($city) => [
            'value' => (string) $city,
            'label' => (string) $city,
            'description' => 'Filtrs pēc pilsētas',
            'search' => (string) $city,
        ])->values();
    @endphp
    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="building" size="h-4 w-4" /><span>Infrastruktura</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-slate"><x-icon name="building" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Ēkas</h1>
                            <p class="page-subtitle">Ēku saraksts un pamata dati ar ātru pieeju telpu pārvaldībai.</p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="{{ route('rooms.create') }}" class="btn-view"><x-icon name="room" size="h-4 w-4" /><span>Pievienot telpu</span></a>
                    <a href="{{ route('buildings.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Pievienot ēku</span></a>
                </div>
            </div>
        </div>

        <div id="buildings-index-root" data-async-table-root>
        <form method="GET" action="{{ route('buildings.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]" data-async-table-form data-async-root="#buildings-index-root" data-search-endpoint="{{ route('buildings.find-by-name') }}">
            <label class="block">
                <span class="crud-label">Ēkas nosaukums</span>
                <div class="flex items-center gap-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="crud-control" placeholder="Ievadi ēkas nosaukumu" data-async-manual="true" data-table-manual-search="true" data-search-mode="contains">
                    <button type="submit" class="btn-search shrink-0" data-table-search-submit="true"><x-icon name="search" size="h-4 w-4" /><span>Meklēt</span></button>
                </div>
            </label>
            <label class="block">
                <span class="crud-label">Pilsēta</span>
                <x-searchable-select
                    name="city"
                    query-name="city_query"
                    identifier="building-city-filter"
                    :options="$cityOptions"
                    :selected="$filters['city']"
                    :query="$filters['city']"
                    placeholder="Izvēlies pilsētu"
                    empty-message="Neviena pilsēta neatbilst meklējumam."
                />
            </label>
            <div class="toolbar-actions md:col-span-2">
                <a href="{{ route('buildings.index') }}" class="btn-clear" data-async-link="true"><x-icon name="clear" size="h-4 w-4" /><span>Notīrīt</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Pilsēta', 'value' => $filters['city']],
            ]"
            :clear-url="route('buildings.index')"
        />

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="app-table-shell">
            <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                <table class="app-table-content app-table-content-compact min-w-full text-sm">
                    <thead class="app-table-head bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Nosaukums</th>
                            <th class="px-4 py-3 text-left">Resursi</th>
                            <th class="px-4 py-3 text-left">Pilsēta</th>
                            <th class="px-4 py-3 text-left">Adrese</th>
                            <th class="px-4 py-3 text-left">Stavu skaits</th>
                            <th class="px-4 py-3 text-left">Piezīmes</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($buildings as $building)
                            <tr class="app-table-row" data-table-row-id="building-{{ $building->id }}" data-table-search-value="{{ \Illuminate\Support\Str::lower(trim((string) $building->building_name)) }}">
                                <td class="px-4 py-3 app-table-cell-strong">{{ $building->building_name }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>Telpas: {{ $building->rooms_count }}</div>
                                    <div>Ierīces: {{ $building->devices_count }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $building->city ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $building->address ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $building->total_floors ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $building->notes ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $building->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('buildings.edit', $building) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediģēt</span></a>
                                        <form
                                            method="POST"
                                            action="{{ route('buildings.destroy', $building) }}"
                                            data-app-confirm-title="Dzēst ēku?"
                                            data-app-confirm-message="Vai tiešām dzēst šo ēku?"
                                            data-app-confirm-accept="Jā, dzēst"
                                            data-app-confirm-cancel="Nē"
                                            data-app-confirm-tone="danger"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger"><x-icon name="trash" size="h-4 w-4" /><span>Dzēst</span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">Ēkas vēl nav pievienotas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $buildings->links() }}
        </div>
    </section>
</x-app-layout>
