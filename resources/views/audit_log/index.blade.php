{{--
    Lapa: Audita žurnāls.
    Atbildība: parāda sistēmas darbību vēsturi, kas noder kontrolei un demonstrācijai komisijai.
    Datu avots: AuditLogController@index.
    Galvenās daļas:
    1. Hero un audita kopsavilkumi.
    2. Filtri pēc darbības, smaguma, objekta, lietotāja un datuma.
    3. Audita ierakstu tabula.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $selectedActionLabel = collect($actionOptions)->firstWhere('value', $filters['action'])['label'] ?? null;
        $selectedUserLabel = collect($actorOptions)->firstWhere('value', $filters['user_id'])['label'] ?? null;
        $selectedEntityLabel = collect($entityOptions ?? [])->firstWhere('value', $filters['entity_type'])['label'] ?? null;
        $severityFilterLinks = [
            ['label' => 'Informācija', 'value' => 'info', 'icon' => 'info', 'tone' => 'slate'],
            ['label' => 'Brīdinājums', 'value' => 'warning', 'icon' => 'exclamation-triangle', 'tone' => 'amber'],
            ['label' => 'Kritiski', 'value' => 'critical', 'icon' => 'x-circle', 'tone' => 'rose'],
        ];
        $selectedSeverities = $filters['severity'] !== '' ? [$filters['severity']] : [];
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="audit" size="h-4 w-4" />
                        <span>Administratora zurnals</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet">
                            <x-icon name="audit" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Audita zurnals</h1>
                            <p class="page-subtitle">Sistēmas darbību vēsture ar lokalizētiem filtriem un skaidru aktivitāšu tabulu.</p>
                        </div>
                    </div>
                </div>

                <div class="inventory-inline-metrics">
                    <div class="inventory-inline-chip inventory-inline-chip-slate">
                        <span class="inventory-inline-label">Kopa</span>
                        <span class="inventory-inline-value">{{ $summary['total'] }}</span>
                    </div>
                    <div class="inventory-inline-chip inventory-inline-chip-amber">
                        <span class="inventory-inline-label">Šodien</span>
                        <span class="inventory-inline-value">{{ $summary['today'] }}</span>
                    </div>
                    <div class="inventory-inline-chip inventory-inline-chip-rose">
                        <span class="inventory-inline-label">Kritiski</span>
                        <span class="inventory-inline-value">{{ $summary['critical'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if (! empty($featureMessage ?? null))
            <div class="surface-empty">{{ $featureMessage }}</div>
        @endif

        <div id="audit-log-index-root" data-async-table-root>
        <form method="GET" action="{{ route('audit-log.index') }}" class="devices-filter-surface" data-async-table-form data-async-root="#audit-log-index-root">
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}" data-sort-hidden="field">
            <input type="hidden" name="direction" value="{{ $filters['direction'] }}" data-sort-hidden="direction">

            <div class="devices-filters-grid">
                <label class="devices-text-search">
                    <span>Meklēt ierakstu</span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="crud-control" placeholder="ID, apraksts vai lietotājs">
                </label>

                <div>
                    <span class="crud-label">Darbība</span>
                    <x-searchable-select
                        name="action"
                        query-name="action_query"
                        :options="$actionOptions"
                        :selected="$filters['action']"
                        :query="$selectedActionLabel"
                        identifier="audit-action"
                        placeholder="Visas darbības"
                    />
                </div>

                <div>
                    <span class="crud-label">Objekts</span>
                    <x-searchable-select
                        name="entity_type"
                        query-name="entity_query"
                        :options="$entityOptions"
                        :selected="$filters['entity_type']"
                        :query="$selectedEntityLabel"
                        identifier="audit-entity"
                        placeholder="Visi objekti"
                    />
                </div>

                <div>
                    <span class="crud-label">Lietotājs</span>
                    <x-searchable-select
                        name="user_id"
                        query-name="user_query"
                        :options="$actorOptions"
                        :selected="$filters['user_id']"
                        :query="$selectedUserLabel"
                        identifier="audit-user"
                        placeholder="Visi lietotāji"
                    />
                </div>

                <x-localized-date-input name="date_from" label="No datuma" :value="$filters['date_from']" />
                <x-localized-date-input name="date_to" label="Līdz datumam" :value="$filters['date_to']" />
            </div>

            <div class="filter-toolbar-footer">
                <div class="quick-filter-groups">
                    <div class="quick-filter-group" x-data="filterChipGroup({ selected: @js($selectedSeverities), minimum: 0 })">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Svarīgums</div>
                        <div class="quick-status-filters">
                            @foreach ($severityFilterLinks as $severityFilter)
                                @php
                                    $toneClass = 'quick-status-filter-' . $severityFilter['tone'];
                                @endphp
                                <button
                                    type="button"
                                    @click="toggle(@js($severityFilter['value'])); $nextTick(() => $el.closest('form').requestSubmit())"
                                    class="quick-status-filter {{ $toneClass }}"
                                    :class="isSelected(@js($severityFilter['value'])) ? 'quick-status-filter-active' : ''"
                                >
                                    <x-icon :name="$severityFilter['icon']" size="h-4 w-4" />
                                    <span>{{ $severityFilter['label'] }}</span>
                                </button>
                            @endforeach

                            <template x-for="value in selected" :key="'audit-severity-' + value">
                                <input type="hidden" name="severity" :value="value">
                            </template>
                        </div>
                    </div>
                </div>

                <div class="toolbar-actions">
                    <a href="{{ route('audit-log.index') }}" class="btn-clear" data-async-link="true">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Notīrīt</span>
                    </a>
                </div>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklēt', 'value' => $filters['search'] !== '' ? $filters['search'] : null],
                ['label' => 'Darbība', 'value' => $selectedActionLabel],
                ['label' => 'Objekts', 'value' => $selectedEntityLabel],
                ['label' => 'Lietotājs', 'value' => $selectedUserLabel],
                ['label' => 'No datuma', 'value' => $filters['date_from'] !== '' ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : null],
                ['label' => 'Līdz datumam', 'value' => $filters['date_to'] !== '' ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : null],
                ['label' => 'Svarīgums', 'value' => $filters['severity'] !== '' ? collect($severityFilterLinks)->firstWhere('value', $filters['severity'])['label'] ?? $filters['severity'] : null],
            ]"
            :clear-url="route('audit-log.index')"
        />

        <div class="app-table-shell">
            <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="app-table-content app-table-content-compact min-w-full text-sm">
                <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                    <tr>
                        @foreach ([
                            'timestamp' => 'Laiks',
                            'user' => 'Lietotājs',
                            'action' => 'Darbība',
                            'entity_type' => 'Objekts',
                            'severity' => 'Svarīgums',
                            'description' => 'Apraksts',
                        ] as $column => $label)
                            @php
                                $isCurrentSort = $filters['sort'] === $column || ($filters['sort'] === 'time' && $column === 'timestamp') || ($filters['sort'] === 'user_id' && $column === 'user') || ($filters['sort'] === 'object' && $column === 'entity_type');
                                $defaultDirection = $column === 'timestamp' ? 'desc' : 'asc';
                                $nextDirection = $isCurrentSort && $filters['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $filters['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                $sortMessage = 'Tabula "Audita žurnāls" kārtota pēc ' . mb_strtolower($label) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $actionStyles = match ($log->action) {
                                'CREATE' => ['icon' => 'plus', 'class' => 'audit-badge audit-badge-emerald'],
                                'UPDATE' => ['icon' => 'edit', 'class' => 'audit-badge audit-badge-sky'],
                                'DELETE' => ['icon' => 'trash', 'class' => 'audit-badge audit-badge-rose'],
                                'LOGIN' => ['icon' => 'user', 'class' => 'audit-badge audit-badge-violet'],
                                'LOGOUT' => ['icon' => 'logout', 'class' => 'audit-badge audit-badge-slate'],
                                'EXPORT' => ['icon' => 'send', 'class' => 'audit-badge audit-badge-amber'],
                                'BACKUP' => ['icon' => 'save', 'class' => 'audit-badge audit-badge-amber'],
                                'RESTORE' => ['icon' => 'check-circle', 'class' => 'audit-badge audit-badge-emerald'],
                                default => ['icon' => 'view', 'class' => 'audit-badge audit-badge-slate'],
                            };

                            $entityStyles = match ($log->entity_type) {
                                'Device' => ['icon' => 'device', 'class' => 'audit-badge audit-badge-sky'],
                                'Repair' => ['icon' => 'repair', 'class' => 'audit-badge audit-badge-amber'],
                                'RepairRequest' => ['icon' => 'repair-request', 'class' => 'audit-badge audit-badge-sky'],
                                'WriteoffRequest' => ['icon' => 'writeoff', 'class' => 'audit-badge audit-badge-rose'],
                                'DeviceTransfer' => ['icon' => 'transfer', 'class' => 'audit-badge audit-badge-emerald'],
                                'Room' => ['icon' => 'room', 'class' => 'audit-badge audit-badge-emerald'],
                                'Building' => ['icon' => 'building', 'class' => 'audit-badge audit-badge-slate'],
                                'DeviceType' => ['icon' => 'tag', 'class' => 'audit-badge audit-badge-violet'],
                                'User' => ['icon' => 'users', 'class' => 'audit-badge audit-badge-violet'],
                                default => ['icon' => 'audit', 'class' => 'audit-badge audit-badge-slate'],
                            };
                        @endphp
                        <tr class="app-table-row border-t border-slate-100" data-table-row-id="audit-log-{{ $log->id }}" data-table-search-value="{{ \Illuminate\Support\Str::lower(trim(implode(' ', array_filter([(string) $log->id, $log->localized_description, $log->user?->full_name, $log->user?->email])))) }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $log->timestamp?->format('d.m.Y') ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->timestamp?->format('H:i:s') ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $log->user?->full_name ?: 'Sistēma' }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $actionStyles['class'] }}">
                                    <x-icon :name="$actionStyles['icon']" size="h-3.5 w-3.5" />
                                    <span>{{ $log->localized_action }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ $entityStyles['class'] }}">
                                    <x-icon :name="$entityStyles['icon']" size="h-3.5 w-3.5" />
                                    <span>{{ $log->localized_entity_type }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <x-status-pill context="severity" :value="$log->severity" :label="$log->localized_severity" />
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->localized_description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Audita ierakstu nav.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $logs->links() }}
        </div>
    </section>
</x-app-layout>
