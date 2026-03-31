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

        <div id="repairs-index-root" data-async-table-root>
            <form
                method="GET"
                action="{{ route('repairs.index') }}"
                class="surface-toolbar repairs-toolbar-surface grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.5fr)]"
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

                <div class="md:col-span-2 xl:col-span-full">
                    <div class="repair-toolbar-search-grid">
                        <label class="block">
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
                        <label class="block">
                            <span class="crud-label">Filtrēt pēc teksta</span>
                            <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, apraksts vai pieprasītājs">
                        </label>
                    </div>
                </div>
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

                <x-localized-date-input name="date_from" label="No datuma" :value="$filters['date_from']" />
                <x-localized-date-input name="date_to" label="Līdz datumam" :value="$filters['date_to']" />

                <div class="filter-toolbar-footer repairs-filter-footer md:col-span-2 xl:col-span-full">
                    <div class="flex flex-wrap items-center gap-4">
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
                    ['label' => 'Piešķirts', 'value' => ($filters['mine'] ?? false) ? 'Man' : null],
                ]"
                :clear-url="route('repairs.index', ['statuses_filter' => '1'])"
            />
            </div>

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
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
                                        $defaultDirection = in_array($column, ['cost', 'end_date', 'start_date'], true) ? 'desc' : 'asc';
                                        $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                        $sortMessage = 'Tabula "Remonti" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
                                <th class="px-4 py-3 text-right w-[9rem]">Darbības</th>
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
                                                        <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                                            @csrf
                                                            <input type="hidden" name="target_status" value="in-progress">
                                                            <button type="submit" class="table-action-button table-action-button-sky">
                                                                <x-icon name="stats" size="h-4 w-4" />
                                                                <span>Pārvietot uz procesu</span>
                                                            </button>
                                                        </form>
                                                    @elseif ($repair->status === 'in-progress')
                                                        <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                                            @csrf
                                                            <input type="hidden" name="target_status" value="completed">
                                                            <button type="submit" class="table-action-button table-action-button-emerald">
                                                                <x-icon name="check-circle" size="h-4 w-4" />
                                                                <span>Pabeigt</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if (in_array($repair->status, ['waiting', 'in-progress'], true))
                                                        <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                                            @csrf
                                                            <input type="hidden" name="target_status" value="cancelled">
                                                            <button type="submit" class="table-action-button table-action-button-rose">
                                                                <x-icon name="clear" size="h-4 w-4" />
                                                                <span>Atcelt remontu</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    </a>
                                                @elseif (! $linkedRequestUrl)
                                                    <div class="px-3 py-2 text-xs font-medium text-slate-400">Nav darbību</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-16 text-center text-sm text-slate-500">
                                        Remonta ieraksti netika atrasti.
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
        </div>
    </section>
</x-app-layout>
