<div>
    <div class="form-group-sep">
        <span class="form-group-sep-label">Ierīce</span>
        <div class="form-group-sep-line"></div>
    </div>

    {{-- Nosaukums + Statuss (rediģēšanā) — galvenā rinda, bez konteinera --}}
    <div class="grid gap-3 md:grid-cols-12">
        <x-ui.form-field class="{{ $isCreating ? 'md:col-span-12' : 'md:col-span-8' }}" label="Nosaukums" name="name" :required="true">
            <input type="text" name="name" value="{{ $fieldValue('name', $current?->name) }}" class="crud-control" required>
        </x-ui.form-field>

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
    </div>

    {{-- Specifikācija: Tips · Ražotājs · Modelis --}}
    <div class="form-field-group mt-3">
        <div class="form-field-group-label">Specifikācija</div>
        <div class="grid gap-3 md:grid-cols-3">
            <x-ui.form-field label="Tips" name="device_type_id" :required="true">
                <x-searchable-select
                    name="device_type_id"
                    query-name="device_type_query"
                    identifier="device-type-form-select-{{ $formKey }}"
                    :options="$typeOptions"
                    :selected="$selectedTypeId"
                    :query="$selectedTypeLabel"
                    placeholder="Izvēlies tipu"
                    empty-message="Neviens tips neatbilst meklējumam."
                />
            </x-ui.form-field>

            <x-ui.form-field label="Ražotājs" name="manufacturer">
                <input type="text" name="manufacturer" value="{{ $fieldValue('manufacturer', $current?->manufacturer) }}" class="crud-control">
            </x-ui.form-field>

            <x-ui.form-field label="Modelis" name="model" :required="true">
                <input type="text" name="model" value="{{ $fieldValue('model', $current?->model) }}" class="crud-control" required>
            </x-ui.form-field>
        </div>
    </div>

    {{-- Identifikatori: Kods · Sērijas numurs --}}
    <div class="form-field-group mt-3">
        <div class="form-field-group-label">Identifikatori</div>
        <div class="grid gap-3 md:grid-cols-2">
            <x-ui.form-field label="Kods" name="code" :required="true">
                <input type="text" name="code" value="{{ $fieldValue('code', $current?->code) }}" class="crud-control" required>
            </x-ui.form-field>

            <x-ui.form-field label="Sērijas numurs" name="serial_number">
                <input type="text" name="serial_number" value="{{ $fieldValue('serial_number', $current?->serial_number) }}" class="crud-control">
            </x-ui.form-field>
        </div>
    </div>
</div>
