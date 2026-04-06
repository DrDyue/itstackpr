{{--
    Lapa: Telpu saraksts.
    Atbildība: rāda visas telpas, to ēkas, atbildīgos un ierīču skaitu.
    Datu avots: RoomController@index.
    Galvenās daļas:
    1. Hero ar kopsavilkumu.
    2. Filtri pēc ēkas, stāva un atbildīgā.
    3. Galvenā telpu tabula.
--}}
<x-app-layout>
    @php
        $buildingOptions = $buildings->map(fn ($building) => [
            'value' => (string) $building->id,
            'label' => $building->building_name,
            'description' => $building->city ?: '',
            'search' => implode(' ', array_filter([$building->building_name, $building->city, $building->address])),
        ])->values();
        $floorOptions = collect($floors)->map(fn ($floor) => [
            'value' => (string) $floor,
            'label' => $floor . '. stāvs',
            'description' => 'Filtrs pēc stāva',
            'search' => $floor . ' ' . $floor . '. stāvs',
        ])->values();
        $userOptions = $responsibleUsers->map(fn ($responsibleUser) => [
            'value' => (string) $responsibleUser->id,
            'label' => $responsibleUser->full_name,
            'description' => $responsibleUser->job_title ?: '',
            'search' => implode(' ', array_filter([$responsibleUser->full_name, $responsibleUser->job_title, $responsibleUser->email])),
        ])->values();
        $selectedBuildingLabel = optional($buildings->firstWhere('id', (int) $filters['building_id']))->building_name;
        $selectedFloorLabel = $filters['floor'] !== ''
            ? ($filters['floor'] . '. stāvs')
            : ($filters['floor_query'] !== '' ? $filters['floor_query'] : null);
        $selectedUserLabel = optional($responsibleUsers->firstWhere('id', (int) $filters['user_id']))->full_name;
    @endphp
    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow"><x-icon name="room" size="h-4 w-4" /><span>Telpu pārvaldība</span></div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="room" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $roomSummary['total'] }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-slate"><x-icon name="room" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Telpas</h1>
                            <p class="page-subtitle">Telpu saraksts ar atbildīgajiem lietotājiem un piesaisti ēkām.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('rooms.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauna telpa</span></a>
            </div>
        </div>

        <div id="rooms-index-root" data-async-table-root>
        <form method="GET" action="{{ route('rooms.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-2 lg:grid-cols-4" data-async-table-form data-async-root="#rooms-index-root" data-search-endpoint="{{ route('rooms.find-by-name') }}">
            <label class="block">
                <span class="crud-label">Telpas nosaukums</span>
                <div class="flex items-center gap-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="crud-control" placeholder="Nosaukums vai numurs" data-async-manual="true" data-table-manual-search="true" data-search-mode="contains">
                    <button type="submit" class="btn-search shrink-0" data-table-search-submit="true"><x-icon name="search" size="h-4 w-4" /><span>Meklēt</span></button>
                </div>
            </label>
            <label class="block">
                <span class="crud-label">Ēka</span>
                <x-searchable-select
                    name="building_id"
                    query-name="building_query"
                    identifier="room-building-filter"
                    :options="$buildingOptions"
                    :selected="$filters['building_id']"
                    :query="$selectedBuildingLabel"
                    placeholder="Izvēlies ēku"
                    empty-message="Neviena ēka neatbilst meklējumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Stāvs</span>
                <x-searchable-select
                    name="floor"
                    query-name="floor_query"
                    identifier="room-floor-filter"
                    :options="$floorOptions"
                    :selected="$filters['floor']"
                    :query="$selectedFloorLabel"
                    placeholder="Izvēlies stāvu"
                    empty-message="Neviens stāvs neatbilst meklējumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Atbildīgais</span>
                <x-searchable-select
                    name="user_id"
                    query-name="user_query"
                    identifier="room-user-filter"
                    :options="$userOptions"
                    :selected="$filters['user_id']"
                    :query="$selectedUserLabel"
                    placeholder="Izvēlies atbildīgo"
                    empty-message="Neviens lietotājs neatbilst meklējumam."
                />
            </label>
            <div class="toolbar-actions md:col-span-2 lg:col-span-4">
                <a href="{{ route('rooms.index') }}" class="btn-clear" data-async-link="true"><x-icon name="clear" size="h-4 w-4" /><span>Notīrīt</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Ēka', 'value' => $filters['building_id'] !== '' ? optional($buildings->firstWhere('id', (int) $filters['building_id']))->building_name : null],
                ['label' => 'Stāvs', 'value' => $selectedFloorLabel],
                ['label' => 'Atbildīgais', 'value' => $filters['user_id'] !== '' ? optional($responsibleUsers->firstWhere('id', (int) $filters['user_id']))->full_name : null],
            ]"
            :clear-url="route('rooms.index')"
        />

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="app-table-shell">
            <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="app-table-content app-table-content-compact min-w-full text-sm">
                <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Ēka</th>
                        <th class="px-4 py-3">Stāvs</th>
                        <th class="px-4 py-3">Numurs</th>
                        <th class="px-4 py-3">Nosaukums</th>
                        <th class="px-4 py-3">Nodaļa</th>
                        <th class="px-4 py-3">Atbildīgais</th>
                        <th class="px-4 py-3">Ierīces</th>
                        <th class="px-4 py-3">Piezīmes</th>
                        <th class="px-4 py-3">Darbības</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        @php
                            $roomDevicesUrl = route('devices.index', ['room_id' => $room->id, 'room_query' => trim(implode(' ', array_filter([$room->room_name, $room->room_number])))]);
                            $canDelete = (int) $room->devices_count === 0;
                            $deleteMessage = 'Telpu nevar dzēst, jo tai joprojām ir piesaistītas ierīces. Vispirms pārvieto vai atsien visas ierīces no šīs telpas.';
                        @endphp
                        <tr class="app-table-row border-t border-slate-100" data-table-row-id="room-{{ $room->id }}" data-table-search-value="{{ \Illuminate\Support\Str::lower(trim(implode(' ', array_filter([$room->room_number, $room->room_name])))) }}">
                            <td class="px-4 py-3">{{ $room->building?->building_name }}</td>
                            <td class="px-4 py-3">{{ $room->floor_number }}</td>
                            <td class="px-4 py-3">{{ $room->room_number }}</td>
                            <td class="px-4 py-3">{{ $room->room_name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $room->department ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $room->user?->full_name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($room->devices_count > 0)
                                    <a
                                        href="{{ route('devices.index', ['room_id' => $room->id, 'room_query' => trim(implode(' ', array_filter([$room->room_name, $room->room_number])))]) }}"
                                        class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 transition hover:bg-sky-100"
                                    >
                                        {{ $room->devices_count }} ierīces
                                    </a>
                                @else
                                    <span class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500">
                                        0 ierīces
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $room->notes ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
<a href="{{ route('rooms.edit', $room) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediģēt</span></a>
                                    @if ($canDelete)
                                        <form
                                            method="POST"
                                            action="{{ route('rooms.destroy', $room) }}"
                                            data-app-confirm-title="Dzēst telpu?"
                                            data-app-confirm-message="Vai tiešām dzēst šo telpu?"
                                            data-app-confirm-accept="Jā, dzēst"
                                            data-app-confirm-cancel="Nē"
                                            data-app-confirm-tone="danger"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger"><x-icon name="trash" size="h-4 w-4" /><span>Dzēst</span></button>
                                        </form>
                                    @else
                                        <button
                                            type="button"
                                            class="btn-disabled"
                                            data-app-toast-title="Dzēšana nav pieejama"
                                            data-app-toast-message="{{ $deleteMessage }}"
                                            data-app-toast-tone="info"
                                        >
                                            <x-icon name="trash" size="h-4 w-4" />
                                            <span>Dzēst</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6">
                                <x-empty-state
                                    compact
                                    icon="room"
                                    title="Telpas vēl nav pievienotas"
                                    description="Pievieno telpu vai paplašini filtrus, lai šeit redzētu ierakstus."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $rooms->links() }}
        </div>
    </section>
</x-app-layout>

