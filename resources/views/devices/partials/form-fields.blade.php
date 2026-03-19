@php
    $current = $device;
    $isWrittenOff = ($current?->status ?? null) === \App\Models\Device::STATUS_WRITEOFF;
    $deviceImageUrl = $current?->deviceImageUrl();
@endphp

<div class="device-form-grid">
    <div class="space-y-6">
        @if ($isWrittenOff)
            <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
                Norakstitai iericei var labot tikai informacijas laukus. Statuss, piesaiste lietotajam un telpa vairs netiek mainiti.
            </div>
        @endif

        <section class="device-form-card">
            <div class="device-form-section-header">
                <div class="device-form-section-icon bg-sky-50 text-sky-700 ring-sky-200">
                    <x-icon name="device" size="h-5 w-5" />
                </div>
                <div class="device-form-section-copy">
                    <div class="device-form-section-name">Pamata dati</div>
                    <div class="device-form-section-note">Ievadi galveno informaciju, pec kuras ierici atradis un atpazis sistēma.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Kods *</span>
                    <input type="text" name="code" value="{{ old('code', $current?->code) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Nosaukums *</span>
                    <input type="text" name="name" value="{{ old('name', $current?->name) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Tips *</span>
                    <select name="device_type_id" class="crud-control" required>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('device_type_id', $current?->device_type_id) == $type->id)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Modelis *</span>
                    <input type="text" name="model" value="{{ old('model', $current?->model) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Razotajs</span>
                    <input type="text" name="manufacturer" value="{{ old('manufacturer', $current?->manufacturer) }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Serijas numurs</span>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $current?->serial_number) }}" class="crud-control">
                </label>
            </div>
        </section>

        <section class="device-form-card">
            <div class="device-form-section-header">
                <div class="device-form-section-icon bg-emerald-50 text-emerald-700 ring-emerald-200">
                    <x-icon name="users" size="h-5 w-5" />
                </div>
                <div class="device-form-section-copy">
                    <div class="device-form-section-name">Statuss un piesaiste</div>
                    <div class="device-form-section-note">Noradi, kam ierice pieskirta un kur ta atrodas ikdiena.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Statuss</span>
                    @if ($isWrittenOff)
                        <input type="hidden" name="status" value="{{ $current?->status }}">
                    @endif
                    <select name="status" class="crud-control" required @disabled($isWrittenOff)>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $current?->status ?? 'active') === $status)>{{ $statusLabels[$status] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Pieskirtais lietotajs</span>
                    @if ($isWrittenOff)
                        <input type="hidden" name="assigned_to_id" value="">
                    @endif
                    <select name="assigned_to_id" class="crud-control" @disabled($isWrittenOff)>
                        <option value="">Nav pieskirts</option>
                        @foreach ($users as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected(old('assigned_to_id', $current?->assigned_to_id) == $assignedUser->id)>{{ $assignedUser->full_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Eka</span>
                    @if ($isWrittenOff)
                        <input type="hidden" name="building_id" value="">
                    @endif
                    <select name="building_id" class="crud-control" @disabled($isWrittenOff)>
                        <option value="">Nav noradita</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected(old('building_id', $current?->building_id) == $building->id)>{{ $building->building_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Telpa</span>
                    @if ($isWrittenOff)
                        <input type="hidden" name="room_id" value="">
                    @endif
                    <select name="room_id" class="crud-control" @disabled($isWrittenOff)>
                        <option value="">Nav noradita</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $current?->room_id) == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="device-form-card">
            <div class="device-form-section-header">
                <div class="device-form-section-icon bg-violet-50 text-violet-700 ring-violet-200">
                    <x-icon name="calendar" size="h-5 w-5" />
                </div>
                <div class="device-form-section-copy">
                    <div class="device-form-section-name">Iegade, garantija un piezimes</div>
                    <div class="device-form-section-note">Papildini finanšu, datumu un paskaidrojumu laukus, ja tie ir zinami.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-localized-date-input
                    name="purchase_date"
                    label="Iegades datums"
                    :value="old('purchase_date', $current?->purchase_date?->format('Y-m-d'))"
                />
                <label class="block">
                    <span class="crud-label">Iegades cena</span>
                    <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $current?->purchase_price) }}" class="crud-control">
                </label>
                <x-localized-date-input
                    name="warranty_until"
                    label="Garantija lidz"
                    :value="old('warranty_until', $current?->warranty_until?->format('Y-m-d'))"
                />
                <div class="block">
                    <span class="crud-label">Ierices attels</span>
                    <input type="file" name="device_image" class="crud-control">
                    @if ($current)
                        <label class="mt-3 inline-flex items-center gap-3">
                            <input type="checkbox" name="remove_device_image" value="1" class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-slate-700">Nonemt ierices attelu</span>
                        </label>
                    @endif
                </div>
                <label class="block md:col-span-2">
                    <span class="crud-label">Piezimes</span>
                    <textarea name="notes" rows="5" class="crud-control">{{ old('notes', $current?->notes) }}</textarea>
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="device-form-card">
            <div class="device-form-section-title">Kopsavilkums</div>
            <div class="space-y-4 text-sm text-slate-600">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kas ir obligati</div>
                    <ul class="mt-3 space-y-2 leading-6">
                        <li>Kods, nosaukums, tips un modelis ir obligati lauki.</li>
                        <li>Piesaiste lietotajam un telpai ir ieteicama, lai ierici viegli izsekot.</li>
                        <li>Datumi, cena un piezimes var palikt tuksi, ja tie nav zinami.</li>
                    </ul>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attela priekšskats</div>
                    <div class="mt-3">
                        @if ($deviceImageUrl)
                            <img src="{{ $deviceImageUrl }}" alt="{{ $current?->name ?: 'Ierice' }}" class="w-full rounded-[1.25rem] border border-slate-200 object-contain">
                        @else
                            <div class="rounded-[1.25rem] border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">
                                Attels tiks paradits pec pievienosanas.
                            </div>
                        @endif
                    </div>
                </div>

                @if ($current)
                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Esošais ieraksts</div>
                        <div class="mt-3 space-y-2">
                            <div><strong class="text-slate-900">Statuss:</strong> {{ $statusLabels[$current->status] ?? $current->status }}</div>
                            <div><strong class="text-slate-900">Kods:</strong> {{ $current->code ?: '-' }}</div>
                            <div><strong class="text-slate-900">Lietotajs:</strong> {{ $current->assignedTo?->full_name ?: 'Nav pieskirts' }}</div>
                            <div><strong class="text-slate-900">Atrasanas vieta:</strong> {{ $current->building?->building_name ?: 'Bez ekas' }} / {{ $current->room?->room_number ?: 'Bez telpas' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </aside>
</div>
