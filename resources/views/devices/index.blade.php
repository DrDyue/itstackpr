<x-app-layout>
    @php
        $selectedFloorLabel = $filters['floor'] !== ''
            ? ($filters['floor'] . '. stavs')
            : ($filters['floor_query'] !== '' ? $filters['floor_query'] : null);
        $selectedRoomLabel = $selectedRoom
            ? ($selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : ''))
            : ($filters['room_query'] !== '' ? $filters['room_query'] : null);
        $selectedTypeLabel = $selectedType?->type_name ?: ($filters['type_query'] !== '' ? $filters['type_query'] : null);
        $selectedAssignedUserLabel = $selectedAssignedUser?->full_name ?: ($filters['assigned_to_query'] !== '' ? $filters['assigned_to_query'] : null);
        $statusFilterLinks = [
            ['label' => 'Aktivas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
            ['label' => 'Remonta', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'amber'],
            ['label' => 'Norakstitas', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
        ];
        $selectedStatuses = $filters['has_status_filter'] ? $filters['statuses'] : collect($statusFilterLinks)->pluck('value')->all();
        $roomSelectOptions = $roomOptions->map(fn ($room) => [
            'value' => (string) $room->id,
            'label' => $room->room_number . ($room->room_name ? ' - ' . $room->room_name : ''),
            'description' => $room->department ?: '',
            'search' => implode(' ', array_filter([
                $room->room_number,
                $room->room_name,
                $room->department,
            ])),
        ])->values();
        $typeSelectOptions = $types->map(fn ($type) => [
            'value' => (string) $type->id,
            'label' => $type->type_name,
            'description' => $type->category ?: '',
            'search' => implode(' ', array_filter([
                $type->type_name,
                $type->category,
                $type->description,
            ])),
        ])->values();
        $floorSelectOptions = collect($floorOptions)->map(fn ($floor) => [
            'value' => (string) $floor,
            'label' => $floor . '. stavs',
            'description' => 'Filtrs pec stava',
            'search' => $floor . ' ' . $floor . '. stavs',
        ])->values();
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="device" size="h-4 w-4" />
                            <span>Inventars</span>
                        </div>

                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="device" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Aktivas</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['active'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="repair" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Remonta</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['repair'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="writeoff" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Norakstitas</span>
                                <span class="inventory-inline-value">{{ $deviceSummary['writeoff'] }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierices</h1>
                            <p class="page-subtitle">{{ $canManageDevices ? 'Pilns iericu saraksts un parvaldiba.' : 'Tavas piesaistitas ierices.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageDevices)
                    <div class="page-actions">
                        <a href="{{ route('devices.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauna ierice</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <form
            method="GET"
            action="{{ route('devices.index') }}"
            class="surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-6"
            x-data="{}"
            @searchable-select-updated.window="if ($event.detail.identifier === 'device-floor-filter') { $dispatch('searchable-select-clear', { target: 'device-room-filter' }) }"
        >
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, razotajs...">
            </label>
            <label class="block">
                <span class="crud-label">Kods</span>
                <input type="text" name="code" value="{{ $filters['code'] }}" class="crud-control">
            </label>
            @if ($canManageDevices)
                <label class="block">
                    <span class="crud-label">Pieskirta</span>
                    <input type="text" name="assigned_to_query" value="{{ $filters['assigned_to_query'] }}" class="crud-control" placeholder="Lietotaja vards">
                    @if ($filters['assigned_to_id'] !== '')
                        <input type="hidden" name="assigned_to_id" value="{{ $filters['assigned_to_id'] }}">
                    @endif
                </label>
            @endif
            <label class="block">
                <span class="crud-label">Stavs</span>
                <x-searchable-select
                    name="floor"
                    query-name="floor_query"
                    identifier="device-floor-filter"
                    :options="$floorSelectOptions"
                    :selected="$filters['floor']"
                    :query="$selectedFloorLabel"
                    placeholder="Izvelies vai raksti stavu"
                    empty-message="Neviens stavs neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Telpa</span>
                <x-searchable-select
                    name="room_id"
                    query-name="room_query"
                    identifier="device-room-filter"
                    :options="$roomSelectOptions"
                    :selected="$filters['room_id']"
                    :query="$selectedRoomLabel"
                    placeholder="Raksti telpas numuru vai nosaukumu"
                    empty-message="Neviena telpa neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <x-searchable-select
                    name="type"
                    query-name="type_query"
                    :options="$typeSelectOptions"
                    :selected="$filters['type']"
                    :query="$selectedTypeLabel"
                    placeholder="Raksti tipa nosaukumu"
                    empty-message="Neviens tips neatbilst meklejumam."
                />
            </label>

            <div class="filter-toolbar-footer md:col-span-2 xl:col-span-5">
                <div class="quick-status-filters">
                    @foreach ($statusFilterLinks as $statusFilter)
                        @php
                            $query = request()->except('page', 'status');
                            $statusValues = collect($selectedStatuses);
                            $isActive = $statusValues->contains($statusFilter['value']);
                            $nextStatuses = $isActive
                                ? $statusValues->reject(fn ($value) => $value === $statusFilter['value'])->values()->all()
                                : $statusValues->push($statusFilter['value'])->unique()->values()->all();

                            if (count($nextStatuses) === 0 || count($nextStatuses) === count($statusFilterLinks)) {
                                unset($query['status']);
                            } else {
                                $query['status'] = $nextStatuses;
                            }
                        @endphp
                        <a
                            href="{{ route('devices.index', $query) }}"
                            class="quick-status-filter quick-status-filter-{{ $statusFilter['tone'] }} {{ $isActive ? 'quick-status-filter-active' : '' }}"
                        >
                            <x-icon :name="$statusFilter['icon']" size="h-4 w-4" />
                            <span>{{ $statusFilter['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="toolbar-actions justify-end">
                    <button type="submit" class="btn-search">
                        <x-icon name="search" size="h-4 w-4" />
                        <span>Meklet</span>
                    </button>
                    <a href="{{ route('devices.index') }}" class="btn-clear">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Notirit</span>
                    </a>
                </div>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Kods', 'value' => $filters['code']],
                ['label' => 'Pieskirta', 'value' => $canManageDevices ? $selectedAssignedUserLabel : null],
                ['label' => 'Stavs', 'value' => $selectedFloorLabel],
                ['label' => 'Telpa', 'value' => $selectedRoomLabel],
                ['label' => 'Tips', 'value' => $selectedTypeLabel],
                ['label' => 'Statuss', 'value' => $filters['has_status_filter'] ? collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ') : null],
            ]"
            :clear-url="route('devices.index')"
        />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Attels</th>
                        <th class="px-4 py-3">Kods</th>
                        <th class="px-4 py-3">Nosaukums</th>
                        <th class="px-4 py-3">Atrasanas vieta</th>
                        <th class="px-4 py-3">Izveidots</th>
                        <th class="px-4 py-3">Pieskirta</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        @php
                            $thumbUrl = $device->deviceImageThumbUrl();
                            $nameMeta = collect([$device->manufacturer, $device->model])->filter(fn ($value) => filled($value))->implode(' | ');
                        @endphp
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-4 py-4">
                                @if ($thumbUrl)
                                    <img src="{{ $thumbUrl }}" alt="{{ $device->name }}" class="device-table-thumb">
                                @else
                                    <div class="device-table-thumb device-table-thumb-placeholder">
                                        <x-icon name="device" size="h-4 w-4" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</div>
                                @if ($device->serial_number)
                                    <div class="mt-1 text-xs text-slate-500">Serija: {{ $device->serial_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('devices.show', $device) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $device->name }}</a>
                                @if ($nameMeta !== '')
                                    <div class="mt-1 text-xs text-slate-500">{{ $nameMeta }}</div>
                                @endif
                                <div class="mt-2 text-xs text-slate-400">{{ $device->type?->type_name ?: 'Bez tipa' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                @if ($device->room)
                                    <div class="font-medium text-slate-900">
                                        {{ $device->room->room_number }}
                                        @if ($device->room->room_name)
                                            | {{ $device->room->room_name }}
                                        @endif
                                    </div>
                                    @if ($device->room->department)
                                        <div class="mt-2 text-xs text-slate-400">{{ $device->room->department }}</div>
                                    @endif
                                @else
                                    <div class="font-medium text-slate-900">Vieta nav noradita</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->created_at?->format('d.m.Y') ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->created_at?->format('H:i') ?: '-' }}</div>
                                <div class="mt-2 text-xs text-slate-400">{{ $device->createdBy?->full_name ?: 'Sistema' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                                @if ($device->assignedTo?->job_title)
                                    <div class="mt-1 text-xs text-slate-500">{{ $device->assignedTo->job_title }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                                @if ($device->activeRepair)
                                    <div class="mt-2 text-xs text-slate-500">
                                        Remonta statuss: {{ ['waiting' => 'Gaida', 'in-progress' => 'Procesa', 'completed' => 'Pabeigts', 'cancelled' => 'Atcelts'][$device->activeRepair->status] ?? 'Remonta' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                    <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                        <span>Darbibas</span>
                                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div class="table-action-list" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                        <a href="{{ route('devices.show', $device) }}" class="table-action-item" @click="open = false">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatit</span>
                                        </a>

                                        @if ($canManageDevices)
                                            <a href="{{ route('devices.edit', $device) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                                <x-icon name="edit" size="h-4 w-4" />
                                                <span>Rediget</span>
                                            </a>

                                            @if ($device->status === 'active')
                                                <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="target_status" value="writeoff">
                                                    <button
                                                        type="submit"
                                                        class="table-action-button table-action-button-rose"
                                                        formmethod="POST"
                                                        onclick="return confirm('Vai tiesam norakstit so ierici? Pec norakstisanas ta vairs nebus pieskirta lietotajam vai telpai.')"
                                                    >
                                                        <x-icon name="writeoff" size="h-4 w-4" />
                                                        <span>Norakstit</span>
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="target_status" value="repair">
                                                    <button type="submit" class="table-action-button table-action-button-amber" formmethod="POST">
                                                        <x-icon name="repair" size="h-4 w-4" />
                                                        <span>Atdot remonta</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Ierices nav atrastas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $devices->links() }}
    </section>
</x-app-layout>
