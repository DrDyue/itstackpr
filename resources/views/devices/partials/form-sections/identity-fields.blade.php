<div>
    <div class="form-group-sep">
        <span class="form-group-sep-label">Ierīce</span>
        <div class="form-group-sep-line"></div>
    </div>

    <div class="grid gap-4 md:grid-cols-12">
        {{-- Nosaukums: pilna platuma izveidē, 8/12 rediģēšanā blakus statusam --}}
        <x-ui.form-field class="{{ $isCreating ? 'md:col-span-12' : 'md:col-span-8' }}" label="Nosaukums" name="name" :required="true">
            <input type="text" name="name" value="{{ $fieldValue('name', $current?->name) }}" class="crud-control" required>
        </x-ui.form-field>

        {{-- Statuss tikai rediģēšanas režīmā, blakus nosaukumam --}}
        @if (!$isCreating)
            <label class="block md:col-span-4">
                <span class="crud-label">Statuss *</span>
                @if ($isStatusLocked)
                    <input type="hidden" name="status" value="{{ $current?->status }}">
                    <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                        <span>{{ $statusLabels[$current?->status] ?? 'Norakstīta' }}</span>
                    </div>
                @else
                    <x-searchable-select
                        name="status"
                        query-name="status_query"
                        identifier="device-status-form-select-{{ $formKey }}"
                        :options="$statusOptions"
                        :selected="(string) $selectedStatus"
                        :query="$selectedStatusLabel"
                        placeholder="Izvēlies statusu"
                        empty-message="Neviens statuss neatbilst meklējumam."
                    />
                @endif
            </label>
        @else
            <input type="hidden" name="status" value="{{ \App\Models\Device::STATUS_ACTIVE }}">
        @endif

        <x-ui.form-field class="md:col-span-4" label="Tips" name="device_type_id" :required="true">
            <x-searchable-select
                name="device_type_id"
                query-name="device_type_query"
                identifier="device-type-form-select-{{ $formKey }}"
                :options="$typeOptions"
                :selected="$selectedTypeId"
                :query="$selectedTypeLabel"
                placeholder="Izvēlies ierīces tipu"
                empty-message="Neviens ierīces tips neatbilst meklējumam."
            />
        </x-ui.form-field>

        <x-ui.form-field class="md:col-span-4" label="Ražotājs" name="manufacturer">
            <input type="text" name="manufacturer" value="{{ $fieldValue('manufacturer', $current?->manufacturer) }}" class="crud-control">
        </x-ui.form-field>

        <x-ui.form-field class="md:col-span-4" label="Modelis" name="model" :required="true">
            <input type="text" name="model" value="{{ $fieldValue('model', $current?->model) }}" class="crud-control" required>
        </x-ui.form-field>

        <x-ui.form-field class="md:col-span-4" label="Kods" name="code" :required="true">
            <input type="text" name="code" value="{{ $fieldValue('code', $current?->code) }}" class="crud-control" required>
        </x-ui.form-field>

        <x-ui.form-field class="md:col-span-4" label="Sērijas numurs" name="serial_number">
            <input type="text" name="serial_number" value="{{ $fieldValue('serial_number', $current?->serial_number) }}" class="crud-control">
        </x-ui.form-field>
    </div>
</div>
