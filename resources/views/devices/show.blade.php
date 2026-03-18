<x-app-layout>
    <section class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">{{ $device->name }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $device->code ?: 'Bez koda' }} | {{ $device->model }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @if ($canManageDevices)
                    <a href="{{ route('devices.edit', $device) }}" class="crud-btn-primary">Rediget</a>
                @endif
                <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Atpakal</a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Pamata informacija</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 text-sm">
                    <div><span class="font-medium text-slate-900">Statuss:</span> {{ $statusLabels[$device->status] ?? $device->status }}</div>
                    <div><span class="font-medium text-slate-900">Tips:</span> {{ $device->type?->type_name ?: '-' }}</div>
                    <div><span class="font-medium text-slate-900">Pieskirta:</span> {{ $device->assignedTo?->full_name ?: '-' }}</div>
                    <div><span class="font-medium text-slate-900">Eka / telpa:</span> {{ $device->building?->building_name ?: '-' }} / {{ $device->room?->room_number ?: '-' }}</div>
                    <div><span class="font-medium text-slate-900">Serijas numurs:</span> {{ $device->serial_number ?: '-' }}</div>
                    <div><span class="font-medium text-slate-900">Razotajs:</span> {{ $device->manufacturer ?: '-' }}</div>
                    <div><span class="font-medium text-slate-900">Iegades datums:</span> {{ $device->purchase_date?->format('d.m.Y') ?: '-' }}</div>
                    <div><span class="font-medium text-slate-900">Garantija lidz:</span> {{ $device->warranty_until?->format('d.m.Y') ?: '-' }}</div>
                    <div class="md:col-span-2"><span class="font-medium text-slate-900">Piezimes:</span> {{ $device->notes ?: '-' }}</div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Atteli</h2>
                <div class="mt-4 grid gap-4">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Ierices attels</div>
                        @if ($deviceImageUrl)
                            <img src="{{ $deviceImageUrl }}" alt="{{ $device->name }}" class="mt-2 max-h-56 rounded-xl border border-slate-200">
                        @else
                            <div class="mt-2 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-sm text-slate-500">Nav pievienots</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-700">Garantijas attels</div>
                        @if ($warrantyImageUrl)
                            <img src="{{ $warrantyImageUrl }}" alt="Warranty" class="mt-2 max-h-56 rounded-xl border border-slate-200">
                        @else
                            <div class="mt-2 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-sm text-slate-500">Nav pievienots</div>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Remonta pieteikumi</h2>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($device->repairRequests as $request)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="font-medium text-slate-900">{{ $request->responsibleUser?->full_name ?: '-' }}</div>
                            <div class="mt-1 text-slate-600">{{ $request->description }}</div>
                            <div class="mt-2 text-xs text-slate-500">{{ $request->status }}</div>
                        </div>
                    @empty
                        <p class="text-slate-500">Nav pieteikumu.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Norakstisanas pieteikumi</h2>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($device->writeoffRequests as $request)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="font-medium text-slate-900">{{ $request->responsibleUser?->full_name ?: '-' }}</div>
                            <div class="mt-1 text-slate-600">{{ $request->reason }}</div>
                            <div class="mt-2 text-xs text-slate-500">{{ $request->status }}</div>
                        </div>
                    @empty
                        <p class="text-slate-500">Nav pieteikumu.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Parsutisanas</h2>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($device->transfers as $transfer)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="font-medium text-slate-900">{{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}</div>
                            <div class="mt-1 text-slate-600">{{ $transfer->transfer_reason }}</div>
                            <div class="mt-2 text-xs text-slate-500">{{ $transfer->status }}</div>
                        </div>
                    @empty
                        <p class="text-slate-500">Nav parsutisanas ierakstu.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</x-app-layout>
