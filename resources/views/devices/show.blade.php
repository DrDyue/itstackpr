<x-app-layout>
    @php
        $statusLabels = [
            'active' => 'Aktiva',
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'retired' => 'Norakstita',
            'kitting' => 'Komplektacija',
        ];
        $statusClasses = [
            'active' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'reserve' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'broken' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'repair' => 'bg-sky-100 text-sky-800 ring-sky-200',
            'retired' => 'bg-slate-200 text-slate-700 ring-slate-300',
            'kitting' => 'bg-violet-100 text-violet-800 ring-violet-200',
        ];
        $creatorName = $device->createdBy?->employee?->full_name
            ?? $device->createdBy?->employee?->email
            ?? ($device->created_by ? 'User #' . $device->created_by : 'Nav zinams');
        $setCountLabel = $device->sets->count() > 0 ? (string) $device->sets->count() : 'Nav';
        $historyAccents = [
            'CREATE' => ['dot' => 'bg-emerald-500', 'card' => 'border-emerald-200 bg-emerald-50/60', 'label' => 'Izveidota'],
            'UPDATE' => ['dot' => 'bg-sky-500', 'card' => 'border-sky-200 bg-sky-50/60', 'label' => 'Labojums'],
            'DELETE' => ['dot' => 'bg-rose-500', 'card' => 'border-rose-200 bg-rose-50/60', 'label' => 'Dzesta'],
            'MOVE' => ['dot' => 'bg-amber-500', 'card' => 'border-amber-200 bg-amber-50/60', 'label' => 'Parvietota'],
            'STATUS_CHANGE' => ['dot' => 'bg-violet-500', 'card' => 'border-violet-200 bg-violet-50/60', 'label' => 'Statusa maina'],
            'SET_ATTACH' => ['dot' => 'bg-teal-500', 'card' => 'border-teal-200 bg-teal-50/60', 'label' => 'Pievienota komplektacijai'],
        ];
    @endphp

    <section x-data="{ tab: 'overview', lightboxOpen: false, lightboxImage: '', lightboxTitle: '' }" class="device-shell">
        <div class="device-page-header">
            <div>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <a href="{{ route('devices.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-sky-700 transition hover:text-sky-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                        </svg>
                        Atpakal uz sarakstu
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-sm text-slate-500">Ierices detalas</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="device-page-title">{{ $device->name }}</h1>
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $statusClasses[$device->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                        {{ $statusLabels[$device->status] ?? $device->status }}
                    </span>
                </div>
                <p class="device-page-subtitle">
                    Kods: <span class="font-medium text-slate-700">{{ $device->code ?: 'nav noradits' }}</span>
                    <span class="mx-2 text-slate-300">|</span>
                    Tips: <span class="font-medium text-slate-700">{{ $device->type?->type_name ?: 'nav noradits' }}</span>
                </p>
            </div>

        </div>

        <div class="device-stat-grid">
            <div class="device-stat-card">
                <div class="device-stat-head">
                    <div class="device-stat-icon bg-rose-100 text-rose-700 ring-rose-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div>
                        <div class="device-stat-label">Statuss</div>
                        <div class="device-stat-value">{{ $statusLabels[$device->status] ?? $device->status }}</div>
                    </div>
                </div>
            </div>
            <div class="device-stat-card">
                <div class="device-stat-head">
                    <div class="device-stat-icon bg-amber-100 text-amber-700 ring-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21"/></svg>
                    </div>
                    <div>
                        <div class="device-stat-label">Telpa</div>
                        <div class="device-stat-value">{{ $device->room?->room_number ?: '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="device-stat-card">
                <div class="device-stat-head">
                    <div class="device-stat-icon bg-violet-100 text-violet-700 ring-violet-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25c2.9 0 5.25-2.35 5.25-5.25S14.9 3.75 12 3.75 6.75 6.1 6.75 9 9.1 14.25 12 14.25Zm0 0c-4.142 0-7.5 2.015-7.5 4.5v1.5h15v-1.5c0-2.485-3.358-4.5-7.5-4.5Z"/></svg>
                    </div>
                    <div>
                        <div class="device-stat-label">Izveidoja</div>
                        <div class="device-stat-value">{{ $creatorName }}</div>
                    </div>
                </div>
            </div>
            <div class="device-stat-card">
                <div class="device-stat-head">
                    <div class="device-stat-icon bg-emerald-100 text-emerald-700 ring-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5 12 12.75 3 7.5m18 0L12 2.25 3 7.5m18 0v9L12 21.75 3 16.5v-9"/></svg>
                    </div>
                    <div>
                        <div class="device-stat-label">Komplektacijas</div>
                        <div class="device-stat-value">{{ $setCountLabel }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="device-toolbar">
            <div class="device-toolbar-copy">Izvelies, ko apskatit par ierici vai veic atro darbibu uzreiz seit.</div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('devices.history', $device) }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 3.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Pilna vesture
                </a>
                <a href="{{ route('devices.edit', $device) }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5"/>
                    </svg>
                    Rediget
                </a>
            </div>
        </div>

        <div class="mb-6 device-tab-list">
            <button type="button" @click="tab = 'overview'" :class="tab === 'overview' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Pilna informacija</button>
            <button type="button" @click="tab = 'history'" :class="tab === 'history' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Vesture</button>
            <button type="button" @click="tab = 'sets'" :class="tab === 'sets' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Komplektacijas</button>
        </div>

        <div x-show="tab === 'overview'" x-cloak class="space-y-6">
            <div class="device-panel">
                <div class="device-panel-header">
                    <h2 class="text-lg font-semibold text-slate-900">Pilna informacija</h2>
                </div>
                <div class="device-panel-body">
                    <div class="device-summary-grid">
                        <div class="device-summary-card">
                            <div class="device-summary-head">
                                <div class="device-summary-icon bg-sky-100 text-sky-700 ring-sky-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-15 4.5h15m-15 4.5h9m-11.25-12h18a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75h-18a.75.75 0 0 1-.75-.75V5.25a.75.75 0 0 1 .75-.75Z"/></svg>
                                </div>
                                <div>
                                    <div class="device-summary-title">Pamata dati</div>
                                    <div class="text-sm text-slate-500">Galvenie identifikatori</div>
                                </div>
                            </div>
                            <div class="device-kv-grid">
                                <div><p class="device-kv-label">Nosaukums</p><p class="device-kv-value">{{ $device->name }}</p></div>
                                <div><p class="device-kv-label">Kods</p><p class="device-kv-value">{{ $device->code ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Tips</p><p class="device-kv-value">{{ $device->type?->type_name ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Modelis</p><p class="device-kv-value">{{ $device->model ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Razotajs</p><p class="device-kv-value">{{ $device->manufacturer ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Serijas numurs</p><p class="device-kv-value">{{ $device->serial_number ?: '-' }}</p></div>
                            </div>
                        </div>

                        <div class="device-summary-card">
                            <div class="device-summary-head">
                                <div class="device-summary-icon bg-amber-100 text-amber-700 ring-amber-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21M9 9.75h.008v.008H9V9.75Zm0 3.75h.008v.008H9V13.5Zm0 3.75h.008v.008H9v-.008Zm6-7.5h.008v.008H15V9.75Zm0 3.75h.008v.008H15V13.5Zm0 3.75h.008v.008H15v-.008Z"/></svg>
                                </div>
                                <div>
                                    <div class="device-summary-title">Atrasanas vieta</div>
                                    <div class="text-sm text-slate-500">Telpa un piesaiste</div>
                                </div>
                            </div>
                            <div class="device-kv-grid">
                                <div><p class="device-kv-label">Eka</p><p class="device-kv-value">{{ $device->building?->building_name ?: ($device->room?->building?->building_name ?: '-') }}</p></div>
                                <div><p class="device-kv-label">Telpa</p><p class="device-kv-value">{{ $device->room?->room_number ?: '-' }}@if ($device->room?->room_name)<span class="text-slate-500"> / {{ $device->room->room_name }}</span>@endif</p></div>
                                <div><p class="device-kv-label">Pieskirta</p><p class="device-kv-value">{{ $device->assignedEmployee?->full_name ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Komplektacijas</p><p class="device-kv-value">{{ $setCountLabel }}</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 device-summary-grid">
                        <div class="device-summary-card">
                            <div class="device-summary-head">
                                <div class="device-summary-icon bg-emerald-100 text-emerald-700 ring-emerald-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12m-12 5.25h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z"/></svg>
                                </div>
                                <div>
                                    <div class="device-summary-title">Iegade</div>
                                    <div class="text-sm text-slate-500">Iegades un garantijas informacija</div>
                                </div>
                            </div>
                            <div class="device-kv-grid">
                                <div><p class="device-kv-label">Pirkuma datums</p><p class="device-kv-value">{{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Cena</p><p class="device-kv-value">{{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2) . ' EUR' : '-' }}</p></div>
                                <div><p class="device-kv-label">Garantija lidz</p><p class="device-kv-value">{{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</p></div>
                                <div><p class="device-kv-label">Izveidots</p><p class="device-kv-value">{{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</p></div>
                            </div>
                        </div>

                        <div class="device-summary-card">
                            <div class="device-summary-head">
                                <div class="device-summary-icon bg-violet-100 text-violet-700 ring-violet-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25c2.9 0 5.25-2.35 5.25-5.25S14.9 3.75 12 3.75 6.75 6.1 6.75 9 9.1 14.25 12 14.25Zm0 0c-4.142 0-7.5 2.015-7.5 4.5v1.5h15v-1.5c0-2.485-3.358-4.5-7.5-4.5Z"/></svg>
                                </div>
                                <div>
                                    <div class="device-summary-title">Atbildiba un piezimes</div>
                                    <div class="text-sm text-slate-500">Izveidotajs un papildinformacija</div>
                                </div>
                            </div>
                            <div class="device-kv-grid">
                                <div><p class="device-kv-label">Izveidoja</p><p class="device-kv-value">{{ $creatorName }}</p></div>
                                <div><p class="device-kv-label">Statuss</p><p class="device-kv-value">{{ $statusLabels[$device->status] ?? $device->status }}</p></div>
                            </div>
                            <div class="mt-4">
                                <p class="device-kv-label">Piezimes</p>
                                <div class="device-kv-value rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200">{{ $device->notes ?: 'Piezimes nav pievienotas.' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 device-media-grid">
                        <div class="device-media-card">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices foto</h3>
                                @if ($deviceImageUrl)
                                    <a href="{{ $deviceImageUrl }}" download class="inline-flex items-center gap-2 text-sm font-medium text-sky-700 hover:text-sky-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v10.5m0 0 4.5-4.5m-4.5 4.5-4.5-4.5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75v1.5A2.25 2.25 0 0 0 6.75 19.5h10.5a2.25 2.25 0 0 0 2.25-2.25v-1.5"/>
                                        </svg>
                                        Lejupieladet
                                    </a>
                                @endif
                            </div>
                            <div class="device-media-frame">
                                @if ($deviceImageUrl)
                                    <button type="button" class="h-full w-full" @click="lightboxImage = '{{ $deviceImageUrl }}'; lightboxTitle = 'Ierices foto'; lightboxOpen = true">
                                        <img src="{{ $deviceImageUrl }}" alt="Ierices attels" class="device-media-image">
                                    </button>
                                @else
                                    <div class="device-empty-media">Foto vel nav pievienots</div>
                                @endif
                            </div>
                        </div>

                        <div class="device-media-card">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Garantijas attels</h3>
                                @if ($warrantyImageUrl)
                                    <a href="{{ $warrantyImageUrl }}" download class="inline-flex items-center gap-2 text-sm font-medium text-sky-700 hover:text-sky-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v10.5m0 0 4.5-4.5m-4.5 4.5-4.5-4.5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75v1.5A2.25 2.25 0 0 0 6.75 19.5h10.5a2.25 2.25 0 0 0 2.25-2.25v-1.5"/>
                                        </svg>
                                        Lejupieladet
                                    </a>
                                @endif
                            </div>
                            <div class="device-media-frame">
                                @if ($warrantyImageUrl)
                                    <button type="button" class="h-full w-full" @click="lightboxImage = '{{ $warrantyImageUrl }}'; lightboxTitle = 'Garantijas attels'; lightboxOpen = true">
                                        <img src="{{ $warrantyImageUrl }}" alt="Garantijas attels" class="device-media-image">
                                    </button>
                                @else
                                    <div class="device-empty-media">Garantijas attels vel nav pievienots</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'history'" x-cloak class="device-panel">
            <div class="device-panel-header">
                <h2 class="text-lg font-semibold text-slate-900">Vesture</h2>
            </div>
            <div class="device-panel-body">
                <div class="device-timeline">
                    @forelse ($device->histories->sortByDesc('timestamp') as $event)
                        @php
                            $accent = $historyAccents[$event->action] ?? ['dot' => 'bg-slate-400', 'card' => 'border-slate-200 bg-slate-50', 'label' => $event->action];
                        @endphp
                        <div class="device-timeline-item {{ $accent['card'] }}">
                            @if (! $loop->last)
                                <div class="device-timeline-line"></div>
                            @endif
                            <div class="device-timeline-dot {{ $accent['dot'] }}"></div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $accent['label'] }}</span>
                                    @if ($event->field_changed)
                                        <span class="text-sm font-semibold text-slate-900">{{ $event->field_changed }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-500">
                                    {{ $event->timestamp?->format('d.m.Y H:i') ?: '-' }}
                                    <span class="mx-2 text-slate-300">|</span>
                                    {{ $event->changedBy?->employee?->full_name ?? $event->changedBy?->employee?->email ?? ($event->changed_by ? 'User #' . $event->changed_by : 'Nav zinams') }}
                                </div>
                            </div>
                            @if ($event->old_value !== null || $event->new_value !== null)
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200">
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Iepriekseja vertiba</div>
                                        <div class="mt-1 break-words text-sm text-slate-700">{{ $event->old_value ?? '-' }}</div>
                                    </div>
                                    <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200">
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jauna vertiba</div>
                                        <div class="mt-1 break-words text-sm text-slate-700">{{ $event->new_value ?? '-' }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            Sai iericei vesture vel nav pieejama.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="tab === 'sets'" x-cloak class="device-panel">
            <div class="device-panel-header">
                <h2 class="text-lg font-semibold text-slate-900">Komplektacijas</h2>
            </div>
            <div class="device-panel-body">
                <div class="space-y-3">
                    @forelse ($device->sets as $set)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $set->set_name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $set->set_code ?: 'Bez koda' }}
                                        @if ($set->room?->room_number)
                                            <span class="mx-2 text-slate-300">|</span>
                                            Telpa {{ $set->room->room_number }}
                                        @endif
                                        @if ($set->pivot?->quantity)
                                            <span class="mx-2 text-slate-300">|</span>
                                            Daudzums {{ $set->pivot->quantity }}
                                        @endif
                                    </div>
                                    @if ($set->pivot?->role || $set->pivot?->description)
                                        <div class="mt-2 text-sm text-slate-600">
                                            {{ $set->pivot->role ?: 'Loma nav noradita' }}
                                            @if ($set->pivot?->description)
                                                <span class="text-slate-400">-</span> {{ $set->pivot->description }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('device-sets.edit', $set) }}" class="inline-flex items-center gap-2 text-sm font-medium text-sky-700 hover:text-sky-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H19.5M19.5 6V12M19.5 6l-7.5 7.5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h-1.5A2.25 2.25 0 0 0 3 9.75v9A2.25 2.25 0 0 0 5.25 21h9a2.25 2.25 0 0 0 2.25-2.25v-1.5"/>
                                    </svg>
                                    Atvert
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            Sai iericei pagaidam nav piesaistita nevienai komplektacijai.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-cloak x-show="lightboxOpen" x-transition.opacity class="device-lightbox" @click.self="lightboxOpen = false" @keydown.escape.window="lightboxOpen = false">
            <div class="device-lightbox-panel">
                <button type="button" class="device-lightbox-close" @click="lightboxOpen = false">Aizvert</button>
                <div class="mb-3 px-2 pt-2 text-sm font-semibold text-white" x-text="lightboxTitle"></div>
                <img :src="lightboxImage" :alt="lightboxTitle" class="device-lightbox-image">
            </div>
        </div>
    </section>
</x-app-layout>
