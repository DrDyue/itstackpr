{{--
    Lapa: Norakstīšanas pieteikumu saraksts.
    Atbildība: rāda norakstīšanas pieprasījumus tabulas veidā; admins izskata visus, lietotājs redz savus.
    Datu avots: WriteoffRequestController@index.
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

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="writeoff" size="h-4 w-4" />
                            <span>Norakstīšana</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="writeoff" size="h-3.5 w-3.5" />
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
                        <div class="page-title-icon page-title-icon-rose">
                            <x-icon name="writeoff" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Norakstīšanas pieteikumi</h1>
                            <p class="page-subtitle">{{ $canReview ? 'Admins redz visus norakstīšanas pieteikumus un var pieņemt gala lēmumu tieši tabulā.' : 'Šeit redzami visi tavi norakstīšanas pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>

                @unless ($canReview)
                    <div class="page-actions">
                        <a href="{{ route('writeoff-requests.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauns pieteikums</span>
                        </a>
                    </div>
                @endunless
            </div>
        </div>

        <div id="writeoff-requests-index-root" data-async-table-root class="writeoff-requests-index-page">
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('writeoff-requests.index') }}"
                class="devices-filter-surface"
                data-async-table-form
                data-async-root="#writeoff-requests-index-root"
            >
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <div class="devices-filter-header">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Meklēšana</span>
                        </h3>
                        <div class="devices-filter-grid">
                            <label class="devices-text-search">
                                <span>Meklēt</span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Kods, nosaukums, pieteicējs vai iemesls">
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
                                    identifier="writeoff-request-device-filter"
                                    :options="$deviceOptions"
                                    :selected="(string) ($filters['device_id'] ?? '')"
                                    :query="$selectedDeviceLabel"
                                    placeholder="Izvēlies ierīci"
                                    empty-message="Neviena ierīce neatbilst izvēlētajiem filtriem."
                                />
                            </label>

                            @if ($canReview)
                                <label class="block">
                                    <span class="crud-label">Pieteicējs</span>
                                    <x-searchable-select
                                        name="requester_id"
                                        query-name="requester_query"
                                        identifier="writeoff-request-requester-filter"
                                        :options="$requesterOptions"
                                        :selected="(string) ($filters['requester_id'] ?? '')"
                                        :query="$selectedRequesterLabel"
                                        placeholder="Izvēlies pieteicēju"
                                        empty-message="Neviens pieteicējs neatbilst meklējumam."
                                    />
                                </label>
                            @endif

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
                                        $toneClass = $status === 'submitted'
                                            ? 'quick-status-filter-amber'
                                            : ($status === 'approved' ? 'quick-status-filter-emerald' : 'quick-status-filter-rose');
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

                                <template x-for="value in selected" :key="'writeoff-request-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('writeoff-requests.index', ['statuses_filter' => 1, 'clear' => 1]) }}" class="btn-clear" data-async-link="true">
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
                    ['label' => 'Pieteicējs', 'value' => $canReview ? $selectedRequesterLabel : null],
                    ['label' => 'No datuma', 'value' => $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                    ['label' => 'Līdz datumam', 'value' => $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                    ['label' => 'Statuss', 'value' => $activeStatusLabel],
                ]"
                :clear-url="route('writeoff-requests.index', ['statuses_filter' => 1, 'clear' => 1])"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
            @endif

        {{-- Norakstīšanas pieteikumu tabula --}}
        <div class="app-table-shell">
            <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <table class="app-table-content app-table-content-wide min-w-full text-sm">
                        <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                            <tr>
                                @foreach ([
                                    'code' => 'Kods',
                                    'name' => 'Nosaukums',
                                    'requester' => 'Pieteicējs',
                                    'reason' => 'Norakstīšanas iemesls',
                                    'created_at' => 'Iesniegts',
                                    'status' => 'Statuss',
                                ] as $column => $label)
                                    <th class="px-4 py-3">
                                        @if (in_array($column, ['code', 'name', 'requester', 'created_at', 'status'], true))
                                            @php
                                                $isCurrentSort = $sorting['sort'] === $column;
                                                $defaultDirection = $column === 'created_at' ? 'desc' : 'asc';
                                                $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                                $sortMessage = 'Tabula "Norakstīšanas pieteikumi" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
                            @forelse ($requests as $writeoffRequest)
                                @php
                                    $device = $writeoffRequest->device;
                                    $deviceFilterUrl = $device
                                        ? route('devices.index', array_filter([
                                            'code' => $device->code,
                                            'q' => $device->code ? null : $device->name,
                                        ]))
                                        : null;
                                    $deviceMeta = collect([$device?->manufacturer, $device?->model])->filter()->implode(' | ');
                                    $reason = trim((string) $writeoffRequest->reason);
                                    $shortReason = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $reason), 70);
                                @endphp
                                <tr class="app-table-row border-t border-slate-100 align-top" data-table-row-id="writeoff-request-{{ $writeoffRequest->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) ($device?->code ?? ''))) }}">
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
                                        <div class="font-semibold text-slate-900">{{ $writeoffRequest->responsibleUser?->full_name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $writeoffRequest->responsibleUser?->job_title ?: ($writeoffRequest->responsibleUser?->email ?: 'Darbinieks') }}</div>
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
                                                {{ $shortReason !== '' ? $shortReason : '-' }}
                                            </button>

                                            @if ($reason !== '')
                                                <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                                    <div class="device-request-popover-head">
                                                        <span class="device-request-popover-title">Pilns norakstīšanas iemesls</span>
                                                        <span class="device-request-popover-date">{{ $writeoffRequest->created_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                                    </div>
                                                    <div class="device-request-popover-copy">{{ $reason }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $writeoffRequest->created_at?->format('d.m.Y') ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $writeoffRequest->created_at?->format('H:i') ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="request" :value="$writeoffRequest->status" :label="$statusLabels[$writeoffRequest->status] ?? null" />
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

                                                @if ($canReview && $writeoffRequest->status === 'submitted')
                                                    <form
                                                        method="POST"
                                                        action="{{ route('writeoff-requests.review', $writeoffRequest) }}"
                                                        data-app-confirm-title="Apstiprināt pieteikumu?"
                                                        data-app-confirm-message="Vai tiešām apstiprināt šo norakstīšanas pieteikumu?"
                                                        data-app-confirm-accept="Jā, apstiprināt"
                                                        data-app-confirm-cancel="Nē"
                                                        data-app-confirm-tone="warning"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="table-action-button table-action-button-amber">
                                                            <x-icon name="check-circle" size="h-4 w-4" />
                                                            <span>Apstiprināt</span>
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('writeoff-requests.review', $writeoffRequest) }}"
                                                        data-app-confirm-title="Noraidīt pieteikumu?"
                                                        data-app-confirm-message="Vai tiešām noraidīt šo norakstīšanas pieteikumu?"
                                                        data-app-confirm-accept="Jā, noraidīt"
                                                        data-app-confirm-cancel="Nē"
                                                        data-app-confirm-tone="danger"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="table-action-button table-action-button-rose">
                                                            <x-icon name="x-circle" size="h-4 w-4" />
                                                            <span>Noraidīt</span>
                                                        </button>
                                                    </form>
                                                @elseif (! $canReview && $writeoffRequest->status === 'submitted')
                                                    <a href="{{ route('my-requests.edit', ['requestType' => 'writeoff', 'requestId' => $writeoffRequest->id]) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                                        <x-icon name="edit" size="h-4 w-4" />
                                                        <span>Labot iemeslu</span>
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('my-requests.destroy', ['requestType' => 'writeoff', 'requestId' => $writeoffRequest->id]) }}"
                                                        data-app-confirm-title="Atcelt pieteikumu?"
                                                        data-app-confirm-message="Vai tiešām atcelt šo norakstīšanas pieteikumu?"
                                                        data-app-confirm-accept="Jā, atcelt"
                                                        data-app-confirm-cancel="Nē"
                                                        data-app-confirm-tone="danger"
                                                    >
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
                                        Norakstīšanas pieteikumi netika atrasti.
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
