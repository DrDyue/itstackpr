@props([
    'devices',
    'deviceStates',
    'sorting',
    'sortOptions',
    'statusLabels',
    'canManageDevices',
    'quickRoomSelectOptions',
    'userRoomOptions' => collect(),
    'quickAssigneeSelectOptions',
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
    $visibleColumns = $canManageDevices
        ? [
            'code' => 'Kods',
            'name' => 'Nosaukums',
            'location' => 'Atrašanās vieta',
            'created_at' => 'Izveidots',
            'assigned_to' => 'Piešķirta',
            'status' => 'Statuss',
        ]
        : [
            'code' => 'Kods',
            'name' => 'Nosaukums',
            'location' => 'Atrašanās vieta',
            'status' => 'Statuss',
        ];
    $emptyColspan = count($visibleColumns) + 2;
@endphp

<x-ui.table-shell
    id="devices-table-root"
    shell-class="device-table-shell device-table-shell-wide"
    scroll-class="device-table-scroll device-table-scroll-balanced rounded-[1.75rem] border border-slate-200 bg-white shadow-sm"
    table-class="device-table-content min-w-full text-sm"
>
    <thead class="device-table-head bg-slate-50 text-left text-slate-500">
        <tr>
            <th class="table-col-image px-3 py-3 text-center">Attēls</th>
            @foreach ($visibleColumns as $column => $label)
                @php
                    $isCurrentSort = $sorting['sort'] === $column;
                    $headerWidthClass = match ($column) {
                        'code' => 'table-col-code',
                        'name' => 'table-col-name',
                        'location' => 'table-col-location',
                        'created_at' => 'table-col-date',
                        'assigned_to' => 'table-col-person',
                        'status' => 'table-col-status',
                        default => '',
                    };
                    $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
                    $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
                    $sortMessage = 'Kārtots pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
            <th class="table-col-actions px-4 py-3 w-[12rem]">Darbības</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($devices as $device)
            @php
                $thumbUrl = $device->deviceImageThumbUrl();
                $nameMeta = collect([$device->manufacturer, $device->model])->filter(fn ($value) => filled($value))->implode(' | ');
                $quickRoomLabel = $device->room
                    ? ($device->room->room_number . ($device->room->room_name ? ' - ' . $device->room->room_name : ''))
                    : null;
                $quickAssigneeLabel = $device->assignedTo?->full_name;
                $deviceState = $deviceStates[$device->id] ?? [];
                $requestAvailability = $deviceState['requestAvailability'] ?? [
                    'repair' => false,
                    'writeoff' => false,
                    'transfer' => false,
                    'can_create_any' => false,
                    'reason' => null,
                ];
                $roomUpdateAvailability = $deviceState['roomUpdateAvailability'] ?? [
                    'allowed' => false,
                    'reason' => 'Telpas maiņa šobrīd nav pieejama.',
                ];
                $pendingRequestBadge = $deviceState['pendingRequestBadge'] ?? null;
                $repairStatusLabel = $deviceState['repairStatusLabel'] ?? null;
                $repairPreview = $deviceState['repairPreview'] ?? null;
                $repairRecord = $device->activeRepair ?? $device->latestRepair;
                $repairModalUrl = $repairRecord
                    ? route('repairs.index', ['repair_modal' => 'edit', 'modal_repair' => $repairRecord->id])
                    : null;
                $repairCreateUrl = route('repairs.index', ['repair_modal' => 'create', 'device_id' => $device->id]);
                $repairRequestCreateUrl = route('repair-requests.index', ['repair_request_modal' => 'create', 'device_id' => $device->id]);
                $writeoffRequestCreateUrl = route('writeoff-requests.index', ['writeoff_request_modal' => 'create', 'device_id' => $device->id]);
                $transferCreateUrl = route('device-transfers.index', ['device_transfer_modal' => 'create', 'device_id' => $device->id]);
                $roomModalName = 'device-user-room-modal-' . $device->id;
                $roomModalFormKey = 'device_user_room_' . $device->id;
            @endphp
            <tr class="device-table-row border-t border-slate-100 align-top" data-table-row-id="device-{{ $device->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) $device->code)) }}">
                <td class="table-col-image px-3 py-4 text-center align-middle tabular-nums">
                    @if ($thumbUrl)
                        <img src="{{ $thumbUrl }}" alt="{{ $device->name }}" class="device-table-thumb">
                    @else
                        <div class="device-table-thumb device-table-thumb-placeholder">
                            <x-icon name="device" size="h-4 w-4" />
                        </div>
                    @endif
                </td>
                <td class="px-4 py-4 tabular-nums">
                    <div class="font-semibold text-slate-900">{{ $device->code ?: '-' }}</div>
                    @if ($device->serial_number)
                        <div class="mt-1 text-xs text-slate-500">Sērija: {{ $device->serial_number }}</div>
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
                        <div class="font-medium text-slate-900">Vieta nav norādīta</div>
                    @endif
                </td>
                @if ($canManageDevices)
                    <td class="px-4 py-4">
                        <div class="font-medium text-slate-900">{{ $device->created_at?->format('d.m.Y') ?: '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $device->created_at?->format('H:i') ?: '-' }}</div>
                        <div class="mt-2 text-xs text-slate-400">{{ $device->createdBy?->full_name ?: 'Sistēma' }}</div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="font-medium text-slate-900">{{ $device->assignedTo?->full_name ?: 'Nav piešķirts' }}</div>
                        @if ($device->assignedTo?->job_title)
                            <div class="mt-1 text-xs text-slate-500">{{ $device->assignedTo->job_title }}</div>
                        @endif
                    </td>
                @endif
                <td class="px-4 py-4">
                    <div class="device-status-stack">
                        @if ($device->status === \App\Models\Device::STATUS_REPAIR && $repairStatusLabel)
                            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                @if ($canManageDevices && $repairModalUrl)
                                    <a href="{{ $repairModalUrl }}" class="device-status-split-chip device-status-split-chip-repair" @focusin="open = true" @focusout="open = false">
                                        <span class="device-status-split-main">
                                            <x-icon name="repair" size="h-3.5 w-3.5" />
                                            <span>Remonts</span>
                                        </span>
                                        <span class="device-status-split-sub">{{ $repairStatusLabel }}</span>
                                    </a>
                                @else
                                    <div class="device-status-split-chip device-status-split-chip-repair" @focusin="open = true" @focusout="open = false" tabindex="0">
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
                            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                @if (! empty($pendingRequestBadge['url']))
                                    <a href="{{ $pendingRequestBadge['url'] }}" class="device-request-badge-link {{ $pendingRequestBadge['class'] }}" @focus="open = true" @blur="open = false">
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
                            <x-status-pill context="device" :value="$device->status" />
                        @endif
                    </div>
                </td>
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
                                    x-data
                                    @click="$dispatch('open-modal', '{{ $roomModalName }}')"
                                >
                                    <x-icon name="room" size="h-4 w-4" />
                                    <span>Mainīt telpu</span>
                                </button>
                            @else
                                <button
                                    type="button"
                                    class="btn-disabled"
                                    data-app-toast-title="Telpas maiņa nav pieejama"
                                    data-app-toast-message="{{ $roomUpdateAvailability['reason'] ?? 'Telpas maiņa šobrīd nav pieejama.' }}"
                                    data-app-toast-tone="info"
                                >
                                    <x-icon name="room" size="h-4 w-4" />
                                    <span>Mainīt telpu</span>
                                </button>
                            @endif

                            @if ($requestAvailability['can_create_any'])
                                <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                    <button type="button" class="table-action-button table-action-button-emerald" @click="open = ! open" :aria-expanded="open.toString()">
                                        <x-icon name="repair-request" size="h-4 w-4" />
                                        <span>Pieteikumi</span>
                                        <svg class="h-4 w-4 text-current/80" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div
                                        class="table-action-list"
                                        x-cloak
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                        @click.outside="open = false"
                                    >
                                        <div class="table-action-section">
                                            <div class="table-action-section-title">Pieteikumi</div>
                                            <div class="table-action-stack">
                                                @if ($requestAvailability['repair'])
                                                    <a href="{{ $repairRequestCreateUrl }}" class="table-action-item table-action-item-sky" @click="open = false">
                                                        <x-icon name="repair" size="h-4 w-4" />
                                                        <span>Remonts</span>
                                                    </a>
                                                @endif
                                                @if ($requestAvailability['writeoff'])
                                                    <a href="{{ $writeoffRequestCreateUrl }}" class="table-action-item table-action-item-rose" @click="open = false">
                                                        <x-icon name="writeoff" size="h-4 w-4" />
                                                        <span>Norakstīšana</span>
                                                    </a>
                                                @endif
                                                @if ($requestAvailability['transfer'])
                                                    <a href="{{ $transferCreateUrl }}" class="table-action-item table-action-item-emerald" @click="open = false">
                                                        <x-icon name="transfer" size="h-4 w-4" />
                                                        <span>Nodot</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <button
                                    type="button"
                                    class="btn-disabled"
                                    data-app-toast-title="{{ $pendingRequestBadge['label'] ?? 'Pieteikumi nav pieejami' }}"
                                    data-app-toast-message="{{ $requestAvailability['reason'] ?? 'Pieteikumus šobrīd nevar izveidot.' }}"
                                    data-app-toast-tone="info"
                                >
                                    <x-icon :name="$pendingRequestBadge['icon'] ?? 'repair-request'" size="h-4 w-4" />
                                    <span>Pieteikumi</span>
                                </button>
                            @endif

                            @if (false)
                            @if ($requestAvailability['can_create_any'])
                                <a href="{{ $repairRequestCreateUrl }}" class="table-action-button table-action-button-sky">
                                    <x-icon name="repair" size="h-4 w-4" />
                                    <span>Remonts</span>
                                </a>
                                <a href="{{ $writeoffRequestCreateUrl }}" class="table-action-button table-action-button-rose">
                                    <x-icon name="writeoff" size="h-4 w-4" />
                                    <span>Norakstīt</span>
                                </a>
                                <a href="{{ $transferCreateUrl }}" class="table-action-button table-action-button-emerald">
                                    <x-icon name="transfer" size="h-4 w-4" />
                                    <span>Nodot</span>
                                </a>
                            @elseif (! empty($pendingRequestBadge['url']))
                                <a href="{{ $pendingRequestBadge['url'] }}" class="table-action-button table-action-button-sky">
                                    <x-icon :name="$pendingRequestBadge['icon'] ?? 'view'" size="h-4 w-4" />
                                    <span>Skatīt pieteikumu</span>
                                </a>
                            @elseif ($requestAvailability['reason'])
                                <button
                                    type="button"
                                    class="btn-disabled"
                                    data-app-toast-title="{{ $pendingRequestBadge['label'] ?? 'Pieteikums nav pieejams' }}"
                                    data-app-toast-message="{{ $requestAvailability['reason'] }}"
                                    data-app-toast-tone="info"
                                >
                                    <x-icon :name="$pendingRequestBadge['icon'] ?? 'clock'" size="h-4 w-4" />
                                    <span>Nav pieejams</span>
                                </button>
                            @endif
                            @endif
                        </div>
                    @else
                        <div class="table-action-menu" x-data="{ open: false, panel: null }" @keydown.escape.window="open = false; panel = null">
                            <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                <span>Darbības</span>
                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div
                                class="table-action-list"
                                :class="panel ? 'table-action-list-wide' : ''"
                                x-cloak
                                x-show="open"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                @click.outside="open = false; panel = null"
                            >
                                <div class="table-action-section">
                                    <div class="table-action-section-title">Pārskats</div>
                                    <div class="table-action-stack">
                                        <a href="{{ route('devices.show', $device) }}" class="table-action-item table-action-item-primary" @click="open = false; panel = null">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatīt</span>
                                        </a>
                                        @if ($repairModalUrl)
                                            <a href="{{ $repairModalUrl }}" class="table-action-item table-action-item-sky" @click="open = false; panel = null">
                                                <x-icon name="repair" size="h-4 w-4" />
                                                <span>Atvērt remontu</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="table-action-divider"></div>
                                <div class="table-action-section">
                                    <div class="table-action-section-title">Pārvaldība</div>
                                    <div class="table-action-stack">
                                        <button
                                            type="button"
                                            class="table-action-item table-action-item-amber"
                                            @click="open = false; panel = null; $dispatch('open-modal', 'device-edit-modal-{{ $device->id }}')"
                                        >
                                            <x-icon name="edit" size="h-4 w-4" />
                                            <span>Rediģēt</span>
                                        </button>

                                        @if ($device->status === 'active')
                                            <button type="button" class="table-action-item table-action-item-sky" @click="panel = panel === 'room' ? null : 'room'">
                                                <x-icon name="room" size="h-4 w-4" />
                                                <span>Mainīt telpu</span>
                                            </button>

                                            <button type="button" class="table-action-item table-action-item-violet" @click="panel = panel === 'assignee' ? null : 'assignee'">
                                                <x-icon name="user" size="h-4 w-4" />
                                                <span>Mainīt atbildīgo</span>
                                            </button>

                                            <form
                                                method="POST"
                                                action="{{ route('devices.quick-update', $device) }}"
                                                data-app-confirm-title="Norakstīt ierīci?"
                                                data-app-confirm-message="Vai tiešām norakstīt šo ierīci? Pēc norakstīšanas tā vairs nebūs piešķirta lietotājam vai telpai."
                                                data-app-confirm-accept="Jā, norakstīt"
                                                data-app-confirm-cancel="Nē"
                                                data-app-confirm-tone="danger"
                                            >
                                                @csrf
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="target_status" value="writeoff">
                                                <button type="submit" class="table-action-button table-action-button-rose" formmethod="POST">
                                                    <x-icon name="writeoff" size="h-4 w-4" />
                                                    <span>Norakstīt</span>
                                                </button>
                                            </form>

                                            <a href="{{ $repairCreateUrl }}" class="table-action-button table-action-button-amber">
                                                <x-icon name="repair" size="h-4 w-4" />
                                                <span>Atvērt remonta formu</span>
                                            </a>
                                        @endif
                                    </div>

                                    <div class="table-action-inline-panel" x-cloak x-show="panel === 'room'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95">
                                        <div class="table-action-inline-head">
                                            <div>
                                                <div class="table-action-inline-title">Mainīt telpu</div>
                                                <div class="table-action-inline-copy">Ierīce tiks uzreiz pārvietota uz citu telpu.</div>
                                            </div>
                                            <button type="button" class="table-action-inline-close" @click="panel = null">
                                                <x-icon name="x-mark" size="h-4 w-4" />
                                            </button>
                                        </div>

                                        <form method="POST" action="{{ route('devices.quick-update', $device) }}" class="space-y-3">
                                            @csrf
                                            <input type="hidden" name="action" value="room">
                                            <x-searchable-select
                                                name="target_room_id"
                                                query-name="target_room_query"
                                                identifier="device-quick-room-{{ $device->id }}"
                                                :options="$quickRoomSelectOptions"
                                                :selected="(string) ($device->room_id ?? '')"
                                                :query="$quickRoomLabel"
                                                placeholder="Izvēlies telpu"
                                                empty-message="Neviena telpa neatbilst meklējumam."
                                            />
                                            <div class="table-action-inline-actions">
                                                <button type="button" class="btn-clear" @click="panel = null">Atcelt</button>
                                                <button type="submit" class="btn-search">
                                                    <x-icon name="save" size="h-4 w-4" />
                                                    <span>Saglabāt</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="table-action-inline-panel" x-cloak x-show="panel === 'assignee'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95">
                                        <div class="table-action-inline-head">
                                            <div>
                                                <div class="table-action-inline-title">Mainīt atbildīgo</div>
                                                <div class="table-action-inline-copy">Izvēlies citu personu, kurai piešķirt ierīci.</div>
                                            </div>
                                            <button type="button" class="table-action-inline-close" @click="panel = null">
                                                <x-icon name="x-mark" size="h-4 w-4" />
                                            </button>
                                        </div>

                                        <form method="POST" action="{{ route('devices.quick-update', $device) }}" class="space-y-3">
                                            @csrf
                                            <input type="hidden" name="action" value="assignee">
                                            <x-searchable-select
                                                name="target_assigned_to_id"
                                                query-name="target_assigned_to_query"
                                                identifier="device-quick-assignee-{{ $device->id }}"
                                                :options="$quickAssigneeSelectOptions"
                                                :selected="(string) ($device->assigned_to_id ?? '')"
                                                :query="$quickAssigneeLabel"
                                                placeholder="Izvēlies atbildīgo personu"
                                                empty-message="Neviena persona neatbilst meklējumam."
                                            />
                                            <div class="table-action-inline-actions">
                                                <button type="button" class="btn-clear" @click="panel = null">Atcelt</button>
                                                <button type="submit" class="btn-search">
                                                    <x-icon name="save" size="h-4 w-4" />
                                                    <span>Saglabāt</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $emptyColspan }}" class="px-4 py-6">
                    <x-empty-state
                        compact
                        icon="devices"
                        title="Ierīces nav atrastas."
                        description="Pamēģini noņemt daļu filtru vai mainīt meklēšanas nosacījumus."
                    />
                </td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table-shell>

@if ($devices->hasPages())
    <div class="mt-5">{{ $devices->links() }}</div>
@endif

@if (! $canManageDevices)
    @foreach ($devices as $device)
        @php
            $roomModalName = 'device-user-room-modal-' . $device->id;
            $roomModalFormKey = 'device_user_room_' . $device->id;
            $roomQuery = $device->room
                ? ($device->room->room_number . ($device->room->room_name ? ' - ' . $device->room->room_name : ''))
                : '';
        @endphp
        <x-modal :name="$roomModalName" maxWidth="2xl">
            <div class="device-user-room-modal-shell">
                <div class="device-user-room-modal-head">
                    <div>
                        <div class="device-user-room-modal-badge">Telpas maiņa</div>
                        <h2 class="device-user-room-modal-title">{{ $device->name }}</h2>
                        <p class="device-user-room-modal-copy">Izvēlies jauno telpu šai ierīcei. Ēka tiks pielāgota automātiski pēc izvēlētās telpas.</p>
                    </div>
                    <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', '{{ $roomModalName }}')" aria-label="Aizvērt">
                        <x-icon name="x-mark" size="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('devices.user-room.update', $device) }}" class="device-user-room-modal-form">
                    @csrf
                    <input type="hidden" name="modal_form" value="{{ $roomModalFormKey }}">

                    <div class="device-user-room-modal-device">
                        <div>
                            <div class="device-user-room-modal-label">Ierīce</div>
                            <div class="device-user-room-modal-value">{{ $device->code ?: 'Bez koda' }} | {{ $device->name }}</div>
                        </div>
                        <div>
                            <div class="device-user-room-modal-label">Pašreizējā telpa</div>
                            <div class="device-user-room-modal-value">{{ $roomQuery !== '' ? $roomQuery : 'Nav norādīta' }}</div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="device-user-room-modal-label" for="device-user-room-{{ $device->id }}-input">Jaunā telpa</label>
                        <x-searchable-select
                            name="room_id"
                            queryName="room_query"
                            :options="$userRoomOptions"
                            :selected="old('modal_form') === $roomModalFormKey ? old('room_id', (string) $device->room_id) : (string) $device->room_id"
                            :query="old('modal_form') === $roomModalFormKey ? old('room_query', $roomQuery) : $roomQuery"
                            identifier="device-user-room-{{ $device->id }}"
                            :prioritize-selected="true"
                            selected-group-label="Pašreizējā telpa"
                            placeholder="Izvēlies telpu"
                            emptyMessage="Neviena telpa neatbilst meklējumam."
                            :error="old('modal_form') === $roomModalFormKey ? $errors->first('room_id') : null"
                        />
                        @if (old('modal_form') === $roomModalFormKey && $errors->has('room_id'))
                            <div class="text-sm text-rose-600">{{ $errors->first('room_id') }}</div>
                        @endif
                    </div>

                    <div class="device-user-room-modal-actions">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', '{{ $roomModalName }}')">Atcelt</button>
                        <button type="submit" class="btn-search">
                            <x-icon name="save" size="h-4 w-4" />
                            <span>Saglabāt</span>
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endforeach

    @if (str_starts_with((string) old('modal_form'), 'device_user_room_'))
        @php($userRoomModalTarget = str_replace('device_user_room_', '', (string) old('modal_form')))
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-user-room-modal-{{ $userRoomModalTarget }}' })));</script>
    @endif
@endif

@if ($canManageDevices)
    @foreach ($devices as $device)
        @include('devices.partials.modal-form', [
            'mode' => 'edit',
            'modalName' => 'device-edit-modal-' . $device->id,
            'device' => $device,
        ])
    @endforeach
@endif
