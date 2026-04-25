{{--
    Partial skats: Dashboard ierīču tabula.
    Izmantots async filtrēšanai bez lapas atjaunošanas.
--}}
@props(['dashboardDevices', 'dashboardDeviceCount', 'dashboardDeviceStates', 'filters', 'sorting', 'sortOptions', 'sortDirectionLabels'])

<div class="device-table-shell mt-5" id="dashboard-devices-table">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
        <div class="text-sm font-semibold text-slate-900">{{ $dashboardDeviceCount }} ierīces</div>
        @if ($filters['floor'] !== '' || $filters['room_id'] !== '')
            <div class="text-xs text-slate-500">
                Atlasītas pēc {{ $filters['room_id'] !== '' ? 'telpas' : 'stāva' }} filtra
            </div>
        @endif
    </div>

    <div class="device-table-scroll rounded-[1.5rem] border border-slate-200 bg-white">
        <table class="dash-table">
            <thead class="dash-table-head">
                <tr>
                    <th class="table-col-image text-center">Attēls</th>
                    @foreach ([
                        'code' => 'Kods',
                        'name' => 'Ierīce',
                        'location' => 'Atrašanās vieta',
                        'assigned_to' => 'Piešķirta',
                        'status' => 'Statuss',
                    ] as $column => $label)
                        @php
                            $isCurrentSort = $sorting['sort'] === $column;
                            $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
                            $sortMessage = 'Dashboard tabula kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                        @endphp
                        <th>
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
                    <th>Darbības</th>
                </tr>
            </thead>
            <tbody class="dash-table-body">
                @forelse ($dashboardDevices as $device)
                    @php
                        $thumbUrl = $device->deviceImageThumbUrl();
                        $manufacturer = trim((string) ($device->manufacturer ?? ''));
                        $model = trim((string) ($device->model ?? ''));
                        $typeName = $device->type?->type_name ?: 'Bez tipa';
                        $brandModel = $model !== ''
                            ? (
                                $manufacturer !== '' && ! \Illuminate\Support\Str::startsWith(
                                    mb_strtolower($model),
                                    mb_strtolower($manufacturer)
                                )
                                    ? trim($manufacturer . ' ' . $model)
                                    : $model
                            )
                            : ($manufacturer !== '' ? $manufacturer : 'Ražotājs un modelis nav norādīts');
                        $roomLabel = collect([
                            $device->room?->room_number,
                            $device->room?->room_name,
                        ])->filter()->implode(' | ');
                        $deviceState = $dashboardDeviceStates[$device->id] ?? [];
                        $repairStatusLabel = $deviceState['repairStatusLabel'] ?? null;
                        $repairPreview = $deviceState['repairPreview'] ?? null;
                        $pendingRequestBadge = $deviceState['pendingRequestBadge'] ?? null;
                        $repairRecord = $device->activeRepair;
                        $hasActiveRepair = (bool) $repairRecord;
                        $displayDeviceStatus = $device->status === \App\Models\Device::STATUS_REPAIR && ! $hasActiveRepair
                            ? \App\Models\Device::STATUS_ACTIVE
                            : $device->status;
                        $repairModalUrl = $repairRecord
                            ? route('repairs.index', [
                                'highlight_id' => 'repair-' . $repairRecord->id,
                            ])
                            : null;
                    @endphp
                    <tr>
                        <td class="table-col-image px-3 py-4 text-center align-middle">
                            @if ($thumbUrl)
                                <img src="{{ $thumbUrl }}" alt="{{ $device->name }}" class="dash-device-thumb mx-auto">
                            @else
                                <div class="dash-device-thumb dash-device-thumb-empty mx-auto">
                                    <x-icon name="device" size="h-4 w-4" />
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="dash-table-cell-strong">{{ $device->code ?: '-' }}</div>
                            <div class="dash-table-subline">{{ $device->serial_number ?: 'Nav sērijas numura' }}</div>
                        </td>
                        <td>
                            <a href="{{ route('devices.show', $device) }}" class="dash-table-link">{{ $device->name }}</a>
                            <div class="dash-table-subline">{{ $typeName }}</div>
                            <div class="dash-table-subline dash-table-subline-wrap">{{ $brandModel }}</div>
                        </td>
                        <td>
                            <div class="dash-table-cell-strong dash-table-nowrap">{{ $device->building?->building_name ?: '-' }}</div>
                            <div class="dash-table-subline">{{ $roomLabel !== '' ? $roomLabel : 'Telpa nav norādīta' }}</div>
                        </td>
                        <td>
                            <x-device-assignment
                                :device="$device"
                                secondary="job_title"
                                primary-class="dash-table-cell-strong"
                                secondary-class="dash-table-subline"
                            />
                        </td>
                        <td>
                            <div class="device-status-stack">
                                @if ($hasActiveRepair && $repairStatusLabel)
                                    <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                        @if ($repairModalUrl)
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
                                            <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.right class="device-request-popover device-request-popover-end">
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
                                        <a href="{{ $pendingRequestBadge['url'] }}" class="device-request-badge-link {{ $pendingRequestBadge['class'] }}" @focus="open = true" @blur="open = false">
                                            <span class="device-status-split-main">
                                                <x-icon :name="$pendingRequestBadge['icon']" size="h-3.5 w-3.5" />
                                                <span>{{ $pendingRequestBadge['short_label'] ?? $pendingRequestBadge['label'] }}</span>
                                            </span>
                                            @if (! empty($pendingRequestBadge['detail_label']))
                                                <span class="device-status-split-sub">{{ $pendingRequestBadge['detail_label'] }}</span>
                                            @endif
                                        </a>

                                        @if (! empty($pendingRequestBadge['preview']))
                                            <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.right class="device-request-popover device-request-popover-end">
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
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('devices.show', $device) }}" class="btn-view dash-table-action-btn">
                                <x-icon name="view" size="h-4 w-4" />
                                <span>Skatīt</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Ierīces pagaidām nav pieejamas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
