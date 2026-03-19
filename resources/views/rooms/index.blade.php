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
            'description' => 'Filtrs pec nodalas',
            'search' => (string) $department,
        ])->values();
        $userOptions = $responsibleUsers->map(fn ($responsibleUser) => [
            'value' => (string) $responsibleUser->id,
            'label' => $responsibleUser->full_name,
            'description' => $responsibleUser->job_title ?: '',
            'search' => implode(' ', array_filter([$responsibleUser->full_name, $responsibleUser->job_title, $responsibleUser->email])),
        ])->values();
        $hasDeviceOptions = [
            ['value' => '1', 'label' => 'Ar iericem', 'description' => 'Telpas ar iericem', 'search' => 'Ar iericem'],
            ['value' => '0', 'label' => 'Bez iericem', 'description' => 'Telpas bez iericem', 'search' => 'Bez iericem'],
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
                <x-searchable-select
                    name="building_id"
                    query-name="building_query"
                    identifier="room-building-filter"
                    :options="$buildingOptions"
                    :selected="$filters['building_id']"
                    :query="$selectedBuildingLabel"
                    placeholder="Izvelies eku"
                    empty-message="Neviena eka neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Nodala</span>
                <x-searchable-select
                    name="department"
                    query-name="department_query"
                    identifier="room-department-filter"
                    :options="$departmentOptions"
                    :selected="$filters['department']"
                    :query="$selectedDepartmentLabel"
                    placeholder="Izvelies nodalu"
                    empty-message="Neviena nodala neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Atbildigais</span>
                <x-searchable-select
                    name="user_id"
                    query-name="user_query"
                    identifier="room-user-filter"
                    :options="$userOptions"
                    :selected="$filters['user_id']"
                    :query="$selectedUserLabel"
                    placeholder="Izvelies atbildigo"
                    empty-message="Neviens lietotajs neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Ierices telpa</span>
                <x-searchable-select
                    name="has_devices"
                    query-name="has_devices_query"
                    identifier="room-device-filter"
                    :options="$hasDeviceOptions"
                    :selected="$filters['has_devices']"
                    :query="$selectedHasDevicesLabel"
                    placeholder="Izvelies telpas tipu"
                    empty-message="Neviens variants neatbilst meklejumam."
                />
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
