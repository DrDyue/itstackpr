@php
    $currentRepair = $repair;
    $selectedDeviceId = (string) old('device_id', $currentRepair?->device_id ?? $preselectedDeviceId ?? '');
    $selectedDevice = ! $currentRepair && ctype_digit($selectedDeviceId)
        ? collect($devices ?? [])->firstWhere('id', (int) $selectedDeviceId)
        : null;
    $selectedDeviceLabel = $selectedDevice
        ? $selectedDevice->name . ' (' . ($selectedDevice->code ?: 'bez koda') . ')'
        : old('device_query', '');
    $statusHint = match ($currentRepair?->status ?? 'waiting') {
        'in-progress' => 'Remonts sobrid ir procesa. Statuss tiek mainits ar darbibu pogam, nevis ar formas lauku.',
        'completed' => 'Remonts ir pabeigts. Ja vajag turpinat, izmanto statusa darbibas virs formas.',
        'cancelled' => 'Remonts ir atcelts. To var atjaunot ar statusa darbibu pogam.',
        default => 'Remonts sobrid gaida uzsaksanu. Statuss automatiski tiek mainits no remonta darbibu pogam.',
    };
@endphp

<div
    class="space-y-6"
    x-data="{ repairType: @js(old('repair_type', $currentRepair?->repair_type ?? 'internal')), repairStatus: @js($currentRepair?->status ?? 'waiting'), priority: @js(old('priority', $currentRepair?->priority ?? 'medium')) }"
>
    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pamata informacija</div>
                    <div class="mt-1 text-sm text-slate-500">Galvenie lauki par ierici, remonta saturu un darba izpildi.</div>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @if ($currentRepair)
                        <div class="block">
                            <span class="crud-label">Ierice</span>
                            <input type="text" class="crud-control bg-slate-50 text-slate-600" value="{{ $currentRepair->device?->name ?: 'Ierice nav atrasta' }} ({{ $currentRepair->device?->code ?: 'bez koda' }})" readonly>
                            <input type="hidden" name="device_id" value="{{ old('device_id', $currentRepair->device_id) }}">
                            <div class="mt-2 text-xs text-slate-500">Esosam remontam ierici mainit nevar. Ja vajag citu ierici, atcel so remontu un izveido jaunu ierakstu.</div>
                        </div>
                    @else
                        <div class="block">
                            <span class="crud-label">Ierice</span>
                            <x-searchable-select
                                name="device_id"
                                query-name="device_query"
                                identifier="repair-create-device"
                                :options="$deviceOptions"
                                :selected="$selectedDeviceId"
                                :query="$selectedDeviceLabel"
                                placeholder="Mekle pec nosaukuma, koda vai lietotaja"
                                empty-message="Neviena ierice neatbilst meklejumam."
                            />
                            <div class="mt-2 text-xs text-slate-500">Redzamas tikai aktivās ierices bez aktiva remonta vai gaidosiem pieprasijumiem.</div>
                        </div>
                    @endif

                    @if ($currentRepair && $currentRepair->status !== 'waiting')
                        <div class="block">
                            <span class="crud-label">Izpilditajs</span>
                            <input type="text" class="crud-control bg-slate-50 text-slate-600" value="{{ $currentRepair->executor?->full_name ?: 'Nav noradits' }}" readonly>
                            <div class="mt-2 text-xs text-slate-500">Izpilditajs tiek pieskirts automatiski bridi, kad remonts no gaida pariet uz procesa statusu.</div>
                        </div>
                    @endif

                    <label class="block md:col-span-2">
                        <span class="crud-label">Apraksts</span>
                        <textarea name="description" rows="5" class="crud-control" required>{{ old('description', $currentRepair?->description) }}</textarea>
                    </label>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta iestatijumi</div>
                    <div class="mt-1 text-sm text-slate-500">Izvelies remonta tipu, prioritati un izmaksu informaciju.</div>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <span class="crud-label">Remonta tips</span>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                            <div class="relative grid grid-cols-2 rounded-xl bg-white p-1 shadow-inner">
                                <div
                                    class="absolute inset-y-1 w-[calc(50%-0.25rem)] rounded-lg bg-slate-900 shadow-sm transition-all duration-200"
                                    :class="repairType === 'internal' ? 'left-1' : 'left-[calc(50%)]'"
                                ></div>
                                <label class="relative z-10 cursor-pointer">
                                    <input type="radio" name="repair_type" value="internal" class="sr-only" x-model="repairType">
                                    <span class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold transition" :class="repairType === 'internal' ? 'text-white' : 'text-slate-600'">
                                        <x-icon name="repair" size="h-4 w-4" />
                                        <span>Ieksejais</span>
                                    </span>
                                </label>
                                <label class="relative z-10 cursor-pointer">
                                    <input type="radio" name="repair_type" value="external" class="sr-only" x-model="repairType">
                                    <span class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold transition" :class="repairType === 'external' ? 'text-white' : 'text-slate-600'">
                                        <x-icon name="repair-request" size="h-4 w-4" />
                                        <span>Arejais</span>
                                    </span>
                                </label>
                            </div>
                            <div class="mt-3 text-sm text-slate-500" x-text="repairType === 'internal' ? 'Ieksejais remonts tiek veikts uz vietas bez vendora datiem.' : 'Arejais remonts paredz vendora informaciju, kad darbs nonak procesa statusa.'"></div>
                        </div>
                    </div>

                    <div class="block md:col-span-2">
                        <span class="crud-label">Prioritate</span>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($priorities as $priority)
                                @php
                                    $selectedPriority = old('priority', $currentRepair?->priority ?? 'medium') === $priority;
                                    $priorityDotClass = match ($priority) {
                                        'low' => 'bg-emerald-500',
                                        'medium' => 'bg-sky-500',
                                        'high' => 'bg-amber-500',
                                        default => 'bg-rose-500',
                                    };
                                @endphp
                                <label class="cursor-pointer">
                                    <input type="radio" name="priority" value="{{ $priority }}" class="sr-only" x-model="priority" @checked($selectedPriority)>
                                    <span
                                        class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold transition"
                                        :class="priority === '{{ $priority }}'
                                            ? 'border-slate-900 bg-slate-900 text-white shadow-[0_20px_40px_-30px_rgba(15,23,42,0.9)]'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'"
                                    >
                                        <span class="inline-flex items-center gap-2">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $priorityDotClass }}"></span>
                                            <span>{{ $priorityLabels[$priority] }}</span>
                                        </span>
                                        <span x-cloak x-show="priority === '{{ $priority }}'">
                                            <x-icon name="check-circle" size="h-4 w-4" />
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <label class="block md:col-span-2" x-cloak x-show="repairStatus !== 'waiting'">
                        <span class="crud-label">Izmaksas</span>
                        <input type="number" step="0.01" name="cost" value="{{ old('cost', $currentRepair?->cost) }}" class="crud-control">
                    </label>
                </div>
            </div>

            <div
                class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"
                x-cloak
                x-show="repairType === 'external'"
            >
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Areja remonta dati</div>
                <div class="mt-1 text-sm text-slate-500">Vendoru informacija tiek izmantota tikai arejam remontam.</div>
                <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" x-show="repairStatus !== 'in-progress'">
                    Vendora lauki aktivizejas un ir obligati tikai tad, kad remonts ir procesa statusa.
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-3" x-show="repairStatus === 'in-progress'">
                    <label class="block">
                        <span class="crud-label">Pakalpojuma sniedzejs</span>
                        <input type="text" name="vendor_name" value="{{ old('vendor_name', $currentRepair?->vendor_name) }}" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="crud-label">Vendora kontakts</span>
                        <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $currentRepair?->vendor_contact) }}" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="crud-label">Rekina numurs</span>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number', $currentRepair?->invoice_number) }}" class="crud-control">
                    </label>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Statusa noteikumi</div>
                <div class="mt-3 text-sm text-slate-600">{{ $statusHint }}</div>
            </div>

            @if ($currentRepair)
                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Laika dati</div>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div><strong class="text-slate-900">Pasreizejais statuss:</strong> {{ $statusLabels[$currentRepair->status] ?? $currentRepair->status }}</div>
                        <div><strong class="text-slate-900">Sakuma datums:</strong> {{ $currentRepair->start_date?->format('d.m.Y') ?: 'Tiks ielikts, kad remonts saksies' }}</div>
                        <div><strong class="text-slate-900">Beigu datums:</strong> {{ $currentRepair->end_date?->format('d.m.Y') ?: 'Tiks ielikts, kad remonts tiks pabeigts' }}</div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <input type="hidden" name="status" value="{{ $currentRepair?->status ?? 'waiting' }}">
</div>
