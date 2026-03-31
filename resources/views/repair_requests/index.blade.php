{{--
    Lapa: Remonta pieteikumu saraksts.
    Atbildība: rāda remonta pieprasījumus tabulas veidā; lietotājs redz savus, admins redz visus un var tos izskatīt.
    Datu avots: RepairRequestController@index.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $selectedDeviceLabel = collect($deviceOptions)->firstWhere('value', (string) ($filters['device_id'] ?? ''))['label'] ?? ($filters['device_query'] ?: null);
        $selectedRequesterLabel = collect($requesterOptions)->firstWhere('value', (string) ($filters['requester_id'] ?? ''))['label'] ?? ($filters['requester_query'] ?: null);
        $activeStatusLabel = count($filters['statuses']) > 0 && count($filters['statuses']) < count($statuses)
            ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')
            : null;
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="repair-request" size="h-4 w-4" />
                            <span>Remonts</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="repair-request" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopā</span>
                                <span class="inventory-inline-value">{{ $requestSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="clock" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Iesniegti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['submitted'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Apstiprināti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['approved'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="x-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Noraidīti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['rejected'] }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber">
                            <x-icon name="repair-request" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Remonta pieteikumi</h1>
                            <p class="page-subtitle">{{ $canReview ? 'Admins redz visus remonta pieteikumus un var tos izskatīt tieši no tabulas.' : 'Šeit vari pārskatīt savus remonta pieteikumus un pārvaldīt vēl neizskatītos ierakstus.' }}</p>
                        </div>
                    </div>
                </div>

                @unless ($canReview)
                    <div class="page-actions">
                        <a href="{{ route('repair-requests.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauns pieteikums</span>
                        </a>
                    </div>
                @endunless
            </div>
        </div>

        <div id="repair-requests-index-root" data-async-table-root" class="repair-requests-index-page">
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('repair-requests.index') }}"
                class="devices-filter-surface"
                data-async-table-form
                data-async-root="#repair-requests-index-root"
            >
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">
                @if (! empty($filters['request_id']))
                    <input type="hidden" name="request_id" value="{{ $filters['request_id'] }}">
                @endif

                <div class="devices-filter-header">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Meklēšana</span>
                        </h3>
                        <div class="devices-filter-grid">
                            <label class="devices-text-search">
                                <span>Meklēt</span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Kods, nosaukums, pieteicējs vai apraksts">
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
                            <label class="block">
                                <span class="crud-label">Ierīce</span>
                                <x-searchable-select
                                    name="device_id"
                                    query-name="device_query"
                                    identifier="repair-request-device-filter"
                                    :options="$deviceOptions"
                                    :selected="(string) ($filters['device_id'] ?? '')"
                                    :query="$selectedDeviceLabel"
                                    placeholder="Izvēlies ierīci"
                                    empty-message="Neviens remonta pieteikums neatbilst izvēlētajai ierīcei."
                                />
                            </label>

                            <label class="block">
                                <span class="crud-label">Pieteicējs</span>
                                <x-searchable-select
                                    name="requester_id"
                                    query-name="requester_query"
                                    identifier="repair-request-requester-filter"
                                    :options="$requesterOptions"
                                    :selected="(string) ($filters['requester_id'] ?? '')"
                                    :query="$selectedRequesterLabel"
                                    placeholder="Izvēlies pieteicēju"
                                    empty-message="Neviens pieteicējs neatbilst meklējumam."
                                />
                            </label>

                            <x-localized-date-input name="date_from" label="No datuma" :value="$filters['date_from']" />
                            <x-localized-date-input name="date_to" label="Līdz datumam" :value="$filters['date_to']" />
                        </div>
                    </div>
                </div>

                <div class="filter-toolbar-footer">
                    <div class="quick-filter-groups">
                        <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($filters['statuses']), minimum: 0 })">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Statuss</div>
                            <div class="quick-status-filters">
                                @foreach ($statuses as $status)
                                    @php
                                        $isWarning = $status === 'submitted';
                                        $isSuccess = $status === 'approved';
                                        $toneClass = $isWarning
                                            ? 'quick-status-filter-amber'
                                            : ($isSuccess ? 'quick-status-filter-emerald' : 'quick-status-filter-rose');
                                    @endphp
                                    <button
                                        type="button"
                                        @click="toggle(@js($status)); $nextTick(() => $el.closest('form').requestSubmit())"
                                        class="quick-status-filter {{ $toneClass }}"
                                        :class="isSelected(@js($status)) ? 'quick-status-filter-active' : ''"
                                    >
                                        <x-status-pill context="request" :value="$status" :label="$statusLabels[$status] ?? null" />
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'repair-request-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('repair-requests.index') }}" class="btn-clear" data-async-link="true">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>Notīrīt filtrus</span>
                        </a>
                    </div>
                </div>
            </form>

            <x-active-filters
                :items="[
                    ['label' => 'Meklēt', 'value' => $filters['q']],
                    ['label' => 'Ierīce', 'value' => $selectedDeviceLabel],
                    ['label' => 'Pieteicējs', 'value' => $selectedRequesterLabel],
                    ['label' => 'No datuma', 'value' => $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                    ['label' => 'Līdz datumam', 'value' => $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                    ['label' => 'Statuss', 'value' => $activeStatusLabel],
                ]"
                :clear-url="route('repair-requests.index')"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
            @endif

        {{-- Remonta pieteikumu tabula --}}
        <div class="device-table-shell">
            <div class="device-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                @foreach ([
                                    'code' => 'Kods',
                                    'name' => 'Nosaukums',
                                    'requester' => 'Pieteicējs',
                                    'description' => 'Problēmas apraksts',
                                    'created_at' => 'Iesniegts',
                                    'status' => 'Statuss',
                                ] as $column => $label)
                                    <th class="px-4 py-3">
                                        @if (in_array($column, ['code', 'name', 'requester', 'created_at', 'status'], true))
                                            @php
                                                $isCurrentSort = $sorting['sort'] === $column;
                                                $defaultDirection = $column === 'created_at' ? 'desc' : 'asc';
                                                $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                                $sortMessage = 'Tabula "Remonta pieteikumi" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                                            @endphp
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
                                        @else
                                            <span class="font-semibold text-slate-500">{{ $label }}</span>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-right">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $repairRequest)
                                @php
                                    $device = $repairRequest->device;
                                    $deviceFilterUrl = $device
                                        ? route('devices.index', array_filter([
                                            'code' => $device->code,
                                            'q' => $device->code ? null : $device->name,
                                        ]))
                                        : null;
                                    $deviceMeta = collect([$device?->manufacturer, $device?->model])->filter()->implode(' | ');
                                    $description = trim((string) $repairRequest->description);
                                    $shortDescription = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $description), 70);
                                @endphp
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $device?->code ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Sērija: {{ $device?->serial_number ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $device?->name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $deviceMeta !== '' ? $deviceMeta : 'Ražotājs un modelis nav norādīti' }}</div>
                                        <div class="mt-1 text-xs text-slate-400">{{ $device?->type?->type_name ?: 'Tips nav norādīts' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $repairRequest->responsibleUser?->full_name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $repairRequest->responsibleUser?->job_title ?: ($repairRequest->responsibleUser?->email ?: 'Darbinieks') }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="relative" x-data="{ open: false }">
                                            <button
                                                type="button"
                                                class="max-w-[22rem] truncate text-left text-sm text-slate-700 hover:text-slate-900"
                                                @mouseenter="open = true"
                                                @mouseleave="open = false"
                                                @focus="open = true"
                                                @blur="open = false"
                                            >
                                                {{ $shortDescription !== '' ? $shortDescription : '-' }}
                                            </button>

                                            @if ($description !== '')
                                                <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                                    <div class="device-request-popover-head">
                                                        <span class="device-request-popover-title">Pilns problēmas apraksts</span>
                                                        <span class="device-request-popover-date">{{ $repairRequest->created_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                                    </div>
                                                    <div class="device-request-popover-copy">{{ $description }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $repairRequest->created_at?->format('d.m.Y') ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $repairRequest->created_at?->format('H:i') ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="request" :value="$repairRequest->status" :label="$statusLabels[$repairRequest->status] ?? null" />
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                            <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                                <span>Darbības</span>
                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            <div class="table-action-list" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                                @if ($deviceFilterUrl)
                                                    <a href="{{ $deviceFilterUrl }}" class="table-action-item" @click="open = false">
                                                        <x-icon name="view" size="h-4 w-4" />
                                                        <span>Skatīt saistīto ierīci</span>
                                                    </a>
                                                @endif

                                                @if ($canReview && $repairRequest->status === 'submitted')
                                                    <form method="POST" action="{{ route('repair-requests.review', $repairRequest) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="table-action-button table-action-button-amber">
                                                            <x-icon name="check-circle" size="h-4 w-4" />
                                                            <span>Apstiprināt</span>
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('repair-requests.review', $repairRequest) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="table-action-button table-action-button-rose">
                                                            <x-icon name="x-circle" size="h-4 w-4" />
                                                            <span>Noraidīt</span>
                                                        </button>
                                                    </form>
                                                @elseif (! $canReview && $repairRequest->status === 'submitted')
                                                    <a href="{{ route('my-requests.edit', ['requestType' => 'repair', 'requestId' => $repairRequest->id]) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                                        <x-icon name="edit" size="h-4 w-4" />
                                                        <span>Labot aprakstu</span>
                                                    </a>

                                                    <form method="POST" action="{{ route('my-requests.destroy', ['requestType' => 'repair', 'requestId' => $repairRequest->id]) }}" onsubmit="return confirm('Vai tiešām atcelt šo pieteikumu?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="table-action-button table-action-button-rose">
                                                            <x-icon name="x-mark" size="h-4 w-4" />
                                                            <span>Atcelt pieteikumu</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-16 text-center text-sm text-slate-500">
                                        Remonta pieteikumi netika atrasti.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($requests->hasPages())
                <div class="mt-5">{{ $requests->links() }}</div>
            @endif
        </div>
    </section>
</x-app-layout>
