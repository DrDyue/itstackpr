<section class="device-form-card">
    <div class="device-form-section-header">
        <div class="device-form-section-icon bg-sky-50 text-sky-700 ring-sky-200">
            <x-icon name="device" size="h-5 w-5" />
        </div>
        <div class="device-form-section-copy">
            <div class="device-form-section-name">Pamata dati</div>
            <div class="device-form-section-note">Ievadi informāciju, pēc kuras ierīci var atpazīt un atrast sistēmā.</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.form-field label="Kods" name="code" :required="true">
            <input type="text" name="code" value="{{ $fieldValue('code', $current?->code) }}" class="crud-control" required>
        </x-ui.form-field>
        <x-ui.form-field label="Nosaukums" name="name" :required="true">
            <input type="text" name="name" value="{{ $fieldValue('name', $current?->name) }}" class="crud-control" required>
        </x-ui.form-field>
        <x-ui.form-field label="Tips" name="device_type_id" :required="true">
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
        <x-ui.form-field label="Modelis" name="model" :required="true">
            <input type="text" name="model" value="{{ $fieldValue('model', $current?->model) }}" class="crud-control" required>
        </x-ui.form-field>
        <x-ui.form-field label="Ražotājs" name="manufacturer">
            <input type="text" name="manufacturer" value="{{ $fieldValue('manufacturer', $current?->manufacturer) }}" class="crud-control">
        </x-ui.form-field>
        <x-ui.form-field label="Sērijas numurs" name="serial_number">
            <input type="text" name="serial_number" value="{{ $fieldValue('serial_number', $current?->serial_number) }}" class="crud-control">
        </x-ui.form-field>
    </div>
</section>
