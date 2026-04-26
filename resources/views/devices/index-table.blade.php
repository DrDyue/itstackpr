@props([
    'devices',
    'deviceStates',
    'sorting',
    'sortOptions',
    'statusLabels',
    'canManageDevices',
    'quickRoomSelectOptions',
    'userRoomOptions' => collect(),
    'quickAssigneeSelectOptions' => null,
    'quickAssigneeOptions' => null,
    'types' => collect(),
    'buildings' => collect(),
    'rooms' => collect(),
    'users' => collect(),
    'statuses' => [],
    'defaultAssignedToId' => null,
    'defaultRoomId' => null,
    'defaultBuildingId' => null,
])

@php
    $quickAssigneeSelectOptions = collect($quickAssigneeSelectOptions ?? $quickAssigneeOptions ?? [])->values();
    $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
    $oldUserRoomModalForm = str_starts_with((string) old('modal_form'), 'device_user_room_')
        ? (string) old('modal_form')
        : null;
    $oldUserRoomDeviceId = $oldUserRoomModalForm ? (int) str_replace('device_user_room_', '', $oldUserRoomModalForm) : null;
    $oldUserRoomDevice = $oldUserRoomDeviceId ? $devices->firstWhere('id', $oldUserRoomDeviceId) : null;
    $oldUserRoomLabel = $oldUserRoomDevice?->room
        ? ($oldUserRoomDevice->room->room_number . ($oldUserRoomDevice->room->room_name ? ' - ' . $oldUserRoomDevice->room->room_name : ''))
        : 'Nav norādīta';
    $columns = $canManageDevices
        ? [
            'code' => 'Kods',
            'serial_number' => 'Sērijas numurs',
            'name' => 'Nosaukums',
            'assigned_to' => 'Piešķirta',
            'location' => 'Atrašanās vieta',
            'status' => 'Statuss',
            'created_at' => 'Izveidota',
        ]
        : [
            'code' => 'Kods',
            'serial_number' => 'Sērijas numurs',
            'name' => 'Nosaukums',
            'location' => 'Atrašanās vieta',
            'status' => 'Statuss',
        ];
@endphp

<div id="devices-table-root">
    <div class="repair-table-shell mt-5 rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <div class="repair-table-scroll">
            <table class="repair-table-content w-full min-w-full text-sm">
                <thead class="repair-table-head bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="table-col-image px-3 py-3">Attēls</th>
                        @foreach ($columns as $column => $label)
                            @php
                                $isCurrentSort = $sorting['sort'] === $column;
                                $headerWidthClass = match ($column) {
                                    'code' => 'table-col-code',
                                    'serial_number' => 'table-col-serial',
                                    'name' => 'table-col-name',
                                    'assigned_to' => 'table-col-person',
                                    'location' => 'table-col-location',
                                    'status' => 'table-col-status',
                                    'created_at' => 'table-col-date',
                                    default => '',
                                };
                                $defaultDirection = in_array($column, ['created_at'], true) ? 'desc' : 'asc';
                                $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                $sortMessage = 'Tabula "Ierīces" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                            @endphp
                            <th class="{{ $headerWidthClass }} px-4 py-3">
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
                        <th class="table-col-actions px-4 py-3 text-right">Darbības</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        @php
                            $thumbUrl = $device->deviceImageThumbUrl();
                            $deviceState = $deviceStates[$device->id] ?? [];
                            $requestAvailability = $deviceState['requestAvailability'] ?? ['repair' => false, 'writeoff' => false, 'transfer' => false, 'can_create_any' => false, 'reason' => null];
                            $roomUpdateAvailability = $deviceState['roomUpdateAvailability'] ?? ['allowed' => false, 'reason' => 'Telpas maiņa šobrīd nav pieejama.'];
                            $pendingRequestBadge = $deviceState['pendingRequestBadge'] ?? null;
                            $activeRequestUrl = $pendingRequestBadge['url'] ?? null;
                            $hasActiveRequest = ! empty($activeRequestUrl);
                            $activeRequestMessage = $hasActiveRequest
                                ? 'Šai ierīcei ir aktīvs pieteikums. Vispirms jāatrisina pieteikums, un tikai pēc tam var rediģēt ierīci vai mainīt telpu/atbildīgo.'
                                : null;
                            $repairStatusLabel = $deviceState['repairStatusLabel'] ?? null;
                            $repairPreview = $deviceState['repairPreview'] ?? null;
                            $repairRecord = $device->activeRepair;
                            $hasActiveRepair = (bool) $repairRecord;
                            $displayDeviceStatus = $device->status === \App\Models\Device::STATUS_REPAIR && ! $hasActiveRepair
                                ? \App\Models\Device::STATUS_ACTIVE
                                : $device->status;
                            $repairModalUrl = $repairRecord ? route('repairs.index', [
                                'highlight_id' => 'repair-' . $repairRecord->id,
                            ]) : null;
                            $repairCreateUrl = route('repairs.index', ['repair_modal' => 'create', 'device_id' => $device->id]);
                            $repairRequestCreateUrl = route('repair-requests.index', ['repair_request_modal' => 'create', 'device_id' => $device->id]);
                            $writeoffRequestCreateUrl = route('writeoff-requests.index', ['writeoff_request_modal' => 'create', 'device_id' => $device->id]);
                            $transferCreateUrl = route('device-transfers.index', ['device_transfer_modal' => 'create', 'device_id' => $device->id]);
                            $roomLabel = trim(collect([
                                $device->room?->room_name,
                                $device->room?->room_number,
                            ])->filter()->implode(' '));
                            $locationPrimary = $roomLabel !== ''
                                ? $roomLabel
                                : ($device->room?->room_number ? 'Telpa ' . $device->room->room_number : 'Atrašanās vieta nav norādīta');
                            $locationSecondary = $device->building?->building_name ?: $device->room?->department;
                            $nameMeta = collect([$device->manufacturer, $device->model])->filter()->implode(' | ');
                            $userRoomModalDetail = [
                                'deviceId' => $device->id,
                                'deviceLabel' => ($device->code ?: 'Bez koda') . ' | ' . $device->name,
                                'currentRoomLabel' => $device->room
                                    ? ($device->room->room_number . ($device->room->room_name ? ' - ' . $device->room->room_name : ''))
                                    : 'Nav norādīta',
                                'selectedRoomId' => (string) ($device->room_id ?? ''),
                                'action' => route('devices.user-room.update', $device),
                            ];
                            $adminRoomModalDetail = [
                                'deviceLabel' => ($device->code ?: 'Bez koda') . ' | ' . $device->name,
                                'selectedRoomId' => (string) ($device->room_id ?? ''),
                                'action' => route('devices.quick-update', $device),
                            ];
                            $adminAssigneeModalDetail = [
                                'deviceLabel' => ($device->code ?: 'Bez koda') . ' | ' . $device->name,
                                'selectedAssigneeId' => (string) ($device->assigned_to_id ?? ''),
                                'action' => route('devices.quick-update', $device),
                            ];
                        @endphp

                        <tr class="repair-table-row border-t border-slate-100 align-top" data-table-row-id="device-{{ $device->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) $device->code)) }}">
                            <td class="px-3 py-4">
                                @if ($thumbUrl)
                                    <img
                                        src="{{ $thumbUrl }}"
                                        alt="{{ $device->name }}"
                                        class="device-table-thumb"
                                        loading="lazy"
                                        decoding="async"
                                        fetchpriority="low"
                                    >
                                @else
                                    <div class="device-table-thumb device-table-thumb-placeholder">
                                        <x-icon name="device" size="h-4 w-4" />
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="text-sm font-semibold text-slate-900">{{ $device->serial_number ?: '-' }}</div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $device->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $nameMeta !== '' ? $nameMeta : 'Ražotājs un modelis nav norādīti' }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $device->type?->type_name ?: 'Tips nav norādīts' }}</div>
                            </td>

                            @if ($canManageDevices)
                                <td class="px-4 py-4">
                                    <x-device-assignment
                                        :device="$device"
                                        secondary="email"
                                        primary-class="font-semibold text-slate-900"
                                        secondary-class="mt-1 text-xs text-slate-500"
                                    />
                                </td>
                            @endif

                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $locationPrimary }}</div>
                                @if ($locationSecondary)
                                    <div class="mt-1 text-xs text-slate-500">{{ $locationSecondary }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                @if ($hasActiveRepair && $repairStatusLabel)
                                    <div class="relative inline-flex" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                        @if ($repairModalUrl)
                                            <a href="{{ $repairModalUrl }}" class="device-status-split-chip device-status-split-chip-repair" @focusin="open = true" @focusout="open = false">
                                                <span class="device-status-split-main">
                                                    <x-icon name="repair" size="h-3.5 w-3.5" />
                                                    <span>Remonts</span>
                                                </span>
                                                <span class="device-status-split-sub">{{ $repairStatusLabel }}</span>
                                            </a>
                                        @else
                                            <div class="device-status-split-chip device-status-split-chip-repair" tabindex="0" @focusin="open = true" @focusout="open = false">
                                                <span class="device-status-split-main">
                                                    <x-icon name="repair" size="h-3.5 w-3.5" />
                                                    <span>Remonts</span>
                                                </span>
                                                <span class="device-status-split-sub">{{ $repairStatusLabel }}</span>
                                            </div>
                                        @endif

                                        @if ($repairPreview)
                                            <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                                <div class="device-request-popover-head">
                                                    <span class="device-request-popover-title">{{ $repairPreview['title'] }}</span>
                                                    <span class="device-request-popover-date">{{ $repairPreview['created_at'] }}</span>
                                                </div>
                                                <div class="device-request-popover-row">
                                                    <span class="device-request-popover-label">Statuss</span>
                                                    <span class="device-request-popover-value">{{ $repairPreview['status'] }}</span>
                                                </div>
                                                <div class="device-request-popover-row">
                                                    <span class="device-request-popover-label">Tips</span>
                                                    <span class="device-request-popover-value">{{ $repairPreview['type'] }}</span>
                                                </div>
                                                <div class="device-request-popover-row">
                                                    <span class="device-request-popover-label">Pieņēma remontu</span>
                                                    <span class="device-request-popover-value">{{ $repairPreview['approved_by'] }}</span>
                                                </div>
                                                <div class="device-request-popover-row device-request-popover-row-stack">
                                                    <span class="device-request-popover-label">Apraksts</span>
                                                    <div class="device-request-popover-copy">{{ $repairPreview['description'] }}</div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($pendingRequestBadge)
                                    <div class="relative inline-flex" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                        @if (! empty($pendingRequestBadge['url']))
                                            <a href="{{ $pendingRequestBadge['url'] }}" class="device-request-badge-link {{ $pendingRequestBadge['class'] }}" @focusin="open = true" @focusout="open = false">
                                                <span class="device-status-split-main">
                                                    <x-icon :name="$pendingRequestBadge['icon']" size="h-3.5 w-3.5" />
                                                    <span>{{ $pendingRequestBadge['short_label'] ?? $pendingRequestBadge['label'] }}</span>
                                                </span>
                                                @if (! empty($pendingRequestBadge['detail_label']))
                                                    <span class="device-status-split-sub">{{ $pendingRequestBadge['detail_label'] }}</span>
                                                @endif
                                            </a>
                                        @else
                                            <div class="device-request-badge-link {{ $pendingRequestBadge['class'] }}">
                                                <span class="device-status-split-main">
                                                    <x-icon :name="$pendingRequestBadge['icon']" size="h-3.5 w-3.5" />
                                                    <span>{{ $pendingRequestBadge['short_label'] ?? $pendingRequestBadge['label'] }}</span>
                                                </span>
                                                @if (! empty($pendingRequestBadge['detail_label']))
                                                    <span class="device-status-split-sub">{{ $pendingRequestBadge['detail_label'] }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if (! empty($pendingRequestBadge['preview']))
                                            <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                                <div class="device-request-popover-head">
                                                    <span class="device-request-popover-title">{{ $pendingRequestBadge['preview']['type_label'] }}</span>
                                                    <span class="device-request-popover-date">{{ $pendingRequestBadge['preview']['submitted_at'] }}</span>
                                                </div>
                                                <div class="device-request-popover-row">
                                                    <span class="device-request-popover-label">Pieteicējs</span>
                                                    <span class="device-request-popover-value">{{ $pendingRequestBadge['preview']['submitted_by'] }}</span>
                                                </div>
                                                @if (! empty($pendingRequestBadge['preview']['recipient']))
                                                    <div class="device-request-popover-row">
                                                        <span class="device-request-popover-label">Saņēmējs</span>
                                                        <span class="device-request-popover-value">{{ $pendingRequestBadge['preview']['recipient'] }}</span>
                                                    </div>
                                                @endif
                                                <div class="device-request-popover-row device-request-popover-row-stack">
                                                    <span class="device-request-popover-label">{{ $pendingRequestBadge['preview']['meta_label'] }}</span>
                                                    <div class="device-request-popover-copy">{{ $pendingRequestBadge['preview']['summary'] }}</div>
                                                </div>
                                                <div class="device-request-popover-link">Atvērt pieprasījumu</div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <x-status-pill context="device" :value="$displayDeviceStatus" />
                                @endif
                            </td>

                            @if ($canManageDevices)
                                <td class="px-4 py-4 tabular-nums">
                                    <div class="font-semibold text-slate-900">{{ $device->created_at?->format('d.m.Y') ?: '-' }}</div>
                                </td>
                            @endif

                            <td class="px-4 py-4">
                                @if (! $canManageDevices)
                                    <div class="device-user-action-row">
                                        <a href="{{ route('devices.show', $device) }}" class="table-action-button table-action-button-sky">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatīt</span>
                                        </a>

                                        @if ($roomUpdateAvailability['allowed'])
                                            <button
                                                type="button"
                                                class="table-action-button table-action-button-slate"
                                                onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-user-room-modal-{{ $device->id }}' }))"
                                            >
                                                <x-icon name="room" size="h-4 w-4" />
                                                <span>Mainīt telpu</span>
                                            </button>
                                        @else
                                            <button type="button" class="btn-disabled" data-app-toast-title="Telpas maiņa nav pieejama" data-app-toast-message="{{ $roomUpdateAvailability['reason'] ?? 'Telpas maiņa šobrīd nav pieejama.' }}" data-app-toast-tone="info">
                                                <x-icon name="room" size="h-4 w-4" />
                                                <span>Mainīt telpu</span>
                                            </button>
                                        @endif

                                        @if ($requestAvailability['can_create_any'])
                                            <div class="table-action-menu" x-data="createFloatingDropdown({ zIndex: 400 })" @keydown.escape.window="closePanel()">
                                                <button type="button" class="table-action-button table-action-button-emerald" x-ref="trigger" @click="togglePanel()" :aria-expanded="open.toString()">
                                                    <x-icon name="repair-request" size="h-4 w-4" />
                                                    <span>Pieteikumi</span>
                                                </button>

                                                <template x-teleport="body">
                                                <div class="table-action-list" data-floating-menu="manual" x-ref="panel" x-cloak x-show="open" x-transition.origin.top.right x-bind:style="panelStyle" @click.outside="closePanel()">
                                                    <div class="table-action-section">
                                                        <div class="table-action-section-title">Pieteikumi</div>
                                                        <div class="table-action-stack">
                                                            @if ($requestAvailability['repair'])
                                                                <a href="{{ $repairRequestCreateUrl }}" class="table-action-item table-action-item-sky" @click="closePanel()">
                                                                    <x-icon name="repair" size="h-4 w-4" />
                                                                    <span>Remonts</span>
                                                                </a>
                                                            @endif
                                                            @if ($requestAvailability['writeoff'])
                                                                <a href="{{ $writeoffRequestCreateUrl }}" class="table-action-item table-action-item-rose" @click="closePanel()">
                                                                    <x-icon name="writeoff" size="h-4 w-4" />
                                                                    <span>Norakstīšana</span>
                                                                </a>
                                                            @endif
                                                            @if ($requestAvailability['transfer'])
                                                                <a href="{{ $transferCreateUrl }}" class="table-action-item table-action-item-emerald" @click="closePanel()">
                                                                    <x-icon name="transfer" size="h-4 w-4" />
                                                                    <span>Nodot</span>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                </template>
                                            </div>
                                        @else
                                            <button type="button" class="btn-disabled" data-app-toast-title="{{ $pendingRequestBadge['label'] ?? 'Pieteikumi nav pieejami' }}" data-app-toast-message="{{ $requestAvailability['reason'] ?? 'Pieteikumus šobrīd nevar izveidot.' }}" data-app-toast-tone="info">
                                                <x-icon :name="$pendingRequestBadge['icon'] ?? 'repair-request'" size="h-4 w-4" />
                                                <span>Pieteikumi</span>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="table-action-menu" x-data="createFloatingDropdown({ zIndex: 400 })" @keydown.escape.window="closePanel()">
                                        <button type="button" class="table-action-summary" x-ref="trigger" @click="togglePanel()" :aria-expanded="open.toString()">
                                            <span>Darbības</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>

                                        <template x-teleport="body">
                                        <div class="table-action-list" data-floating-menu="manual" x-ref="panel" x-cloak x-show="open" x-transition.origin.top.right x-bind:style="panelStyle" @click.outside="closePanel()">
                                            <div class="table-action-header">
                                                <div class="table-action-header-title">Darbības</div>
                                            </div>

                                            <a href="{{ route('devices.show', $device) }}" class="table-action-item table-action-item-primary" @click="closePanel()">
                                                <x-icon name="view" size="h-4 w-4" />
                                                <span>Skatīt ierīci</span>
                                            </a>

                                            @if ($activeRequestUrl)
                                                <a href="{{ $activeRequestUrl }}" class="table-action-item table-action-item-amber" @click="closePanel()">
                                                    <x-icon :name="$pendingRequestBadge['icon'] ?? 'repair-request'" size="h-4 w-4" />
                                                    <span>Pāriet uz pieteikumu</span>
                                                </a>
                                            @endif

                                            @if ($hasActiveRequest)
                                                <button type="button" class="table-action-item table-action-item-amber opacity-50 cursor-not-allowed" data-app-toast-title="Rediģēšana nav pieejama" data-app-toast-message="{{ $activeRequestMessage }}" data-app-toast-tone="info" @click="closePanel()">
                                                    <x-icon name="edit" size="h-4 w-4" />
                                                    <span>Rediģēt</span>
                                                </button>
                                            @else
                                                <button type="button" class="table-action-item table-action-item-amber" onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-edit-modal-{{ $device->id }}' }))" @click="closePanel()">
                                                    <x-icon name="edit" size="h-4 w-4" />
                                                    <span>Rediģēt</span>
                                                </button>
                                            @endif

                                            @if ($device->status === 'active')
                                                <button
                                                    type="button"
                                                    class="table-action-item table-action-item-sky {{ $hasActiveRequest ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    @if ($hasActiveRequest)
                                                        data-app-toast-title="Telpas maiņa nav pieejama"
                                                        data-app-toast-message="{{ $activeRequestMessage }}"
                                                        data-app-toast-tone="info"
                                                        @click="closePanel()"
                                                    @else
                                                        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-admin-room-modal-{{ $device->id }}' }))"
                                                        @click="closePanel()"
                                                    @endif
                                                >
                                                    <x-icon name="room" size="h-4 w-4" />
                                                    <span>Mainīt telpu</span>
                                                </button>

                                                <button
                                                    type="button"
                                                    class="table-action-item table-action-item-violet {{ $hasActiveRequest ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    @if ($hasActiveRequest)
                                                        data-app-toast-title="Atbildīgā maiņa nav pieejama"
                                                        data-app-toast-message="{{ $activeRequestMessage }}"
                                                        data-app-toast-tone="info"
                                                        @click="closePanel()"
                                                    @else
                                                        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-admin-assignee-modal-{{ $device->id }}' }))"
                                                        @click="closePanel()"
                                                    @endif
                                                >
                                                    <x-icon name="user" size="h-4 w-4" />
                                                    <span>Mainīt atbildīgo</span>
                                                </button>

                                                @if ($requestAvailability['can_create_any'])
                                                    <form method="POST" action="{{ route('devices.quick-update', $device) }}" data-app-confirm-title="Norakstīt ierīci?" data-app-confirm-message="Vai tiešām norakstīt šo ierīci? Pēc norakstīšanas tā vairs nebūs piešķirta lietotājam vai telpai." data-app-confirm-accept="Jā, norakstīt" data-app-confirm-cancel="Nē" data-app-confirm-tone="danger">
                                                        @csrf
                                                        <input type="hidden" name="action" value="status">
                                                        <input type="hidden" name="target_status" value="writeoff">
                                                        <button type="submit" class="table-action-item table-action-item-rose">
                                                            <x-icon name="writeoff" size="h-4 w-4" />
                                                            <span>Norakstīt</span>
                                                        </button>
                                                    </form>

                                                    <a href="{{ $repairCreateUrl }}" class="table-action-item table-action-item-amber" @click="closePanel()">
                                                        <x-icon name="repair" size="h-4 w-4" />
                                                        <span>Atdot uz remontu</span>
                                                    </a>
                                                @else
                                                    <button type="button"
                                                        class="table-action-item table-action-item-rose opacity-50 cursor-not-allowed"
                                                        data-app-toast-title="Norakstīšana nav pieejama"
                                                        data-app-toast-message="{{ $requestAvailability['reason'] ?? 'Ierīcei ir aktīvs pieteikums.' }}"
                                                        data-app-toast-tone="info"
                                                        @click="closePanel()">
                                                        <x-icon name="writeoff" size="h-4 w-4" />
                                                        <span>Norakstīt</span>
                                                    </button>

                                                    <button type="button"
                                                        class="table-action-item table-action-item-amber opacity-50 cursor-not-allowed"
                                                        data-app-toast-title="Remonts nav pieejams"
                                                        data-app-toast-message="{{ $requestAvailability['reason'] ?? 'Ierīcei ir aktīvs pieteikums.' }}"
                                                        data-app-toast-tone="info"
                                                        @click="closePanel()">
                                                        <x-icon name="repair" size="h-4 w-4" />
                                                        <span>Atdot uz remontu</span>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                        </template>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageDevices ? 9 : 7 }}" class="px-4 py-6">
                                <x-empty-state
                                    compact
                                    icon="devices"
                                    title="Ierīces netika atrastas"
                                    description="Pamēģini paplašināt filtru nosacījumus vai notīrīt atlasītos kritērijus."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@if (! $canManageDevices)
    <x-modal name="device-user-room-modal" maxWidth="2xl">
        {{-- 
            Lietotāja ātrā telpas maiņa.
            Forma pati klausās `open-device-user-room` notikumu, lai tā darbotos arī tad,
            ja poga atrodas tabulas rindā vai pārzīmētā async tabulas fragmentā.
        --}}
        <div
            class="device-user-room-modal-shell"
            x-data='{
                userRoomModal: {
                    deviceId: @js($oldUserRoomDeviceId),
                    deviceLabel: @js($oldUserRoomDevice ? (($oldUserRoomDevice->code ?: "Bez koda") . " | " . $oldUserRoomDevice->name) : ""),
                    currentRoomLabel: @js($oldUserRoomLabel),
                    selectedRoomId: @js($oldUserRoomModalForm ? old("room_id", (string) ($oldUserRoomDevice?->room_id ?? "")) : ""),
                    action: @js($oldUserRoomDeviceId ? route("devices.user-room.update", $oldUserRoomDeviceId) : ""),
                },
                open(detail) {
                    this.userRoomModal = {
                        deviceId: detail.deviceId || null,
                        deviceLabel: detail.deviceLabel || "",
                        currentRoomLabel: detail.currentRoomLabel || "Nav norādīta",
                        selectedRoomId: detail.selectedRoomId || "",
                        action: detail.action || "",
                    };
                    window.dispatchEvent(new CustomEvent("open-modal", { detail: "device-user-room-modal" }));
                },
            }'
            @open-device-user-room.window="open($event.detail || {})"
        >
            <div class="device-user-room-modal-head">
                <div>
                    <div class="device-user-room-modal-badge">Telpas maiņa</div>
                    <h2 class="device-user-room-modal-title" x-text="userRoomModal.deviceLabel || 'Ierīce'"></h2>
                    <p class="device-user-room-modal-copy">Izvēlies jauno telpu šai ierīcei. Ēka tiks pielāgota automātiski pēc izvēlētās telpas.</p>
                </div>
                <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'device-user-room-modal')" aria-label="Aizvērt">
                    <x-icon name="x-mark" size="h-5 w-5" />
                </button>
            </div>
            <form method="POST" :action="userRoomModal.action || '#'" class="device-user-room-modal-form">
                @csrf
                <input type="hidden" name="modal_form" :value="userRoomModal.deviceId ? `device_user_room_${userRoomModal.deviceId}` : 'device_user_room'">
                <div class="device-user-room-modal-device">
                    <div>
                        <div class="device-user-room-modal-label">Ierīce</div>
                        <div class="device-user-room-modal-value" x-text="userRoomModal.deviceLabel || 'Nav izvēlēta ierīce'"></div>
                    </div>
                    <div>
                        <div class="device-user-room-modal-label">Pašreizējā telpa</div>
                        <div class="device-user-room-modal-value" x-text="userRoomModal.currentRoomLabel || 'Nav norādīta'"></div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="device-user-room-modal-label" for="device-user-room-shared-input">Jaunā telpa</label>
                    <select id="device-user-room-shared-input" name="room_id" class="crud-control" x-model="userRoomModal.selectedRoomId">
                        <option value="">Izvēlies telpu</option>
                        @foreach ($userRoomOptions as $roomOption)
                            <option
                                value="{{ $roomOption['value'] }}"
                                @selected($oldUserRoomModalForm && old('room_id', (string) ($oldUserRoomDevice?->room_id ?? '')) === (string) $roomOption['value'])
                            >
                                {{ $roomOption['label'] }}@if (! empty($roomOption['description'])) | {{ $roomOption['description'] }}@endif
                            </option>
                        @endforeach
                    </select>
                    @if ($oldUserRoomModalForm && $errors->has('room_id'))
                        <div class="text-sm text-rose-600">{{ $errors->first('room_id') }}</div>
                    @endif
                </div>
                <div class="device-user-room-modal-actions">
                    <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'device-user-room-modal')">Atcelt</button>
                    <button type="submit" class="btn-search">
                        <x-icon name="save" size="h-4 w-4" />
                        <span>Saglabāt</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @foreach ($devices as $userRoomDeviceModalItem)
        @php
            $userRoomDeviceLabel = ($userRoomDeviceModalItem->code ?: 'Bez koda') . ' | ' . $userRoomDeviceModalItem->name;
            $userRoomCurrentLabel = $userRoomDeviceModalItem->room
                ? ($userRoomDeviceModalItem->room->room_number . ($userRoomDeviceModalItem->room->room_name ? ' - ' . $userRoomDeviceModalItem->room->room_name : ''))
                : 'Nav norādīta';
            $isOldUserRoomModal = $oldUserRoomDeviceId === $userRoomDeviceModalItem->id;
        @endphp
        <x-modal :name="'device-user-room-modal-' . $userRoomDeviceModalItem->id" maxWidth="2xl">
            <div class="device-user-room-modal-shell">
                <div class="device-user-room-modal-head">
                    <div>
                        <div class="device-user-room-modal-badge">Telpas maiņa</div>
                        <h2 class="device-user-room-modal-title">{{ $userRoomDeviceLabel }}</h2>
                        <p class="device-user-room-modal-copy">Izvēlies jauno telpu šai ierīcei. Ēka tiks pielāgota automātiski pēc izvēlētās telpas.</p>
                    </div>
                    <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'device-user-room-modal-{{ $userRoomDeviceModalItem->id }}')" aria-label="Aizvērt">
                        <x-icon name="x-mark" size="h-5 w-5" />
                    </button>
                </div>
                <form method="POST" action="{{ route('devices.user-room.update', $userRoomDeviceModalItem) }}" class="device-user-room-modal-form">
                    @csrf
                    <input type="hidden" name="modal_form" value="device_user_room_{{ $userRoomDeviceModalItem->id }}">
                    <div class="device-user-room-modal-device">
                        <div>
                            <div class="device-user-room-modal-label">Ierīce</div>
                            <div class="device-user-room-modal-value">{{ $userRoomDeviceLabel }}</div>
                        </div>
                        <div>
                            <div class="device-user-room-modal-label">Pašreizējā telpa</div>
                            <div class="device-user-room-modal-value">{{ $userRoomCurrentLabel }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="device-user-room-modal-label" for="device-user-room-input-{{ $userRoomDeviceModalItem->id }}">Jaunā telpa</label>
                        <x-searchable-select
                            name="room_id"
                            queryName="room_query"
                            :options="$userRoomOptions"
                            :selected="$isOldUserRoomModal ? old('room_id', (string) ($userRoomDeviceModalItem->room_id ?? '')) : (string) ($userRoomDeviceModalItem->room_id ?? '')"
                            :query="$isOldUserRoomModal ? old('room_query', $userRoomCurrentLabel) : $userRoomCurrentLabel"
                            :identifier="'device-user-room-' . $userRoomDeviceModalItem->id"
                            :prioritize-selected="true"
                            selected-group-label="Pašreizējā telpa"
                            placeholder="Izvēlies telpu"
                            empty-message="Neviena telpa neatbilst meklējumam."
                            :error="$isOldUserRoomModal ? $errors->first('room_id') : null"
                        />
                        @if ($isOldUserRoomModal && $errors->has('room_id'))
                            <div class="text-sm text-rose-600">{{ $errors->first('room_id') }}</div>
                        @endif
                    </div>
                    <div class="device-user-room-modal-actions">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'device-user-room-modal-{{ $userRoomDeviceModalItem->id }}')">Atcelt</button>
                        <button type="submit" class="btn-search">
                            <x-icon name="save" size="h-4 w-4" />
                            <span>Saglabāt</span>
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endforeach

    @if ($oldUserRoomModalForm)
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-user-room-modal-{{ $oldUserRoomDeviceId }}' })));</script>
    @endif
@else
    {{-- 
        Ierīču rediģēšanas modāļi tiek sagatavoti kopā ar redzamo tabulu.
        Tāpēc darbību izvēlnes poga "Rediģēt" atver formu uzreiz un nelieto
        `device_modal=edit` URL pārlādi.
    --}}
    @foreach ($devices as $deviceModalItem)
        @include('devices.partials.modal-form', [
            'mode' => 'edit',
            'modalName' => 'device-edit-modal-' . $deviceModalItem->id,
            'device' => $deviceModalItem,
        ])
        @php
            $quickRoomSelectedOption = $quickRoomSelectOptions->firstWhere('value', (string) $deviceModalItem->room_id);
            $quickAssigneeSelectedOption = $quickAssigneeSelectOptions->firstWhere('value', (string) $deviceModalItem->assigned_to_id);
        @endphp

        {{-- 
            Ātrās darbības modāļi ir piesaistīti konkrētai tabulas rindai.
            Šādi pogas "Mainīt telpu" un "Mainīt atbildīgo" atver jau sagatavotu formu
            bez lapas pārlādes un bez papildu JavaScript datu parsēšanas klikšķa brīdī.
        --}}
        <x-modal :name="'device-admin-room-modal-' . $deviceModalItem->id" maxWidth="2xl">
            <div class="device-user-room-modal-shell">
                <div class="device-user-room-modal-head">
                    <div>
                        <div class="device-user-room-modal-badge">Telpas maiņa</div>
                        <h2 class="device-user-room-modal-title">{{ ($deviceModalItem->code ?: 'Bez koda') . ' | ' . $deviceModalItem->name }}</h2>
                        <p class="device-user-room-modal-copy">Izvēlies telpu, uz kuru uzreiz pārvietot ierīci.</p>
                    </div>
                    <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'device-admin-room-modal-{{ $deviceModalItem->id }}')" aria-label="Aizvērt">
                        <x-icon name="x-mark" size="h-5 w-5" />
                    </button>
                </div>
                <form method="POST" action="{{ route('devices.quick-update', $deviceModalItem) }}" class="device-user-room-modal-form">
                    @csrf
                    <input type="hidden" name="action" value="room">
                    <div class="device-user-room-modal-device">
                        <div>
                            <div class="device-user-room-modal-label">Pašreizējā telpa</div>
                            <div class="device-user-room-modal-value">
                                {{ $deviceModalItem->room ? ($deviceModalItem->room->room_number . ($deviceModalItem->room->room_name ? ' - ' . $deviceModalItem->room->room_name : '')) : 'Nav norādīta' }}
                            </div>
                        </div>
                        <div>
                            <div class="device-user-room-modal-label">Ēka</div>
                            <div class="device-user-room-modal-value">{{ $deviceModalItem->building?->building_name ?: 'Nav norādīta' }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <span class="device-user-room-modal-label">Jaunā telpa</span>
                        <x-searchable-select
                            name="target_room_id"
                            query-name="target_room_query"
                            identifier="device-admin-room-quick-select-{{ $deviceModalItem->id }}"
                            :options="$quickRoomSelectOptions"
                            :selected="(string) $deviceModalItem->room_id"
                            :query="$quickRoomSelectedOption['label'] ?? ''"
                            placeholder="Meklē telpu"
                            empty-message="Neviena telpa neatbilst meklējumam."
                        />
                    </div>
                    <div class="device-user-room-modal-actions">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'device-admin-room-modal-{{ $deviceModalItem->id }}')">Atcelt</button>
                        <button type="submit" class="btn-search">
                            <x-icon name="save" size="h-4 w-4" />
                            <span>Saglabāt</span>
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>

        <x-modal :name="'device-admin-assignee-modal-' . $deviceModalItem->id" maxWidth="2xl">
            <div class="device-user-room-modal-shell">
                <div class="device-user-room-modal-head">
                    <div>
                        <div class="device-user-room-modal-badge">Atbildīgā maiņa</div>
                        <h2 class="device-user-room-modal-title">{{ ($deviceModalItem->code ?: 'Bez koda') . ' | ' . $deviceModalItem->name }}</h2>
                        <p class="device-user-room-modal-copy">Izvēlies personu, kurai piešķirt ierīci.</p>
                    </div>
                    <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'device-admin-assignee-modal-{{ $deviceModalItem->id }}')" aria-label="Aizvērt">
                        <x-icon name="x-mark" size="h-5 w-5" />
                    </button>
                </div>
                <form method="POST" action="{{ route('devices.quick-update', $deviceModalItem) }}" class="device-user-room-modal-form">
                    @csrf
                    <input type="hidden" name="action" value="assignee">
                    <div class="device-user-room-modal-device">
                        <div>
                            <div class="device-user-room-modal-label">Pašreizējais atbildīgais</div>
                            <div class="device-user-room-modal-value">{{ $deviceModalItem->assignedTo?->full_name ?: 'Nav norādīts' }}</div>
                        </div>
                        <div>
                            <div class="device-user-room-modal-label">E-pasts</div>
                            <div class="device-user-room-modal-value">{{ $deviceModalItem->assignedTo?->email ?: 'Nav norādīts' }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <span class="device-user-room-modal-label">Jaunais atbildīgais</span>
                        <x-searchable-select
                            name="target_assigned_to_id"
                            query-name="target_assigned_to_query"
                            identifier="device-admin-assignee-quick-select-{{ $deviceModalItem->id }}"
                            :options="$quickAssigneeSelectOptions"
                            :selected="(string) $deviceModalItem->assigned_to_id"
                            :query="$quickAssigneeSelectedOption['label'] ?? ''"
                            placeholder="Meklē atbildīgo personu"
                            empty-message="Neviens lietotājs neatbilst meklējumam."
                        />
                    </div>
                    <div class="device-user-room-modal-actions">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'device-admin-assignee-modal-{{ $deviceModalItem->id }}')">Atcelt</button>
                        <button type="submit" class="btn-search">
                            <x-icon name="save" size="h-4 w-4" />
                            <span>Saglabāt</span>
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endforeach

@endif
</div>
