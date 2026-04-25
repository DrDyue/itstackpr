{{--
    Partial skats: Norakstīšanas pieteikumu tabula.
    Izmantots async filtrēšanai bez lapas atjaunošanas.
--}}
@props(['requests', 'canReview', 'sorting', 'sortOptions', 'statusLabels', 'sortDirectionLabels'])

<x-ui.table-shell
    id="writeoff-requests-table-root"
    shell-class="app-table-shell"
    scroll-class="app-table-scroll table-scroll-overlay-frame rounded-[1.75rem] border border-slate-200 bg-white shadow-sm"
    table-class="app-table-content app-table-content-wide min-w-full text-sm"
>
            <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="table-col-image px-4 py-3 text-center">Attēls</th>
                    @foreach ([
                        'code' => 'Kods',
                        'name' => 'Nosaukums',
                        'requester' => 'Pieteicējs',
                        'reason' => 'Iemesls',
                        'created_at' => 'Iesniegts',
                        'status' => 'Statuss',
                    ] as $column => $label)
                        @php
                            $headerWidthClass = match ($column) {
                                'code' => 'table-col-code',
                                'name' => 'table-col-name',
                                'requester' => 'table-col-person',
                                'reason' => 'table-col-note',
                                'created_at' => 'table-col-date',
                                'status' => 'table-col-status',
                                default => '',
                            };
                        @endphp
                        <th class="{{ $headerWidthClass }} px-4 py-3">
                            @if (in_array($column, ['code', 'name', 'requester', 'created_at', 'status'], true))
                                @php
                                    $isCurrentSort = $sorting['sort'] === $column;
                                    $defaultDirection = $column === 'created_at' ? 'desc' : 'asc';
                                    $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                    $sortMessage = 'Tabula "Norakstīšanas pieteikumi" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                                @endphp
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
                            @else
                                <span class="font-semibold text-slate-500">{{ $label }}</span>
                            @endif
                        </th>
                    @endforeach
                    <th class="table-col-actions px-4 py-3 text-right">Darbības</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $writeoffRequest)
                    @php
                        $device = $writeoffRequest->device;
                        $isPendingAction = $writeoffRequest->status === 'submitted';
                        $thumbUrl = $device?->deviceImageThumbUrl();
                        $editRequestUrl = route('writeoff-requests.index', array_merge(request()->except(['page', 'writeoff_request_modal', 'modal_request']), [
                            'writeoff_request_modal' => 'edit',
                            'modal_request' => $writeoffRequest->id,
                        ]));
                        $deviceFilterUrl = $device
                            ? route('devices.index', array_filter([
                                'code' => $device->code,
                                'q' => $device->code ? null : $device->name,
                                'highlight' => $device->code ?: $device->name,
                                'highlight_mode' => $device->code ? 'exact' : 'contains',
                                'highlight_id' => 'device-' . $device->id,
                            ]))
                            : null;
                        $deviceMeta = collect([$device?->manufacturer, $device?->model])->filter()->implode(' | ');
                        $reason = trim((string) $writeoffRequest->reason);
                        $shortReason = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $reason), 70);
                    @endphp
                    <tr id="writeoff-request-{{ $writeoffRequest->id }}" class="request-notification-target app-table-row align-top {{ $isPendingAction ? 'app-table-row-pending' : '' }}" data-table-row-id="writeoff-request-{{ $writeoffRequest->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) ($device?->code ?? ''))) }}" data-table-search-highlight-style="{{ $isPendingAction ? 'outline' : 'background' }}">
                        <td class="table-col-image px-4 py-4 text-center align-middle">
                            @if ($thumbUrl)
                                <img
                                    src="{{ $thumbUrl }}"
                                    alt="{{ $device?->name ?: 'Ierīce' }}"
                                    class="request-device-thumb mx-auto"
                                    loading="lazy"
                                    decoding="async"
                                    fetchpriority="low"
                                >
                            @else
                                <div class="request-device-thumb request-device-thumb-placeholder mx-auto">
                                    <x-icon name="device" size="h-4 w-4" />
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-900">{{ $device?->code ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">Sērija: {{ $device?->serial_number ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-900">{{ $device?->name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $deviceMeta !== '' ? $deviceMeta : 'Ražotājs un modelis nav norādīti' }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $device?->type?->type_name ?: 'Tips nav norādīts' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-900">{{ $writeoffRequest->responsibleUser?->full_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $writeoffRequest->responsibleUser?->job_title ?: ($writeoffRequest->responsibleUser?->email ?: 'Darbinieks') }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="relative" x-data="{ open: false }">
                                <button
                                    type="button"
                                    class="max-w-[22rem] truncate text-left text-sm text-slate-700 hover:text-slate-900"
                                    @mouseenter="open = true"
                                    @mouseleave="open = false"
                                    @focus="open = true"
                                    @blur="open = false"
                                >
                                    {{ $shortReason !== '' ? $shortReason : '-' }}
                                </button>

                                @if ($reason !== '')
                                    <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                        <div class="device-request-popover-head">
                                            <span class="device-request-popover-title">Pilns norakstīšanas iemesls</span>
                                            <span class="device-request-popover-date">{{ $writeoffRequest->created_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                        </div>
                                        <div class="device-request-popover-copy">{{ $reason }}</div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-900">{{ $writeoffRequest->created_at?->format('d.m.Y') ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $writeoffRequest->created_at?->format('H:i') ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <x-status-pill context="request" :value="$writeoffRequest->status" :label="$statusLabels[$writeoffRequest->status] ?? null" />
                        </td>
                        <td class="px-4 py-4 text-right">
                            @if (in_array($writeoffRequest->status, ['approved', 'rejected'], true) && $deviceFilterUrl)
                                {{-- Apstiprinātiem/noraidītiem pieteikumiem - tikai viena poga --}}
                                <a href="{{ $deviceFilterUrl }}" class="table-action-summary table-action-summary-single">
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Saistītā ierīce</span>
                                </a>
                            @else
                                {{-- Pārējiem statusiem - dropdown ar darbībām --}}
                                <div class="table-action-menu inline-block" x-data="createFloatingDropdown({ zIndex: 400 })" @keydown.escape.window="closePanel()">
                                    <button type="button" class="table-action-summary" x-ref="trigger" @click="togglePanel()" :aria-expanded="open.toString()">
                                        <span>Darbības</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <template x-teleport="body">
                                    <div class="table-action-list table-action-list-dropdown" data-floating-menu="manual" x-ref="panel" x-cloak x-show="open" x-transition.origin.top.right x-bind:style="panelStyle" @click.outside="closePanel()">
                                        @if ($deviceFilterUrl)
                                            <a href="{{ $deviceFilterUrl }}" class="table-action-item table-action-item-sky table-action-item-wide text-sky-700" @click="closePanel()">
                                                <x-icon name="view" size="h-4 w-4" />
                                                <span>Skatīt saistīto ierīci</span>
                                            </a>
                                        @endif

                                        @if ($canReview && $writeoffRequest->status === 'submitted')
                                            <div class="table-action-grid-actions">
                                                <x-post-action-button
                                                    :action="route('writeoff-requests.review', $writeoffRequest)"
                                                    button-class="table-action-item table-action-item-rose"
                                                    data-app-confirm-title="Noraidīt pieteikumu?"
                                                    data-app-confirm-message="Vai tiešām noraidīt šo norakstīšanas pieteikumu?"
                                                    data-app-confirm-accept="Jā, noraidīt"
                                                    data-app-confirm-cancel="Nē"
                                                    data-app-confirm-tone="danger"
                                                >
                                                    <x-slot:fields>
                                                        <input type="hidden" name="status" value="rejected">
                                                    </x-slot:fields>
                                                    <x-icon name="x-circle" size="h-4 w-4" />
                                                    <span>Noraidīt</span>
                                                </x-post-action-button>

                                                <x-post-action-button
                                                    :action="route('writeoff-requests.review', $writeoffRequest)"
                                                    button-class="table-action-item table-action-item-emerald"
                                                    data-app-confirm-title="Apstiprināt pieteikumu?"
                                                    data-app-confirm-message="Vai tiešām apstiprināt šo norakstīšanas pieteikumu?"
                                                    data-app-confirm-accept="Jā, apstiprināt"
                                                    data-app-confirm-cancel="Nē"
                                                    data-app-confirm-tone="warning"
                                                >
                                                    <x-slot:fields>
                                                        <input type="hidden" name="status" value="approved">
                                                    </x-slot:fields>
                                                    <x-icon name="check-circle" size="h-4 w-4" />
                                                    <span>Apstiprināt</span>
                                                </x-post-action-button>
                                            </div>
                                        @elseif (! $canReview && $writeoffRequest->status === 'submitted')
                                            <a href="{{ $editRequestUrl }}" class="table-action-item table-action-item-amber" data-async-link="true" @click="closePanel()">
                                                <x-icon name="edit" size="h-4 w-4" />
                                                <span>Labot pieteikumu</span>
                                            </a>

                                            <x-post-action-button
                                                :action="route('my-requests.destroy', ['requestType' => 'writeoff', 'requestId' => $writeoffRequest->id])"
                                                method="DELETE"
                                                button-class="table-action-item table-action-item-rose"
                                                :button-attributes="['@click' => 'closePanel()']"
                                                data-app-confirm-title="Atcelt pieteikumu?"
                                                data-app-confirm-message="Vai tiešām atcelt šo norakstīšanas pieteikumu?"
                                                data-app-confirm-accept="Jā, atcelt"
                                                data-app-confirm-cancel="Nē"
                                                data-app-confirm-tone="danger"
                                            >
                                                <x-icon name="x-mark" size="h-4 w-4" />
                                                <span>Atcelt pieteikumu</span>
                                            </x-post-action-button>
                                        @endif
                                    </div>
                                    </template>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6">
                            <x-empty-state
                                compact
                                icon="writeoff"
                                title="Norakstīšanas pieteikumi netika atrasti"
                                description="Maini filtrus vai meklēšanas nosacījumus, lai atrastu vajadzīgo pieteikumu."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table-shell>
