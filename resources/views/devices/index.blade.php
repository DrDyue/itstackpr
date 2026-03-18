<x-app-layout>
    @php
        $selectedRoomLabel = $selectedRoom
            ? ($selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : ''))
            : null;
        $statusFilterLinks = [
            ['label' => 'Visas', 'value' => '', 'icon' => 'device', 'tone' => 'slate'],
            ['label' => 'Aktivas', 'value' => 'active', 'icon' => 'check-circle', 'tone' => 'emerald'],
            ['label' => 'Remonta', 'value' => 'repair', 'icon' => 'repair', 'tone' => 'amber'],
            ['label' => 'Norakstitas', 'value' => 'writeoff', 'icon' => 'writeoff', 'tone' => 'rose'],
        ];
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="page-eyebrow">
                        <x-icon name="device" size="h-4 w-4" />
                        <span>Inventars</span>
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

                    <div class="inventory-summary-strip">
                        <div class="inventory-summary-card inventory-summary-card-slate">
                            <div class="inventory-summary-label">Kopa</div>
                            <div class="inventory-summary-value">{{ $deviceSummary['total'] }}</div>
                        </div>
                        <div class="inventory-summary-card inventory-summary-card-emerald">
                            <div class="inventory-summary-label">Aktivas</div>
                            <div class="inventory-summary-value">{{ $deviceSummary['active'] }}</div>
                        </div>
                        <div class="inventory-summary-card inventory-summary-card-amber">
                            <div class="inventory-summary-label">Remonta</div>
                            <div class="inventory-summary-value">{{ $deviceSummary['repair'] }}</div>
                        </div>
                        <div class="inventory-summary-card inventory-summary-card-rose">
                            <div class="inventory-summary-label">Norakstitas</div>
                            <div class="inventory-summary-value">{{ $deviceSummary['writeoff'] }}</div>
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

        <form method="GET" action="{{ route('devices.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Nosaukums, modelis, razotajs...">
            </label>
            <label class="block">
                <span class="crud-label">Kods</span>
                <input type="text" name="code" value="{{ $filters['code'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Stavs</span>
                <select name="floor" class="crud-control">
                    <option value="">Visi stavi</option>
                    @foreach ($floorOptions as $floor)
                        <option value="{{ $floor }}" @selected($filters['floor'] === (string) $floor)>{{ $floor }}. stavs</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Telpa</span>
                <select name="room_id" class="crud-control">
                    <option value="">Visas telpas</option>
                    @foreach ($roomOptions as $room)
                        <option value="{{ $room->id }}" @selected($filters['room_id'] === (string) $room->id)>
                            {{ $room->room_number }}{{ $room->room_name ? ' - ' . $room->room_name : '' }}{{ $room->building?->building_name ? ' | ' . $room->building->building_name : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <select name="type" class="crud-control">
                    <option value="">Visi tipi</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected($filters['type'] == $type->id)>{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="toolbar-actions md:col-span-2 xl:col-span-5">
                <button type="submit" class="btn-search">
                    <x-icon name="search" size="h-4 w-4" />
                    <span>Meklet</span>
                </button>
                <a href="{{ route('devices.index') }}" class="btn-clear">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Notirit</span>
                </a>
            </div>
        </form>

        <div class="quick-status-filters">
            @foreach ($statusFilterLinks as $statusFilter)
                @php
                    $query = request()->except('page', 'status');
                    if ($statusFilter['value'] !== '') {
                        $query['status'] = $statusFilter['value'];
                    }
                @endphp
                <a
                    href="{{ route('devices.index', array_filter($query, fn ($value) => $value !== null && $value !== '')) }}"
                    class="quick-status-filter quick-status-filter-{{ $statusFilter['tone'] }} {{ $filters['status'] === $statusFilter['value'] || ($statusFilter['value'] === '' && $filters['status'] === '') ? 'quick-status-filter-active' : '' }}"
                >
                    <x-icon :name="$statusFilter['icon']" size="h-4 w-4" />
                    <span>{{ $statusFilter['label'] }}</span>
                </a>
            @endforeach
        </div>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Kods', 'value' => $filters['code']],
                ['label' => 'Stavs', 'value' => $filters['floor'] !== '' ? ($filters['floor'] . '. stavs') : null],
                ['label' => 'Telpa', 'value' => $selectedRoomLabel],
                ['label' => 'Tips', 'value' => $filters['type'] !== '' && ctype_digit($filters['type']) ? optional($types->firstWhere('id', (int) $filters['type']))->type_name : null],
                ['label' => 'Statuss', 'value' => $filters['status'] !== '' ? ($statusLabels[$filters['status']] ?? $filters['status']) : null],
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
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->type?->type_name ?: 'Bez tipa' }}</div>
                                <div class="mt-2 text-xs text-slate-400">{{ $device->serial_number ?: 'Bez serijas numura' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('devices.show', $device) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $device->name }}</a>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->model ?: '-' }}</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if ($device->manufacturer)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $device->manufacturer }}</span>
                                    @endif
                                    @if ($device->purchase_date)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-medium text-sky-700">
                                            <x-icon name="calendar" size="h-3.5 w-3.5" />
                                            <span>{{ $device->purchase_date->format('d.m.Y') }}</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->building?->building_name ?: 'Bez ekas' }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $device->room?->room_number ?: '-' }}
                                    @if ($device->room?->room_name)
                                        | {{ $device->room->room_name }}
                                    @endif
                                </div>
                                <div class="mt-2 text-xs text-slate-400">{{ $device->room?->department ?: 'Nodala nav noradita' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->created_at?->format('d.m.Y') ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->created_at?->format('H:i') ?: '-' }}</div>
                                <div class="mt-2 text-xs text-slate-400">{{ $device->createdBy?->full_name ?: 'Sistema' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $device->assignedTo?->job_title ?: 'Amats nav noradits' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                                <div class="mt-2 text-xs text-slate-500">
                                    @if ($device->activeRepair)
                                        Aktivs remonts
                                    @else
                                        Bez aktiva remonta
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <details class="table-action-menu">
                                    <summary class="table-action-summary">
                                        <span>Darbibas</span>
                                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </summary>

                                    <div class="table-action-list">
                                        <a href="{{ route('devices.show', $device) }}" class="table-action-item">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatit</span>
                                        </a>

                                        @if ($canManageDevices)
                                            <a href="{{ route('devices.edit', $device) }}" class="table-action-item table-action-item-amber">
                                                <x-icon name="edit" size="h-4 w-4" />
                                                <span>Rediget</span>
                                            </a>

                                            <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="target_status" value="writeoff">
                                                <button type="submit" class="table-action-button table-action-button-rose" @disabled($device->status === 'writeoff' || $device->status === 'repair')>
                                                    <x-icon name="writeoff" size="h-4 w-4" />
                                                    <span>Norakstit</span>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('devices.quick-update', $device) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="target_status" value="repair">
                                                <button type="submit" class="table-action-button table-action-button-amber" @disabled($device->status !== 'active')>
                                                    <x-icon name="repair" size="h-4 w-4" />
                                                    <span>Atdot remonta</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Ierices nav atrastas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $devices->links() }}
    </section>
</x-app-layout>
