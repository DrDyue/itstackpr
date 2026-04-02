{{--
    Partial skats: Remonta pieteikumu tabula.
    Izmantots async filtrēšanai bez lapas atjaunošanas.
--}}
@props(['requests', 'canReview', 'sorting', 'sortOptions', 'statusLabels', 'sortDirectionLabels'])

<div class="app-table-shell" id="repair-requests-table-root">
    <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <table class="app-table-content app-table-content-wide min-w-full text-sm">
            <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                <tr>
                    @foreach ([
                        'code' => 'Kods',
                        'name' => 'Nosaukums',
                        'requester' => 'Pieteicējs',
                        'description' => 'Problēmas apraksts',
                        'created_at' => 'Iesniegts',
                        'status' => 'Statuss',
                    ] as $column => $label)
                        @php
                            $headerWidthClass = match ($column) {
                                'code' => 'table-col-code',
                                'name' => 'table-col-name',
                                'requester' => 'table-col-person',
                                'description' => 'table-col-note',
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
                                    $sortMessage = 'Tabula "Remonta pieteikumi" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
                @forelse ($requests as $repairRequest)
                    @php
                        $device = $repairRequest->device;
                        $deviceFilterUrl = $device
                            ? route('devices.index', array_filter([
                                'code' => $device->code,
                                'q' => $device->code ? null : $device->name,
                            ]))
                            : null;
                        $deviceMeta = collect([$device?->manufacturer, $device?->model])->filter()->implode(' | ');
                        $description = trim((string) $repairRequest->description);
                        $shortDescription = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $description), 70);
                    @endphp
                    <tr class="app-table-row border-t border-slate-100 align-top" data-table-row-id="repair-request-{{ $repairRequest->id }}" data-table-code="{{ \Illuminate\Support\Str::lower(trim((string) ($device?->code ?? ''))) }}">
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
                            <div class="font-semibold text-slate-900">{{ $repairRequest->responsibleUser?->full_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $repairRequest->responsibleUser?->job_title ?: ($repairRequest->responsibleUser?->email ?: 'Darbinieks') }}</div>
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
                                    {{ $shortDescription !== '' ? $shortDescription : '-' }}
                                </button>

                                @if ($description !== '')
                                    <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left class="device-request-popover">
                                        <div class="device-request-popover-head">
                                            <span class="device-request-popover-title">Pilns problēmas apraksts</span>
                                            <span class="device-request-popover-date">{{ $repairRequest->created_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                        </div>
                                        <div class="device-request-popover-copy">{{ $description }}</div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-900">{{ $repairRequest->created_at?->format('d.m.Y') ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $repairRequest->created_at?->format('H:i') ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <x-status-pill context="request" :value="$repairRequest->status" :label="$statusLabels[$repairRequest->status] ?? null" />
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                    <span>Darbības</span>
                                    <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div class="table-action-list" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                    @if ($deviceFilterUrl)
                                        <a href="{{ $deviceFilterUrl }}" class="table-action-item" @click="open = false">
                                            <x-icon name="view" size="h-4 w-4" />
                                            <span>Skatīt saistīto ierīci</span>
                                        </a>
                                    @endif

                                    @if ($canReview && $repairRequest->status === 'submitted')
                                        <form
                                            method="POST"
                                            action="{{ route('repair-requests.review', $repairRequest) }}"
                                            data-app-confirm-title="Apstiprināt pieteikumu?"
                                            data-app-confirm-message="Vai tiešām apstiprināt šo remonta pieteikumu?"
                                            data-app-confirm-accept="Jā, apstiprināt"
                                            data-app-confirm-cancel="Nē"
                                            data-app-confirm-tone="warning"
                                        >
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="table-action-button table-action-button-amber">
                                                <x-icon name="check-circle" size="h-4 w-4" />
                                                <span>Apstiprināt</span>
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('repair-requests.review', $repairRequest) }}"
                                            data-app-confirm-title="Noraidīt pieteikumu?"
                                            data-app-confirm-message="Vai tiešām noraidīt šo remonta pieteikumu?"
                                            data-app-confirm-accept="Jā, noraidīt"
                                            data-app-confirm-cancel="Nē"
                                            data-app-confirm-tone="danger"
                                        >
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="table-action-button table-action-button-rose">
                                                <x-icon name="x-circle" size="h-4 w-4" />
                                                <span>Noraidīt</span>
                                            </button>
                                        </form>
                                    @elseif (! $canReview && $repairRequest->status === 'submitted')
                                        <a href="{{ route('my-requests.edit', ['requestType' => 'repair', 'requestId' => $repairRequest->id]) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                            <x-icon name="edit" size="h-4 w-4" />
                                            <span>Labot aprakstu</span>
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('my-requests.destroy', ['requestType' => 'repair', 'requestId' => $repairRequest->id]) }}"
                                            data-app-confirm-title="Atcelt pieteikumu?"
                                            data-app-confirm-message="Vai tiešām atcelt šo remonta pieteikumu?"
                                            data-app-confirm-accept="Jā, atcelt"
                                            data-app-confirm-cancel="Nē"
                                            data-app-confirm-tone="danger"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-button table-action-button-rose">
                                                <x-icon name="x-mark" size="h-4 w-4" />
                                                <span>Atcelt pieteikumu</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6">
                            <x-empty-state
                                compact
                                icon="repair-request"
                                title="Remonta pieteikumi netika atrasti"
                                description="Maini filtrus vai meklēšanas nosacījumus, lai atrastu vajadzīgo pieteikumu."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($requests->hasPages())
    <div class="mt-5">{{ $requests->links() }}</div>
@endif
