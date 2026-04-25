{{--
    Lapa: Norakstīšanas pieteikumu saraksts.
    Atbildība: rāda norakstīšanas pieprasījumus tabulas veidā; admins izskata visus, lietotājs redz savus.
    Datu avots: WriteoffRequestController@index.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $livePendingWriteoffCount = $canReview ? ($requestSummary['submitted'] ?? 0) : 0;
        $selectedDeviceLabel = collect($deviceOptions)->firstWhere('value', (string) ($filters['device_id'] ?? ''))['label'] ?? ($filters['device_query'] ?: null);
        $selectedRequesterLabel = collect($requesterOptions)->firstWhere('value', (string) ($filters['requester_id'] ?? ''))['label'] ?? ($filters['requester_query'] ?: null);
        $activeStatusLabel = count($filters['statuses']) > 0 && count($filters['statuses']) < count($statuses)
            ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')
            : null;
        $detailStatusClasses = [
            'submitted' => 'request-detail-status-amber',
            'approved' => 'request-detail-status-emerald',
            'rejected' => 'request-detail-status-rose',
        ];
        $createSelectedDeviceLabel = collect($createDeviceOptions ?? [])
            ->firstWhere('value', (string) request()->query('device_id', ''))['label'] ?? '';
        $shouldOpenCreateModal = ! $canReview && (
            (old('request_form_type') === 'writeoff' && $errors->hasAny(['device_id', 'reason']))
            || request()->query('writeoff_request_modal') === 'create'
        );
    @endphp

    <section
        x-data="{
            livePendingCount: {{ $livePendingWriteoffCount }},
            syncCounts(counts = {}) {
                if (! {{ $canReview ? 'true' : 'false' }}) {
                    return;
                }

                this.livePendingCount = Number(counts?.writeoff_requests || 0);
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
                            <x-icon name="writeoff" size="h-4 w-4" />
                            <span>Norakstīšana</span>
                        </div>
                        @if ($canReview)
                            <span
                                x-cloak
                                x-show="livePendingCount > 0"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm"
                            >
                                <x-icon name="clock" size="h-3.5 w-3.5" />
                                <span>Gaida izskatīšanu</span>
                                <span x-text="livePendingCount">{{ $livePendingWriteoffCount }}</span>
                            </span>
                        @endif
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
                        <button
                            type="button"
                            class="btn-create"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-writeoff' }))"
                        >
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauns pieteikums</span>
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        <div id="writeoff-requests-index-root" data-async-table-root class="writeoff-requests-index-page space-y-6">
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('writeoff-requests.index') }}"
                class="devices-filter-surface"
                data-async-table-form
                data-async-root="#writeoff-requests-index-root"
                data-search-endpoint="{{ route('writeoff-requests.find-by-code') }}"
                data-manual-search-pagination="false"
            >
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <div class="toolbar-panels">
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
                        <div class="request-toolbar-filters-grid {{ $canReview ? 'request-toolbar-filters-grid-admin' : '' }}">
                            <label class="devices-text-search">
                                <span>Filtrēt pēc teksta</span>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, pieteicējs vai iemesls">
                            </label>
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
                                        $iconName = match ($status) {
                                            'submitted' => 'clock',
                                            'approved' => 'check-circle',
                                            'rejected' => 'x-circle',
                                            default => 'information-circle',
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        @click="toggle(@js($status)); $nextTick(() => window.submitAsyncTableForm($el.closest('form'), { resetPage: true }))"
                                        class="quick-status-filter {{ $toneClass }}"
                                        :class="isSelected(@js($status)) ? 'quick-status-filter-active' : ''"
                                    >
                                        <x-icon :name="$iconName" size="h-4 w-4" />
                                        <span>{{ $statusLabels[$status] ?? $status }}</span>
                                        <span class="quick-filter-count" @if($canReview && $status === 'submitted') x-text="livePendingCount" @endif>{{ $requestSummary[$status] ?? 0 }}</span>
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'writeoff-request-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('writeoff-requests.index', ['statuses_filter' => 1, 'clear' => 1]) }}" class="btn-clear" data-async-link="true" data-async-clear="true">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>Notīrīt filtrus</span>
                        </a>
                    </div>
                </div>
            </form>

            <x-active-filters
                :items="[
                    ['label' => 'Filtrēt tekstu', 'value' => $filters['q']],
                    ['label' => 'Ierīce', 'value' => $selectedDeviceLabel],
                    ['label' => 'Pieteicējs', 'value' => $canReview ? $selectedRequesterLabel : null],
                    ['label' => 'No datuma', 'value' => $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                    ['label' => 'Līdz datumam', 'value' => $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                    ['label' => 'Statuss', 'value' => $activeStatusLabel],
                ]"
                :clear-url="route('writeoff-requests.index', ['statuses_filter' => 1])"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
            @endif

        {{-- Norakstīšanas pieteikumu tabula --}}
        @include('writeoff_requests.index-table', [
            'requests' => $requests,
            'canReview' => $canReview,
            'sorting' => $sorting,
            'sortOptions' => $sortOptions,
            'statusLabels' => $statusLabels,
            'sortDirectionLabels' => $sortDirectionLabels,
        ])
        </div>
    </section>

    {{-- Modāļa forma jauna pieteikuma izveidei / rediģēšanai --}}
    <x-request-form-modal
        type="writeoff"
        :show="$shouldOpenCreateModal"
        :device-options="$createDeviceOptions ?? []"
        :selected-device-id="(string) request()->query('device_id', '')"
        :selected-device-label="$createSelectedDeviceLabel"
    />

    @unless ($canReview)
        @if (($selectedEditableRequest?->id ?? null))
            <x-request-edit-modal
                type="writeoff"
                :modal-name="'writeoff-request-edit-' . $selectedEditableRequest->id"
                :request-model="$selectedEditableRequest"
                field-name="reason"
                field-label="Iemesls"
                :action="route('my-requests.update', ['requestType' => 'writeoff', 'requestId' => $selectedEditableRequest->id])"
            />
        @endif
    @endunless

    @if (old('request_form_type') === 'writeoff' && $errors->any())
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-writeoff' })));</script>
    @elseif (str_starts_with((string) old('modal_form'), 'writeoff_request_edit_'))
        @php($writeoffRequestModalTarget = str_replace('writeoff_request_edit_', '', (string) old('modal_form')))
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'writeoff-request-edit-{{ $writeoffRequestModalTarget }}' })));</script>
    @elseif (request()->query('writeoff_request_modal') === 'create' && ! $canReview)
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-writeoff' })));</script>
    @elseif (request()->query('writeoff_request_modal') === 'edit' && request()->query('modal_request') && ! $canReview)
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'writeoff-request-edit-{{ request()->query('modal_request') }}' })));</script>
    @endif
</x-app-layout>
