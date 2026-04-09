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
                <button type="button" class="btn-create" x-data @click="$dispatch('open-modal', 'room-create-modal')">
                    <x-icon name="plus" size="h-4 w-4" />
                    <span>Jauna telpa</span>
                </button>
            </div>
        </div>

        <div id="rooms-index-root" data-async-table-root>
        <form method="GET" action="{{ route('rooms.index') }}" class="devices-filter-surface devices-filter-surface-elevated" data-async-table-form data-async-root="#rooms-index-root" data-search-endpoint="{{ route('rooms.find-by-name') }}">
            <input type="hidden" name="sort" value="{{ $sorting['sort'] ?? 'id' }}" data-sort-hidden="field">
            <input type="hidden" name="direction" value="{{ $sorting['direction'] ?? 'asc' }}" data-sort-hidden="direction">

            <div class="devices-filter-header">
                <div class="devices-filter-section">
                    <h3 class="devices-filter-title">
                        <x-icon name="search" size="h-4 w-4" />
                        <span>Meklēšana</span>
                    </h3>
                    <div class="devices-search-group">
                        <label class="devices-search-label">
                            <span>Meklēt pēc nosaukuma vai numura</span>
                            <input type="text" name="search" value="{{ $filters['search'] }}" class="devices-code-input" placeholder="Nosaukums vai numurs" data-async-manual="true" data-table-manual-search="true" data-search-mode="contains">
                        </label>
                        <button type="submit" class="devices-code-search-btn" data-table-search-submit="true">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Atrast telpu</span>
                        </button>
                    </div>
                </div>

                <div class="devices-filter-divider-vertical"></div>

                <div class="devices-filter-section">
                    <h3 class="devices-filter-title">
                        <x-icon name="filter" size="h-4 w-4" />
                        <span>Filtri</span>
                    </h3>
                    <div class="rooms-filters-grid">
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
                    </div>
                </div>
            </div>

            <div class="filter-toolbar-footer">
                <div class="toolbar-actions">
                    <a href="{{ route('rooms.index') }}" class="btn-clear" data-async-link="true">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Notīrīt filtrus</span>
                    </a>
                </div>
            </div>
        </form>

        <div class="mt-4">
            <x-active-filters
                :items="[
                    ['label' => 'Ēka', 'value' => $filters['building_id'] !== '' ? optional($buildings->firstWhere('id', (int) $filters['building_id']))->building_name : null],
                    ['label' => 'Stāvs', 'value' => $selectedFloorLabel],
                    ['label' => 'Atbildīgais', 'value' => $filters['user_id'] !== '' ? optional($responsibleUsers->firstWhere('id', (int) $filters['user_id']))->full_name : null],
                ]"
                :clear-url="route('rooms.index')"
            />
        </div>

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="app-table-shell mt-4">
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
                                    <button type="button" class="btn-edit" x-data @click="$dispatch('open-modal', 'room-edit-modal-{{ $room->id }}')">
                                        <x-icon name="edit" size="h-4 w-4" />
                                        <span>Rediģēt</span>
                                    </button>
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

        <x-modal name="room-create-modal" maxWidth="2xl" focusable>
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Jauna telpa</h2>
                <p class="mt-1 text-sm text-slate-500">Izveido telpu tieši no saraksta lapas.</p>

                <form method="POST" action="{{ route('rooms.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="modal_form" value="room_create">

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.form-field label="Ēka" name="building_id" :required="true">
                            <select name="building_id" class="crud-control" required>
                                <option value="">Izvēlies ēku</option>
                                @foreach ($buildings as $building)
                                    <option value="{{ $building->id }}" @selected((string) old('building_id') === (string) $building->id)>{{ $building->building_name }}</option>
                                @endforeach
                            </select>
                        </x-ui.form-field>
                        <x-ui.form-field label="Stāvs" name="floor_number" :required="true">
                            <input type="number" name="floor_number" value="{{ old('floor_number') }}" class="crud-control" required>
                        </x-ui.form-field>
                        <x-ui.form-field label="Telpas numurs" name="room_number" :required="true">
                            <input type="text" name="room_number" value="{{ old('room_number') }}" class="crud-control" required>
                        </x-ui.form-field>
                        <x-ui.form-field label="Telpas nosaukums" name="room_name">
                            <input type="text" name="room_name" value="{{ old('room_name') }}" class="crud-control">
                        </x-ui.form-field>
                        <x-ui.form-field label="Atbildīgais lietotājs" name="user_id">
                            <select name="user_id" class="crud-control">
                                <option value="">Nav norādīts</option>
                                @foreach ($responsibleUsers as $responsibleUser)
                                    <option value="{{ $responsibleUser->id }}" @selected((string) old('user_id') === (string) $responsibleUser->id)>{{ $responsibleUser->full_name }}</option>
                                @endforeach
                            </select>
                        </x-ui.form-field>
                        <x-ui.form-field label="Nodaļa" name="department">
                            <input type="text" name="department" value="{{ old('department') }}" class="crud-control">
                        </x-ui.form-field>
                        <x-ui.form-field class="md:col-span-2" label="Piezīmes" name="notes">
                            <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
                        </x-ui.form-field>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'room-create-modal')">Atcelt</button>
                        <button type="submit" class="btn-create">Saglabāt</button>
                    </div>
                </form>
            </div>
        </x-modal>

        @foreach ($rooms as $room)
            <x-modal name="room-edit-modal-{{ $room->id }}" maxWidth="2xl" focusable>
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Rediģēt telpu</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $room->room_number }}{{ $room->room_name ? ' — ' . $room->room_name : '' }}</p>

                    <form method="POST" action="{{ route('rooms.update', $room) }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal_form" value="room_edit_{{ $room->id }}">

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-ui.form-field label="Ēka" name="building_id" :required="true">
                                <select name="building_id" class="crud-control" required>
                                    <option value="">Izvēlies ēku</option>
                                    @foreach ($buildings as $building)
                                        @php
                                            $roomBuildingValue = old('modal_form') === 'room_edit_' . $room->id ? old('building_id') : $room->building_id;
                                        @endphp
                                        <option value="{{ $building->id }}" @selected((string) $roomBuildingValue === (string) $building->id)>{{ $building->building_name }}</option>
                                    @endforeach
                                </select>
                            </x-ui.form-field>
                            <x-ui.form-field label="Stāvs" name="floor_number" :required="true">
                                <input type="number" name="floor_number" value="{{ old('modal_form') === 'room_edit_' . $room->id ? old('floor_number') : $room->floor_number }}" class="crud-control" required>
                            </x-ui.form-field>
                            <x-ui.form-field label="Telpas numurs" name="room_number" :required="true">
                                <input type="text" name="room_number" value="{{ old('modal_form') === 'room_edit_' . $room->id ? old('room_number') : $room->room_number }}" class="crud-control" required>
                            </x-ui.form-field>
                            <x-ui.form-field label="Telpas nosaukums" name="room_name">
                                <input type="text" name="room_name" value="{{ old('modal_form') === 'room_edit_' . $room->id ? old('room_name') : $room->room_name }}" class="crud-control">
                            </x-ui.form-field>
                            <x-ui.form-field label="Atbildīgais lietotājs" name="user_id">
                                @php
                                    $roomUserValue = old('modal_form') === 'room_edit_' . $room->id ? old('user_id') : $room->user_id;
                                @endphp
                                <select name="user_id" class="crud-control">
                                    <option value="">Nav norādīts</option>
                                    @foreach ($responsibleUsers as $responsibleUser)
                                        <option value="{{ $responsibleUser->id }}" @selected((string) $roomUserValue === (string) $responsibleUser->id)>{{ $responsibleUser->full_name }}</option>
                                    @endforeach
                                </select>
                            </x-ui.form-field>
                            <x-ui.form-field label="Nodaļa" name="department">
                                <input type="text" name="department" value="{{ old('modal_form') === 'room_edit_' . $room->id ? old('department') : $room->department }}" class="crud-control">
                            </x-ui.form-field>
                            <x-ui.form-field class="md:col-span-2" label="Piezīmes" name="notes">
                                <textarea name="notes" rows="3" class="crud-control">{{ old('modal_form') === 'room_edit_' . $room->id ? old('notes') : $room->notes }}</textarea>
                            </x-ui.form-field>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'room-edit-modal-{{ $room->id }}')">Atcelt</button>
                            <button type="submit" class="btn-edit">Saglabāt</button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endforeach

        @if (old('modal_form') === 'room_create')
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'room-create-modal' })));</script>
        @elseif (str_starts_with((string) old('modal_form'), 'room_edit_'))
            @php($roomModalTarget = str_replace('room_edit_', '', (string) old('modal_form')))
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'room-edit-modal-{{ $roomModalTarget }}' })));</script>
        @endif
        </section>
</x-app-layout>
