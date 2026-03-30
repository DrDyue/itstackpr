{{--
    Lapa: Ierīču saraksts.
    Atbildība: rāda visas lietotājam pieejamās ierīces; adminam tas ir pilnais inventārs, lietotājam - tikai viņa ierīces.
    Datu avots: DeviceController@index.
    Galvenās daļas:
    1. Kopsavilkuma hero ar skaitītājiem.
    2. Filtru rīkjosla meklēšanai, telpām, tipiem, statusiem un aktīvajiem pieprasījumiem.
    3. Galvenā tabula ar statusiem, preview un ātrajām darbībām.
--}}
<x-app-layout>
    @php
        $selectedFloorLabel = $filters['floor'] !== ''
            ? ($filters['floor'] . '. stavs')
            : ($filters['floor_query'] !== '' ? $filters['floor_query'] : null);
        $selectedRoomLabel = $selectedRoom
            ? ($selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : ''))
            : ($filters['room_query'] !== '' ? $filters['room_query'] : null);
        $selectedTypeLabel = $selectedType?->type_name ?: ($filters['type_query'] !== '' ? $filters['type_query'] : null);
        $selectedAssignedUserLabel = $selectedAssignedUser?->full_name ?: ($filters['assigned_to_query'] !== '' ? $filters['assigned_to_query'] : null);
        $quickRoomSelectOptions = collect($quickRoomOptions ?? [])->values();
        $quickAssigneeSelectOptions = collect($quickAssigneeOptions ?? [])->values();
        $statusFilterLinks = [
            ['label' => 'Aktivas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
            ['label' => 'Remonta', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'amber'],
            ['label' => 'Norakstitas', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
        ];
        $requestFilterLinks = [
            ['label' => 'Remonts', 'value' => 'repair', 'icon' => 'repair-request', 'tone' => 'amber'],
            ['label' => 'Norakstisana', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
            ['label' => 'Nodosana', 'value' => 'transfer', 'icon' => 'transfer', 'tone' => 'emerald'],
        ];
        $selectedStatuses = $filters['has_status_filter'] ? $filters['statuses'] : collect($statusFilterLinks)->pluck('value')->all();
        $selectedRequestTypes = $filters['has_request_type_filter'] ? $filters['request_types'] : collect($requestFilterLinks)->pluck('value')->all();
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
            'description' => $type->category ?: '',
            'search' => implode(' ', array_filter([
                $type->type_name,
                $type->category,
                $type->description,
            ])),
        ])->values();
        $floorSelectOptions = collect($floorOptions)->map(fn ($floor) => [
            'value' => (string) $floor,
            'label' => $floor . '. stavs',
            'description' => 'Filtrs pec stava',
            'search' => $floor . ' ' . $floor . '. stavs',
        ])->values();
        $toolbarGridClass = $canManageDevices
            ? 'surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.85fr)_minmax(0,1fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)]'
            : 'surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,0.9fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)]';
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="device" size="h-4 w-4" />
                            <span>Inventars</span>
                        </div>

                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="device" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Aktivas</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['active'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="repair" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Remonta</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['repair'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="writeoff" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Norakstitas</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['writeoff'] }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierices</h1>
                            <p class="page-subtitle">{{ $canManageDevices ? 'Pilns iericu saraksts un parvaldiba.' : 'Tavas piesaistitas ierices.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageDevices)
                    <div class="page-actions">
                        <a href="{{ route('devices.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauna ierice</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Filtru rīkjosla: meklēšana, telpas, tipi, statusi un aktīvie pieprasījumi. --}}
        <form
            method="GET"
            action="{{ route('devices.index') }}"
            class="{{ $toolbarGridClass }}"
            x-data="{}"
            @searchable-select-updated.window="if ($event.detail.identifier === 'device-floor-filter') { $dispatch('searchable-select-clear', { target: 'device-room-filter' }) }"
        >
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, razotajs...">
            </label>
            <label class="block">
                <span class="crud-label">Kods</span>
                <input type="text" name="code" value="{{ $filters['code'] }}" class="crud-control">
            </label>
            @if ($canManageDevices)
                <label class="block">
                    <span class="crud-label">Pieskirta</span>
                    <input type="text" name="assigned_to_query" value="{{ $filters['assigned_to_query'] }}" class="crud-control" placeholder="Lietotaja vards">
                    @if ($filters['assigned_to_id'] !== '')
                        <input type="hidden" name="assigned_to_id" value="{{ $filters['assigned_to_id'] }}">
                    @endif
                </label>
            @endif
            <label class="block">
                <span class="crud-label">Stavs</span>
                <x-searchable-select
                    name="floor"
                    query-name="floor_query"
                    identifier="device-floor-filter"
                    :options="$floorSelectOptions"
                    :selected="$filters['floor']"
                    :query="$selectedFloorLabel"
                    placeholder="Izvelies vai raksti stavu"
                    empty-message="Neviens stavs neatbilst meklejumam."
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
                    empty-message="Neviena telpa neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <x-searchable-select
                    name="type"
                    query-name="type_query"
                    :options="$typeSelectOptions"
                    :selected="$filters['type']"
                    :query="$selectedTypeLabel"
                    placeholder="Raksti tipa nosaukumu"
                    empty-message="Neviens tips neatbilst meklejumam."
                />
            </label>

            <div class="filter-toolbar-footer md:col-span-2 xl:col-span-full">
                <div class="quick-filter-groups">
                    <div class="quick-filter-group">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices statuss</div>
                        <div class="quick-status-filters">
                            @foreach ($statusFilterLinks as $statusFilter)
                                @php
                                    $query = request()->except('page', 'status');
                                    $statusValues = collect($selectedStatuses);
                                    $isActive = $statusValues->contains($statusFilter['value']);
                                    $nextStatuses = $isActive
                                        ? $statusValues->reject(fn ($value) => $value === $statusFilter['value'])->values()->all()
                                        : $statusValues->push($statusFilter['value'])->unique()->values()->all();

                                    if (count($nextStatuses) === 0 || count($nextStatuses) === count($statusFilterLinks)) {
                                        unset($query['status']);
                                    } else {
                                        $query['status'] = $nextStatuses;
                                    }
                                @endphp
                                <a
                                    href="{{ route('devices.index', $query) }}"
                                    class="quick-status-filter quick-status-filter-{{ $statusFilter['tone'] }} {{ $isActive ? 'quick-status-filter-active' : '' }}"
                                >
                                    <x-icon :name="$statusFilter['icon']" size="h-4 w-4" />
                                    <span>{{ $statusFilter['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="quick-filter-group quick-filter-group-end">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Aktivie pieprasijumi</div>
                        <div class="quick-status-filters">
                            @foreach ($requestFilterLinks as $requestFilter)
                                @php
                                    $query = request()->except('page', 'request_type');
                                    $requestValues = collect($selectedRequestTypes);
                                    $isActive = $requestValues->contains($requestFilter['value']);
                                    $nextRequestTypes = $isActive
                                        ? $requestValues->reject(fn ($value) => $value === $requestFilter['value'])->values()->all()
                                        : $requestValues->push($requestFilter['value'])->unique()->values()->all();

                                    if (count($nextRequestTypes) === 0 || count($nextRequestTypes) === count($requestFilterLinks)) {
                                        unset($query['request_type']);
                                    } else {
                                        $query['request_type'] = $nextRequestTypes;
                                    }
                                @endphp
                                <a
                                    href="{{ route('devices.index', $query) }}"
                                    class="quick-status-filter quick-status-filter-{{ $requestFilter['tone'] }} {{ $isActive ? 'quick-status-filter-active' : '' }}"
                                >
                                    <x-icon :name="$requestFilter['icon']" size="h-4 w-4" />
                                    <span>{{ $requestFilter['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="toolbar-actions justify-end">
                    <button type="submit" class="btn-search">
                        <x-icon name="search" size="h-4 w-4" />
                        <span>Meklet</span>
                    </button>
                    <a href="{{ route('devices.index') }}" class="btn-clear">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Notirit</span>
                    </a>
                </div>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Kods', 'value' => $filters['code']],
                ['label' => 'Pieskirta', 'value' => $canManageDevices ? $selectedAssignedUserLabel : null],
                ['label' => 'Stavs', 'value' => $selectedFloorLabel],
                ['label' => 'Telpa', 'value' => $selectedRoomLabel],
                ['label' => 'Tips', 'value' => $selectedTypeLabel],
                ['label' => 'Statuss', 'value' => $filters['has_status_filter'] ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ') : null],
                ['label' => 'Pieprasijumi', 'value' => $filters['has_request_type_filter'] ? collect($filters['request_types'])->map(fn ($type) => collect($requestFilterLinks)->firstWhere('value', $type)['label'] ?? $type)->implode(', ') : null],
            ]"
            :clear-url="route('devices.index')"
        />

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        {{-- Galvenā ierīču tabula ar statusu preview un admina ātrajām darbībām. --}}
        <div class="device-table-shell">
            <div class="device-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Attels</th>
                        <th class="px-4 py-3">Kods</th>
                        <th class="px-4 py-3">Nosaukums</th>
                        <th class="px-4 py-3">Atrasanas vieta</th>
                        <th class="px-4 py-3">Izveidots</th>
                        <th class="px-4 py-3">Pieskirta</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Darbibas</th>
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
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-4 py-4">
                                @if ($thumbUrl)
                                    <img src="{{ $thumbUrl }}" alt="{{ $device->name }}" class="device-table-thumb">
                                @else
                                    <div class="device-table-thumb device-table-thumb-placeholder">
                                        <x-icon name="device" size="h-4 w-4" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</div>
                                @if ($device->serial_number)
                                    <div class="mt-1 text-xs text-slate-500">Serija: {{ $device->serial_number }}</div>
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
                                    <div class="font-medium text-slate-900">Vieta nav noradita</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->created_at?->format('d.m.Y') ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->created_at?->format('H:i') ?: '-' }}</div>
                                <div class="mt-2 text-xs text-slate-400">{{ $device->createdBy?->full_name ?: 'Sistema' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
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
                                                        <span class="device-request-popover-label">Pienema remontu</span>
                                                        <span class="device-request-popover-value">{{ $repairPreview['approved_by'] }}</span>
                                                    </div>
                                                    <div class="device-request-popover-row device-request-popover-row-stack">
                                                        <span class="device-request-popover-label">Apraksts</span>
                                                        <div class="device-request-popover-copy">{{ $repairPreview['description'] }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                                    @endif

                                    @if ($pendingRequestBadge)
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
                                                        <span class="device-request-popover-label">Pieteicejs</span>
                                                        <span class="device-request-popover-value">{{ $pendingRequestBadge['preview']['submitted_by'] }}</span>
                                                    </div>
                                                    @if (! empty($pendingRequestBadge['preview']['recipient']))
                                                        <div class="device-request-popover-row">
                                                            <span class="device-request-popover-label">Sanemejs</span>
                                                            <span class="device-request-popover-value">{{ $pendingRequestBadge['preview']['recipient'] }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="device-request-popover-row device-request-popover-row-stack">
                                                        <span class="device-request-popover-label">{{ $pendingRequestBadge['preview']['meta_label'] }}</span>
                                                        <div class="device-request-popover-copy">{{ $pendingRequestBadge['preview']['summary'] }}</div>
                                                    </div>
                                                    @if (! empty($pendingRequestBadge['url']))
                                                        <div class="device-request-popover-link">Atvert pieprasijumu</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="table-action-menu" x-data="{ open: false, panel: null }" @keydown.escape.window="open = false; panel = null">
                                    <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                        <span>Darbibas</span>
                                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div class="table-action-list" :class="panel ? 'table-action-list-wide' : ''" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false; panel = null">
                                        <a href="{{ route('devices.show', $device) }}" class="table-action-item" @click="open = false; panel = null">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatit</span>
                                        </a>

                                        @if (! $canManageDevices)
                                            @if ($requestAvailability['can_create_any'])
                                                <a href="{{ route('repair-requests.create', ['device_id' => $device->id]) }}" class="table-action-item text-sky-700 hover:bg-sky-50" @click="open = false; panel = null">
                                                    <x-icon name="repair" size="h-4 w-4" />
                                                    <span>Pieteikt remontu</span>
                                                </a>

                                                <a href="{{ route('writeoff-requests.create', ['device_id' => $device->id]) }}" class="table-action-item text-rose-700 hover:bg-rose-50" @click="open = false; panel = null">
                                                    <x-icon name="writeoff" size="h-4 w-4" />
                                                    <span>Pieteikt norakstisanu</span>
                                                </a>

                                                <a href="{{ route('device-transfers.create', ['device_id' => $device->id]) }}" class="table-action-item text-emerald-700 hover:bg-emerald-50" @click="open = false; panel = null">
                                                    <x-icon name="transfer" size="h-4 w-4" />
                                                    <span>Nodot citam</span>
                                                </a>
                                            @elseif ($requestAvailability['reason'])
                                                @if (! empty($pendingRequestBadge['url']))
                                                    <a href="{{ $pendingRequestBadge['url'] }}" class="table-action-item text-sky-700 hover:bg-sky-50" @click="open = false; panel = null">
                                                        <x-icon :name="$pendingRequestBadge['icon'] ?? 'view'" size="h-4 w-4" />
                                                        <span>Skatit pieteikumu</span>
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
                                                <span>Rediget</span>
                                            </a>

                                            @if ($device->status === 'active')
                                                <button type="button" class="table-action-item text-sky-700 hover:bg-sky-50" @click="panel = panel === 'room' ? null : 'room'">
                                                    <x-icon name="room" size="h-4 w-4" />
                                                    <span>Mainit telpu</span>
                                                </button>

                                                <button type="button" class="table-action-item text-violet-700 hover:bg-violet-50" @click="panel = panel === 'assignee' ? null : 'assignee'">
                                                    <x-icon name="user" size="h-4 w-4" />
                                                    <span>Mainit atbildigo</span>
                                                </button>

                                                <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="target_status" value="writeoff">
                                                    <button
                                                        type="submit"
                                                        class="table-action-button table-action-button-rose"
                                                        formmethod="POST"
                                                        onclick="return confirm('Vai tiesam norakstit so ierici? Pec norakstisanas ta vairs nebus pieskirta lietotajam vai telpai.')"
                                                    >
                                                        <x-icon name="writeoff" size="h-4 w-4" />
                                                        <span>Norakstit</span>
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
                                                            <div class="table-action-inline-title">Mainit telpu</div>
                                                            <div class="table-action-inline-copy">Ierice tiks uzreiz parvietota uz citu telpu.</div>
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
                                                            placeholder="Izvelies telpu"
                                                            empty-message="Neviena telpa neatbilst meklejumam."
                                                        />
                                                        <div class="table-action-inline-actions">
                                                            <button type="button" class="btn-clear" @click="panel = null">Atcelt</button>
                                                            <button type="submit" class="btn-search">
                                                                <x-icon name="save" size="h-4 w-4" />
                                                                <span>Saglabat</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <div class="table-action-inline-panel" x-cloak x-show="panel === 'assignee'" x-transition.opacity>
                                                    <div class="table-action-inline-head">
                                                        <div>
                                                            <div class="table-action-inline-title">Mainit atbildigo</div>
                                                            <div class="table-action-inline-copy">Izvelies citu personu, kurai pieskirt ierici.</div>
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
                                                            placeholder="Izvelies atbildigo personu"
                                                            empty-message="Neviena persona neatbilst meklejumam."
                                                        />
                                                        <div class="table-action-inline-actions">
                                                            <button type="button" class="btn-clear" @click="panel = null">Atcelt</button>
                                                            <button type="submit" class="btn-search">
                                                                <x-icon name="save" size="h-4 w-4" />
                                                                <span>Saglabat</span>
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
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Ierices nav atrastas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $devices->links() }}
    </section>
</x-app-layout>
