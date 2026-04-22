@props([
    'mode' => 'create',
    'modalName',
    'device' => null,
])

@php
    $isEdit = $mode === 'edit' && $device;
    $modalForm = $isEdit ? 'device_edit_' . $device->id : 'device_create';
    $shouldUseOldInput = old('modal_form') === $modalForm;
    $action = $isEdit ? route('devices.update', $device) : route('devices.store');
    $title = $isEdit ? 'Rediģēt ierīci' : 'Jauna ierīce';
    $subtitle = $isEdit
        ? 'Atjauno inventāra ierakstu, piesaisti un papildu informāciju vienuviet.'
        : 'Pievieno jaunu ierīci ar skaidri sakārtotiem pamatdatiem, piesaisti un iegādes informāciju.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Izveidot ierīci';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
    $badgeLabel = $isEdit ? 'Rediģēšana' : 'Jauns ieraksts';
    $iconName = $isEdit ? 'edit' : 'plus';
    $validationFieldLabels = [
        'code' => 'Kods',
        'name' => 'Nosaukums',
        'device_type_id' => 'Tips',
        'model' => 'Modelis',
        'status' => 'Statuss',
        'assigned_to_id' => 'Atbildīgā persona',
        'room_id' => 'Telpa',
        'purchase_date' => 'Iegādes datums',
        'purchase_price' => 'Iegādes cena',
        'warranty_until' => 'Garantija līdz',
        'serial_number' => 'Sērijas numurs',
        'manufacturer' => 'Ražotājs',
        'notes' => 'Piezīmes',
        'device_image' => 'Ierīces attēls',
    ];
@endphp

<x-modal :name="$modalName" maxWidth="6xl">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="flex max-h-[calc(100vh-2.5rem)] flex-col overflow-hidden">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="modal_form" value="{{ $modalForm }}">

        <div class="device-type-modal-head">
            <div class="device-type-modal-head-copy">
                <div class="device-type-modal-badge">
                    <x-icon :name="$iconName" size="h-4 w-4" />
                    <span>{{ $badgeLabel }}</span>
                </div>

                <div class="device-type-modal-title-row">
                    <div class="device-type-modal-icon">
                        <x-icon name="device" size="h-6 w-6" />
                    </div>
                    <div class="device-type-modal-title-copy">
                        <h2 class="device-type-modal-title">{{ $title }}</h2>
                        <p class="device-type-modal-subtitle">{{ $subtitle }}</p>
                    </div>
                </div>
            </div>

            <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', '{{ $modalName }}')" aria-label="Aizvērt">
                <x-icon name="x-mark" size="h-4 w-4" />
            </button>
        </div>

        <div class="device-type-modal-body overflow-y-auto">
            @if ($shouldUseOldInput && $errors->any())
                <x-validation-summary
                    class="mb-5"
                    title="{{ $isEdit ? 'Neizdevās saglabāt ierīces izmaiņas' : 'Neizdevās izveidot ierīci' }}"
                    :field-labels="$validationFieldLabels"
                />
            @endif

            @include('devices.partials.form-fields', [
                'device' => $device,
                'formKey' => $modalForm,
                'useOldInput' => $shouldUseOldInput,
            ])
        </div>

        <div class="device-type-modal-actions">
            <div class="device-type-modal-actions-copy">
                <div class="device-type-modal-actions-title">{{ $isEdit ? 'Saglabāt izmaiņas ierīcei' : 'Pārbaudi datus pirms izveides' }}</div>
                <div class="device-type-modal-actions-text">{{ $isEdit ? 'Atjauninātā informācija būs redzama uzreiz pēc saglabāšanas.' : 'Atbildīgā persona un telpa tiks piesaistīta jaunajai ierīcei uzreiz pēc izveides.' }}</div>
            </div>
            <div class="device-type-modal-actions-buttons">
                <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', '{{ $modalName }}')">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Atcelt</span>
                </button>

                <button type="submit" class="{{ $submitClass }}">
                    <x-icon name="save" size="h-4 w-4" />
                    <span>{{ $submitLabel }}</span>
                </button>
            </div>
        </div>
    </form>
</x-modal>

@if ($shouldUseOldInput && $errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.setTimeout(() => window.focusValidationField?.('{{ array_key_first($errors->getMessages()) }}'), 180);
        });
    </script>
@endif
