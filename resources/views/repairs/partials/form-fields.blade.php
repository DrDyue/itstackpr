@php
    $currentRepair = $repair;
    $statusHint = match ($currentRepair?->status ?? 'waiting') {
        'in-progress' => 'Remonts sobrid ir procesa. Statuss tiek mainits ar darbibu pogam, nevis ar formas lauku.',
        'completed' => 'Remonts ir pabeigts. Ja vajag turpinat, izmanto statusa darbibas virs formas.',
        'cancelled' => 'Remonts ir atcelts. To var atjaunot ar statusa darbibu pogam.',
        default => 'Remonts sobrid gaida uzsaksanu. Statuss automatiski tiek mainits no remonta darbibu pogam.',
    };
@endphp

<div
    class="space-y-6"
    x-data="{ repairType: @js(old('repair_type', $currentRepair?->repair_type ?? 'internal')), repairStatus: @js($currentRepair?->status ?? 'waiting') }"
>
    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pamata informacija</div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="crud-label">Ierice</span>
                        <select name="device_id" class="crud-control" required>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}" @selected(old('device_id', $currentRepair?->device_id ?? $preselectedDeviceId ?? null) == $device->id)>{{ $device->name }} ({{ $device->code ?: 'bez koda' }}){{ $device->assignedTo ? ' | ' . $device->assignedTo->full_name : '' }}{{ $device->room ? ' | telpa ' . $device->room->room_number : '' }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="crud-label">Izpilditajs</span>
                        <select name="issue_reported_by" class="crud-control">
                            <option value="">Nav noradits</option>
                            @foreach ($users as $repairUser)
                                <option value="{{ $repairUser->id }}" @selected(old('issue_reported_by', $currentRepair?->issue_reported_by ?? $defaultExecutorId ?? null) == $repairUser->id)>{{ $repairUser->full_name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block md:col-span-2">
                        <span class="crud-label">Apraksts</span>
                        <textarea name="description" rows="5" class="crud-control" required>{{ old('description', $currentRepair?->description) }}</textarea>
                    </label>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta iestatijumi</div>
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

                    <label class="block">
                        <span class="crud-label">Prioritate</span>
                        <select name="priority" class="crud-control">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', $currentRepair?->priority ?? 'medium') === $priority)>{{ $priorityLabels[$priority] }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
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
                <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" x-show="repairStatus !== 'in-progress'">
                    Vendora lauki aktivizējas un ir obligati tikai tad, kad remonts ir procesa statusa.
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

            @if ($currentRepair?->request_id)
                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm text-sm text-slate-600">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Saistiba</div>
                    <div class="mt-3">Saistitais remonta pieteikums: #{{ $currentRepair->request_id }}</div>
                </div>
            @endif
        </div>
    </div>

    <input type="hidden" name="status" value="{{ $currentRepair?->status ?? 'waiting' }}">
</div>
