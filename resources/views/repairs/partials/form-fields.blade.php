{{--
    Partialis: Remonta formas lauki.
    Atbildība: satur laukus kas mainās atkarībā no statusa un remonta tipa.
--}}
@php
    $currentRepair = $repair;
    $statusHint = match ($currentRepair?->status ?? 'waiting') {
        'in-progress' => 'Remonts ir procesā. Pieejami visi lauki.',
        'completed' => 'Remonts ir pabeigts. Datus vari precizēt.',
        'cancelled' => 'Remonts ir atcelts. Datus vari precizēt.',
        default => 'Remonts gaida. Aizpildi pamata informāciju pirms remonta uzsākšanas.',
    };
@endphp

<div class="space-y-6">
    {{-- PAMATA INFORMĀCIJA --}}
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pamata informācija</div>
            <div class="mt-1 text-sm text-slate-500">Galvenie lauki par ierīci un remonta saturu.</div>
        </div>
        <div class="mt-4 grid gap-4">
            @if ($currentRepair)
                <div class="block">
                    <span class="crud-label">Ierīce</span>
                    <input type="text" class="crud-control bg-slate-50 text-slate-600" value="{{ $currentRepair->device?->name ?: 'Ierīce nav atrasta' }} ({{ $currentRepair->device?->code ?: 'bez koda' }})" readonly>
                    <input type="hidden" name="device_id" value="{{ old('device_id', $currentRepair->device_id) }}">
                    <div class="mt-2 text-xs text-slate-500">Esošam remontam ierīci mainīt nevar.</div>
                </div>
            @endif

            {{-- Apraksts - vienmēr redzams --}}
            <label class="block">
                <span class="crud-label">Apraksts <span class="text-rose-500">*</span></span>
                <textarea name="description" rows="5" class="crud-control" required x-model="description">{{ old('description', $currentRepair?->description) }}</textarea>
                <div class="mt-2 text-xs text-slate-500">
                    <span x-show="status === 'waiting'">Apraksti remonta problēmu. Nav obligāts, lai sāktu remontu.</span>
                    <span x-show="status === 'in-progress'">Apraksti veikto darbu. Obligāts, lai pabeigtu remontu.</span>
                    <span x-show="status === 'completed' || status === 'cancelled'">Remonta apraksts vēsturei.</span>
                </div>
            </label>
        </div>
    </div>

    {{-- REMONTA IESTATĪJUMI --}}
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta iestatījumi</div>
            <div class="mt-1 text-sm text-slate-500">Remonta tips un prioritāte.</div>
        </div>
        <div class="mt-4 grid gap-4">
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
                                <span>Iekšējais</span>
                            </span>
                        </label>
                        <label class="relative z-10 cursor-pointer">
                            <input type="radio" name="repair_type" value="external" class="sr-only" x-model="repairType">
                            <span class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold transition" :class="repairType === 'external' ? 'text-white' : 'text-slate-600'">
                                <x-icon name="send" size="h-4 w-4" />
                                <span>Ārējais</span>
                            </span>
                        </label>
                    </div>
                    <div class="mt-3 text-sm text-slate-500" x-text="repairType === 'internal' ? 'Iekšējais remonts tiek veikts uz vietas.' : 'Ārējais remonts paredz vendora informāciju.'"></div>
                </div>
            </div>

            <div class="block">
                <span class="crud-label">Prioritāte</span>
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

            {{-- Izmaksas - redzamas tikai ja statuss ir in-progress vai completed --}}
            <div x-show="status === 'in-progress' || status === 'completed'" style="display: none;">
                <label class="block">
                    <span class="crud-label">Izmaksas</span>
                    <input type="number" step="0.01" name="cost" value="{{ old('cost', $currentRepair?->cost) }}" class="crud-control" x-model="cost">
                    <div class="mt-2 text-xs text-slate-500">
                        <span x-show="repairType === 'external'">Ārējam remontam izmaksas ir obligātas.</span>
                        <span x-show="repairType === 'internal'">Iekšējam remontam izmaksas nav obligātas.</span>
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- ĀRĒJĀ REMONTA DATI - redzami tikai ja repair_type === 'external' un statuss ir in-progress vai completed --}}
    <div
        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"
        x-cloak
        x-show="repairType === 'external' && (status === 'in-progress' || status === 'completed')"
        style="display: none;"
    >
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ārējā remonta dati</div>
        <div class="mt-1 text-sm text-slate-500">Šie lauki ir obligāti ārējam remontam.</div>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="crud-label">Pakalpojuma sniedzējs <span class="text-rose-500">*</span></span>
                <input type="text" name="vendor_name" value="{{ old('vendor_name', $currentRepair?->vendor_name) }}" class="crud-control" x-model="vendorName">
            </label>
            <label class="block">
                <span class="crud-label">Vendora kontakts <span class="text-rose-500">*</span></span>
                <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $currentRepair?->vendor_contact) }}" class="crud-control" x-model="vendorContact">
            </label>
            <label class="block">
                <span class="crud-label">Rēķina numurs <span class="text-rose-500">*</span></span>
                <input type="text" name="invoice_number" value="{{ old('invoice_number', $currentRepair?->invoice_number) }}" class="crud-control" x-model="invoiceNumber">
            </label>
        </div>
    </div>
</div>

<input type="hidden" name="status" value="{{ $currentRepair?->status ?? 'waiting' }}">
