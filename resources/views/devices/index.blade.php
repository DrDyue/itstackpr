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
        $statusFilterLinks = [
            ['label' => 'Aktīvas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
            ['label' => 'Remonta', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'sky'],
            ['label' => 'Norakstītas', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
        ];
        $selectedStatuses = $filters['statuses'];
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
        $toolbarGridClass = $canManageDevices
            ? 'surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)_minmax(0,1fr)_minmax(0,0.8fr)_minmax(0,1.5fr)_minmax(0,1.5fr)]'
            : 'surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)_minmax(0,0.8fr)_minmax(0,1.5fr)_minmax(0,1.5fr)]';
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
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="writeoff" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Norakstītas</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['writeoff'] }}</span>
                            </span>
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
                        <a href="{{ route('devices.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauna ierīce</span>
                        </a>
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
                        <div class="devices-filter-grid">
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
                                <button type="submit" class="devices-code-search-btn" data-code-search-submit="true">
                                    <x-icon name="search" size="h-4 w-4" />
                                    <span>Atrast ierīci</span>
                                </button>
                            </div>
                            <label class="devices-text-search">
                                <span>Filtrēt pēc teksta</span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, ražotājs, sērija...">
                            </label>
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
        <div class="device-table-shell device-table-shell-wide">
            <div class="device-table-scroll device-table-scroll-balanced rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="device-table-content min-w-full text-sm">
                <thead class="device-table-head bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Attēls</th>
                        @foreach ([
                            'code' => 'Kods',
                            'name' => 'Nosaukums',
                            'location' => 'Atrašanās vieta',
                            'created_at' => 'Izveidots',
                            'assigned_to' => 'Piešķirta',
                            'status' => 'Statuss',
                        ] as $column => $label)
                            @php
                                $isCurrentSort = $sorting['sort'] === $column;
                                $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
                                $sortMessage = 'Kārtots pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                            @endphp
                            <th class="px-4 py-3">
                                <button
                                    type="button"
                                    class="device-sort-trigger {{ $isCurrentSort ? 'device-sort-trigger-active' : '' }}"
                                    data-sort-trigger="true"
                                    data-sort-field="{{ $column }}"
                                    data-sort-direction="{{ $nextDirection }}"
                                    data-sort-toast="{{ $sortMessage }}"
                                >
                                    <span>{{ $label }}</span>
                                    <span class="device-sort-icon" aria-hidden="true">
                                        <svg class="h-[1.05em] w-[1.05em]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 9 3.75-3.75L15.75 9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15-3.75 3.75L8.25 15" />
                                        </svg>
                                    </span>
                                </button>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 w-[8rem]">Darbības</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        @php
                            $thumbUrl = $device->deviceImageThumbUrl();
                            $nameMeta = collect([$device->manufacturer, $device->model])->filter(fn ($value) => filled($value))->implode(' | ');
                            $quickRoomLabel = $device->room
                                ? ($device->room->room_number . ($device->room->room_name ? ' - ' . $device->room->room_name : ''))
                                : null;
                            $quickAssigneeLabel = $device->assignedTo?->full_name;
                            $deviceState = $deviceStates[$device->id] ?? [];
                            $requestAvailability = $deviceState['requestAvailability'] ?? [
                                'repair' => false,
                                'writeoff' => false,
                                'transfer' => false,
                                'can_create_any' => false,
                                'reason' => null,
                            ];
                            $pendingRequestBadge = $deviceState['pendingRequestBadge'] ?? null;
                            $repairStatusLabel = $deviceState['repairStatusLabel'] ?? null;
                            $repairPreview = $deviceState['repairPreview'] ?? null;
                        @endphp
                        <tr class="device-table-row border-t border-slate-100 align-top" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) $device->code)) }}">
                            <td class="px-4 py-4 tabular-nums">
                                @if ($thumbUrl)
                                    <img src="{{ $thumbUrl }}" alt="{{ $device->name }}" class="device-table-thumb">
                                @else
                                    <div class="device-table-thumb device-table-thumb-placeholder">
                                        <x-icon name="device" size="h-4 w-4" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 tabular-nums">
                                <div class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</div>
                                @if ($device->serial_number)
                                    <div class="mt-1 text-xs text-slate-500">Sērija: {{ $device->serial_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('devices.show', $device) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $device->name }}</a>
                                @if ($nameMeta !== '')
                                    <div class="mt-1 text-xs text-slate-500">{{ $nameMeta }}</div>
                                @endif
                                <div class="mt-2 text-xs text-slate-400">{{ $device->type?->type_name ?: 'Bez tipa' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                @if ($device->room)
                                    <div class="font-medium text-slate-900">
                                        {{ $device->room->room_number }}
                                        @if ($device->room->room_name)
                                            | {{ $device->room->room_name }}
                                        @endif
                                    </div>
                                    @if ($device->room->department)
                                        <div class="mt-2 text-xs text-slate-400">{{ $device->room->department }}</div>
                                    @endif
                                @else
                                    <div class="font-medium text-slate-900">Vieta nav norādīta</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->created_at?->format('d.m.Y') ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->created_at?->format('H:i') ?: '-' }}</div>
                                <div class="mt-2 text-xs text-slate-400">{{ $device->createdBy?->full_name ?: 'Sistēma' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->assignedTo?->full_name ?: 'Nav piešķirts' }}</div>
                                @if ($device->assignedTo?->job_title)
                                    <div class="mt-1 text-xs text-slate-500">{{ $device->assignedTo->job_title }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="device-status-stack">
                                    @if ($device->status === \App\Models\Device::STATUS_REPAIR && $repairStatusLabel)
                                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                            <div class="device-status-split-chip device-status-split-chip-repair" @focusin="open = true" @focusout="open = false" tabindex="0">
                                                <span class="device-status-split-main">
                                                    <x-icon name="repair" size="h-3.5 w-3.5" />
                                                    <span>Remonts</span>
                                                </span>
                                                <span class="device-status-split-sub">{{ $repairStatusLabel }}</span>
                                            </div>

                                            @if ($repairPreview)
                                                <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                                    <div class="device-request-popover-head">
                                                        <span class="device-request-popover-title">{{ $repairPreview['title'] }}</span>
                                                        <span class="device-request-popover-date">{{ $repairPreview['created_at'] }}</span>
                                                    </div>
                                                    <div class="device-request-popover-row">
                                                        <span class="device-request-popover-label">Statuss</span>
                                                        <span class="device-request-popover-value">{{ $repairPreview['status'] }}</span>
                                                    </div>
                                                    <div class="device-request-popover-row">
                                                        <span class="device-request-popover-label">Tips</span>
                                                        <span class="device-request-popover-value">{{ $repairPreview['type'] }}</span>
                                                    </div>
                                                    <div class="device-request-popover-row">
                                                        <span class="device-request-popover-label">Pieņēma remontu</span>
                                                        <span class="device-request-popover-value">{{ $repairPreview['approved_by'] }}</span>
                                                    </div>
                                                    <div class="device-request-popover-row device-request-popover-row-stack">
                                                        <span class="device-request-popover-label">Apraksts</span>
                                                        <div class="device-request-popover-copy">{{ $repairPreview['description'] }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($pendingRequestBadge)
                                        {{-- Ja ir aktīvs pieprasījums, rādīt tikai pieprasījuma badge (nevis "Aktīva" statusu) --}}
                                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                            @if (! empty($pendingRequestBadge['url']))
                                                <a href="{{ $pendingRequestBadge['url'] }}" class="device-request-badge-link {{ $pendingRequestBadge['class'] }}" @focus="open = true" @blur="open = false">
                                                    <span class="device-status-split-main">
                                                        <x-icon :name="$pendingRequestBadge['icon']" size="h-3.5 w-3.5" />
                                                        <span>{{ $pendingRequestBadge['short_label'] ?? $pendingRequestBadge['label'] }}</span>
                                                    </span>
                                                    @if (! empty($pendingRequestBadge['detail_label']))
                                                        <span class="device-status-split-sub">{{ $pendingRequestBadge['detail_label'] }}</span>
                                                    @endif
                                                </a>
                                            @else
                                                <div class="device-request-badge-link {{ $pendingRequestBadge['class'] }}">
                                                    <span class="device-status-split-main">
                                                        <x-icon :name="$pendingRequestBadge['icon']" size="h-3.5 w-3.5" />
                                                        <span>{{ $pendingRequestBadge['short_label'] ?? $pendingRequestBadge['label'] }}</span>
                                                    </span>
                                                    @if (! empty($pendingRequestBadge['detail_label']))
                                                        <span class="device-status-split-sub">{{ $pendingRequestBadge['detail_label'] }}</span>
                                                    @endif
                                                </div>
                                            @endif

                                            @if (! empty($pendingRequestBadge['preview']))
                                                <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                                    <div class="device-request-popover-head">
                                                        <span class="device-request-popover-title">{{ $pendingRequestBadge['preview']['type_label'] }}</span>
                                                        <span class="device-request-popover-date">{{ $pendingRequestBadge['preview']['submitted_at'] }}</span>
                                                    </div>
                                                    <div class="device-request-popover-row">
                                                        <span class="device-request-popover-label">Pieteicējs</span>
                                                        <span class="device-request-popover-value">{{ $pendingRequestBadge['preview']['submitted_by'] }}</span>
                                                    </div>
                                                    @if (! empty($pendingRequestBadge['preview']['recipient']))
                                                        <div class="device-request-popover-row">
                                                            <span class="device-request-popover-label">Saņēmējs</span>
                                                            <span class="device-request-popover-value">{{ $pendingRequestBadge['preview']['recipient'] }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="device-request-popover-row device-request-popover-row-stack">
                                                        <span class="device-request-popover-label">{{ $pendingRequestBadge['preview']['meta_label'] }}</span>
                                                        <div class="device-request-popover-copy">{{ $pendingRequestBadge['preview']['summary'] }}</div>
                                                    </div>
                                                    @if (! empty($pendingRequestBadge['url']))
                                                        <div class="device-request-popover-link">Atvērt pieprasījumu</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Ja nav pieprasījumu, rādīt ierīces statusu (Aktīva, Norakstīta, utt.) --}}
                                        <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="table-action-menu" x-data="{ open: false, panel: null }" @keydown.escape.window="open = false; panel = null">
                                    <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                        <span>Darbības</span>
                                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div class="table-action-list" :class="panel ? 'table-action-list-wide' : ''" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false; panel = null">
                                        <a href="{{ route('devices.show', $device) }}" class="table-action-item" @click="open = false; panel = null">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatīt</span>
                                        </a>

                                        @if (! $canManageDevices)
                                            @if ($requestAvailability['can_create_any'])
                                                <a href="{{ route('repair-requests.create', ['device_id' => $device->id]) }}" class="table-action-item text-sky-700 hover:bg-sky-50" @click="open = false; panel = null">
                                                    <x-icon name="repair" size="h-4 w-4" />
                                                    <span>Pieteikt remontu</span>
                                                </a>

                                                <a href="{{ route('writeoff-requests.create', ['device_id' => $device->id]) }}" class="table-action-item text-rose-700 hover:bg-rose-50" @click="open = false; panel = null">
                                                    <x-icon name="writeoff" size="h-4 w-4" />
                                                    <span>Pieteikt norakstīšanu</span>
                                                </a>

                                                <a href="{{ route('device-transfers.create', ['device_id' => $device->id]) }}" class="table-action-item text-emerald-700 hover:bg-emerald-50" @click="open = false; panel = null">
                                                    <x-icon name="transfer" size="h-4 w-4" />
                                                    <span>Nodot citam</span>
                                                </a>
                                            @elseif ($requestAvailability['reason'])
                                                @if (! empty($pendingRequestBadge['url']))
                                                    <a href="{{ $pendingRequestBadge['url'] }}" class="table-action-item text-sky-700 hover:bg-sky-50" @click="open = false; panel = null">
                                                        <x-icon :name="$pendingRequestBadge['icon'] ?? 'view'" size="h-4 w-4" />
                                                        <span>Skatīt pieteikumu</span>
                                                    </a>
                                                @endif
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-500">
                                                    <div class="inline-flex items-center gap-2 font-semibold text-slate-700">
                                                        <x-icon :name="$pendingRequestBadge['icon'] ?? 'clock'" size="h-3.5 w-3.5" />
                                                        <span>{{ $pendingRequestBadge['label'] ?? 'Pieteikums nav pieejams' }}</span>
                                                    </div>
                                                    <div class="mt-1">{{ $requestAvailability['reason'] }}</div>
                                                </div>
                                            @endif
                                        @endif

                                        @if ($canManageDevices)
                                            <a href="{{ route('devices.edit', $device) }}" class="table-action-item table-action-item-amber" @click="open = false; panel = null">
                                                <x-icon name="edit" size="h-4 w-4" />
                                                <span>Rediģēt</span>
                                            </a>

                                            @if ($device->status === 'active')
                                                <button type="button" class="table-action-item text-sky-700 hover:bg-sky-50" @click="panel = panel === 'room' ? null : 'room'">
                                                    <x-icon name="room" size="h-4 w-4" />
                                                    <span>Mainīt telpu</span>
                                                </button>

                                                <button type="button" class="table-action-item text-violet-700 hover:bg-violet-50" @click="panel = panel === 'assignee' ? null : 'assignee'">
                                                    <x-icon name="user" size="h-4 w-4" />
                                                    <span>Mainīt atbildīgo</span>
                                                </button>

                                                <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="target_status" value="writeoff">
                                                    <button
                                                        type="submit"
                                                        class="table-action-button table-action-button-rose"
                                                        formmethod="POST"
                                                        onclick="return confirm('Vai tiešām norakstīt šo ierīci? Pēc norakstīšanas tā vairs nebūs piešķirta lietotājam vai telpai.')"
                                                    >
                                                        <x-icon name="writeoff" size="h-4 w-4" />
                                                        <span>Norakstīt</span>
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="target_status" value="repair">
                                                    <button type="submit" class="table-action-button table-action-button-amber" formmethod="POST">
                                                        <x-icon name="repair" size="h-4 w-4" />
                                                        <span>Atdot remonta</span>
                                                    </button>
                                                </form>

                                                <div class="table-action-inline-panel" x-cloak x-show="panel === 'room'" x-transition.opacity>
                                                    <div class="table-action-inline-head">
                                                        <div>
                                                            <div class="table-action-inline-title">Mainīt telpu</div>
                                                            <div class="table-action-inline-copy">Ierīce tiks uzreiz pārvietota uz citu telpu.</div>
                                                        </div>
                                                        <button type="button" class="table-action-inline-close" @click="panel = null">
                                                            <x-icon name="x-mark" size="h-4 w-4" />
                                                        </button>
                                                    </div>

                                                    <form method="POST" action="{{ route('devices.quick-update', $device) }}" class="space-y-3">
                                                        @csrf
                                                        <input type="hidden" name="action" value="room">
                                                        <x-searchable-select
                                                            name="target_room_id"
                                                            query-name="target_room_query"
                                                            identifier="device-quick-room-{{ $device->id }}"
                                                            :options="$quickRoomSelectOptions"
                                                            :selected="(string) ($device->room_id ?? '')"
                                                            :query="$quickRoomLabel"
                                                            placeholder="Izvēlies telpu"
                                                            empty-message="Neviena telpa neatbilst meklējumam."
                                                        />
                                                        <div class="table-action-inline-actions">
                                                            <button type="button" class="btn-clear" @click="panel = null">Atcelt</button>
                                                            <button type="submit" class="btn-search">
                                                                <x-icon name="save" size="h-4 w-4" />
                                                                <span>Saglabāt</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <div class="table-action-inline-panel" x-cloak x-show="panel === 'assignee'" x-transition.opacity>
                                                    <div class="table-action-inline-head">
                                                        <div>
                                                            <div class="table-action-inline-title">Mainīt atbildīgo</div>
                                                            <div class="table-action-inline-copy">Izvēlies citu personu, kurai piešķirt ierīci.</div>
                                                        </div>
                                                        <button type="button" class="table-action-inline-close" @click="panel = null">
                                                            <x-icon name="x-mark" size="h-4 w-4" />
                                                        </button>
                                                    </div>

                                                    <form method="POST" action="{{ route('devices.quick-update', $device) }}" class="space-y-3">
                                                        @csrf
                                                        <input type="hidden" name="action" value="assignee">
                                                        <x-searchable-select
                                                            name="target_assigned_to_id"
                                                            query-name="target_assigned_to_query"
                                                            identifier="device-quick-assignee-{{ $device->id }}"
                                                            :options="$quickAssigneeSelectOptions"
                                                            :selected="(string) ($device->assigned_to_id ?? '')"
                                                            :query="$quickAssigneeLabel"
                                                            placeholder="Izvēlies atbildīgo personu"
                                                            empty-message="Neviena persona neatbilst meklējumam."
                                                        />
                                                        <div class="table-action-inline-actions">
                                                            <button type="button" class="btn-clear" @click="panel = null">Atcelt</button>
                                                            <button type="submit" class="btn-search">
                                                                <x-icon name="save" size="h-4 w-4" />
                                                                <span>Saglabāt</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Ierīces nav atrastas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

            {{ $devices->links() }}
        </div>
    </section>
</x-app-layout>
