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
    @endphp

    <section x-data="{ tab: 'overview', lightboxOpen: false, lightboxImage: '', lightboxTitle: '' }" class="device-shell">
        <div class="device-page-header">
            <div>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <a href="{{ route('devices.index') }}" class="text-sm font-medium text-sky-700 transition hover:text-sky-900">Atpakal uz sarakstu</a>
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

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('devices.history', $device) }}" class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Pilna vesture
                </a>
                <a href="{{ route('devices.edit', $device) }}" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Rediget
                </a>
            </div>
        </div>

        <div class="device-stat-grid">
            <div class="device-stat-card">
                <div class="device-stat-label">Statuss</div>
                <div class="device-stat-value">{{ $statusLabels[$device->status] ?? $device->status }}</div>
            </div>
            <div class="device-stat-card">
                <div class="device-stat-label">Telpa</div>
                <div class="device-stat-value">{{ $device->room?->room_number ?: '-' }}</div>
            </div>
            <div class="device-stat-card">
                <div class="device-stat-label">Izveidoja</div>
                <div class="device-stat-value">{{ $creatorName }}</div>
            </div>
            <div class="device-stat-card">
                <div class="device-stat-label">Komplektacijas</div>
                <div class="device-stat-value">{{ $device->sets->count() }}</div>
            </div>
        </div>

        <div class="mb-6 device-tab-list">
            <button type="button" @click="tab = 'overview'" :class="tab === 'overview' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Pilna informacija</button>
            <button type="button" @click="tab = 'history'" :class="tab === 'history' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Vesture</button>
            <button type="button" @click="tab = 'sets'" :class="tab === 'sets' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Komplektacijas</button>
            <button type="button" @click="tab = 'files'" :class="tab === 'files' ? 'device-tab-button device-tab-button-active' : 'device-tab-button device-tab-button-idle'">Atteli un faili</button>
        </div>

        <div x-show="tab === 'overview'" x-cloak class="device-panel">
            <div class="device-panel-header">
                <h2 class="text-lg font-semibold text-slate-900">Pilna informacija</h2>
                <p class="text-sm text-slate-500">Galvena informacija par ierici vienkopus.</p>
            </div>
            <div class="device-panel-body">
                <div class="device-kv-grid">
                    <div><p class="device-kv-label">Nosaukums</p><p class="device-kv-value">{{ $device->name }}</p></div>
                    <div><p class="device-kv-label">Kods</p><p class="device-kv-value">{{ $device->code ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Tips</p><p class="device-kv-value">{{ $device->type?->type_name ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Modelis</p><p class="device-kv-value">{{ $device->model ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Razotajs</p><p class="device-kv-value">{{ $device->manufacturer ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Serijas numurs</p><p class="device-kv-value">{{ $device->serial_number ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Pirkuma datums</p><p class="device-kv-value">{{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Cena</p><p class="device-kv-value">{{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2) . ' EUR' : '-' }}</p></div>
                    <div><p class="device-kv-label">Garantija lidz</p><p class="device-kv-value">{{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Pieskirta</p><p class="device-kv-value">{{ $device->assigned_to ?: '-' }}</p></div>
                    <div><p class="device-kv-label">Eka</p><p class="device-kv-value">{{ $device->building?->building_name ?: ($device->room?->building?->building_name ?: '-') }}</p></div>
                    <div><p class="device-kv-label">Telpa</p><p class="device-kv-value">{{ $device->room?->room_number ?: '-' }}@if ($device->room?->room_name)<span class="text-slate-500"> / {{ $device->room->room_name }}</span>@endif</p></div>
                    <div><p class="device-kv-label">Izveidoja</p><p class="device-kv-value">{{ $creatorName }}</p></div>
                    <div><p class="device-kv-label">Izveidots</p><p class="device-kv-value">{{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</p></div>
                    <div class="sm:col-span-2">
                        <p class="device-kv-label">Piezimes</p>
                        <div class="device-kv-value rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">{{ $device->notes ?: 'Piezimes nav pievienotas.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'history'" x-cloak class="device-panel">
            <div class="device-panel-header">
                <h2 class="text-lg font-semibold text-slate-900">Vesture</h2>
                <p class="text-sm text-slate-500">Sinhronizeta ar `device_history` tabulu.</p>
            </div>
            <div class="device-panel-body">
                <div class="device-timeline">
                    @forelse ($device->histories->sortByDesc('timestamp') as $event)
                        <div class="device-timeline-item">
                            @if (! $loop->last)
                                <div class="device-timeline-line"></div>
                            @endif
                            <div class="device-timeline-dot {{ match($event->action) {
                                'CREATE' => 'bg-emerald-500',
                                'UPDATE' => 'bg-sky-500',
                                'DELETE' => 'bg-rose-500',
                                default => 'bg-slate-400',
                            } }}"></div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $event->action }} @if ($event->field_changed)<span class="text-slate-500">/ {{ $event->field_changed }}</span>@endif</div>
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
                <p class="text-sm text-slate-500">Kur sis aprikojums ir izmantots.</p>
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
                                <a href="{{ route('device-sets.edit', $set) }}" class="text-sm font-medium text-sky-700 hover:text-sky-900">Atvert</a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            Ierice pagaidam nav piesaistita nevienai komplektacijai.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="tab === 'files'" x-cloak class="space-y-6">
            <div class="device-panel">
                <div class="device-panel-header">
                    <h2 class="text-lg font-semibold text-slate-900">Atteli un faili</h2>
                    <p class="text-sm text-slate-500">Vizuālā informācija un saites uz failiem.</p>
                </div>
                <div class="device-panel-body">
                <div class="device-media-grid">
                    <div class="device-media-card">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices foto</h3>
                                @if ($deviceImageUrl)
                                    <a href="{{ $deviceImageUrl }}" target="_blank" class="text-sm font-medium text-sky-700 hover:text-sky-900">Atvert</a>
                                @endif
                            </div>
                            <div class="device-media-frame">
                                @if ($deviceImageUrl)
                                    <button
                                        type="button"
                                        class="h-full w-full"
                                        @click="lightboxImage = '{{ $deviceImageUrl }}'; lightboxTitle = 'Ierices foto'; lightboxOpen = true"
                                    >
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
                                    <a href="{{ $warrantyImageUrl }}" target="_blank" class="text-sm font-medium text-sky-700 hover:text-sky-900">Atvert</a>
                                @endif
                            </div>
                            <div class="device-media-frame">
                                @if ($warrantyImageUrl)
                                    <button
                                        type="button"
                                        class="h-full w-full"
                                        @click="lightboxImage = '{{ $warrantyImageUrl }}'; lightboxTitle = 'Garantijas attels'; lightboxOpen = true"
                                    >
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

            <div class="device-panel">
                <div class="device-panel-header">
                    <h2 class="text-lg font-semibold text-slate-900">Glabasana</h2>
                    <p class="text-sm text-slate-500">Faili glabajas uz konfigurēta servera diska.</p>
                </div>
                <div class="device-panel-body space-y-4 text-sm text-slate-600">
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        <div class="font-semibold text-slate-900">Ierices foto</div>
                        <div class="mt-1 break-all">{{ $device->device_image_url ?: 'Nav faila' }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        <div class="font-semibold text-slate-900">Garantijas attels</div>
                        <div class="mt-1 break-all">{{ $device->warranty_photo_name ?: 'Nav faila' }}</div>
                    </div>
                    <p class="text-xs leading-6 text-slate-500">
                        Pec noklusejuma faili glabajas servera `public` diskā. Produkcijai vari iestatīt `DEVICE_ASSET_DISK=s3`, lai izmantotu mākoņglabātuvi.
                    </p>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="lightboxOpen"
            x-transition.opacity
            class="device-lightbox"
            @click.self="lightboxOpen = false"
            @keydown.escape.window="lightboxOpen = false"
        >
            <div class="device-lightbox-panel">
                <button type="button" class="device-lightbox-close" @click="lightboxOpen = false">Aizvert</button>
                <div class="mb-3 px-2 pt-2 text-sm font-semibold text-white" x-text="lightboxTitle"></div>
                <img :src="lightboxImage" :alt="lightboxTitle" class="device-lightbox-image">
            </div>
        </div>
    </section>
</x-app-layout>
