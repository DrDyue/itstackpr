{{--
    Lapa: Remontu saraksts.
    Atbildība: rāda visus remonta ierakstus vienotā tabulā ar filtriem, kārtošanu un darbībām.
    Datu avots: RepairController@index.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $selectedDeviceLabel = collect($deviceOptions)->firstWhere('value', (string) ($filters['device_id'] ?? ''))['label'] ?? ($filters['device_query'] ?: null);
        $selectedRequesterLabel = collect($requesterOptions)->firstWhere('value', (string) ($filters['requester_id'] ?? ''))['label'] ?? ($filters['requester_query'] ?: null);
        $activeStatusLabel = count($filters['statuses']) > 0 && count($filters['statuses']) < count($statuses)
            ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')
            : null;
        $activePriorityLabel = count($filters['priorities'] ?? []) > 0 && count($filters['priorities']) < count($priorityLabels)
            ? collect($filters['priorities'])->map(fn ($priority) => $priorityLabels[$priority] ?? $priority)->implode(', ')
            : null;
        $activeTypeLabel = $filters['repair_type'] ?? null;
        $repairTypeLabels = [
            'internal' => 'Iekšējais',
            'external' => 'Ārējais',
        ];
        $detailStatusClasses = [
            'waiting' => 'request-detail-status-amber',
            'in-progress' => 'request-detail-status-sky',
            'completed' => 'request-detail-status-emerald',
            'cancelled' => 'request-detail-status-rose',
        ];
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-5xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="repair" size="h-4 w-4" />
                            <span>Serviss</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="repair" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopā</span>
                                <span class="inventory-inline-value">{{ $repairSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="clock" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Gaida</span>
                                <span class="inventory-inline-value">{{ $repairSummary['waiting'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-sky">
                                <x-icon name="stats" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Procesā</span>
                                <span class="inventory-inline-value">{{ $repairSummary['in_progress'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Pabeigti</span>
                                <span class="inventory-inline-value">{{ $repairSummary['completed'] }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber">
                            <x-icon name="repair" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Remonti</h1>
                            <p class="page-subtitle">{{ $canManageRepairs ? 'Vienota remonta tabula ar pilnu ierīces, statusa un izmaksu informāciju.' : 'Šeit redzami ar tavām ierīcēm saistītie remonta ieraksti.' }}</p>
                        </div>
                    </div>
                </div>

                @if ($canManageRepairs)
                    <div class="page-actions">
                        <a href="{{ route('repairs.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauns remonts</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div id="repairs-index-root" data-async-table-root x-data="requestDetailsDrawer()" @open-request-detail.window="show($event.detail)">
            <form
                method="GET"
                action="{{ route('repairs.index') }}"
                class="surface-toolbar surface-toolbar-elevated repairs-toolbar-surface"
                data-async-table-form
                data-async-root="#repairs-index-root"
                data-search-endpoint="{{ route('repairs.find-by-code') }}"
            >
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">
                @if ($filters['mine'] ?? false)
                    <input type="hidden" name="mine" value="1">
                @endif

                <div class="repairs-toolbar-main">
                    <label class="block repairs-toolbar-code-field">
                        <span class="crud-label">Meklēt pēc koda</span>
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                name="code"
                                value="{{ $filters['code'] }}"
                                class="crud-control"
                                placeholder="Ievadi precīzu kodu"
                                data-async-manual="true"
                                data-async-code-search="true"
                            >
                            <button type="submit" class="btn-search shrink-0" data-code-search-submit="true">
                                <x-icon name="search" size="h-4 w-4" />
                                <span>Meklēt</span>
                            </button>
                        </div>
                    </label>

                    <label class="block repairs-toolbar-text-field">
                        <span class="crud-label">Filtrēt pēc teksta</span>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, apraksts vai pieprasītājs">
                    </label>

                    <label class="block">
                        <span class="crud-label">Ierīce</span>
                        <x-searchable-select
                            name="device_id"
                            query-name="device_query"
                            identifier="repairs-device-filter"
                            :options="$deviceOptions"
                            :selected="(string) ($filters['device_id'] ?? '')"
                            :query="$selectedDeviceLabel"
                            placeholder="Izvēlies ierīci"
                            empty-message="Neviens remonts neatbilst izvēlētajai ierīcei."
                        />
                    </label>

                    <label class="block">
                        <span class="crud-label">Pieprasītājs</span>
                        <x-searchable-select
                            name="requester_id"
                            query-name="requester_query"
                            identifier="repairs-requester-filter"
                            :options="$requesterOptions"
                            :selected="(string) ($filters['requester_id'] ?? '')"
                            :query="$selectedRequesterLabel"
                            placeholder="Izvēlies pieprasītāju"
                            empty-message="Neviens pieprasītājs neatbilst meklējumam."
                        />
                    </label>

                    <div class="repairs-toolbar-date-field">
                        <x-localized-date-input name="date_from" label="No datuma" :value="$filters['date_from']" />
                    </div>

                    <div class="repairs-toolbar-date-field">
                        <x-localized-date-input name="date_to" label="Līdz datumam" :value="$filters['date_to']" />
                    </div>
                </div>

                <div class="filter-toolbar-footer repairs-filter-footer">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="quick-filter-group">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta tips</div>
                            <div class="quick-status-filters">
                                <a
                                    href="{{ route('repairs.index', array_merge(request()->except(['repair_type', 'page']), ['repair_type' => 'internal', 'statuses_filter' => '1'])) }}"
                                    class="quick-status-filter quick-status-filter-sky {{ ($filters['repair_type'] ?? '') === 'internal' ? 'quick-status-filter-active' : '' }}"
                                    data-async-link="true"
                                >
                                    <x-icon name="repair" size="h-4 w-4" />
                                    <span>Iekšējais</span>
                                </a>
                                <a
                                    href="{{ route('repairs.index', array_merge(request()->except(['repair_type', 'page']), ['repair_type' => 'external', 'statuses_filter' => '1'])) }}"
                                    class="quick-status-filter quick-status-filter-violet {{ ($filters['repair_type'] ?? '') === 'external' ? 'quick-status-filter-active' : '' }}"
                                    data-async-link="true"
                                >
                                    <x-icon name="send" size="h-4 w-4" />
                                    <span>Ārējais</span>
                                </a>
                                <a
                                    href="{{ route('repairs.index', array_merge(request()->except(['repair_type', 'page']), ['statuses_filter' => '1'])) }}"
                                    class="quick-status-filter quick-status-filter-slate {{ !isset($filters['repair_type']) ? 'quick-status-filter-active' : '' }}"
                                    data-async-link="true"
                                >
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Visi</span>
                                </a>
                            </div>
                        </div>

                        <div class="quick-filter-groups">
                            <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($filters['statuses']), minimum: 0 })">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta statuss</div>
                                <div class="quick-status-filters">
                                    @foreach ($statuses as $status)
                                        @php
                                            $toneClass = match ($status) {
                                                'waiting' => 'quick-status-filter-amber',
                                                'in-progress' => 'quick-status-filter-sky',
                                                'cancelled' => 'quick-status-filter-rose',
                                                default => 'quick-status-filter-emerald',
                                            };
                                        @endphp
                                        <button
                                            type="button"
                                            @click="toggle(@js($status)); $nextTick(() => $el.closest('form').requestSubmit())"
                                            class="quick-status-filter {{ $toneClass }}"
                                            :class="isSelected(@js($status)) ? 'quick-status-filter-active' : ''"
                                        >
                                            <x-icon :name="match($status) { 'waiting' => 'clock', 'in-progress' => 'stats', 'completed' => 'check-circle', default => 'x-circle' }" size="h-4 w-4" />
                                            <span>{{ $statusLabels[$status] ?? $status }}</span>
                                        </button>
                                    @endforeach

                                    <template x-for="value in selected" :key="'repair-status-' + value">
                                        <input type="hidden" name="status[]" :value="value">
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($filters['priorities'] ?? []), minimum: 0 })">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Prioritāte</div>
                            <div class="quick-status-filters">
                                @foreach (['low', 'medium', 'high', 'critical'] as $priority)
                                    @php
                                        $priorityToneClass = match ($priority) {
                                            'low' => 'quick-status-filter-slate',
                                            'medium' => 'quick-status-filter-sky',
                                            'high' => 'quick-status-filter-amber',
                                            default => 'quick-status-filter-rose',
                                        };
                                        $priorityIcon = match ($priority) {
                                            'low' => 'information-circle',
                                            'medium' => 'tag',
                                            'high' => 'flag',
                                            default => 'bolt',
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        @click="toggle(@js($priority)); $nextTick(() => $el.closest('form').requestSubmit())"
                                        class="quick-status-filter {{ $priorityToneClass }}"
                                        :class="isSelected(@js($priority)) ? 'quick-status-filter-active' : ''"
                                    >
                                        <x-icon :name="$priorityIcon" size="h-4 w-4" />
                                        <span>{{ $priorityLabels[$priority] ?? $priority }}</span>
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'repair-priority-' + value">
                                    <input type="hidden" name="priorities[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions justify-end">
                        <a href="{{ route('repairs.index', ['statuses_filter' => '1']) }}" class="btn-clear" data-async-link="true">
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
                    ['label' => 'Ierīce', 'value' => $selectedDeviceLabel],
                    ['label' => 'Pieprasītājs', 'value' => $selectedRequesterLabel],
                    ['label' => 'No datuma', 'value' => $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                    ['label' => 'Līdz datumam', 'value' => $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                    ['label' => 'Statuss', 'value' => $activeStatusLabel],
                    ['label' => 'Prioritāte', 'value' => $activePriorityLabel],
                    ['label' => 'Remonta tips', 'value' => $activeTypeLabel ? $repairTypeLabels[$activeTypeLabel] ?? $activeTypeLabel : null],
                    ['label' => 'Piešķirts', 'value' => ($filters['mine'] ?? false) ? 'Man' : null],
                ]"
                :clear-url="route('repairs.index', ['statuses_filter' => '1'])"
            />
            </div>

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
            @endif

            <div class="repair-table-shell mt-5 rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                <div class="repair-table-scroll">
                    <table class="repair-table-content w-full min-w-full text-sm">
                        <thead class="repair-table-head bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Attēls</th>
                                @foreach ([
                                    'code' => 'Kods',
                                    'name' => 'Nosaukums',
                                    'assigned' => 'Piešķirta',
                                    'location' => 'Atrašanās vieta',
                                    'status' => 'Remonta statuss',
                                    'priority' => 'Prioritāte',
                                    'repair_type' => 'Tips',
                                    'cost' => 'Izmaksas',
                                    'start_date' => 'Sākuma datums',
                                    'end_date' => 'Beigu datums',
                                ] as $column => $label)
                                    @php
                                        $isCurrentSort = $sorting['sort'] === $column;
                                        $headerWidthClass = match ($column) {
                                            'code' => 'table-col-code',
                                            'name' => 'table-col-name',
                                            'assigned_to' => 'table-col-person',
                                            'location' => 'table-col-location',
                                            'status' => 'table-col-status',
                                            'priority' => 'table-col-priority',
                                            'repair_type' => 'table-col-type',
                                            'cost' => 'table-col-money',
                                            'start_date', 'end_date' => 'table-col-date',
                                            default => '',
                                        };
                                        $defaultDirection = in_array($column, ['cost', 'end_date', 'start_date'], true) ? 'desc' : 'asc';
                                        $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                        $sortMessage = 'Tabula "Remonti" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                                    @endphp
                                    <th class="{{ $headerWidthClass }} px-4 py-3">
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
                                <th class="table-col-actions px-4 py-3 text-right w-[9rem]">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($repairs as $repair)
                                @php
                                    $device = $repair->device;
                                    $thumbUrl = $device?->deviceImageThumbUrl();
                                    $assignedUser = $device?->assignedTo;
                                    $requester = $repair->request?->responsibleUser ?: $repair->reporter;
                                    $roomLabel = trim(collect([
                                        $device?->room?->room_name,
                                        $device?->room?->room_number,
                                    ])->filter()->implode(' '));
                                    $locationPrimary = $roomLabel !== ''
                                        ? $roomLabel
                                        : ($device?->room?->room_number ? 'Telpa ' . $device->room->room_number : 'Atrašanās vieta nav norādīta');
                                    $locationSecondary = $device?->building?->building_name ?: null;
                                    $linkedRequestUrl = $repair->request_id
                                        ? route('repair-requests.index', [
                                            'request_id' => $repair->request_id,
                                            'statuses_filter' => 1,
                                        ])
                                        : null;
                                    $deviceShowUrl = $device ? route('devices.show', $device) : null;
                                @endphp
                                <tr class="repair-table-row border-t border-slate-100 align-top" data-table-row-id="repair-{{ $repair->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) ($device?->code ?? ''))) }}">
                                    <td class="px-4 py-4">
                                        @if ($thumbUrl)
                                            <img src="{{ $thumbUrl }}" alt="{{ $device?->name ?: 'Ierīce' }}" class="device-table-thumb">
                                        @else
                                            <div class="device-table-thumb device-table-thumb-placeholder">
                                                <x-icon name="device" size="h-4 w-4" />
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $device?->code ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Sērija: {{ $device?->serial_number ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $device?->name ?: 'Ierīce nav atrasta' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ collect([$device?->manufacturer, $device?->model])->filter()->implode(' | ') ?: 'Ražotājs un modelis nav norādīti' }}</div>
                                        <div class="mt-1 text-xs text-slate-400">{{ $device?->type?->type_name ?: 'Tips nav norādīts' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $assignedUser?->full_name ?: 'Nav piešķirta' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $requester?->full_name ? 'Pieprasītājs: ' . $requester->full_name : 'Pieprasītājs nav norādīts' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $locationPrimary !== '' ? $locationPrimary : 'Atrašanās vieta nav norādīta' }}</div>
                                        @if ($locationSecondary)
                                            <div class="mt-1 text-xs text-slate-500">{{ $locationSecondary }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="priority" :value="$repair->priority" :label="$priorityLabels[$repair->priority] ?? null" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="repair-type" :value="$repair->repair_type" :label="$typeLabels[$repair->repair_type] ?? null" />
                                    </td>
                                    <td class="px-4 py-4 tabular-nums">
                                        <div class="font-semibold text-slate-900">
                                            {{ $repair->cost !== null ? number_format((float) $repair->cost, 2, '.', ' ') . ' EUR' : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 tabular-nums">
                                        <div class="font-semibold text-slate-900">{{ $repair->start_date?->format('d.m.Y') ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 tabular-nums">
                                        <div class="font-semibold text-slate-900">{{ $repair->end_date?->format('d.m.Y') ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-right repair-table-actions-cell">
                                        <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                            <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                                <span>Darbības</span>
                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            <div class="table-action-list" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                                @if ($linkedRequestUrl)
                                                    <a href="{{ $linkedRequestUrl }}" class="table-action-item" @click="open = false">
                                                        <x-icon name="repair-request" size="h-4 w-4" />
                                                        <span>Skatīt saistīto pieprasījumu</span>
                                                    </a>
                                                @endif

                                                @if ($canManageRepairs)
                                                    <a href="{{ route('repairs.edit', $repair) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                                        <x-icon name="edit" size="h-4 w-4" />
                                                        <span>Rediģēt</span>
                                                    </a>

                                                    @if ($repair->status === 'waiting')
                                                        <form
                                                            method="POST"
                                                            action="{{ route('repairs.transition', $repair) }}"
                                                            data-app-confirm-title="Sākt remontu?"
                                                            data-app-confirm-message="Vai tiešām pārvietot šo remontu uz statusu “Procesā”?"
                                                            data-app-confirm-accept="Jā, sākt"
                                                            data-app-confirm-cancel="Nē"
                                                            data-app-confirm-tone="warning"
                                                        >
                                                            @csrf
                                                            <input type="hidden" name="target_status" value="in-progress">
                                                            <button type="submit" class="table-action-button table-action-button-sky">
                                                                <x-icon name="stats" size="h-4 w-4" />
                                                                <span>Pārvietot uz procesu</span>
                                                            </button>
                                                        </form>
                                                    @elseif ($repair->status === 'in-progress')
                                                        <form
                                                            method="POST"
                                                            action="{{ route('repairs.transition', $repair) }}"
                                                            data-app-confirm-title="Pabeigt remontu?"
                                                            data-app-confirm-message="Vai tiešām pabeigt šo remonta ierakstu?"
                                                            data-app-confirm-accept="Jā, pabeigt"
                                                            data-app-confirm-cancel="Nē"
                                                            data-app-confirm-tone="warning"
                                                        >
                                                            @csrf
                                                            <input type="hidden" name="target_status" value="completed">
                                                            <button type="submit" class="table-action-button table-action-button-emerald">
                                                                <x-icon name="check-circle" size="h-4 w-4" />
                                                                <span>Pabeigt</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <button
                                                        type="button"
                                                        class="table-action-item"
                                                        @click="open = false; $dispatch('open-request-detail', @js([
                                                            'drawer_title' => 'Remonta ātrais skats',
                                                            'drawer_subtitle' => 'Galvenā informācija par remonta statusu, saistīto pieprasījumu un izpildes gaitu.',
                                                            'status_label' => $statusLabels[$repair->status] ?? $repair->status,
                                                            'status_badge_class' => $detailStatusClasses[$repair->status] ?? 'request-detail-status-slate',
                                                            'submitted_at' => $repair->created_at?->format('d.m.Y H:i') ?: '-',
                                                            'hero_icon' => 'repair',
                                                            'hero_tone' => match ($repair->status) {
                                                                'waiting' => 'amber',
                                                                'in-progress' => 'sky',
                                                                'completed' => 'emerald',
                                                                'cancelled' => 'rose',
                                                                default => 'slate',
                                                            },
                                                            'hero_title' => $device?->name ?: 'Ierīce nav atrasta',
                                                            'hero_meta' => collect([
                                                                $device?->code ? 'Kods: '.$device->code : null,
                                                                $device?->serial_number ? 'Sērija: '.$device->serial_number : null,
                                                            ])->filter()->implode(' | ') ?: 'Ierīces identifikācija nav pilnīga',
                                                            'hero_note' => collect([$device?->manufacturer, $device?->model, $device?->type?->type_name])->filter()->implode(' | ') ?: 'Ražotājs, modelis vai tips nav pilnībā norādīts',
                                                            'primary_label' => 'Statuss un nozīme',
                                                            'primary_icon' => 'information-circle',
                                                            'primary_tone' => match ($repair->status) {
                                                                'waiting' => 'amber',
                                                                'in-progress' => 'sky',
                                                                'completed' => 'emerald',
                                                                'cancelled' => 'rose',
                                                                default => 'slate',
                                                            },
                                                            'primary_value' => $statusLabels[$repair->status] ?? $repair->status,
                                                            'primary_meta' => match ($repair->status) {
                                                                'waiting' => 'Remonts ir reģistrēts un gaida uzsākšanu.',
                                                                'in-progress' => 'Remonts pašlaik tiek veikts.',
                                                                'completed' => 'Remonts ir pabeigts un ieraksts noslēgts.',
                                                                'cancelled' => 'Remonts ir atcelts un vairs netiek turpināts.',
                                                                default => 'Statusa skaidrojums nav pieejams.',
                                                            },
                                                            'primary_note' => $linkedRequestUrl ? 'Šim remontam ir saistīts pieprasījums, kuru var atvērt atsevišķi.' : 'Remonts izveidots bez saistīta pieprasījuma.',
                                                            'primary_note_secondary' => $locationPrimary !== '' ? 'Atrašanās vieta: '.$locationPrimary : 'Atrašanās vieta nav norādīta.',
                                                            'primary_link_url' => $linkedRequestUrl ?: $deviceShowUrl,
                                                            'primary_link_label' => $linkedRequestUrl ? 'Atvērt saistīto pieprasījumu' : 'Atvērt ierīces kartīti',
                                                            'secondary_label' => 'Saistītais pieprasījums',
                                                            'secondary_icon' => 'repair-request',
                                                            'secondary_tone' => $repair->request ? 'violet' : 'slate',
                                                            'secondary_value' => $repair->request ? 'Remonta pieteikums' : 'Nav pievienots',
                                                            'secondary_meta' => $repair->request
                                                                ? ('Statuss: '.match ($repair->request->status) {
                                                                    'submitted' => 'iesniegts',
                                                                    'approved' => 'apstiprināts',
                                                                    'rejected' => 'noraidīts',
                                                                    default => $repair->request->status,
                                                                }.($repair->request?->created_at ? ' · iesniegts '.$repair->request->created_at->format('d.m.Y H:i') : ''))
                                                                : 'Saistīts remonta pieteikums nav pievienots.',
                                                            'secondary_note' => $repair->request
                                                                ? (\Illuminate\Support\Str::limit(trim((string) $repair->request->description) !== '' ? preg_replace('/\s+/u', ' ', trim((string) $repair->request->description)) : 'Pieteikumā apraksts nav pievienots.', 140))
                                                                : 'Šis remonts izveidots tieši no remonta sadaļas bez sākotnējā pieteikuma.',
                                                            'tertiary_label' => 'Prioritāte, tips un izmaksas',
                                                            'tertiary_icon' => match ($repair->priority) {
                                                                'critical' => 'flag',
                                                                'high' => 'exclamation-triangle',
                                                                'medium' => 'tag',
                                                                default => 'stats',
                                                            },
                                                            'tertiary_tone' => match ($repair->priority) {
                                                                'critical' => 'rose',
                                                                'high' => 'amber',
                                                                'medium' => 'sky',
                                                                default => 'slate',
                                                            },
                                                            'tertiary_value' => $priorityLabels[$repair->priority] ?? 'Prioritāte nav norādīta',
                                                            'tertiary_meta' => 'Remonta tips: '.($typeLabels[$repair->repair_type] ?? 'Nav norādīts'),
                                                            'tertiary_note' => collect([
                                                                $repair->cost !== null ? 'Izmaksas: '.number_format((float) $repair->cost, 2, '.', ' ').' EUR' : 'Izmaksas nav norādītas',
                                                                $assignedUser?->full_name ? 'Par ierīci atbild: '.$assignedUser->full_name : null,
                                                            ])->filter()->implode("\n"),
                                                            'description_label' => 'Remonta apraksts',
                                                            'description_icon' => 'repair',
                                                            'description_tone' => 'sky',
                                                            'description' => trim((string) $repair->description) !== ''
                                                                ? $repair->description
                                                                : 'Remonta apraksts vēl nav pievienots.',
                                                            'details_title' => 'Izpildes laika līnija',
                                                            'details_icon' => 'calendar',
                                                            'details_tone' => 'emerald',
                                                            'details_intro_label' => 'Pašreizējā gatavība:',
                                                            'details_intro' => match ($repair->status) {
                                                                'waiting' => 'Remonts vēl nav uzsākts.',
                                                                'in-progress' => 'Remonts notiek un gaida pabeigšanu.',
                                                                'completed' => 'Remonts ir pilnībā noslēgts.',
                                                                'cancelled' => 'Remonta process ir pārtraukts.',
                                                                default => 'Gatavības informācija nav pieejama.',
                                                            },
                                                            'details_body' => collect([
                                                                $requester?->full_name ? 'Pieteikumu iesniedza: '.$requester->full_name : null,
                                                                $repair->acceptedBy?->full_name ? 'Remontu pieņēma: '.$repair->acceptedBy->full_name : null,
                                                                $repair->start_date?->format('d.m.Y') ? 'Sākuma datums: '.$repair->start_date->format('d.m.Y') : null,
                                                                $repair->end_date?->format('d.m.Y') ? 'Beigu datums: '.$repair->end_date->format('d.m.Y') : null,
                                                                $locationSecondary ? 'Ēka: '.$locationSecondary : null,
                                                            ])->filter()->implode("\n"),
                                                            'details_link_url' => $deviceShowUrl,
                                                            'details_link_label' => 'Atvērt ierīces kartīti',
                                                            'details_link_icon' => 'device',
                                                        ]))"
                                                    >
                                                        <x-icon name="view" size="h-4 w-4" />
                                                        <span>Ātrais skats</span>
                                                    </button>

                                                    @if (in_array($repair->status, ['waiting', 'in-progress'], true))
                                                        <form
                                                            method="POST"
                                                            action="{{ route('repairs.transition', $repair) }}"
                                                            data-app-confirm-title="Atcelt remontu?"
                                                            data-app-confirm-message="Vai tiešām atcelt šo remonta ierakstu?"
                                                            data-app-confirm-accept="Jā, atcelt"
                                                            data-app-confirm-cancel="Nē"
                                                            data-app-confirm-tone="danger"
                                                        >
                                                            @csrf
                                                            <input type="hidden" name="target_status" value="cancelled">
                                                            <button type="submit" class="table-action-button table-action-button-rose">
                                                                <x-icon name="clear" size="h-4 w-4" />
                                                                <span>Atcelt remontu</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @elseif (! $linkedRequestUrl)
                                                    <button
                                                        type="button"
                                                        class="btn-disabled"
                                                        data-app-toast-title="Darbības nav pieejamas"
                                                        data-app-toast-message="Šim remonta ierakstam nav saistīta pieprasījuma, un tavai lomai šobrīd nav pieejamu papildu darbību."
                                                        data-app-toast-tone="info"
                                                    >
                                                        <x-icon name="information-circle" size="h-4 w-4" />
                                                        <span>Nav darbību</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-6">
                                        <x-empty-state
                                            compact
                                            icon="repair"
                                            title="Remonta ieraksti netika atrasti"
                                            description="Pamēģini paplašināt filtru nosacījumus vai notīrīt atlasītos kritērijus."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($repairs->hasPages())
                <div class="mt-5">{{ $repairs->links() }}</div>
            @endif

            <x-request-detail-drawer />
        </div>
    </section>
</x-app-layout>
