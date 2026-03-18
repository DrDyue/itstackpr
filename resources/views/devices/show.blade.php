<x-app-layout>
    <section class="app-shell max-w-6xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="device" size="h-4 w-4" />
                        <span>Ierices kartite</span>
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
                    @endif
                    <a href="{{ route('devices.index') }}" class="btn-back">
                        <x-icon name="back" size="h-4 w-4" />
                        <span>Atpakal</span>
                    </a>
                </div>
            </div>
        </div>

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
                    @forelse ($device->writeoffRequests as $request)
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
    </section>
</x-app-layout>

