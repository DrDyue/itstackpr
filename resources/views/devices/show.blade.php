{{--
    Lapa: Vienas ierīces detalizētais skats.
    Atbildība: parāda pilnu informāciju par konkrētu ierīci, tās statusu, atrašanās vietu un saistītajām darbībām.
    Datu avots: DeviceController@show.
    Galvenās daļas:
    1. Hero zona ar ierīces nosaukumu un pogām.
    2. Pamata informācijas kartītes ar attēlu, identitāti un piesaisti.
    3. Saistītā vēsture un papildu darbības atkarībā no lietotāja tiesībām.
--}}
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
                        <span>{{ $canManageDevices ? 'Ierīces kartīte' : 'Mana ierīce' }}</span>
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
                            <span>Rediģēt</span>
                        </a>
                    @else
                        @if ($requestAvailability['repair'])
                            <a href="{{ route('repair-requests.create', ['device_id' => $device->id]) }}" class="btn-edit">
                                <x-icon name="repair" size="h-4 w-4" />
                                <span>Pieteikt remontu</span>
                            </a>
                        @endif
                        @if ($requestAvailability['writeoff'])
                            <a href="{{ route('writeoff-requests.create', ['device_id' => $device->id]) }}" class="btn-danger">
                                <x-icon name="writeoff" size="h-4 w-4" />
                                <span>Pieteikt norakstīšanu</span>
                            </a>
                        @endif
                        @if ($requestAvailability['transfer'])
                            <a href="{{ route('device-transfers.create', ['device_id' => $device->id]) }}" class="btn-view">
                                <x-icon name="transfer" size="h-4 w-4" />
                                <span>Nodot citam</span>
                            </a>
                        @endif
                    @endif
                    <a href="{{ route('devices.index') }}" class="btn-back">
                        <x-icon name="back" size="h-4 w-4" />
                        <span>Atpakaļ</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Ierīces galvenā informācija, attēls un saistītās darbības. --}}
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
                            @if ($repairStatusLabel)
                                <span class="device-repair-state-chip">
                                    <x-icon name="repair" size="h-3.5 w-3.5" />
                                    <span>{{ $repairStatusLabel }}</span>
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                <x-icon name="type" size="h-3.5 w-3.5" />
                                <span>{{ $device->type?->type_name ?: 'Bez tipa' }}</span>
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Identitāte</div>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <div><strong class="text-slate-900">Kods:</strong> {{ $device->code ?: '-' }}</div>
                                    <div><strong class="text-slate-900">Nosaukums:</strong> {{ $device->name }}</div>
                                    <div><strong class="text-slate-900">Ražotājs un modelis:</strong> {{ $deviceMeta !== '' ? $deviceMeta : '-' }}</div>
                                    <div><strong class="text-slate-900">Sērijas numurs:</strong> {{ $device->serial_number ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Piesaiste</div>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <div><strong class="text-slate-900">Lietotājs:</strong> {{ $device->assignedTo?->full_name ?: 'Nav piešķirts' }}</div>
                                    <div><strong class="text-slate-900">Ēka:</strong> {{ $device->building?->building_name ?: 'Nav norādīta' }}</div>
                                    <div><strong class="text-slate-900">Telpa:</strong> {{ $device->room?->room_number ?: 'Nav norādīta' }}@if ($device->room?->room_name) | {{ $device->room->room_name }} @endif</div>
                                    <div><strong class="text-slate-900">Izveidoja:</strong> {{ $device->createdBy?->full_name ?: 'Sistēma' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Datumi un finanses</div>
                        <div class="mt-3 space-y-2 text-sm text-slate-700">
                            <div><strong class="text-slate-900">Iegādes datums:</strong> {{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</div>
                            <div><strong class="text-slate-900">Iegādes cena:</strong> {{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2, '.', ' ') . ' EUR' : '-' }}</div>
                            <div><strong class="text-slate-900">Garantija līdz:</strong> {{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</div>
                            <div><strong class="text-slate-900">Izveidots:</strong> {{ $device->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        </div>
                    </div>

                    @if (! $canManageDevices)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kā ierīce nonāca pie tevis</div>
                            <div class="mt-3 text-sm leading-6 text-slate-700">{{ $originLabel }}</div>
                        </div>
                    @endif

                    @if ($requestAvailability['reason'])
                        <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Svarīgs ierobežojums</div>
                            <div class="mt-3 text-sm leading-6 text-amber-900">{{ $requestAvailability['reason'] }}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($device->notes)
                <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Piezīmes</div>
                    <div class="mt-3 text-sm leading-6 text-slate-700">{{ $device->notes }}</div>
                </div>
            @endif
        </section>

        @if (! $canManageDevices)
            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-6">
                    <section class="surface-card p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="max-w-2xl">
                                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    <x-icon name="room" size="h-5 w-5" class="text-emerald-600" />
                                    <span>Atrašanās vieta un darbība</span>
                                </h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Šeit ir tikai tā informācija, kas papildina augšējo ierīces kartīti: kur ierīce atrodas šobrīd,
                                    kā tā nonāca pie tevis un ko vari izdarīt tālāk.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-5 xl:grid-cols-[0.85fr_1.15fr]">
                            <div class="space-y-4">
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pašreizējā atrašanās vieta</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ $device->building?->building_name ?: 'Bez ēkas' }}</div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        {{ $device->room?->room_number ?: 'Telpa nav norādīta' }}
                                        @if ($device->room?->room_name)
                                            | {{ $device->room->room_name }}
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Kā ierīce nonāca pie tevis</div>
                                    <div class="mt-2 text-sm leading-6 text-slate-700">{{ $originLabel }}</div>
                                </div>

                                @if ($requestAvailability['reason'])
                                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4">
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Pieteikumu ierobežojums</div>
                                        <div class="mt-2 text-sm leading-6 text-amber-900">{{ $requestAvailability['reason'] }}</div>
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="max-w-xl">
                                        <h3 class="inline-flex items-center gap-2 text-base font-semibold text-slate-900">
                                            <x-icon name="room" size="h-4.5 w-4.5" class="text-emerald-600" />
                                            <span>Mainīt ierīces telpu</span>
                                        </h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            Ja ierīce reāli atrodas citā telpā, atjauno to šeit bez iešanas uz citu lapu.
                                        </p>
                                    </div>
                                </div>

                                @if ($roomUpdateAvailability['allowed'])
                                    <form method="POST" action="{{ route('devices.user-room.update', $device) }}" class="mt-5 space-y-4">
                                        @csrf
                                        <div>
                                            <x-searchable-select
                                                name="room_id"
                                                queryName="room_query"
                                                :options="$roomOptions"
                                                :selected="old('room_id', (string) $device->room_id)"
                                                :query="''"
                                                identifier="device-user-room"
                                                placeholder="Izvēlies telpu"
                                            />
                                            @error('room_id')
                                                <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn-create w-full justify-center">
                                            <x-icon name="check" size="h-4 w-4" />
                                            <span>Atjaunot telpu</span>
                                        </button>
                                    </form>
                                @else
                                    <div class="mt-5 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                                        {{ $roomUpdateAvailability['reason'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>

            </div>

        @else
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="surface-card p-6">
                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                    <x-icon name="repair-request" size="h-5 w-5" class="text-sky-600" />
                    <span>Remonta pieteikumi</span>
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Visi ierīces remonta pieteikumi ar iesniedzēju, statusu un izskatīšanas piezīmēm.</p>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($visibleRepairRequests as $request)
                        <div class="surface-card-muted">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $request->responsibleUser?->full_name ?: 'Nav norādīts' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                                <x-status-pill context="request" :value="$request->status" />
                            </div>
                            <div class="mt-3 leading-6 text-slate-700">{{ $request->description ?: 'Apraksts nav pievienots.' }}</div>
                            @if ($request->reviewedBy || $request->review_notes)
                                <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-xs text-slate-600">
                                    @if ($request->reviewedBy)
                                        <div><span class="font-semibold text-slate-900">Izskatīja:</span> {{ $request->reviewedBy->full_name }}</div>
                                    @endif
                                    @if ($request->review_notes)
                                        <div class="mt-1"><span class="font-semibold text-slate-900">Piezīmes:</span> {{ $request->review_notes }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">Remonta pieteikumu vēl nav.</p>
                    @endforelse
                </div>
            </section>

            <section class="surface-card p-6">
                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                    <x-icon name="repair" size="h-5 w-5" class="text-amber-600" />
                    <span>Remonta ieraksti</span>
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Vecie un esošie remonta darbi, kas ierīcei jau ir veikti vai šobrīd turpinās.</p>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($visibleRepairs as $repair)
                        <div class="surface-card-muted">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">Remonts #{{ $repair->id }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $repair->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                                <x-status-pill context="repair" :value="$repair->status" />
                            </div>
                            <div class="mt-3 grid gap-2 text-xs text-slate-600 md:grid-cols-2">
                                <div><span class="font-semibold text-slate-900">Tips:</span> {{ $repair->repair_type ?: '-' }}</div>
                                <div><span class="font-semibold text-slate-900">Prioritate:</span> {{ $repair->priority ?: '-' }}</div>
                                <div><span class="font-semibold text-slate-900">Pieņēma:</span> {{ $repair->approval_actor_name ?: '-' }}</div>
                                <div><span class="font-semibold text-slate-900">Izpildītājs:</span> {{ $repair->executor?->full_name ?: '-' }}</div>
                                <div><span class="font-semibold text-slate-900">Sākums:</span> {{ $repair->start_date?->format('d.m.Y') ?: '-' }}</div>
                                <div><span class="font-semibold text-slate-900">Beigas:</span> {{ $repair->end_date?->format('d.m.Y') ?: '-' }}</div>
                                <div class="md:col-span-2"><span class="font-semibold text-slate-900">Saistītais pieteicējs:</span> {{ $repair->request?->responsibleUser?->full_name ?: '-' }}</div>
                            </div>
                            <div class="mt-3 leading-6 text-slate-700">{{ $repair->description ?: 'Apraksts nav pievienots.' }}</div>
                        </div>
                    @empty
                        <p class="text-slate-500">Remonta ierakstu vēl nav.</p>
                    @endforelse
                </div>
            </section>

            <section class="surface-card p-6">
                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                    <x-icon name="writeoff" size="h-5 w-5" class="text-rose-600" />
                    <span>Norakstīšanas pieteikumi</span>
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Visi ierīces norakstīšanas pieprasījumi ar iemesliem un admina lēmumiem.</p>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($visibleWriteoffRequests as $request)
                        <div class="surface-card-muted">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $request->responsibleUser?->full_name ?: 'Nav norādīts' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                                <x-status-pill context="request" :value="$request->status" />
                            </div>
                            <div class="mt-3 leading-6 text-slate-700">{{ $request->reason ?: 'Iemesls nav pievienots.' }}</div>
                            @if ($request->reviewedBy || $request->review_notes)
                                <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-xs text-slate-600">
                                    @if ($request->reviewedBy)
                                        <div><span class="font-semibold text-slate-900">Izskatīja:</span> {{ $request->reviewedBy->full_name }}</div>
                                    @endif
                                    @if ($request->review_notes)
                                        <div class="mt-1"><span class="font-semibold text-slate-900">Piezīmes:</span> {{ $request->review_notes }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">Norakstīšanas pieteikumu vēl nav.</p>
                    @endforelse
                </div>
            </section>

            <section class="surface-card p-6">
                <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                    <x-icon name="transfer" size="h-5 w-5" class="text-emerald-600" />
                    <span>Pārsūtīšanas un nodošanas</span>
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Ierīces nodošanas vēsture starp lietotājiem un saistītie izskatīšanas lēmumi.</p>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($visibleTransfers as $transfer)
                        <div class="surface-card-muted">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                                <x-status-pill context="request" :value="$transfer->status" />
                            </div>
                            <div class="mt-3 leading-6 text-slate-700">{{ $transfer->transfer_reason ?: 'Iemesls nav pievienots.' }}</div>
                            @if ($transfer->reviewedBy || $transfer->review_notes)
                                <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-xs text-slate-600">
                                    @if ($transfer->reviewedBy)
                                        <div><span class="font-semibold text-slate-900">Izskatīja:</span> {{ $transfer->reviewedBy->full_name }}</div>
                                    @endif
                                    @if ($transfer->review_notes)
                                        <div class="mt-1"><span class="font-semibold text-slate-900">Piezīmes:</span> {{ $transfer->review_notes }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">Pārsūtīšanas ierakstu vēl nav.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</x-app-layout>
