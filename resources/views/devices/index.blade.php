{{--
    Lapa: Ierīču saraksts.
    Atbildība: rāda adminam pilnu inventāru un lietotājam tikai viņa ierīces vienotā tabulas skatā.
    Datu avots: DeviceController@index.
--}}
<x-app-layout>
    @php
        // Select opcijas sagatavojam Blade sākumā, lai reusable searchable-select komponente saņemtu
        // vienādu `value/label/description/search` struktūru neatkarīgi no filtra tipa.
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
        $filterStatuses = $filterStatuses ?? $statuses ?? [];
        $activeRequestsSelected = (bool) ($filters['active_requests'] ?? false);
        $statusFilterLinks = collect([
            ['label' => 'Aktīvas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
            ['label' => 'Remontā', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'sky'],
            ['label' => 'Norakstītas', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
        ])->filter(fn (array $statusFilter) => in_array($statusFilter['value'], $filterStatuses, true))->values()->all();
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
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-5xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="device" size="h-4 w-4" />
                            <span>Inventārs</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="device" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopā</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['total'] }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierīces</h1>
                            <p class="page-subtitle">{{ $canManageDevices ? 'Pilns inventāra saraksts ar statusiem, atrašanās vietu un pārvaldības darbībām.' : 'Šeit redzamas tikai ar tevi saistītās ierīces un pieejamās darbības.' }}</p>
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

        <div id="devices-index-root" data-async-table-root>
            <form
                method="GET"
                action="{{ route('devices.index') }}"
                class="surface-toolbar surface-toolbar-elevated repairs-toolbar-surface"
                data-async-table-form
                data-async-root="#devices-index-root"
                data-search-endpoint="{{ route('devices.find-by-code') }}"
                data-manual-search-pagination="false"
                {{-- Stāvs un telpa ir hierarhiski saistīti filtri.
                     Mainot stāvu, telpas filtrs tiek notīrīts, lai nepaliktu nederīga telpa no cita stāva. --}}
                @searchable-select-updated.window="if ($event.detail.identifier === 'device-floor-filter') { $dispatch('searchable-select-clear', { target: 'device-room-filter' }) }"
            >
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <div class="toolbar-panels toolbar-panels-wide">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Meklēšana</span>
                        </h3>
                        <div class="devices-filter-grid">
                            <label class="block repairs-toolbar-code-field">
                                <span class="crud-label">Meklēt pēc koda</span>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        name="code"
                                        value="{{ $filters['code'] }}"
                                        class="crud-control"
                                        placeholder="Ievadi precīzu ierīces kodu"
                                        autocomplete="off"
                                        data-async-manual="true"
                                        data-async-code-search="true"
                                    >
                                    <button type="button" class="btn-search shrink-0" data-code-search-submit="true" onclick="return window.runManualTableSearchFromTrigger(this);">
                                        <x-icon name="search" size="h-4 w-4" />
                                        <span>Meklēt</span>
                                    </button>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title repairs-filter-title">
                            <x-icon name="filter" size="h-4 w-4" />
                            <span>Filtri</span>
                        </h3>

                        <div class="repairs-toolbar-filters-grid">
                            <label class="block repairs-toolbar-text-field">
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

                    <div class="devices-filter-section"
                        x-data="{
                            field: @js($sorting['sort']),
                            dir: @js($sorting['direction']),
                            apply() {
                                // Kārtošanas vadība raksta vērtības hidden laukos un submito formu,
                                // lai backend saņemtu to pašu klasisko payload kā no tabulas header sort pogām.
                                const form = this.$el.closest('form');
                                form.querySelector('[data-sort-hidden=\'field\']').value = this.field;
                                form.querySelector('[data-sort-hidden=\'direction\']').value = this.dir;
                                form.requestSubmit();
                            }
                        }"
                    >
                        <h3 class="devices-filter-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                            </svg>
                            <span>Kārtošana</span>
                        </h3>
                        <div class="devices-filter-grid">
                            <label class="block">
                                <span class="crud-label">Kārtot pēc</span>
                                <select
                                    class="crud-control"
                                    x-model="field"
                                    @change="apply()"
                                >
                                    @foreach ($sortOptions as $value => $option)
                                        <option value="{{ $value }}">{{ $option['display'] ?? ucfirst($option['label']) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="crud-label">Virziens</span>
                                <div class="flex gap-1.5">
                                    <button
                                        type="button"
                                        @click="dir = 'asc'; apply()"
                                        :class="dir === 'asc' ? 'quick-status-filter-active quick-status-filter-sky' : ''"
                                        class="quick-status-filter quick-status-filter-slate flex-1"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" />
                                        </svg>
                                        <span>A → Z</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="dir = 'desc'; apply()"
                                        :class="dir === 'desc' ? 'quick-status-filter-active quick-status-filter-sky' : ''"
                                        class="quick-status-filter quick-status-filter-slate flex-1"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0-3.75-3.75M17.25 21l3.75-3.75" />
                                        </svg>
                                        <span>Z → A</span>
                                    </button>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="filter-toolbar-footer repairs-filter-footer">
                    <div class="quick-filter-groups">
                        <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($selectedStatuses), minimum: 0 })">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ierīces statuss</div>
                            <div class="quick-status-filters">
                                @foreach ($statusFilterLinks as $statusFilter)
                                    @php
                                        $toneClass = 'quick-status-filter-' . $statusFilter['tone'];
                                        $statusCount = match ($statusFilter['value']) {
                                            'active' => $deviceSummary['active'] ?? 0,
                                            'repair' => $deviceSummary['repair'] ?? 0,
                                            'writeoff' => $deviceSummary['writeoff'] ?? 0,
                                            default => 0,
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        {{-- Statusa filtri tiek glabāti kā masīvs, jo lietotājs drīkst atlasīt vairākus statusus reizē. --}}
                                        @click="toggle(@js($statusFilter['value'])); $nextTick(() => $el.closest('form').requestSubmit())"
                                        class="quick-status-filter {{ $toneClass }}"
                                        :class="isSelected(@js($statusFilter['value'])) ? 'quick-status-filter-active' : ''"
                                    >
                                        <x-icon :name="$statusFilter['icon']" size="h-4 w-4" />
                                        <span>{{ $statusFilter['label'] }}</span>
                                        <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold text-current ring-1 ring-black/5">{{ $statusCount }}</span>
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'device-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>

                        <div class="quick-filter-group" x-data="{ active: @js($activeRequestsSelected) }">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Pieteikumi</div>
                            <div class="quick-status-filters">
                                <button
                                    type="button"
                                    {{-- Šis ir binārs filtrs, tāpēc pietiek ar vienu hidden lauku `active_requests=1`,
                                         kuru iesniedzam tikai tad, ja slēdzis ir ieslēgts. --}}
                                    @click="active = ! active; $nextTick(() => $el.closest('form').requestSubmit())"
                                    class="quick-status-filter quick-status-filter-amber"
                                    :class="active ? 'quick-status-filter-active' : ''"
                                >
                                    <x-icon name="repair-request" size="h-4 w-4" />
                                    <span>Aktīvie pieteikumi</span>
                                    <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold text-current ring-1 ring-black/5">{{ $deviceSummary['active_requests'] ?? 0 }}</span>
                                </button>

                                <input type="hidden" name="active_requests" value="1" @disabled(! $activeRequestsSelected) :disabled="!active">
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
                        ['label' => 'Teksts', 'value' => $filters['q'], 'remove' => 'q'],
                        ['label' => 'Piešķirta', 'value' => $canManageDevices ? $selectedAssignedUserLabel : null, 'remove' => ['assigned_to_id', 'assigned_to_query']],
                        ['label' => 'Stāvs', 'value' => $selectedFloorLabel, 'remove' => ['floor', 'floor_query', 'room_id', 'room_query']],
                        ['label' => 'Telpa', 'value' => $selectedRoomLabel, 'remove' => ['room_id', 'room_query']],
                        ['label' => 'Tips', 'value' => $selectedTypeLabel, 'remove' => ['type', 'type_query']],
                        ['label' => 'Statuss', 'value' => $filters['has_status_filter'] ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ') : null, 'remove' => 'status'],
                        ['label' => 'Pieteikumi', 'value' => $activeRequestsSelected ? 'Aktīvie pieteikumi' : null, 'remove' => 'active_requests'],
                    ]"
                    :clear-url="route('devices.index')"
                />
            </div>

            @if (session('error'))
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (session('warning'))
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('warning') }}</div>
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

            @if (($selectedModalDevice ?? null)?->id && ! $devices->contains('id', $selectedModalDevice->id))
                @include('devices.partials.modal-form', [
                    'mode' => 'edit',
                    'modalName' => 'device-edit-modal-' . $selectedModalDevice->id,
                    'device' => $selectedModalDevice,
                ])
            @endif
        @endif

        @if (old('modal_form') === 'device_create')
            {{-- Pēc validācijas kļūdām vai query signāla atveram pareizo modāli atkārtoti.
                 `openModalWithLoading` tiek izmantots, ja globālais helper jau ir ielādēts un vajag vienotu loading UX. --}}
            <script>window.addEventListener('DOMContentLoaded', () => window.openModalWithLoading ? window.openModalWithLoading('device-create-modal') : window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-create-modal' })));</script>
        @elseif (str_starts_with((string) old('modal_form'), 'device_edit_'))
            @php($deviceModalTarget = str_replace('device_edit_', '', (string) old('modal_form')))
            <script>window.addEventListener('DOMContentLoaded', () => window.openModalWithLoading ? window.openModalWithLoading('device-edit-modal-{{ $deviceModalTarget }}') : window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-edit-modal-{{ $deviceModalTarget }}' })));</script>
        @elseif ($deviceModalQuery === 'create')
            <script>window.addEventListener('DOMContentLoaded', () => window.openModalWithLoading ? window.openModalWithLoading('device-create-modal') : window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-create-modal' })));</script>
        @elseif ($deviceModalQuery === 'edit' && $deviceModalDeviceId)
            <script>window.addEventListener('DOMContentLoaded', () => window.openModalWithLoading ? window.openModalWithLoading('device-edit-modal-{{ $deviceModalDeviceId }}') : window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-edit-modal-{{ $deviceModalDeviceId }}' })));</script>
        @endif
    </section>
</x-app-layout>
