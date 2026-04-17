@props([
    'mode' => 'create',
    'type' => null,
    'modalName',
])

@php
    $isEdit = $mode === 'edit' && $type;
    $modalForm = $isEdit ? 'device_type_edit_' . $type->id : 'device_type_create';
    $shouldUseOldInput = old('modal_form') === $modalForm;
    $action = $isEdit ? route('device-types.update', $type) : route('device-types.store');
    $title = $isEdit ? 'Rediģēt ierīces tipu' : 'Jauns ierīces tips';
    $subtitle = $isEdit
        ? 'Atjauno tipa nosaukumu bez lapas maiņas.'
        : 'Pievieno jaunu ierīču klasifikatora ierakstu tieši no tabulas.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Izveidot tipu';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
    $badgeLabel = $isEdit ? 'Rediģēšana' : 'Jauns ieraksts';
@endphp

<x-modal :name="$modalName" maxWidth="xl">
    <form method="POST" action="{{ $action }}" class="flex max-h-[calc(100vh-2.5rem)] flex-col overflow-hidden">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="modal_form" value="{{ $modalForm }}">

        <div class="device-type-modal-head">
            <div class="device-type-modal-head-copy">
                <div class="device-type-modal-badge">
                    <x-icon :name="$isEdit ? 'edit' : 'plus'" size="h-4 w-4" />
                    <span>{{ $badgeLabel }}</span>
                </div>

                <div class="device-type-modal-title-row">
                    <div class="device-type-modal-icon">
                        <x-icon name="type" size="h-6 w-6" />
                    </div>
                    <div>
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
                    :title="$isEdit ? 'Neizdevās saglabāt ierīces tipa izmaiņas' : 'Neizdevās izveidot ierīces tipu'"
                    :field-labels="[
                        'type_name' => 'Tipa nosaukums',
                    ]"
                />
            @endif

            <section class="device-form-card">
                <div class="device-form-section-header">
                    <div class="device-form-section-icon bg-sky-50 text-sky-700 ring-sky-200">
                        <x-icon name="tag" size="h-5 w-5" />
                    </div>
                    <div class="device-form-section-copy">
                        <div class="device-form-section-name">Tipa informācija</div>
                        <div class="device-form-section-note">Nosaukums būs pieejams ierīču formās, filtros un sarakstos.</div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.form-field class="md:col-span-2" label="Tipa nosaukums" name="type_name" :required="true">
                        <input
                            type="text"
                            name="type_name"
                            value="{{ $shouldUseOldInput ? old('type_name') : ($type?->type_name ?? '') }}"
                            class="crud-control"
                            maxlength="30"
                            required
                            autofocus
                            placeholder="Piemēram: Dators, Monitors, Klaviatūra"
                        >
                    </x-ui.form-field>
                </div>
            </section>
        </div>

        <div class="device-type-modal-actions justify-end">
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
