<x-app-layout>
    @php
        $deviceMeta = collect([$device->manufacturer, $device->model])->filter(fn ($value) => filled($value))->implode(' | ');
    @endphp

    <section class="app-shell max-w-7xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="device" size="h-4 w-4" />
                        <span>{{ $canManageDevices ? 'Ierices kartite' : 'Mana ierice' }}</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">{{ $device->name }}</h1>
                            <p class="page-subtitle">{{ $device->code ?: 'Bez koda' }} | {{ $device->model }}</p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    @if ($canManageDevices)
                        <a href="{{ route('devices.edit', $device) }}" class="btn-edit">
                            <x-icon name="edit" size="h-4 w-4" />
                            <span>Rediget</span>
                        </a>
                    @else
                        @if ($requestAvailability['repair'])
                            <a href="{{ route('my-requests.create', ['type' => 'repair', 'device_id' => $device->id]) }}" class="btn-edit">
                                <x-icon name="repair" size="h-4 w-4" />
                                <span>Pieteikt remontu</span>
                            </a>
                        @endif
                        @if ($requestAvailability['writeoff'])
                            <a href="{{ route('my-requests.create', ['type' => 'writeoff', 'device_id' => $device->id]) }}" class="btn-danger">
                                <x-icon name="writeoff" size="h-4 w-4" />
                                <span>Pieteikt norakstisanu</span>
                            </a>
                        @endif
                        @if ($requestAvailability['transfer'])
                            <a href="{{ route('my-requests.create', ['type' => 'transfer', 'device_id' => $device->id]) }}" class="btn-view">
                                <x-icon name="transfer" size="h-4 w-4" />
                                <span>Nodot citam</span>
                            </a>
                        @endif
                    @endif
                    <a href="{{ route('devices.index') }}" class="btn-back">
                        <x-icon name="back" size="h-4 w-4" />
                        <span>Atpakal</span>
                    </a>
                </div>
            </div>
        </div>

        <section class="surface-card p-6">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <div class="flex flex-col gap-5 md:flex-row">
                    <div class="shrink-0">
                        @if ($deviceImageUrl)
                            <img src="{{ $deviceImageUrl }}" alt="{{ $device->name }}" class="h-44 w-44 rounded-[1.75rem] border border-slate-200 object-cover">
                        @else
                            <div class="flex h-44 w-44 items-center justify-center rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                                <x-icon name="device" size="h-10 w-10" />
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                            @if ($device->activeRepair)
                                <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">
                                    <x-icon name="repair" size="h-3.5 w-3.5" />
                                    <span>Remonts: {{ $repairStatusLabel }}</span>
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                <x-icon name="type" size="h-3.5 w-3.5" />
                                <span>{{ $device->type?->type_name ?: 'Bez tipa' }}</span>
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Identitate</div>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <div><strong class="text-slate-900">Kods:</strong> {{ $device->code ?: '-' }}</div>
                                    <div><strong class="text-slate-900">Nosaukums:</strong> {{ $device->name }}</div>
                                    <div><strong class="text-slate-900">Razotajs un modelis:</strong> {{ $deviceMeta !== '' ? $deviceMeta : '-' }}</div>
                                    <div><strong class="text-slate-900">Serijas numurs:</strong> {{ $device->serial_number ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Piesaiste</div>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <div><strong class="text-slate-900">Lietotajs:</strong> {{ $device->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                                    <div><strong class="text-slate-900">Eka:</strong> {{ $device->building?->building_name ?: 'Nav noradita' }}</div>
                                    <div><strong class="text-slate-900">Telpa:</strong> {{ $device->room?->room_number ?: 'Nav noradita' }}@if ($device->room?->room_name) | {{ $device->room->room_name }} @endif</div>
                                    <div><strong class="text-slate-900">Izveidoja:</strong> {{ $device->createdBy?->full_name ?: 'Sistema' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Datumi un finanses</div>
                        <div class="mt-3 space-y-2 text-sm text-slate-700">
                            <div><strong class="text-slate-900">Iegades datums:</strong> {{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</div>
                            <div><strong class="text-slate-900">Iegades cena:</strong> {{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2, '.', ' ') . ' EUR' : '-' }}</div>
                            <div><strong class="text-slate-900">Garantija lidz:</strong> {{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</div>
                            <div><strong class="text-slate-900">Izveidots:</strong> {{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        </div>
                    </div>

                    @if (! $canManageDevices)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ka ierice nonaca pie tevis</div>
                            <div class="mt-3 text-sm leading-6 text-slate-700">{{ $originLabel }}</div>
                        </div>
                    @endif

                    @if ($requestAvailability['reason'])
                        <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Svarigs ierobezojums</div>
                            <div class="mt-3 text-sm leading-6 text-amber-900">{{ $requestAvailability['reason'] }}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($device->notes)
                <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Piezimes</div>
                    <div class="mt-3 text-sm leading-6 text-slate-700">{{ $device->notes }}</div>
                </div>
            @endif
        </section>

        @if (! $canManageDevices)
            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-6">
                    <section class="surface-card p-6">
                        <div class="grid gap-5 md:grid-cols-[1.1fr_0.9fr]">
                            <div>
                                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    <x-icon name="stats" size="h-5 w-5" class="text-sky-600" />
                                    <span>Pamata informacija</span>
                                </h2>
                                <div class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Statuss</div>
                                        <div class="mt-2">
                                            <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                                        </div>
                                        @if ($device->activeRepair)
                                            <div class="mt-2 text-xs text-slate-500">Remonta statuss: {{ $repairStatusLabel }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tips</div>
                                        <div class="mt-2 text-slate-700">{{ $device->type?->type_name ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Serijas numurs</div>
                                        <div class="mt-2 text-slate-700">{{ $device->serial_number ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Razotajs</div>
                                        <div class="mt-2 text-slate-700">{{ $device->manufacturer ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Iegades datums</div>
                                        <div class="mt-2 text-slate-700">{{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Garantija lidz</div>
                                        <div class="mt-2 text-slate-700">{{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Piezimes</div>
                                        <div class="mt-2 leading-6 text-slate-700">{{ $device->notes ?: 'Piezimes nav pievienotas.' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ka ierice nonaca pie tevis</div>
                                    <div class="mt-2 text-sm leading-6 text-slate-700">{{ $originLabel }}</div>
                                </div>
                                @if ($requestAvailability['reason'])
                                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4">
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Pieteikumu ierobezojums</div>
                                        <div class="mt-2 text-sm leading-6 text-amber-900">{{ $requestAvailability['reason'] }}</div>
                                    </div>
                                @endif
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pasreizeja atrasanas vieta</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ $device->building?->building_name ?: 'Bez ekas' }}</div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        {{ $device->room?->room_number ?: 'Telpa nav noradita' }}
                                        @if ($device->room?->room_name)
                                            | {{ $device->room->room_name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="surface-card p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    <x-icon name="room" size="h-5 w-5" class="text-emerald-600" />
                                    <span>Mainit ierices telpu</span>
                                </h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    Ja ierice atrodas citur, vari uzreiz atjaunot tas atrasanas vietu.
                                </p>
                            </div>
                        </div>

                        @if ($roomUpdateAvailability['allowed'])
                            <form method="POST" action="{{ route('devices.user-room.update', $device) }}" class="mt-5 grid gap-4 lg:grid-cols-[1fr_auto]">
                                @csrf
                                <div>
                                    <x-searchable-select
                                        name="room_id"
                                        queryName="room_query"
                                        :options="$roomOptions"
                                        :selected="old('room_id', (string) $device->room_id)"
                                        :query="''"
                                        identifier="device-user-room"
                                        placeholder="Izvelies telpu"
                                    />
                                    @error('room_id')
                                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="btn-create">
                                        <x-icon name="check" size="h-4 w-4" />
                                        <span>Atjaunot telpu</span>
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="mt-5 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                                {{ $roomUpdateAvailability['reason'] }}
                            </div>
                        @endif
                    </section>
                </div>

                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="view" size="h-5 w-5" class="text-violet-600" />
                        <span>Ierices attels</span>
                    </h2>
                    <div class="mt-4">
                        @if ($deviceImageUrl)
                            <img src="{{ $deviceImageUrl }}" alt="{{ $device->name }}" class="max-h-[28rem] w-full rounded-[1.75rem] border border-slate-200 object-contain">
                        @else
                            <div class="rounded-[1.75rem] border border-dashed border-slate-300 px-6 py-16 text-center text-sm text-slate-500">
                                Ierices attels nav pievienots.
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="repair-request" size="h-5 w-5" class="text-sky-600" />
                        <span>Remonta pieteikumu vesture</span>
                    </h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($device->repairRequests as $request)
                            <div class="surface-card-muted">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="font-medium text-slate-900">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                    <x-status-pill context="request" :value="$request->status" />
                                </div>
                                <div class="mt-2 leading-6 text-slate-600">{{ $request->description }}</div>
                                @if ($request->reviewedBy || $request->review_notes)
                                    <div class="mt-3 text-xs text-slate-500">
                                        @if ($request->reviewedBy)
                                            <div>Izskatija: {{ $request->reviewedBy->full_name }}</div>
                                        @endif
                                        @if ($request->review_notes)
                                            <div>Piezimes: {{ $request->review_notes }}</div>
                                        @endif
                                    </div>
                                @endif
                                @if ($request->repair)
                                    <div class="mt-3 rounded-2xl border border-sky-200 bg-sky-50 px-3 py-3 text-xs text-sky-900">
                                        <div class="font-semibold">Saistitais remonts #{{ $request->repair->id }}</div>
                                        <div class="mt-1">Statuss: {{ ['waiting' => 'Gaida', 'in-progress' => 'Procesa', 'completed' => 'Pabeigts', 'cancelled' => 'Atcelts'][$request->repair->status] ?? $request->repair->status }}</div>
                                        <div class="mt-1">Apstiprinaja: {{ $request->repair->acceptedBy?->full_name ?: '-' }}</div>
                                        <div class="mt-1">Izpilditajs: {{ $request->repair->executor?->full_name ?: '-' }}</div>
                                        <div class="mt-1">Apraksts: {{ $request->repair->description }}</div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500">Remonta pieteikumu vel nav.</p>
                        @endforelse
                    </div>
                </section>

                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="writeoff" size="h-5 w-5" class="text-rose-600" />
                        <span>Noraiditie norakstisanas pieteikumi</span>
                    </h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($visibleWriteoffRequests as $request)
                            <div class="surface-card-muted">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="font-medium text-slate-900">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                    <x-status-pill context="request" :value="$request->status" />
                                </div>
                                <div class="mt-2 leading-6 text-slate-600">{{ $request->reason }}</div>
                                @if ($request->reviewedBy || $request->review_notes)
                                    <div class="mt-3 text-xs text-slate-500">
                                        @if ($request->reviewedBy)
                                            <div>Izskatija: {{ $request->reviewedBy->full_name }}</div>
                                        @endif
                                        @if ($request->review_notes)
                                            <div>Piezimes: {{ $request->review_notes }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500">Noraiditu norakstisanas pieteikumu nav.</p>
                        @endforelse
                    </div>
                </section>

                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="transfer" size="h-5 w-5" class="text-emerald-600" />
                        <span>Nodosanas vesture</span>
                    </h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($device->transfers as $transfer)
                            <div class="surface-card-muted">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="font-medium text-slate-900">{{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                    <x-status-pill context="request" :value="$transfer->status" />
                                </div>
                                <div class="mt-2 text-slate-700">
                                    {{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}
                                </div>
                                <div class="mt-2 leading-6 text-slate-600">{{ $transfer->transfer_reason }}</div>
                                @if ($transfer->reviewedBy || $transfer->review_notes)
                                    <div class="mt-3 text-xs text-slate-500">
                                        @if ($transfer->reviewedBy)
                                            <div>Izskatija: {{ $transfer->reviewedBy->full_name }}</div>
                                        @endif
                                        @if ($transfer->review_notes)
                                            <div>Piezimes: {{ $transfer->review_notes }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500">Nodosanas ierakstu vel nav.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="stats" size="h-5 w-5" class="text-sky-600" />
                        <span>Pamata informacija</span>
                    </h2>
                    <div class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                        <div><span class="font-medium text-slate-900">Statuss:</span> <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" class="ml-2" /></div>
                        <div><span class="font-medium text-slate-900">Tips:</span> {{ $device->type?->type_name ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Pieskirta:</span> {{ $device->assignedTo?->full_name ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Eka / telpa:</span> {{ $device->building?->building_name ?: '-' }} / {{ $device->room?->room_number ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Serijas numurs:</span> {{ $device->serial_number ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Razotajs:</span> {{ $device->manufacturer ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Iegades datums:</span> {{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Iegades cena:</span> {{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2, '.', ' ') . ' EUR' : '-' }}</div>
                        <div><span class="font-medium text-slate-900">Garantija lidz:</span> {{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Izveidoja:</span> {{ $device->createdBy?->full_name ?: '-' }}</div>
                        <div><span class="font-medium text-slate-900">Izveidots:</span> {{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        <div class="md:col-span-2"><span class="font-medium text-slate-900">Piezimes:</span> {{ $device->notes ?: '-' }}</div>
                    </div>
                </section>

                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="view" size="h-5 w-5" class="text-violet-600" />
                        <span>Atteli</span>
                    </h2>
                    <div class="mt-4 grid gap-4">
                        <div>
                            <div class="text-sm font-medium text-slate-700">Ierices attels</div>
                            @if ($deviceImageUrl)
                                <img src="{{ $deviceImageUrl }}" alt="{{ $device->name }}" class="mt-2 max-h-56 rounded-xl border border-slate-200">
                            @else
                                <div class="mt-2 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-sm text-slate-500">Nav pievienots</div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="repair-request" size="h-5 w-5" class="text-sky-600" />
                        <span>Remonta pieteikumi</span>
                    </h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($device->repairRequests as $request)
                            <div class="surface-card-muted">
                                <div class="font-medium text-slate-900">{{ $request->responsibleUser?->full_name ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                <div class="mt-1 text-slate-600">{{ $request->description }}</div>
                                <div class="mt-2"><x-status-pill context="request" :value="$request->status" /></div>
                                @if ($request->reviewedBy || $request->review_notes)
                                    <div class="mt-2 text-xs text-slate-500">
                                        @if ($request->reviewedBy)
                                            <div>Izskatija: {{ $request->reviewedBy->full_name }}</div>
                                        @endif
                                        @if ($request->review_notes)
                                            <div>Piezimes: {{ $request->review_notes }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500">Nav pieteikumu.</p>
                        @endforelse
                    </div>
                </section>

                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="writeoff" size="h-5 w-5" class="text-rose-600" />
                        <span>Norakstisanas pieteikumi</span>
                    </h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($visibleWriteoffRequests as $request)
                            <div class="surface-card-muted">
                                <div class="font-medium text-slate-900">{{ $request->responsibleUser?->full_name ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                <div class="mt-1 text-slate-600">{{ $request->reason }}</div>
                                <div class="mt-2"><x-status-pill context="request" :value="$request->status" /></div>
                                @if ($request->reviewedBy || $request->review_notes)
                                    <div class="mt-2 text-xs text-slate-500">
                                        @if ($request->reviewedBy)
                                            <div>Izskatija: {{ $request->reviewedBy->full_name }}</div>
                                        @endif
                                        @if ($request->review_notes)
                                            <div>Piezimes: {{ $request->review_notes }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500">Nav pieteikumu.</p>
                        @endforelse
                    </div>
                </section>

                <section class="surface-card p-6">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                        <x-icon name="transfer" size="h-5 w-5" class="text-emerald-600" />
                        <span>Parsutisanas</span>
                    </h2>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($device->transfers as $transfer)
                            <div class="surface-card-muted">
                                <div class="font-medium text-slate-900">{{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                <div class="mt-1 text-slate-600">{{ $transfer->transfer_reason }}</div>
                                <div class="mt-2"><x-status-pill context="request" :value="$transfer->status" /></div>
                                @if ($transfer->reviewedBy || $transfer->review_notes)
                                    <div class="mt-2 text-xs text-slate-500">
                                        @if ($transfer->reviewedBy)
                                            <div>Izskatija: {{ $transfer->reviewedBy->full_name }}</div>
                                        @endif
                                        @if ($transfer->review_notes)
                                            <div>Piezimes: {{ $transfer->review_notes }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500">Nav parsutisanas ierakstu.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </section>
</x-app-layout>
