{{--
    Partial skats: Dashboard ierīču tabula.
    Izmantots async filtrēšanai bez lapas atjaunošanas.
--}}
@props(['dashboardDevices', 'dashboardDeviceStates', 'filters'])

<div class="device-table-shell mt-5" id="dashboard-devices-table">
    <div class="device-table-scroll rounded-[1.5rem] border border-slate-200 bg-white">
    <table class="dash-table">
        <thead class="dash-table-head">
            <tr>
                <th>Kods</th>
                <th>Ierīce</th>
                <th>Atrašanās vieta</th>
                <th>Piešķirta</th>
                <th>Statuss</th>
                <th>Darbības</th>
            </tr>
        </thead>
        <tbody class="dash-table-body">
            @forelse ($dashboardDevices as $device)
                @php
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
                    $assignedJobTitle = $device->assignedTo?->job_title ?: 'Nav amata';
                    $roomLabel = collect([
                        $device->room?->room_number,
                        $device->room?->room_name,
                    ])->filter()->implode(' | ');
                    $deviceState = $dashboardDeviceStates[$device->id] ?? [];
                    $repairStatusLabel = $deviceState['repairStatusLabel'] ?? null;
                    $repairPreview = $deviceState['repairPreview'] ?? null;
                    $pendingRequestBadge = $deviceState['pendingRequestBadge'] ?? null;
                @endphp
                <tr>
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
                        <div class="dash-table-cell-strong">{{ $device->assignedTo?->full_name ?: 'Nav piešķirts' }}</div>
                        <div class="dash-table-subline">{{ $assignedJobTitle }}</div>
                    </td>
                    <td>
                        <div class="device-status-stack">
                            @if ($device->status === \App\Models\Device::STATUS_REPAIR && $repairStatusLabel)
                                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                    <div class="device-status-split-chip device-status-split-chip-repair" @focusin="open = true" @focusout="open = false" tabindex="0">
                                        <span class="device-status-split-main">
                                            <x-icon name="repair" size="h-3.5 w-3.5" />
                                            <span>Remonts</span>
                                        </span>
                                        <span class="device-status-split-sub">{{ $repairStatusLabel }}</span>
                                    </div>

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
                                <x-status-pill context="device" :value="$device->status" />
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
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ierīces pagaidam nav pieejamas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if ($dashboardDevices->hasPages())
        @php
            $currentPage = $dashboardDevices->currentPage();
            $lastPage = $dashboardDevices->lastPage();
            $startPage = max(1, $currentPage - 2);
            $endPage = min($lastPage, $currentPage + 2);
        @endphp
        <div class="dashboard-pagination">
            <div class="dashboard-pagination-meta">
                <span>Kopa {{ $dashboardDevices->total() }} ierīces</span>
                <span>Lapa {{ $currentPage }} no {{ $lastPage }}</span>
            </div>

            <div class="dashboard-pagination-links">
                @if ($dashboardDevices->onFirstPage())
                    <span class="dashboard-pagination-btn dashboard-pagination-btn-disabled">Iepriekšējā</span>
                @else
                    <a href="{{ $dashboardDevices->previousPageUrl() }}" class="dashboard-pagination-btn" data-async-link="true">Iepriekšējā</a>
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    @if ($page === $currentPage)
                        <span class="dashboard-pagination-btn dashboard-pagination-btn-active">{{ $page }}</span>
                    @else
                        <a href="{{ $dashboardDevices->url($page) }}" class="dashboard-pagination-btn" data-async-link="true">{{ $page }}</a>
                    @endif
                @endfor

                @if ($dashboardDevices->hasMorePages())
                    <a href="{{ $dashboardDevices->nextPageUrl() }}" class="dashboard-pagination-btn" data-async-link="true">Nākamā</a>
                @else
                    <span class="dashboard-pagination-btn dashboard-pagination-btn-disabled">Nākamā</span>
                @endif
            </div>
        </div>
    @endif
</div>
