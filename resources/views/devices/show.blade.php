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

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <a href="{{ route('devices.index') }}" class="text-sm font-medium text-sky-700 transition hover:text-sky-900">Atpakal uz sarakstu</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-sm text-slate-500">Ierices detalas</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $device->name }}</h1>
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $statusClasses[$device->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                        {{ $statusLabels[$device->status] ?? $device->status }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-500">
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

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.9fr)]">
            <div class="space-y-6">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Vizuala informacija</h2>
                        <p class="text-sm text-slate-500">Ierices foto un garantijas materials vienuviet.</p>
                    </div>
                    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices foto</h3>
                                @if ($deviceImageUrl)
                                    <a href="{{ $deviceImageUrl }}" target="_blank" class="text-sm font-medium text-sky-700 hover:text-sky-900">Atvert pilna izmera</a>
                                @endif
                            </div>
                            @if ($deviceImageUrl)
                                <img src="{{ $deviceImageUrl }}" alt="Ierices attels" class="h-72 w-full rounded-2xl object-cover ring-1 ring-slate-200">
                            @else
                                <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white text-sm text-slate-500">
                                    Foto vel nav pievienots
                                </div>
                            @endif
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Garantijas attels</h3>
                                @if ($warrantyImageUrl)
                                    <a href="{{ $warrantyImageUrl }}" target="_blank" class="text-sm font-medium text-sky-700 hover:text-sky-900">Atvert pilna izmera</a>
                                @endif
                            </div>
                            @if ($warrantyImageUrl)
                                <img src="{{ $warrantyImageUrl }}" alt="Garantijas attels" class="h-72 w-full rounded-2xl object-cover ring-1 ring-slate-200">
                            @else
                                <div class="flex h-72 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white text-sm text-slate-500">
                                    Garantijas attels vel nav pievienots
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Pilna informacija</h2>
                        <p class="text-sm text-slate-500">Visa pieejama informacija no ierices kartites.</p>
                    </div>
                    <div class="grid gap-x-8 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nosaukums</p><p class="mt-1 text-sm text-slate-900">{{ $device->name }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kods</p><p class="mt-1 text-sm text-slate-900">{{ $device->code ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tips</p><p class="mt-1 text-sm text-slate-900">{{ $device->type?->type_name ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Modelis</p><p class="mt-1 text-sm text-slate-900">{{ $device->model ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Razotajs</p><p class="mt-1 text-sm text-slate-900">{{ $device->manufacturer ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Serijas numurs</p><p class="mt-1 text-sm text-slate-900">{{ $device->serial_number ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pirkuma datums</p><p class="mt-1 text-sm text-slate-900">{{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cena</p><p class="mt-1 text-sm text-slate-900">{{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2) . ' EUR' : '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Garantija lidz</p><p class="mt-1 text-sm text-slate-900">{{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pieskirta</p><p class="mt-1 text-sm text-slate-900">{{ $device->assigned_to ?: '-' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Eka</p><p class="mt-1 text-sm text-slate-900">{{ $device->building?->building_name ?: ($device->room?->building?->building_name ?: '-') }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Telpa</p><p class="mt-1 text-sm text-slate-900">{{ $device->room?->room_number ?: '-' }}@if ($device->room?->room_name)<span class="text-slate-500"> / {{ $device->room->room_name }}</span>@endif</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Izveidoja</p><p class="mt-1 text-sm text-slate-900">{{ $creatorName }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Izveidots</p><p class="mt-1 text-sm text-slate-900">{{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</p></div>
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Piezimes</p>
                            <div class="mt-1 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700 ring-1 ring-slate-200">{{ $device->notes ?: 'Piezimes nav pievienotas.' }}</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Vesture</h2>
                        <p class="text-sm text-slate-500">Sinhronizeta ar `device_history` tabulu.</p>
                    </div>
                    <div class="p-5 sm:p-6">
                        <div class="space-y-4">
                            @forelse ($device->histories->sortByDesc('timestamp') as $event)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
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
            </div>

            <div class="space-y-6">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Komplektacijas</h2>
                        <p class="text-sm text-slate-500">Kur sis aprikojums ir izmantots.</p>
                    </div>
                    <div class="p-5">
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

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Fails un glabasana</h2>
                        <p class="text-sm text-slate-500">Atteli var glabat lokali vai makoni ar viena diska konfiguraciju.</p>
                    </div>
                    <div class="space-y-4 p-5 text-sm text-slate-600">
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <div class="font-semibold text-slate-900">Ierices foto</div>
                            <div class="mt-1 break-all">{{ $device->device_image_url ?: 'Nav faila' }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <div class="font-semibold text-slate-900">Garantijas attels</div>
                            <div class="mt-1 break-all">{{ $device->warranty_photo_name ?: 'Nav faila' }}</div>
                        </div>
                        <p class="text-xs leading-6 text-slate-500">
                            Ja `DEVICE_ASSET_DISK` ir iestatits uz `s3`, tie pasi atteli tiks glabati makoni. Lokaliem failiem vajadzigs `php artisan storage:link`.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
