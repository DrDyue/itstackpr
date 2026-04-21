@php
    $currentRepair = $repair;
@endphp

<div class="space-y-4">
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pamatinformācija</div>
                <div class="mt-1 text-sm text-slate-500">Galvenie lauki par remonta saturu.</div>
            </div>

            @if ($currentRepair)
                <div class="repair-device-note">
                    <x-icon name="device" size="h-4 w-4" />
                    <span>{{ $currentRepair->device?->name ?: 'Ierīce nav atrasta' }}</span>
                    <span class="repair-device-note-code">{{ $currentRepair->device?->code ?: 'bez koda' }}</span>
                </div>
                <input type="hidden" name="device_id" value="{{ old('device_id', $currentRepair->device_id) }}">
            @endif
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            @unless ($currentRepair)
                <div class="lg:col-span-1">
                    <x-ui.form-field label="Ierīce" name="device_id">
                        <x-searchable-select
                            name="device_id"
                            query-name="device_query"
                            identifier="repair-device"
                            :options="$deviceOptions"
                            :selected="old('device_id', $preselectedDeviceId ?? '')"
                            :query="old('device_query', '')"
                            placeholder="Meklē pēc nosaukuma, koda vai telpas"
                            empty-message="Neviena ierīce neatbilst meklējumam."
                        />
                    </x-ui.form-field>
                </div>
            @endunless

            <div class="{{ $currentRepair ? 'lg:col-span-3' : 'lg:col-span-2' }}">
                <x-ui.form-field label="Apraksts" name="description">
                    <textarea name="description" rows="4" class="crud-control min-h-[7.5rem] {{ $errors->has('description') ? 'crud-control-error' : '' }}" x-model="description">{{ old('description', $currentRepair?->description) }}</textarea>
                </x-ui.form-field>
            </div>
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta iestatījumi</div>
            <div class="mt-1 text-sm text-slate-500">Izvēlies remonta tipu, prioritāti un, ja nepieciešams, izmaksas.</div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <span class="crud-label">Remonta tips</span>
                <div class="mt-2 rounded-2xl border bg-slate-50 p-2 {{ $errors->has('repair_type') ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200' }}">
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
                </div>
                @if ($errors->has('repair_type'))
                    <div class="mt-2 text-xs font-semibold text-rose-600">{{ $errors->first('repair_type') }}</div>
                @endif
            </div>

            <div class="lg:col-span-2">
                <span class="crud-label">Prioritāte</span>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($priorities as $priority)
                        @php
                            $priorityDotClass = match ($priority) {
                                'low' => 'bg-emerald-500',
                                'medium' => 'bg-sky-500',
                                'high' => 'bg-amber-500',
                                default => 'bg-rose-500',
                            };
                        @endphp
                        <label class="cursor-pointer" @click="priority = '{{ $priority }}'">
                            <input type="radio" name="priority" value="{{ $priority }}" class="sr-only" x-model="priority" :checked="priority === '{{ $priority }}'">
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
                @if ($errors->has('priority'))
                    <div class="mt-2 text-xs font-semibold text-rose-600">{{ $errors->first('priority') }}</div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <x-ui.form-field label="Izmaksas" name="cost">
                    <input type="number" step="0.01" name="cost" value="{{ old('cost', $currentRepair?->cost) }}" class="crud-control {{ $errors->has('cost') ? 'crud-control-error' : '' }}" x-model="cost">
                </x-ui.form-field>
            </div>
        </div>
    </div>

    <div
        class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
        x-cloak
        x-show="repairType === 'external'"
    >
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ārējā remonta dati</div>
        <div class="mt-1 text-sm text-slate-500">Šie lauki tiek izmantoti ārējā pakalpojuma uzskaitei un vēsturei.</div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <x-ui.form-field label="Pakalpojuma sniedzējs" name="vendor_name">
                <input type="text" name="vendor_name" value="{{ old('vendor_name', $currentRepair?->vendor_name) }}" class="crud-control {{ $errors->has('vendor_name') ? 'crud-control-error' : '' }}" x-model="vendorName">
            </x-ui.form-field>

            <x-ui.form-field label="Vendora kontakts" name="vendor_contact">
                <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $currentRepair?->vendor_contact) }}" class="crud-control {{ $errors->has('vendor_contact') ? 'crud-control-error' : '' }}" x-model="vendorContact">
            </x-ui.form-field>

            <x-ui.form-field label="Rēķina numurs" name="invoice_number">
                <input type="text" name="invoice_number" value="{{ old('invoice_number', $currentRepair?->invoice_number) }}" class="crud-control {{ $errors->has('invoice_number') ? 'crud-control-error' : '' }}" x-model="invoiceNumber">
            </x-ui.form-field>
        </div>
    </div>
</div>

<input type="hidden" name="status" value="{{ $currentRepair?->status ?? 'waiting' }}">
