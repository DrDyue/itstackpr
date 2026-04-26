{{--
    Lapa: Ierīču nodošanas pieprasījumu saraksts.
    Atbildība: rāda visus nodošanas pieprasījumus tabulas veidā; admins redz kopskatu, lietotājs redz savus nosūtītos un saņemtos.
    Datu avots: DeviceTransferController@index.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $liveTransferPendingCount = $isAdmin ? 0 : ($incomingPendingCount ?? 0);
        $selectedDeviceLabel = collect($deviceOptions)->firstWhere('value', (string) ($filters['device_id'] ?? ''))['label'] ?? ($filters['device_query'] ?: null);
        $selectedRequesterLabel = collect($requesterOptions)->firstWhere('value', (string) ($filters['requester_id'] ?? ''))['label'] ?? ($filters['requester_query'] ?: null);
        $selectedRecipientLabel = collect($recipientOptions)->firstWhere('value', (string) ($filters['recipient_id'] ?? ''))['label'] ?? ($filters['recipient_query'] ?: null);
        $activeStatusLabel = count($filters['statuses']) > 0 && count($filters['statuses']) < count($statuses)
            ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')
            : null;
        $isIncomingFilter = $filters['incoming'] ?? false;
        $activeTransferViewLabel = $isIncomingFilter ? 'Ienākošās nodošanas' : null;
        $detailStatusClasses = [
            'submitted' => 'request-detail-status-amber',
            'approved' => 'request-detail-status-emerald',
            'rejected' => 'request-detail-status-rose',
        ];
        $createSelectedDeviceLabel = collect($createDeviceOptions ?? [])
            ->firstWhere('value', (string) request()->query('device_id', ''))['label'] ?? '';
        $shouldOpenCreateModal = (old('request_form_type') === 'transfer' && $errors->hasAny(['device_id', 'transfered_to_id', 'transfer_reason']))
            || request()->query('device_transfer_modal') === 'create';
    @endphp

    <section
        x-data="{
            livePendingCount: {{ $liveTransferPendingCount }},
            syncCounts(counts = {}) {
                this.livePendingCount = {{ $isAdmin ? '0' : 'Number(counts?.incoming_transfers || 0)' }};
            },
        }"
        @nav-counts-updated.window="syncCounts($event.detail)"
        class="app-shell app-shell-wide"
    >
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="transfer" size="h-4 w-4" />
                            <span>Nodošana</span>
                        </div>
                        <span
                            x-cloak
                            x-show="livePendingCount > 0"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm"
                        >
                            <x-icon name="clock" size="h-3.5 w-3.5" />
                            <span>{{ $isAdmin ? 'Pārskatam' : 'Jāizskata' }}</span>
                            <span x-text="livePendingCount">{{ $liveTransferPendingCount }}</span>
                        </span>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald">
                            <x-icon name="transfer" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierīču nodošanas pieteikumi</h1>
                            <p class="page-subtitle">{{ $isAdmin ? 'Admins redz kopējo nodošanas vēsturi un saistītās ierīces.' : 'Šeit redzami tavi nosūtītie un saņemtie ierīču nodošanas pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="page-actions">
                    <button
                        type="button"
                        class="btn-create"
                        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-transfer' }))"
                    >
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Jauns pieteikums</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="device-transfers-index-root" data-async-table-root class="device-transfers-index-page space-y-6">
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('device-transfers.index') }}"
                class="space-y-3"
                data-async-table-form
                data-async-root="#device-transfers-index-root"
                data-search-endpoint="{{ route('device-transfers.find-by-code') }}"
                data-manual-search-pagination="false"
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
                                <div class="devices-search-group device-transfers-code-search">
                                    <label class="devices-search-label">
                                        <span>Meklēt pēc koda</span>
                                        <input
                                            type="text"
                                            name="code"
                                            value="{{ $filters['code'] }}"
                                            class="crud-control"
                                            placeholder="Ierīces kods"
                                            autocomplete="off"
                                            data-async-manual="true"
                                            data-async-code-search="true"
                                        >
                                    </label>
                                    <button type="button" class="btn-search" data-code-search-submit="true" onclick="return window.runManualTableSearchFromTrigger(this);">
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
                            <div class="{{ $isAdmin ? 'transfer-toolbar-filters-grid transfer-toolbar-filters-grid-admin' : 'transfer-toolbar-filters-grid transfer-toolbar-filters-grid-compact' }}">
                                <label class="block">
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

                    <div
                        class="filter-toolbar-footer"
                        x-data="{
                            selected: Array.from(new Set((@js($filters['statuses']) ?? []).map((value) => String(value)))),
                            incoming: @js($isIncomingFilter),
                            isSelected(value) {
                                return this.selected.includes(String(value));
                            },
                            toggleStatus(value) {
                                const normalizedValue = String(value);

                                this.incoming = false;

                                if (this.isSelected(normalizedValue)) {
                                    this.selected = this.selected.filter((item) => item !== normalizedValue);
                                    return;
                                }

                                this.selected = [normalizedValue];
                            },
                            toggleIncoming() {
                                this.incoming = ! this.incoming;

                                if (this.incoming) {
                                    this.selected = [];
                                }
                            },
                        }"
                    >
                        <div class="quick-filter-groups">
                            @if (! $isAdmin)
                                <div class="quick-filter-group">
                                    <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ātrie filtri</div>
                                    <div class="quick-status-filters">
                                        <button
                                            type="button"
                                            @click="toggleIncoming(); $nextTick(() => window.submitAsyncTableForm($el.closest('form'), { resetPage: true }))"
                                            class="quick-status-filter quick-status-filter-sky"
                                            :class="incoming ? 'quick-status-filter-active' : ''"
                                        >
                                            <x-icon name="transfer" size="h-4 w-4" />
                                            <span>Ienākošās nodošanas</span>
                                            <span class="quick-filter-count" x-text="livePendingCount">{{ $incomingPendingCount }}</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div class="quick-filter-group">
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
                                            @click="toggleStatus(@js($status)); $nextTick(() => window.submitAsyncTableForm($el.closest('form'), { resetPage: true }))"
                                            class="quick-status-filter {{ $toneClass }}"
                                            :class="isSelected(@js($status)) && !incoming ? 'quick-status-filter-active' : ''"
                                        >
                                            <x-icon :name="$iconName" size="h-4 w-4" />
                                            <span>{{ $statusLabels[$status] ?? $status }}</span>
                                            <span class="quick-filter-count" @if($status === 'submitted') x-text="livePendingCount" @endif>{{ $transferSummary[$status] ?? 0 }}</span>
                                        </button>
                                    @endforeach

                                    <template x-for="value in selected" :key="'device-transfer-status-' + value">
                                        <input type="hidden" name="status[]" :value="value">
                                    </template>

                                    <input x-bind:disabled="!incoming" type="hidden" name="incoming" value="1">
                                </div>
                            </div>
                        </div>

                        <div class="toolbar-actions">
                            <a href="{{ route('device-transfers.index', ['clear' => 1]) }}" class="btn-clear" data-async-link="true" data-async-clear="true">
                                <x-icon name="clear" size="h-4 w-4" />
                                <span>Notīrīt filtrus</span>
                            </a>
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
                    ['label' => 'Skats', 'value' => $activeTransferViewLabel],
                    ['label' => 'No datuma', 'value' => $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                    ['label' => 'Līdz datumam', 'value' => $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                    ['label' => 'Statuss', 'value' => $activeStatusLabel],
                ]"
                :clear-url="route('device-transfers.index')"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
            @endif

            <div class="mt-4 device-table-shell">
                <div class="device-table-scroll table-scroll-overlay-frame rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <div class="table-scroll-viewport">
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
                                    $editTransferUrl = route('device-transfers.index', array_merge(request()->except(['page', 'device_transfer_modal', 'modal_request']), [
                                        'device_transfer_modal' => 'edit',
                                        'modal_request' => $transfer->id,
                                    ]));
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
                                    $usesUserTransferState = ! $isAdmin;
                                    $isIncomingPending = $usesUserTransferState
                                        && (int) $currentUserId === (int) $transfer->transfered_to_id
                                        && $transfer->status === 'submitted';
                                    $isPendingAction = $usesUserTransferState && $transfer->status === 'submitted';
                                    $rowStateClass = $isIncomingPending
                                        ? 'app-table-row-incoming'
                                        : ($isPendingAction ? 'app-table-row-pending' : '');
                                    $statusLabel = $isIncomingPending
                                        ? 'Ienākošs'
                                        : ($statusLabels[$transfer->status] ?? null);
                                    $hasActions = true;
                                @endphp
                                <tr id="device-transfer-{{ $transfer->id }}" class="request-notification-target app-table-row border-t border-slate-100 align-top {{ $rowStateClass }}" data-table-row-id="device-transfer-{{ $transfer->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) ($device?->code ?? ''))) }}" data-table-search-highlight-style="{{ $rowStateClass !== '' ? 'outline' : 'background' }}">
                                    <td class="table-col-image px-4 py-4 text-center align-middle">
                                        @php
                                            $thumbUrl = $device?->deviceImageThumbUrl();
                                        @endphp
                                        @if ($thumbUrl)
                                            <img
                                                src="{{ $thumbUrl }}"
                                                alt="{{ $device?->name ?: 'Ierīce' }}"
                                                class="request-device-thumb mx-auto"
                                                loading="lazy"
                                                decoding="async"
                                                fetchpriority="low"
                                            >
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
                                        <x-status-pill
                                            context="request"
                                            :value="$transfer->status"
                                            :label="$statusLabel"
                                            :pending-suffix="$usesUserTransferState && ! $isIncomingPending ? 'gaida' : false"
                                            :pending-action="$usesUserTransferState && ! $isIncomingPending && $transfer->status === 'submitted'"
                                            class="{{ $isIncomingPending ? 'status-pill-incoming' : '' }}"
                                        />
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
                                            <div class="table-action-menu inline-block" x-data="createFloatingDropdown({ zIndex: 400 })" @keydown.escape.window="closePanel()">
                                                <button type="button" class="table-action-summary {{ $isIncomingPending ? 'table-action-summary-pending' : '' }}" x-ref="trigger" @click="togglePanel()" :aria-expanded="open.toString()">
                                                    @if ($isIncomingPending)
                                                        <span class="table-action-attention">Jārīkojas</span>
                                                    @endif
                                                    <span>Darbības</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>

                                                <template x-teleport="body">
                                                <div class="table-action-list table-action-list-dropdown" data-floating-menu="manual" x-ref="panel" x-cloak x-show="open" x-transition.origin.top.right x-bind:style="panelStyle" @click.outside="closePanel()">
                                                    @if ($deviceFilterUrl && ! $isIncomingPending)
                                                        <a href="{{ $deviceFilterUrl }}" class="table-action-item table-action-item-sky table-action-item-wide text-sky-700" @click="closePanel()">
                                                            <x-icon name="view" size="h-4 w-4" />
                                                            <span>Skatīt saistīto ierīci</span>
                                                        </a>
                                                    @endif

                                                    @php
                                                        $isOwnerCanEdit = $usesUserTransferState
                                                            && (int) $currentUserId === (int) $transfer->responsible_user_id
                                                            && $transfer->status === 'submitted';
                                                    @endphp

                                                    @if ($isOwnerCanEdit)
                                                        <button
                                                            type="button"
                                                            class="table-action-item table-action-item-amber"
                                                            @click="closePanel(); $dispatch('open-modal', 'transfer-request-edit-{{ $transfer->id }}')"
                                                        >
                                                            <x-icon name="edit" size="h-4 w-4" />
                                                            <span>Rediģēt pieteikumu</span>
                                                        </button>

                                                        <x-post-action-button
                                                            :action="route('my-requests.destroy', ['requestType' => 'transfer', 'requestId' => $transfer->id])"
                                                            method="DELETE"
                                                            button-class="table-action-item table-action-item-rose"
                                                            :button-attributes="['@click' => 'closePanel()']"
                                                            data-app-confirm-title="Atcelt nodošanu?"
                                                            data-app-confirm-message="Vai tiešām atcelt šo nodošanas pieteikumu?"
                                                            data-app-confirm-accept="Jā, atcelt"
                                                            data-app-confirm-cancel="Nē"
                                                            data-app-confirm-tone="danger"
                                                        >
                                                            <x-icon name="x-mark" size="h-4 w-4" />
                                                            <span>Atcelt pieteikumu</span>
                                                        </x-post-action-button>
                                                    @endif

                                                    @if ($isIncomingPending)
                                                        <div class="table-action-grid-actions">
                                                            <x-post-action-button
                                                                :action="route('device-transfers.review', $transfer)"
                                                                button-class="table-action-item table-action-item-rose"
                                                                data-app-confirm-title="Noraidīt nodošanu?"
                                                                data-app-confirm-message="Vai tiešām noraidīt šo ierīces nodošanas pieteikumu?"
                                                                data-app-confirm-accept="Jā, noraidīt"
                                                                data-app-confirm-cancel="Nē"
                                                                data-app-confirm-tone="danger"
                                                            >
                                                                <x-slot:fields>
                                                                    <input type="hidden" name="status" value="rejected">
                                                                </x-slot:fields>
                                                                <x-icon name="x-circle" size="h-4 w-4" />
                                                                <span>Noraidīt</span>
                                                            </x-post-action-button>

                                                            <x-post-action-button
                                                                :action="route('device-transfers.review', $transfer)"
                                                                button-class="table-action-item table-action-item-emerald"
                                                                data-app-confirm-title="Apstiprināt nodošanu?"
                                                                data-app-confirm-message="Vai tiešām apstiprināt šo ierīces nodošanas pieteikumu?"
                                                                data-app-confirm-accept="Jā, apstiprināt"
                                                                data-app-confirm-cancel="Nē"
                                                                data-app-confirm-tone="warning"
                                                            >
                                                                <x-slot:fields>
                                                                    <input type="hidden" name="status" value="approved">
                                                                </x-slot:fields>
                                                                <x-icon name="check-circle" size="h-4 w-4" />
                                                                <span>Apstiprināt</span>
                                                            </x-post-action-button>
                                                        </div>
                                                    @endif
                                                </div>
                                                </template>
                                            </div>
                                        @else
                                            <button
                                                type="button"
                                                class="btn-disabled"
                                                data-app-toast-title="Darbības nav pieejamas"
                                                data-app-toast-message="Šim nodošanas pieteikumam pašlaik nav pieejamu darbību. Tas jau ir izskatīts vai arī tavai lomai nav atļauts to mainīt."
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
            </div>

        </div>
    </section>

    {{-- Modāļa forma jauna pieteikuma izveidei / rediģēšanai --}}
    <x-request-form-modal
        type="transfer"
        :show="$shouldOpenCreateModal"
        :device-options="$createDeviceOptions ?? []"
        :user-options="$createRecipientOptions ?? []"
        :selected-device-id="(string) request()->query('device_id', '')"
        :selected-device-label="$createSelectedDeviceLabel"
    />

    @unless ($isAdmin)
        @if (($selectedEditableRequest?->id ?? null))
            <x-request-edit-modal
                type="transfer"
                :modal-name="'transfer-request-edit-' . $selectedEditableRequest->id"
                :request-model="$selectedEditableRequest"
                field-name="transfer_reason"
                field-label="Nodošanas iemesls"
                :action="route('my-requests.update', ['requestType' => 'transfer', 'requestId' => $selectedEditableRequest->id])"
            />
        @endif
    @endunless

    @if (old('request_form_type') === 'transfer' && $errors->any())
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-transfer' })));</script>
    @elseif (str_starts_with((string) old('modal_form'), 'transfer_request_edit_'))
        @php($transferRequestModalTarget = str_replace('transfer_request_edit_', '', (string) old('modal_form')))
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'transfer-request-edit-{{ $transferRequestModalTarget }}' })));</script>
    @elseif (request()->query('device_transfer_modal') === 'create')
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-transfer' })));</script>
    @elseif (request()->query('device_transfer_modal') === 'edit' && request()->query('modal_request') && ! $isAdmin)
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'transfer-request-edit-{{ request()->query('modal_request') }}' })));</script>
    @endif
</x-app-layout>
