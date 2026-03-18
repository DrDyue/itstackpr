<x-app-layout>
    @php
        $columnMeta = [
            'waiting' => [
                'title' => 'Gaida',
                'subtitle' => 'Ierices, kuram remonts ir apstiprinats, bet vel nav uzsakts.',
                'icon' => 'repair',
                'tone' => 'amber',
            ],
            'in-progress' => [
                'title' => 'Procesa',
                'subtitle' => 'Aktivie remonti, pie kuriem sobrid notiek darbs.',
                'icon' => 'stats',
                'tone' => 'sky',
            ],
            'completed' => [
                'title' => 'Pabeigti',
                'subtitle' => 'Pabeigtie vai atceltie remonta ieraksti.',
                'icon' => 'check-circle',
                'tone' => 'emerald',
            ],
        ];
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="page-eyebrow"><x-icon name="repair" size="h-4 w-4" /><span>Serviss</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="repair" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Remonti</h1>
                            <p class="page-subtitle">{{ $canManageRepairs ? 'Parvaldi remonta rindas, procesa darbus un pabeigtos ierakstus.' : 'Tavu iericu remontu statuss pa kolonnam.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageRepairs)
                    <a href="{{ route('repairs.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns remonts</span></a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-4">
            <label class="block md:col-span-2">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierice, kods, apraksts, pieteicejs...">
            </label>
            <label class="block">
                <span class="crud-label">Prioritate</span>
                <select name="priority" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <select name="repair_type" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($repairTypes as $repairType)
                        <option value="{{ $repairType }}" @selected($filters['repair_type'] === $repairType)>{{ $typeLabels[$repairType] }}</option>
                    @endforeach
                </select>
            </label>

            <div class="filter-toolbar-footer md:col-span-4">
                <div class="quick-status-filters">
                    @foreach (['' => 'Visi', 'waiting' => 'Gaida', 'in-progress' => 'Procesa', 'completed' => 'Pabeigti', 'cancelled' => 'Atcelti'] as $statusValue => $statusLabel)
                        @php
                            $query = request()->except('page', 'status');
                            if ($statusValue !== '') {
                                $query['status'] = $statusValue;
                            }
                        @endphp
                        <a
                            href="{{ route('repairs.index', array_filter($query, fn ($value) => $value !== null && $value !== '')) }}"
                            class="quick-status-filter {{ $statusValue === '' ? 'quick-status-filter-slate' : ($statusValue === 'waiting' ? 'quick-status-filter-amber' : ($statusValue === 'in-progress' ? 'quick-status-filter-slate' : ($statusValue === 'completed' ? 'quick-status-filter-emerald' : 'quick-status-filter-rose'))) }} {{ $filters['status'] === $statusValue || ($statusValue === '' && $filters['status'] === '') ? 'quick-status-filter-active' : '' }}"
                        >
                            <x-icon :name="$statusValue === 'completed' ? 'check-circle' : ($statusValue === 'cancelled' ? 'clear' : 'repair')" size="h-4 w-4" />
                            <span>{{ $statusLabel }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="toolbar-actions justify-end">
                    <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                    <a href="{{ route('repairs.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
                </div>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Statuss', 'value' => $filters['status'] !== '' ? ($statusLabels[$filters['status']] ?? $filters['status']) : null],
                ['label' => 'Prioritate', 'value' => $filters['priority'] !== '' ? ($priorityLabels[$filters['priority']] ?? $filters['priority']) : null],
                ['label' => 'Tips', 'value' => $filters['repair_type'] !== '' ? ($typeLabels[$filters['repair_type']] ?? $filters['repair_type']) : null],
            ]"
            :clear-url="route('repairs.index')"
        />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif
        @if (! empty($featureMessage))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
        @endif

        <div
            class="repair-board-grid"
            x-data="repairBoard({
                transitionBaseUrl: @js(url('/repairs')),
                csrfToken: @js(csrf_token()),
            })"
        >
            @foreach ($columnMeta as $columnKey => $column)
                @php
                    $columnRepairs = $repairColumns[$columnKey] ?? collect();
                @endphp
                <div
                    class="repair-board-column repair-column-drop"
                    :class="dropTargetStatus === @js($columnKey) ? 'repair-column-drop-active' : ''"
                    @if ($canManageRepairs)
                        @dragover.prevent="onDragOver(@js($columnKey))"
                        @dragleave="clearDropTarget(@js($columnKey))"
                        @drop.prevent="handleDrop(@js($columnKey))"
                    @endif
                >
                    <div class="repair-board-column-head">
                        <div class="repair-board-column-copy">
                            <div class="repair-board-column-icon repair-board-column-icon-{{ $column['tone'] }}">
                                <x-icon :name="$column['icon']" size="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="repair-board-column-title">{{ $column['title'] }}</h2>
                                <p class="repair-board-column-note">{{ $column['subtitle'] }}</p>
                            </div>
                        </div>
                        <span class="repair-board-column-count">{{ $columnRepairs->count() }}</span>
                    </div>

                    <div class="repair-board-stack">
                        @forelse ($columnRepairs as $repair)
                            <article
                                class="repair-board-card {{ $canManageRepairs ? 'repair-card-draggable' : '' }}"
                                @if ($canManageRepairs)
                                    draggable="true"
                                    @dragstart="startDrag({ id: {{ $repair->id }}, status: @js($repair->status), name: @js($repair->device?->name ?: ('Remonts #' . $repair->id)) }, $event)"
                                    @dragend="clearDrag()"
                                    :class="draggedRepair?.id === {{ $repair->id }} ? 'repair-card-dragging' : ''"
                                @endif
                            >
                                <div class="repair-board-card-head">
                                    <div>
                                        @if ($repair->device)
                                            <a href="{{ route('devices.show', $repair->device) }}" class="repair-board-device-link">{{ $repair->device->name }}</a>
                                        @else
                                            <span class="repair-board-device-link">Ierice nav atrasta</span>
                                        @endif
                                        <div class="repair-board-device-meta">
                                            <span>{{ $repair->device?->code ?: 'bez koda' }}</span>
                                            @if ($repair->device?->room)
                                                <span>Telpa {{ $repair->device->room->room_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" />
                                </div>

                                <div class="repair-board-description">{{ $repair->description }}</div>

                                <div class="repair-board-chip-row">
                                    <x-status-pill context="priority" :value="$repair->priority" :label="$priorityLabels[$repair->priority] ?? null" />
                                    <x-status-pill context="repair-type" :value="$repair->repair_type" :label="$typeLabels[$repair->repair_type] ?? null" />
                                </div>

                                <dl class="repair-board-meta-grid">
                                    <div>
                                        <dt>Pieteicejs</dt>
                                        <dd>{{ $repair->reporter?->full_name ?: 'Nav noradits' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Apstiprinaja</dt>
                                        <dd>{{ $repair->acceptedBy?->full_name ?: 'Nav noradits' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Pieteikums</dt>
                                        <dd>{{ $repair->request_id ? '#' . $repair->request_id : 'Admins bez pieteikuma' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Izmaksas</dt>
                                        <dd>{{ $repair->cost !== null ? number_format((float) $repair->cost, 2, '.', ' ') . ' EUR' : '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Sakums</dt>
                                        <dd>{{ $repair->start_date?->format('d.m.Y') ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Beigas</dt>
                                        <dd>{{ $repair->end_date?->format('d.m.Y') ?: '-' }}</dd>
                                    </div>
                                </dl>

                                <div class="repair-board-actions">
                                    <a href="{{ route('repairs.edit', $repair) }}" class="repair-action repair-action-edit">
                                        <x-icon name="edit" size="h-4 w-4" />
                                        <span>Atvert</span>
                                    </a>

                                    @if ($canManageRepairs && $repair->status === 'waiting')
                                        <button type="button" class="repair-action repair-action-start" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                                            <x-icon name="stats" size="h-4 w-4" />
                                            <span>Sakt</span>
                                        </button>
                                    @endif

                                    @if ($canManageRepairs && $repair->status === 'in-progress')
                                        <button type="button" class="repair-action repair-action-complete" @click="submitCompletion({ id: {{ $repair->id }}, name: @js($repair->device?->name ?: ('Remonts #' . $repair->id)) })">
                                            <x-icon name="check-circle" size="h-4 w-4" />
                                            <span>Pabeigt</span>
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="repair-board-empty">Saja kolonna sobrid nav ierakstu.</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
