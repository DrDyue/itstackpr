{{--
    Lapa: Ierīču saraksts.
    Atbildība: rāda visas lietotājam pieejamās ierīces; adminam tas ir pilnais inventārs, lietotājam - tikai viņa ierīces.
    Datu avots: DeviceController@index.
    Galvenās daļas:
    1. Kopsavilkuma hero ar skaitītājiem.
    2. Filtru rīkjosla meklēšanai, telpām, tipiem un statusiem.
    3. Galvenā tabula ar statusiem, preview un ātrajām darbībām.
--}}
<x-app-layout>
    @php
        $selectedFloorLabel = $filters['floor'] !== ''
            ? ($filters['floor'] . '. stāvs')
            : ($filters['floor_query'] !== '' ? $filters['floor_query'] : null);
        $selectedRoomLabel = $selectedRoom
            ? ($selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : ''))
            : ($filters['room_query'] !== '' ? $filters['room_query'] : null);
        $selectedTypeLabel = $selectedType?->type_name ?: ($filters['type_query'] !== '' ? $filters['type_query'] : null);
        $selectedAssignedUserLabel = $selectedAssignedUser?->full_name;
        $quickRoomSelectOptions = collect($quickRoomOptions ?? [])->values();
        $quickAssigneeSelectOptions = collect($quickAssigneeOptions ?? [])->values();
        $selectedStatuses = $filters['statuses'];
        $statusFilterLinks = $canManageDevices
            ? [
                ['label' => 'Aktīvas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
                ['label' => 'Remontā', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'sky'],
                ['label' => 'Norakstītas', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
            ]
            : [
                ['label' => 'Aktīvas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
                ['label' => 'Remontā', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'sky'],
            ];
        $roomSelectOptions = $roomOptions->map(fn ($room) => [
            'value' => (string) $room->id,
            'label' => $room->room_number . ($room->room_name ? ' - ' . $room->room_name : ''),
            'description' => $room->department ?: '',
            'search' => implode(' ', array_filter([
                $room->room_number,
                $room->room_name,
                $room->department,
            ])),
        ])->values();
        $typeSelectOptions = $types->map(fn ($type) => [
            'value' => (string) $type->id,
            'label' => $type->type_name,
            'description' => 'Ierīces tips',
            'search' => $type->type_name,
        ])->values();
        $assignedUserSelectOptions = collect($assignableUsers ?? [])->map(fn ($managedUser) => [
            'value' => (string) $managedUser->id,
            'label' => $managedUser->full_name,
            'description' => implode(' | ', array_filter([
                $managedUser->job_title,
                $managedUser->email,
            ])),
            'search' => implode(' ', array_filter([
                $managedUser->full_name,
                $managedUser->job_title,
                $managedUser->email,
            ])),
        ])->values();
        $floorSelectOptions = collect($floorOptions)->map(fn ($floor) => [
            'value' => (string) $floor,
            'label' => $floor . '. stāvs',
            'description' => 'Filtrs pēc stāva',
            'search' => $floor . ' ' . $floor . '. stāvs',
        ])->values();
        $toolbarGridClass = 'devices-filter-grid';
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="device" size="h-4 w-4" />
                            <span>Inventārs</span>
                        </div>

                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="device" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Aktīvas</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['active'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="repair" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Remonta</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['repair'] }}</span>
                            </span>
                            @if ($canManageDevices)
                                <span class="inventory-inline-chip inventory-inline-chip-rose">
                                    <x-icon name="writeoff" size="h-3.5 w-3.5" />
                                    <span class="inventory-inline-label">Norakstītas</span>
                                    <span class="inventory-inline-value">{{ $deviceSummary['writeoff'] }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierīces</h1>
                            <p class="page-subtitle">{{ $canManageDevices ? 'Pilns ierīču saraksts un pārvaldība.' : 'Tavas piesaistītās ierīces.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageDevices)
                    <div class="page-actions">
                        <button type="button" class="btn-create" x-data @click="$dispatch('open-modal', 'device-create-modal')">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauna ierīce</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div id="devices-index-root" data-async-table-root class="devices-index-page">
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('devices.index') }}"
                class="devices-filter-surface devices-filter-surface-elevated"
                data-async-table-form
                data-async-root="#devices-index-root"
                data-search-endpoint="{{ route('devices.find-by-code') }}"
                @searchable-select-updated.window="if ($event.detail.identifier === 'device-floor-filter') { $dispatch('searchable-select-clear', { target: 'device-room-filter' }) }"
            >
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <div class="devices-filter-header">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Meklēšana</span>
                        </h3>
                        <div class="{{ $toolbarGridClass }}">
                            <div class="devices-search-group">
                                <label class="devices-search-label">
                                    <span>Meklēt pēc koda</span>
                                    <input
                                        type="text"
                                        name="code"
                                        value="{{ $filters['code'] }}"
                                        class="devices-code-input"
                                        placeholder="Ievadi ierīces kodu"
                                        autocomplete="off"
                                        data-async-manual="true"
                                        data-async-code-search="true"
                                    >
                                </label>
                                <button type="button" class="devices-code-search-btn" data-code-search-submit="true" onclick="return window.runManualTableSearchFromTrigger(this);">
                                    <x-icon name="search" size="h-4 w-4" />
                                    <span>Atrast ierīci</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="devices-filter-divider"></div>

                <div class="devices-filter-header">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="filter" size="h-4 w-4" />
                            <span>Filtri</span>
                        </h3>
                        <div class="devices-filters-grid">
                            <label class="block">
                                <span class="crud-label">Filtrēt pēc teksta</span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, ražotājs, sērija...">
                            </label>
                            @if ($canManageDevices)
                                <label class="block">
                                    <span class="crud-label">Piešķirta</span>
                                    <x-searchable-select
                                        name="assigned_to_id"
                                        query-name="assigned_to_query"
                                        identifier="device-assignee-filter"
                                        :options="$assignedUserSelectOptions"
                                        :selected="$filters['assigned_to_id']"
                                        :query="$selectedAssignedUserLabel"
                                        placeholder="Izvēlies darbinieku"
                                        empty-message="Neviens darbinieks neatbilst meklējumam."
                                    />
                                </label>
                            @endif
                            <label class="block">
                                <span class="crud-label">Stāvs</span>
                                <x-searchable-select
                                    name="floor"
                                    query-name="floor_query"
                                    identifier="device-floor-filter"
                                    :options="$floorSelectOptions"
                                    :selected="$filters['floor']"
                                    :query="$selectedFloorLabel"
                                    placeholder="Izvēlies vai raksti stāvu"
                                    empty-message="Neviens stāvs neatbilst meklējumam."
                                />
                            </label>
                            <label class="block">
                                <span class="crud-label">Telpa</span>
                                <x-searchable-select
                                    name="room_id"
                                    query-name="room_query"
                                    identifier="device-room-filter"
                                    :options="$roomSelectOptions"
                                    :selected="$filters['room_id']"
                                    :query="$selectedRoomLabel"
                                    placeholder="Raksti telpas numuru vai nosaukumu"
                                    empty-message="Neviena telpa neatbilst meklējumam."
                                />
                            </label>
                            <label class="block">
                                <span class="crud-label">Tips</span>
                                <x-searchable-select
                                    name="type"
                                    query-name="type_query"
                                    identifier="device-type-filter"
                                    :options="$typeSelectOptions"
                                    :selected="$filters['type']"
                                    :query="$selectedTypeLabel"
                                    placeholder="Raksti tipa nosaukumu"
                                    empty-message="Neviens tips neatbilst meklējumam."
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <div class="filter-toolbar-footer">
                    <div class="quick-filter-groups">
                        <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($selectedStatuses), minimum: 0 })">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ierīces statuss</div>
                            <div class="quick-status-filters">
                                @foreach ($statusFilterLinks as $statusFilter)
                                    @php
                                        $toneClass = 'quick-status-filter-' . $statusFilter['tone'];
                                    @endphp
                                    <button
                                        type="button"
                                        @click="toggle(@js($statusFilter['value'])); $nextTick(() => $el.closest('form').requestSubmit())"
                                        class="quick-status-filter {{ $toneClass }}"
                                        :class="isSelected(@js($statusFilter['value'])) ? 'quick-status-filter-active' : ''"
                                    >
                                        <x-icon :name="$statusFilter['icon']" size="h-4 w-4" />
                                        <span>{{ $statusFilter['label'] }}</span>
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'device-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('devices.index') }}" class="btn-clear" data-async-link="true">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>Notīrīt filtrus</span>
                        </a>
                    </div>
                </div>
            </form>

            <div class="mt-5">
                <x-active-filters
                    :items="[
                        ['label' => 'Teksts', 'value' => $filters['q']],
                        ['label' => 'Piešķirta', 'value' => $canManageDevices ? $selectedAssignedUserLabel : null],
                        ['label' => 'Stāvs', 'value' => $selectedFloorLabel],
                        ['label' => 'Telpa', 'value' => $selectedRoomLabel],
                        ['label' => 'Tips', 'value' => $selectedTypeLabel],
                        ['label' => 'Statuss', 'value' => $filters['has_status_filter'] ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ') : null],
                    ]"
                    :clear-url="route('devices.index')"
                />
            </div>

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

        {{-- Galvenā ierīču tabula ar statusu preview un admina ātrajām darbībām. --}}
        @if (session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('warning') }}</div>
        @endif

        @include('devices.index-table', [
            'devices' => $devices,
            'deviceStates' => $deviceStates,
            'sorting' => $sorting,
            'sortOptions' => $sortOptions,
            'statusLabels' => $statusLabels,
            'canManageDevices' => $canManageDevices,
            'quickRoomSelectOptions' => $quickRoomSelectOptions,
            'userRoomOptions' => $userRoomOptions ?? collect(),
            'quickAssigneeOptions' => $quickAssigneeSelectOptions,
            'types' => $types ?? collect(),
            'buildings' => $buildings ?? collect(),
            'rooms' => $rooms ?? collect(),
            'users' => $users ?? collect(),
            'statuses' => $statuses ?? [],
            'defaultAssignedToId' => $defaultAssignedToId ?? null,
            'defaultRoomId' => $defaultRoomId ?? null,
            'defaultBuildingId' => $defaultBuildingId ?? null,
        ])

        </div>

        @if ($canManageDevices)
            @include('devices.partials.modal-form', [
                'mode' => 'create',
                'modalName' => 'device-create-modal',
                'device' => null,
            ])
        @endif

        @if (old('modal_form') === 'device_create')
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-create-modal' })));</script>
        @elseif (str_starts_with((string) old('modal_form'), 'device_edit_'))
            @php($deviceModalTarget = str_replace('device_edit_', '', (string) old('modal_form')))
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-edit-modal-{{ $deviceModalTarget }}' })));</script>
        @elseif ($deviceModalQuery === 'create')
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-create-modal' })));</script>
        @elseif ($deviceModalQuery === 'edit' && $deviceModalDeviceId)
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-edit-modal-{{ $deviceModalDeviceId }}' })));</script>
        @endif
    </section>
</x-app-layout>
