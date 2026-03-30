{{--
    Lapa: Telpu saraksts.
    Atbildība: rāda visas telpas, to ēkas, atbildīgos un ierīču skaitu.
    Datu avots: RoomController@index.
    Galvenās daļas:
    1. Hero ar kopsavilkumu.
    2. Filtri pēc ēkas, nodaļas, atbildīgā un aizpildījuma.
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
        $departmentOptions = collect($departments)->map(fn ($department) => [
            'value' => (string) $department,
            'label' => (string) $department,
            'description' => 'Filtrs pēc nodaļas',
            'search' => (string) $department,
        ])->values();
        $userOptions = $responsibleUsers->map(fn ($responsibleUser) => [
            'value' => (string) $responsibleUser->id,
            'label' => $responsibleUser->full_name,
            'description' => $responsibleUser->job_title ?: '',
            'search' => implode(' ', array_filter([$responsibleUser->full_name, $responsibleUser->job_title, $responsibleUser->email])),
        ])->values();
        $hasDeviceOptions = [
            ['value' => '1', 'label' => 'Ar ierīcēm', 'description' => 'Telpas ar ierīcēm', 'search' => 'Ar ierīcēm'],
            ['value' => '0', 'label' => 'Bez ierīcēm', 'description' => 'Telpas bez ierīcēm', 'search' => 'Bez ierīcēm'],
        ];
        $selectedBuildingLabel = optional($buildings->firstWhere('id', (int) $filters['building_id']))->building_name;
        $selectedDepartmentLabel = $filters['department'] !== '' ? $filters['department'] : null;
        $selectedUserLabel = optional($responsibleUsers->firstWhere('id', (int) $filters['user_id']))->full_name;
        $selectedHasDevicesLabel = collect($hasDeviceOptions)->firstWhere('value', $filters['has_devices'])['label'] ?? null;
    @endphp
    <section class="app-shell">
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

        <form method="GET" action="{{ route('rooms.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-5">
            <label class="block">
                <span class="crud-label">Meklēt</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Telpa, ēka, nodaļa, atbildīgais...">
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
                <span class="crud-label">Nodaļa</span>
                <x-searchable-select
                    name="department"
                    query-name="department_query"
                    identifier="room-department-filter"
                    :options="$departmentOptions"
                    :selected="$filters['department']"
                    :query="$selectedDepartmentLabel"
                    placeholder="Izvēlies nodaļu"
                    empty-message="Neviena nodaļa neatbilst meklējumam."
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
            <label class="block">
                <span class="crud-label">Ierīces telpa</span>
                <x-searchable-select
                    name="has_devices"
                    query-name="has_devices_query"
                    identifier="room-device-filter"
                    :options="$hasDeviceOptions"
                    :selected="$filters['has_devices']"
                    :query="$selectedHasDevicesLabel"
                    placeholder="Izvēlies telpas tipu"
                    empty-message="Neviens variants neatbilst meklējumam."
                />
            </label>
            <div class="toolbar-actions md:col-span-5">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklēt</span></button>
                <a href="{{ route('rooms.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notīrīt</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklēt', 'value' => $filters['q']],
                ['label' => 'Ēka', 'value' => $filters['building_id'] !== '' ? optional($buildings->firstWhere('id', (int) $filters['building_id']))->building_name : null],
                ['label' => 'Nodaļa', 'value' => $filters['department']],
                ['label' => 'Atbildīgais', 'value' => $filters['user_id'] !== '' ? optional($responsibleUsers->firstWhere('id', (int) $filters['user_id']))->full_name : null],
                ['label' => 'Ierīces telpa', 'value' => $filters['has_devices'] === '1' ? 'Ar ierīcēm' : ($filters['has_devices'] === '0' ? 'Bez ierīcēm' : null)],
            ]"
            :clear-url="route('rooms.index')"
        />

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
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
                                    <a href="{{ route('rooms.edit', $room) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediģēt</span></a>
                                    <form method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('Dzēst šo telpu?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger"><x-icon name="trash" size="h-4 w-4" /><span>Dzēst</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">Telpas vēl nav pievienotas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $rooms->links() }}
    </section>
</x-app-layout>
