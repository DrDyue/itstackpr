{{--
    Lapa: Ierīču nodošanas pieprasījumu saraksts.
    Atbildība: rāda visus nodošanas pieprasījumus tabulas veidā; admins redz kopskatu, lietotājs redz savus nosūtītos un saņemtos.
    Datu avots: DeviceTransferController@index.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $selectedDeviceLabel = collect($deviceOptions)->firstWhere('value', (string) ($filters['device_id'] ?? ''))['label'] ?? ($filters['device_query'] ?: null);
        $selectedRequesterLabel = collect($requesterOptions)->firstWhere('value', (string) ($filters['requester_id'] ?? ''))['label'] ?? ($filters['requester_query'] ?: null);
        $selectedRecipientLabel = collect($recipientOptions)->firstWhere('value', (string) ($filters['recipient_id'] ?? ''))['label'] ?? ($filters['recipient_query'] ?: null);
        $activeStatusLabel = count($filters['statuses']) > 0 && count($filters['statuses']) < count($statuses)
            ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')
            : null;
        $isIncomingFilter = $filters['incoming'] ?? false;
        $detailStatusClasses = [
            'submitted' => 'request-detail-status-amber',
            'approved' => 'request-detail-status-emerald',
            'rejected' => 'request-detail-status-rose',
        ];
    @endphp

    <section class="{{ $isAdmin ? 'app-shell app-shell-wide' : 'app-shell' }}">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="transfer" size="h-4 w-4" />
                            <span>Nodošana</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="transfer" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopā</span>
                                <span class="inventory-inline-value">{{ $transferSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="clock" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Iesniegti</span>
                                <span class="inventory-inline-value">{{ $transferSummary['submitted'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Apstiprināti</span>
                                <span class="inventory-inline-value">{{ $transferSummary['approved'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="x-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Noraidīti</span>
                                <span class="inventory-inline-value">{{ $transferSummary['rejected'] }}</span>
                            </span>
                            @if (($incomingPendingCount ?? 0) > 0)
                                <span class="inventory-inline-chip inventory-inline-chip-amber">
                                    <x-icon name="exclamation-triangle" size="h-3.5 w-3.5" />
                                    <span class="inventory-inline-label">Jāizskata</span>
                                    <span class="inventory-inline-value">{{ $incomingPendingCount }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald">
                            <x-icon name="transfer" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierīču pārsūtīšanas pieteikumi</h1>
                            <p class="page-subtitle">{{ $isAdmin ? 'Admins redz kopējo nodošanas vēsturi un saistītās ierīces.' : 'Šeit redzami tavi nosūtītie un saņemtie ierīču nodošanas pieprasījumi.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="page-actions">
                    <a href="{{ route('device-transfers.create') }}" class="btn-create">
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Jauns pieteikums</span>
                    </a>
                </div>
            </div>
        </div>

        <div id="device-transfers-index-root" data-async-table-root>
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('device-transfers.index') }}"
                class="space-y-3"
                data-async-table-form
                data-async-root="#device-transfers-index-root"
            >
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <div class="devices-filter-surface devices-filter-surface-elevated">
                    <div class="toolbar-panels toolbar-panels-wide">
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
                                        class="crud-control"
                                        placeholder="Ierīces kods"
                                        autocomplete="off"
                                    >
                                </label>
                                <button type="submit" class="btn-search">
                                    <x-icon name="search" size="h-4 w-4" />
                                    <span>Meklēt</span>
                                </button>
                            </div>
                            </div>
                        </div>

                        <div class="devices-filter-section">
                            <h3 class="devices-filter-title">
                                <x-icon name="filter" size="h-4 w-4" />
                                <span>Filtri</span>
                            </h3>
                            <div class="{{ $isAdmin ? 'transfer-toolbar-filters-grid transfer-toolbar-filters-grid-admin' : 'transfer-toolbar-filters-grid' }}">
                            <label class="devices-text-search">
                                <span>Filtrēt pēc teksta</span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, pieteicējs, saņēmējs vai iemesls">
                            </label>

                            <label class="block">
                                <span class="crud-label">Ierīce</span>
                                <x-searchable-select
                                    name="device_id"
                                    query-name="device_query"
                                    identifier="transfer-device-filter"
                                    :options="$deviceOptions"
                                    :selected="(string) ($filters['device_id'] ?? '')"
                                    :query="$selectedDeviceLabel"
                                    placeholder="Izvēlies ierīci"
                                    empty-message="Neviena ierīce neatbilst izvēlētajiem filtriem."
                                />
                            </label>

                            @if ($isAdmin)
                                <label class="block">
                                    <span class="crud-label">Pieteicējs</span>
                                    <x-searchable-select
                                        name="requester_id"
                                        query-name="requester_query"
                                        identifier="transfer-requester-filter"
                                        :options="$requesterOptions"
                                        :selected="(string) ($filters['requester_id'] ?? '')"
                                        :query="$selectedRequesterLabel"
                                        placeholder="Izvēlies pieteicēju"
                                        empty-message="Neviens pieteicējs neatbilst meklējumam."
                                    />
                                </label>

                                <label class="block">
                                    <span class="crud-label">Saņēmējs</span>
                                    <x-searchable-select
                                        name="recipient_id"
                                        query-name="recipient_query"
                                        identifier="transfer-recipient-filter"
                                        :options="$recipientOptions"
                                        :selected="(string) ($filters['recipient_id'] ?? '')"
                                        :query="$selectedRecipientLabel"
                                        placeholder="Izvēlies saņēmēju"
                                        empty-message="Neviens saņēmējs neatbilst meklējumam."
                                    />
                                </label>
                            @else
                                <label class="block">
                                    <span class="crud-label">Kam nosūtīju</span>
                                    <x-searchable-select
                                        name="recipient_id"
                                        query-name="recipient_query"
                                        identifier="transfer-recipient-filter"
                                        :options="$recipientOptions"
                                        :selected="(string) ($filters['recipient_id'] ?? '')"
                                        :query="$selectedRecipientLabel"
                                        placeholder="Izvēlies saņēmēju"
                                        empty-message="Neviens saņēmējs neatbilst meklējumam."
                                    />
                                </label>

                                <label class="block">
                                    <span class="crud-label">No kā saņēmu</span>
                                    <x-searchable-select
                                        name="requester_id"
                                        query-name="requester_query"
                                        identifier="transfer-requester-filter"
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
                        @if (! $isAdmin)
                            <div class="quick-filter-group">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ātrie filtri</div>
                                <div class="quick-status-filters">
                                    <a
                                        href="{{ route('device-transfers.index', array_merge(request()->except(['incoming']), ['incoming' => 1])) }}"
                                        class="quick-status-filter quick-status-filter-sky {{ $isIncomingFilter ? 'quick-status-filter-active' : '' }}"
                                    >
                                        <x-icon name="transfer" size="h-4 w-4" />
                                        <span>Ienākošie piedāvājumi</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($filters['statuses']), minimum: 0 })">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Statuss</div>
                            <div class="quick-status-filters">
                                @foreach ($statuses as $status)
                                    @php
                                        $toneClass = $status === 'submitted'
                                            ? 'quick-status-filter-amber'
                                            : ($status === 'approved' ? 'quick-status-filter-emerald' : 'quick-status-filter-rose');
                                        $iconName = match ($status) {
                                            'submitted' => 'clock',
                                            'approved' => 'check-circle',
                                            'rejected' => 'x-circle',
                                            default => 'information-circle',
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        @click="toggle(@js($status)); $nextTick(() => $el.closest('form').requestSubmit())"
                                        class="quick-status-filter {{ $toneClass }}"
                                        :class="isSelected(@js($status)) ? 'quick-status-filter-active' : ''"
                                    >
                                        <x-icon :name="$iconName" size="h-4 w-4" />
                                        <span>{{ $statusLabels[$status] ?? $status }}</span>
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'transfer-request-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('device-transfers.index', ['statuses_filter' => 1, 'clear' => 1]) }}" class="btn-clear" data-async-link="true">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>Notīrīt filtrus</span>
                        </a>
                    </div>
                        </div>
                    </div>
                </div>
            </form>

            <x-active-filters
                :items="[
                    ['label' => 'Filtrēt tekstu', 'value' => $filters['q']],
                    ['label' => 'Ierīce', 'value' => $selectedDeviceLabel],
                    ['label' => 'Pieteicējs', 'value' => $isAdmin ? $selectedRequesterLabel : ($isIncomingFilter ? null : $selectedRequesterLabel)],
                    ['label' => 'Saņēmējs', 'value' => $isAdmin ? $selectedRecipientLabel : ($isIncomingFilter ? null : $selectedRecipientLabel)],
                    ['label' => 'Ienākošie', 'value' => $isIncomingFilter ? 'Jā' : null],
                    ['label' => 'No datuma', 'value' => $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                    ['label' => 'Līdz datumam', 'value' => $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                    ['label' => 'Statuss', 'value' => $activeStatusLabel],
                ]"
                :clear-url="route('device-transfers.index', ['statuses_filter' => 1, 'clear' => 1])"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
            @endif

            @if (($incomingPendingCount ?? 0) > 0 && ! $isAdmin)
                <div class="mt-4 rounded-[1.5rem] border border-amber-200 bg-amber-50/90 px-5 py-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-sm">
                            <x-icon name="exclamation-triangle" size="h-5 w-5" />
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-amber-950">Tev ir {{ $incomingPendingCount }} ienākošs pārsūtīšanas pieteikums{{ $incomingPendingCount > 1 ? 'i' : '' }}</div>
                            <div class="mt-1 text-sm text-amber-900">Tabulā vari uzreiz apstiprināt vai noraidīt ienākošos ierīču nodošanas pieprasījumus.</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-4 device-table-shell">
                <div class="device-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="table-col-image px-4 py-3 text-center">Attēls</th>
                                @foreach ([
                                    'code' => 'Kods',
                                    'name' => 'Nosaukums',
                                    'requester' => 'Pieteicējs',
                                    'recipient' => 'Saņēmējs',
                                    'reason' => 'Iemesls',
                                    'created_at' => 'Iesniegts',
                                    'status' => 'Statuss',
                                ] as $column => $label)
                                    @php
                                        $headerWidthClass = match ($column) {
                                            'code' => 'table-col-code',
                                            'name' => 'table-col-name',
                                            'requester', 'recipient' => 'table-col-person',
                                            'reason' => 'table-col-note',
                                            'created_at' => 'table-col-date',
                                            'status' => 'table-col-status',
                                            default => '',
                                        };
                                    @endphp
                                    <th class="{{ $headerWidthClass }} px-4 py-3">
                                        @if (in_array($column, ['code', 'name', 'requester', 'recipient', 'created_at', 'status'], true))
                                            @php
                                                $isCurrentSort = $sorting['sort'] === $column;
                                                $defaultDirection = $column === 'created_at' ? 'desc' : 'asc';
                                                $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                                $sortMessage = 'Tabula "Nodošanas pieteikumi" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
                                <th class="table-col-actions px-4 py-3 text-right">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transfers as $transfer)
                                @php
                                    $device = $transfer->device;
                                    $deviceFilterUrl = $device
                                        ? route('devices.index', array_filter([
                                            'code' => $device->code,
                                            'q' => $device->code ? null : $device->name,
                                            'highlight' => $device->code ?: $device->name,
                                            'highlight_mode' => $device->code ? 'exact' : 'contains',
                                            'highlight_id' => 'device-' . $device->id,
                                        ]))
                                        : null;
                                    $deviceMeta = collect([$device?->manufacturer, $device?->model])->filter()->implode(' | ');
                                    $reason = trim((string) $transfer->transfer_reason);
                                    $shortReason = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $reason), 70);
                                    $isIncomingPending = ! $isAdmin
                                        && (int) $currentUserId === (int) $transfer->transfered_to_id
                                        && $transfer->status === 'submitted';
                                    $hasActions = true;
                                @endphp
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="table-col-image px-4 py-4 text-center align-middle">
                                        @php
                                            $thumbUrl = $device?->deviceImageThumbUrl();
                                        @endphp
                                        @if ($thumbUrl)
                                            <img src="{{ $thumbUrl }}" alt="{{ $device?->name ?: 'Ierīce' }}" class="request-device-thumb mx-auto">
                                        @else
                                            <div class="request-device-thumb request-device-thumb-placeholder mx-auto">
                                                <x-icon name="device" size="h-4 w-4" />
                                            </div>
                                        @endif
                                    </td>
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
                                        <div class="font-semibold text-slate-900">{{ $transfer->responsibleUser?->full_name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $transfer->responsibleUser?->job_title ?: ($transfer->responsibleUser?->email ?: 'Darbinieks') }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $transfer->transferTo?->full_name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $transfer->transferTo?->job_title ?: ($transfer->transferTo?->email ?: 'Darbinieks') }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="relative" x-data="{ open: false }">
                                            <button
                                                type="button"
                                                class="max-w-[20rem] truncate text-left text-sm text-slate-700 hover:text-slate-900"
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
                                                        <span class="device-request-popover-title">Pilns nodošanas iemesls</span>
                                                        <span class="device-request-popover-date">{{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                                    </div>
                                                    <div class="device-request-popover-copy">{{ $reason }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $transfer->created_at?->format('d.m.Y') ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $transfer->created_at?->format('H:i') ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="request" :value="$transfer->status" :label="$statusLabels[$transfer->status] ?? null" />
                                        @if ($isIncomingPending)
                                            <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-sky-700 ring-1 ring-sky-200">
                                                <x-icon name="transfer" size="h-3.5 w-3.5" />
                                                <span>Ienākošs piedāvājums</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        @if ($isAdmin && $deviceFilterUrl)
                                            {{-- Adminam - tikai viena poga bez dropdown --}}
                                            <a href="{{ $deviceFilterUrl }}" class="table-action-summary table-action-summary-single">
                                                <x-icon name="view" size="h-4 w-4" />
                                                <span>Saistītā ierīce</span>
                                            </a>
                                        @elseif ($hasActions)
                                            {{-- Pārējiem - dropdown ar darbībām --}}
                                            <div class="table-action-menu inline-block" x-data="{ open: false }" @keydown.escape.window="open = false">
                                                <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                                    <span>Darbības</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>

                                                <div class="table-action-list table-action-list-dropdown" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                                    @if ($deviceFilterUrl)
                                                        <a href="{{ $deviceFilterUrl }}" class="table-action-item table-action-item-sky table-action-item-wide text-sky-700" @click="open = false">
                                                            <x-icon name="view" size="h-4 w-4" />
                                                            <span>Skatīt saistīto ierīci</span>
                                                        </a>
                                                    @endif

                                                    @php
                                                        $isOwnerCanEdit = ! auth()->user()?->isAdmin()
                                                            && (int) $currentUserId === (int) $transfer->responsible_user_id
                                                            && $transfer->status === 'submitted';
                                                    @endphp

                                                    @if ($isOwnerCanEdit)
                                                        <a href="{{ route('my-requests.edit', ['requestType' => 'transfer', 'requestId' => $transfer->id]) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                                            <x-icon name="edit" size="h-4 w-4" />
                                                            <span>Rediģēt aprakstu</span>
                                                        </a>

                                                        <form
                                                            method="POST"
                                                            action="{{ route('my-requests.destroy', ['requestType' => 'transfer', 'requestId' => $transfer->id]) }}"
                                                            data-app-confirm-title="Atcelt nodošanu?"
                                                            data-app-confirm-message="Vai tiešām atcelt šo nodošanas pieteikumu?"
                                                            data-app-confirm-accept="Jā, atcelt"
                                                            data-app-confirm-cancel="Nē"
                                                            data-app-confirm-tone="danger"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="table-action-item table-action-item-rose" @click="open = false">
                                                                <x-icon name="x-mark" size="h-4 w-4" />
                                                                <span>Atcelt pieteikumu</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if ($isIncomingPending)
                                                        <div class="table-action-grid-actions">
                                                            <form
                                                                method="POST"
                                                                action="{{ route('device-transfers.review', $transfer) }}"
                                                                data-app-confirm-title="Noraidīt nodošanu?"
                                                                data-app-confirm-message="Vai tiešām noraidīt šo ierīces nodošanas pieteikumu?"
                                                                data-app-confirm-accept="Jā, noraidīt"
                                                                data-app-confirm-cancel="Nē"
                                                                data-app-confirm-tone="danger"
                                                            >
                                                                @csrf
                                                                <input type="hidden" name="status" value="rejected">
                                                                <button type="submit" class="table-action-item table-action-item-rose">
                                                                    <x-icon name="x-circle" size="h-4 w-4" />
                                                                    <span>Noraidīt</span>
                                                                </button>
                                                            </form>

                                                            <form
                                                                method="POST"
                                                                action="{{ route('device-transfers.review', $transfer) }}"
                                                                data-app-confirm-title="Apstiprināt nodošanu?"
                                                                data-app-confirm-message="Vai tiešām apstiprināt šo ierīces nodošanas pieteikumu?"
                                                                data-app-confirm-accept="Jā, apstiprināt"
                                                                data-app-confirm-cancel="Nē"
                                                                data-app-confirm-tone="warning"
                                                            >
                                                                @csrf
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="table-action-item table-action-item-emerald">
                                                                    <x-icon name="check-circle" size="h-4 w-4" />
                                                                    <span>Apstiprināt</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <button
                                                type="button"
                                                class="btn-disabled"
                                                data-app-toast-title="Darbības nav pieejamas"
                                                data-app-toast-message="Šim pārsūtīšanas pieteikumam pašlaik nav pieejamu darbību. Tas jau ir izskatīts vai arī tava lomai nav atļauts to mainīt."
                                                data-app-toast-tone="info"
                                            >
                                                <x-icon name="information-circle" size="h-4 w-4" />
                                                <span>Nav darbību</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6">
                                        <x-empty-state
                                            compact
                                            icon="transfer"
                                            title="Nodošanas pieteikumi netika atrasti"
                                            description="Pamēģini noņemt daļu filtru vai mainīt meklēšanas nosacījumus."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($transfers->hasPages())
                <div class="mt-5">{{ $transfers->links() }}</div>
            @endif
        </div>
    </section>
</x-app-layout>
