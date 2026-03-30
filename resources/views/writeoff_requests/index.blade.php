{{--
    Lapa: Norakstīšanas pieteikumu saraksts.
    Atbildība: rāda norakstīšanas pieprasījumus un ļauj administratoram pieņemt gala lēmumu.
    Datu avots: WriteoffRequestController@index.
    Galvenās daļas:
    1. Hero ar statusu statistiku.
    2. Filtri meklēšanai un statusu pārslēgšanai.
    3. Kartīšu saraksts ar pieteikuma pamatojumu un ierīces datiem.
--}}
<x-app-layout>
    @php
        $statusFilterOptions = collect($statuses)->map(fn ($status) => [
            'value' => (string) $status,
            'label' => $statusLabels[$status] ?? $status,
            'icon' => match ($status) {
                'submitted' => 'clock',
                'approved' => 'check-circle',
                'rejected' => 'x-circle',
                default => 'view',
            },
            'activeClasses' => match ($status) {
                'submitted' => 'border-amber-300 bg-amber-50 text-amber-950 shadow-[0_16px_36px_-28px_rgba(245,158,11,0.6)]',
                'approved' => 'border-emerald-300 bg-emerald-50 text-emerald-950 shadow-[0_16px_36px_-28px_rgba(16,185,129,0.6)]',
                'rejected' => 'border-rose-300 bg-rose-50 text-rose-950 shadow-[0_16px_36px_-28px_rgba(244,63,94,0.55)]',
                default => 'border-sky-300 bg-sky-50 text-sky-900',
            },
            'inactiveClasses' => 'border-slate-200 bg-white text-slate-600',
            'activeIconClasses' => match ($status) {
                'submitted' => 'bg-amber-500 text-white',
                'approved' => 'bg-emerald-600 text-white',
                'rejected' => 'bg-rose-600 text-white',
                default => 'bg-sky-600 text-white',
            },
            'inactiveIconClasses' => 'bg-slate-100 text-slate-400',
        ])->values();
    @endphp
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow"><x-icon name="writeoff" size="h-4 w-4" /><span>Norakstīšana</span></div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="writeoff" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $requestSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="clock" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Iesniegti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['submitted'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Apstiprināti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['approved'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="x-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Noraidīti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['rejected'] }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-rose"><x-icon name="writeoff" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Norakstīšanas pieteikumi</h1>
                            <p class="page-subtitle">{{ $canReview ? 'Visi lietotāju norakstīšanas pieteikumi. Admins pieņem gala lēmumu.' : 'Tavi norakstīšanas pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>
                @unless ($canReview)
                    <a href="{{ route('writeoff-requests.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns pieteikums</span></a>
                @endunless
            </div>
        </div>

        {{-- Saraksta filtrēšana pēc teksta un pieprasījuma statusiem. --}}
        <form method="GET" action="{{ route('writeoff-requests.index') }}" class="surface-toolbar grid gap-4 xl:grid-cols-[1.2fr_1fr_auto] xl:items-end">
            <input type="hidden" name="statuses_filter" value="1">
            <label class="block">
                <span class="crud-label">Meklēt</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierīce, kods vai iemesls...">
            </label>
            <div x-data="filterChipGroup({ selected: @js($filters['statuses']), minimum: 1 })">
                <span class="crud-label">Statuss</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($statusFilterOptions as $option)
                        <button
                            type="button"
                            @click="toggle(@js($option['value']))"
                            :class="isSelected(@js($option['value'])) ? @js($option['activeClasses']) : @js($option['inactiveClasses'])"
                            class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition hover:border-slate-300 hover:text-slate-900"
                        >
                            <span
                                :class="isSelected(@js($option['value'])) ? @js($option['activeIconClasses']) : @js($option['inactiveIconClasses'])"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full transition"
                            >
                                <x-icon :name="$option['icon']" size="h-3.5 w-3.5" />
                            </span>
                            <span>{{ $option['label'] }}</span>
                        </button>
                    @endforeach
                    <template x-for="value in selected" :key="'writeoff-status-' + value">
                        <input type="hidden" name="status[]" :value="value">
                    </template>
                </div>
            </div>
            <div class="toolbar-actions xl:justify-end">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklēt</span></button>
                <a href="{{ route('writeoff-requests.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notīrīt</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklēt', 'value' => $filters['q']],
                ['label' => 'Statuss', 'value' => collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')],
            ]"
            :clear-url="route('writeoff-requests.index')"
        />

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif
        @if (! empty($featureMessage))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
        @endif

        <div class="space-y-4">
            @forelse ($requests as $request)
                @php
                    $deviceThumbUrl = $request->device?->deviceImageThumbUrl();
                    $deviceTypeName = $request->device?->type?->type_name ?: 'Ierīce';
                    $deviceMeta = collect([$request->device?->manufacturer, $request->device?->model])
                        ->filter(fn ($value) => filled($value))
                        ->implode(' | ');
                    $deviceRoom = collect([
                        $request->device?->room?->room_number,
                        $request->device?->room?->room_name,
                    ])->filter()->implode(' | ');
                @endphp
                <div id="writeoff-request-{{ $request->id }}" class="surface-card request-notification-target scroll-mt-28">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                                    {{ $deviceTypeName }}
                                </span>
                                <div class="text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $request->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-pill context="request" :value="$request->status" :label="$statusLabels[$request->status] ?? null" />
                            @if ($request->device)
                                <a href="{{ route('devices.show', $request->device) }}" class="btn-view">
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Apskatīt ierīci</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="request-card-grid">
                        <div class="request-info-panel">
                            <div class="request-panel-heading">
                                <span class="request-panel-heading-icon"><x-icon name="writeoff" size="h-4 w-4" /></span>
                                <span>Iesniegums</span>
                            </div>

                            <div class="request-field-grid">
                                <div class="request-field-card">
                                    <div class="request-field-label"><x-icon name="profile" size="h-4 w-4" /><span>Pieteicējs</span></div>
                                    <div class="request-field-value">{{ $request->responsibleUser?->full_name ?: '-' }}</div>
                                </div>
                                <div class="request-field-card">
                                    <div class="request-field-label"><x-icon name="check-circle" size="h-4 w-4" /><span>Statuss</span></div>
                                    <div class="request-field-value">
                                        <x-status-pill context="request" :value="$request->status" :label="$statusLabels[$request->status] ?? null" />
                                    </div>
                                </div>
                                <div class="request-field-card">
                                    <div class="request-field-label"><x-icon name="clock" size="h-4 w-4" /><span>Iesniegts</span></div>
                                    <div class="request-field-value">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                                @if ($request->reviewedBy)
                                    <div class="request-field-card">
                                        <div class="request-field-label"><x-icon name="user" size="h-4 w-4" /><span>Izskatīja</span></div>
                                        <div class="request-field-value">{{ $request->reviewedBy->full_name }}</div>
                                    </div>
                                @endif
                                <div class="request-field-card request-field-card-wide">
                                    <div class="request-field-label"><x-icon name="writeoff" size="h-4 w-4" /><span>Norakstīšanas iemesls</span></div>
                                    <div class="request-description-box">{{ $request->reason }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="request-device-panel">
                            <div class="request-panel-heading">
                                <span class="request-panel-heading-icon"><x-icon name="device" size="h-4 w-4" /></span>
                                <span>Ierīce</span>
                            </div>

                            <div class="request-device-hero mt-4">
                                <div class="min-w-0 flex-1">
                                    <span class="request-device-chip">
                                        <x-icon name="type" size="h-3.5 w-3.5" />
                                        <span>{{ $deviceTypeName }}</span>
                                    </span>
                                    <div class="mt-3 text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                                    <div class="mt-2 text-sm leading-6 text-slate-500">{{ $deviceMeta !== '' ? $deviceMeta : 'Ražotājs un modelis nav norādīti.' }}</div>
                                </div>
                                @if ($deviceThumbUrl)
                                    <img src="{{ $deviceThumbUrl }}" alt="{{ $request->device?->name ?: 'Ierīce' }}" class="request-device-thumb shrink-0">
                                @else
                                    <div class="request-device-thumb request-device-thumb-placeholder shrink-0">
                                        <x-icon name="device" size="h-6 w-6" />
                                    </div>
                                @endif
                            </div>

                            <div class="request-device-meta-grid">
                                <div class="request-field-card">
                                    <div class="request-field-label"><x-icon name="tag" size="h-4 w-4" /><span>Kods</span></div>
                                    <div class="request-field-value">{{ $request->device?->code ?: '-' }}</div>
                                </div>
                                <div class="request-field-card">
                                    <div class="request-field-label"><x-icon name="key" size="h-4 w-4" /><span>Sērijas numurs</span></div>
                                    <div class="request-field-value">{{ $request->device?->serial_number ?: '-' }}</div>
                                </div>
                                <div class="request-field-card md:col-span-2">
                                    <div class="request-field-label"><x-icon name="building" size="h-4 w-4" /><span>Vieta</span></div>
                                    <div class="request-field-value">{{ $deviceRoom !== '' ? $deviceRoom : 'Telpa nav norādīta' }}</div>
                                </div>
                                <div class="request-field-card md:col-span-2">
                                    <div class="request-field-label"><x-icon name="stats" size="h-4 w-4" /><span>Pašreizējāis statuss</span></div>
                                    <div class="request-field-value">
                                        <x-status-pill
                                            context="device"
                                            :value="$request->device?->status ?: 'active'"
                                            :label="$request->device?->status === 'active' ? 'Aktīva' : ($request->device?->status === 'repair' ? 'Remonta' : 'Norakstīta')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (! $canReview && $request->status === 'submitted')
                        <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                            <a href="{{ route('my-requests.edit', ['requestType' => 'writeoff', 'requestId' => $request->id]) }}" class="btn-view">
                                <x-icon name="view" size="h-4 w-4" />
                                <span>Labot iemeslu</span>
                            </a>
                            <form method="POST" action="{{ route('my-requests.destroy', ['requestType' => 'writeoff', 'requestId' => $request->id]) }}" onsubmit="return confirm('Vai tiešām atcelt šo pieteikumu?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">
                                    <x-icon name="x-mark" size="h-4 w-4" />
                                    <span>Atcelt pieteikumu</span>
                                </button>
                            </form>
                        </div>
                    @endif

                    @if ($canReview && $request->status === 'submitted')
                        <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                            <form method="POST" action="{{ route('writeoff-requests.review', $request) }}">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn-approve">
                                    <x-icon name="check-circle" size="h-4 w-4" />
                                    <span>Apstiprināt</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('writeoff-requests.review', $request) }}">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn-reject">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>Noraidīt</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="surface-empty">Pieteikumu vēl nav.</div>
            @endforelse
        </div>

        {{ $requests->links() }}
    </section>
</x-app-layout>
