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
        $detailStatusClasses = [
            'submitted' => 'request-detail-status-amber',
            'approved' => 'request-detail-status-emerald',
            'rejected' => 'request-detail-status-rose',
        ];
        $createSelectedDeviceLabel = collect($createDeviceOptions ?? [])
            ->firstWhere('value', (string) request()->query('device_id', ''))['label'] ?? '';
        $shouldOpenCreateModal = ! $canReview && (
            (old('request_form_type') === 'repair' && $errors->hasAny(['device_id', 'description']))
            || request()->query('repair_request_modal') === 'create'
        );
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="repair-request" size="h-4 w-4" />
                            <span>Remonts</span>
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
                        <button
                            type="button"
                            class="btn-create"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-repair' }))"
                        >
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauns pieteikums</span>
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        <div id="repair-requests-index-root" data-async-table-root class="repair-requests-index-page space-y-6">
            {{-- Filtru un meklēšanas josla --}}
            <form
                method="GET"
                action="{{ route('repair-requests.index') }}"
                class="devices-filter-surface"
                data-async-table-form
                data-async-root="#repair-requests-index-root"
                data-search-endpoint="{{ route('repair-requests.find-by-code') }}"
            >
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">
                @if (! empty($filters['request_id']))
                    <input type="hidden" name="request_id" value="{{ $filters['request_id'] }}">
                @endif

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
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, pieteicējs vai apraksts">
                            </label>
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

                            @if ($canReview)
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
                                <button
                                    type="button"
                                    @click="selected = []; $nextTick(() => window.submitAsyncTableForm($el.closest('form'), { resetPage: true }))"
                                    class="quick-status-filter quick-status-filter-slate"
                                    :class="selected.length === 0 ? 'quick-status-filter-active' : ''"
                                >
                                    <x-icon name="filter" size="h-4 w-4" />
                                    <span>Visi</span>
                                    <span class="quick-filter-count">{{ $requestSummary['total'] }}</span>
                                </button>
                                @foreach ($statuses as $status)
                                    @php
                                        $isWarning = $status === 'submitted';
                                        $isSuccess = $status === 'approved';
                                        $toneClass = $isWarning
                                            ? 'quick-status-filter-amber'
                                            : ($isSuccess ? 'quick-status-filter-emerald' : 'quick-status-filter-rose');
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
                                        <span class="quick-filter-count">{{ $requestSummary[$status] ?? 0 }}</span>
                                    </button>
                                @endforeach

                                <template x-for="value in selected" :key="'repair-request-status-' + value">
                                    <input type="hidden" name="status[]" :value="value">
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('repair-requests.index', ['statuses_filter' => 1, 'clear' => 1]) }}" class="btn-clear" data-async-link="true" data-async-clear="true">
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
                :clear-url="route('repair-requests.index', ['statuses_filter' => 1])"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (! empty($featureMessage))
                <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
            @endif

        {{-- Remonta pieteikumu tabula --}}
        @include('repair_requests.index-table', [
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
        type="repair"
        :show="$shouldOpenCreateModal"
        :device-options="$createDeviceOptions ?? []"
        :selected-device-id="(string) request()->query('device_id', '')"
        :selected-device-label="$createSelectedDeviceLabel"
    />

    @unless ($canReview)
        @foreach ($requests as $repairRequest)
            @if ($repairRequest->status === \App\Models\RepairRequest::STATUS_SUBMITTED)
                <x-request-edit-modal
                    type="repair"
                    :modal-name="'repair-request-edit-' . $repairRequest->id"
                    :request-model="$repairRequest"
                    field-name="description"
                    field-label="Apraksts"
                    :action="route('my-requests.update', ['requestType' => 'repair', 'requestId' => $repairRequest->id])"
                />
            @endif
        @endforeach

        @if (($selectedEditableRequest?->id ?? null) && ! $requests->getCollection()->contains('id', $selectedEditableRequest->id))
            <x-request-edit-modal
                type="repair"
                :modal-name="'repair-request-edit-' . $selectedEditableRequest->id"
                :request-model="$selectedEditableRequest"
                field-name="description"
                field-label="Apraksts"
                :action="route('my-requests.update', ['requestType' => 'repair', 'requestId' => $selectedEditableRequest->id])"
            />
        @endif
    @endunless

    @if (old('request_form_type') === 'repair' && $errors->any())
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-repair' })));</script>
    @elseif (str_starts_with((string) old('modal_form'), 'repair_request_edit_'))
        @php($repairRequestModalTarget = str_replace('repair_request_edit_', '', (string) old('modal_form')))
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'repair-request-edit-{{ $repairRequestModalTarget }}' })));</script>
    @elseif (request()->query('repair_request_modal') === 'create' && ! $canReview)
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'request-form-repair' })));</script>
    @elseif (request()->query('repair_request_modal') === 'edit' && request()->query('modal_request') && ! $canReview)
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'repair-request-edit-{{ request()->query('modal_request') }}' })));</script>
    @endif
</x-app-layout>
