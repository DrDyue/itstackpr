<x-app-layout>
    @php
        $deviceAssignees = $devices->mapWithKeys(fn ($device) => [$device->id => $device->created_by])->all();
        $selectedDeviceId = old('device_id', $preselectedDeviceId);
        $priorityDescriptions = [
            'low' => 'Var planot bez steigas',
            'medium' => 'Standarta izpildes seciba',
            'high' => 'Jareage iespejami driz',
            'critical' => 'Japrioritize uzreiz',
        ];
    @endphp

    <section class="repair-form-shell"
        x-data="{
            repairType: @js(old('repair_type', 'internal')),
            priority: @js(old('priority', 'medium')),
            status: 'waiting',
            assignedTo: @js((string) old('assigned_to', '')),
            assignedTouched: {{ old('assigned_to') ? 'true' : 'false' }},
            deviceAssignees: @js($deviceAssignees),
            onDeviceChange(deviceId) {
                if (!this.assignedTouched) {
                    this.assignedTo = this.deviceAssignees[deviceId] ?? '';
                }
            }
        }"
        x-init="onDeviceChange(@js($selectedDeviceId))">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Jauns remonts</h1>
                <p class="device-page-subtitle">Izveido remonta ierakstu secigi, tikai ar vajadzigajiem laukiem.</p>
            </div>
            <a href="{{ route('repairs.index') }}" class="type-back-link inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakal uz sarakstu
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('repairs.store') }}" class="repair-form-grid">
            @csrf
            <input type="hidden" name="status" value="waiting">

            <div class="space-y-4">
                <div class="repair-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-sky-100 text-sky-700 ring-sky-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-15 4.5h15m-15 4.5h9M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">1. Ierice un remonta tips</div>
                            <div class="device-form-section-note">Vispirms izvelies, ko remontesim un kada veida remonts tas bus.</div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'device', 'class' => 'h-4 w-4'])
                                Ierice *
                            </label>
                            <select name="device_id" required class="crud-control" @change="onDeviceChange($event.target.value)">
                                <option value="">Izvelies ierici</option>
                                @foreach ($devices as $device)
                                    <option value="{{ $device->id }}" @selected($selectedDeviceId == $device->id)>{{ $device->code ?? ('Ierice #' . $device->id) }} - {{ $device->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'wrench', 'class' => 'h-4 w-4'])
                                Remonta tips *
                            </label>
                            @include('repairs.partials.custom-select', [
                                'name' => 'repair_type',
                                'selected' => old('repair_type', 'internal'),
                                'options' => $repairTypes,
                                'labels' => $typeLabels,
                                'icons' => $typeIcons,
                                'classes' => $typeClasses,
                                'descriptions' => [
                                    'internal' => 'Darbs tiek veikts uz vietas',
                                    'external' => 'Darbs tiek nodots arejam servisam',
                                ],
                                'syncModel' => 'repairType',
                                'placeholder' => 'Izvelies remonta tipu',
                            ])
                        </div>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-amber-100 text-amber-700 ring-amber-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">2. Remonta saturs</div>
                            <div class="device-form-section-note">Apraksts, prioritate, datumi un izmaksas.</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label flex items-center gap-2">
                            @include('repairs.partials.icon', ['name' => 'note', 'class' => 'h-4 w-4'])
                            Apraksts *
                        </label>
                        <textarea name="description" rows="4" required class="crud-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="sm:col-span-2 xl:col-span-4">
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'flame', 'class' => 'h-4 w-4'])
                                Prioritate
                            </label>
                            @include('repairs.partials.custom-select', [
                                'name' => 'priority',
                                'selected' => old('priority', 'medium'),
                                'options' => $priorities,
                                'labels' => $priorityLabels,
                                'icons' => $priorityIcons,
                                'classes' => $priorityClasses,
                                'descriptions' => $priorityDescriptions,
                                'syncModel' => 'priority',
                                'placeholder' => 'Izvelies prioritati',
                            ])
                        </div>
                        <x-localized-date-picker
                            name="start_date"
                            :value="old('start_date', now()->toDateString())"
                            label="Sakuma datums"
                            label-class="crud-label flex items-center gap-2"
                        />
                        <x-localized-date-picker
                            name="estimated_completion"
                            :value="old('estimated_completion')"
                            label="Planotais beigums"
                            label-class="crud-label flex items-center gap-2"
                        />
                        <x-localized-date-picker
                            name="actual_completion"
                            :value="old('actual_completion')"
                            label="Realais beigu datums"
                            label-class="crud-label flex items-center gap-2"
                        />
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'money', 'class' => 'h-4 w-4'])
                                Izmaksas (EUR)
                            </label>
                            <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'clock', 'class' => 'h-4 w-4'])
                                Statuss
                            </label>
                            <div class="crud-control flex items-center gap-3 bg-slate-50">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $statusClasses['waiting'] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                    @include('repairs.partials.icon', ['name' => $statusIcons['waiting'] ?? 'clock', 'class' => 'h-3.5 w-3.5'])
                                    {{ $statusLabels['waiting'] ?? 'Gaida' }}
                                </span>
                                <span class="text-sm text-slate-500">Jauns remonts vienmer sakas ar gaidisanas statusu.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-emerald-100 text-emerald-700 ring-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25c2.9 0 5.25-2.35 5.25-5.25S14.9 3.75 12 3.75 6.75 6.1 6.75 9 9.1 14.25 12 14.25Zm0 0c-4.142 0-7.5 2.015-7.5 4.5v1.5h15v-1.5c0-2.485-3.358-4.5-7.5-4.5Z"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">3. Atbildiba</div>
                            <div class="device-form-section-note">Kas pieteica remontu un kam tas ir piešķirts.</div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'user', 'class' => 'h-4 w-4'])
                                Zinoja darbinieks
                            </label>
                            <select name="issue_reported_by" class="crud-control">
                                <option value="">Nav</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('issue_reported_by', $defaultReporterId) == $employee->id)>{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'users', 'class' => 'h-4 w-4'])
                                Pieskirts lietotajam
                            </label>
                            <select name="assigned_to" class="crud-control" x-model="assignedTo" @change="assignedTouched = true">
                                <option value="">Nav</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->employee?->full_name ?? ('Lietotajs #' . $user->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div x-show="repairType === 'external'" x-cloak class="repair-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-rose-100 text-rose-700 ring-rose-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M4.5 9.75V18L12 21l7.5-3V9.75M9 12h6"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">4. Areja remonta dati</div>
                            <div class="device-form-section-note">Piegadatajs un ar arejo remontu saistitie ieraksti.</div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'truck', 'class' => 'h-4 w-4'])
                                Piegadatajs *
                            </label>
                            <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'users', 'class' => 'h-4 w-4'])
                                Piegadataja kontakts *
                            </label>
                            <input type="text" name="vendor_contact" value="{{ old('vendor_contact') }}" class="crud-control">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div>
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'document', 'class' => 'h-4 w-4'])
                                Rekina numurs
                            </label>
                            <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number') }}" class="crud-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbibas</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            Saglabat
                        </button>
                        <a href="{{ route('repairs.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Atcelt
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
