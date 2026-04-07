@props([
    'mode' => 'create',
    'action',
    'modalName',
    'type' => null,
    'title' => '',
    'subtitle' => '',
    'submitLabel' => 'Saglabāt',
    'submitClass' => 'btn-create',
])

@php
    $typeNameError = $errors->first('type_name');
    $hasServerErrors = $errors->any();
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="device-type-modal-shell"
    data-device-type-modal-form="true"
    data-modal-name="{{ $modalName }}"
    data-success-message="{{ $mode === 'create' ? 'Ierīces tips veiksmīgi pievienots.' : 'Ierīces tips atjaunināts.' }}"
>
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    <input type="hidden" name="_device_type_modal_mode" value="{{ $mode }}">
    @if ($mode === 'edit' && $type)
        <input type="hidden" name="_device_type_modal_id" value="{{ $type->id }}">
    @endif

    <div class="device-type-modal-head">
        <div class="device-type-modal-head-copy">
            <div class="device-type-modal-badge">
                <x-icon name="type" size="h-4 w-4" />
                <span>{{ $mode === 'create' ? 'Jauns ieraksts' : 'Labošana' }}</span>
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

        <button type="button" class="device-type-modal-close" x-on:click="$dispatch('close')">
            <x-icon name="x-mark" size="h-4 w-4" />
        </button>
    </div>

    <div class="device-type-modal-body">
        <div
            class="{{ $hasServerErrors ? '' : 'hidden ' }}mx-5 mb-0 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
            data-device-type-form-summary
        >
            <div class="font-semibold">Formu nevarēja saglabāt.</div>
            <ul class="mt-2 list-disc pl-5" data-device-type-form-summary-list>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>

        <div class="device-type-modal-card">
            <div class="device-type-modal-card-head">
                <div class="device-type-modal-card-title">Tipa informācija</div>
                <div class="device-type-modal-card-copy">Nosaukums būs pieejams ierīču formās, filtros un sarakstos.</div>
            </div>

            <div class="device-type-modal-field-wrap">
                <label class="crud-label" for="{{ $mode }}-device-type-name{{ $type ? '-'.$type->id : '' }}">Tipa nosaukums *</label>
                <input
                    id="{{ $mode }}-device-type-name{{ $type ? '-'.$type->id : '' }}"
                    type="text"
                    name="type_name"
                    value="{{ old('type_name', $type?->type_name) }}"
                    class="crud-control {{ $typeNameError ? 'border-rose-300 bg-rose-50/60 focus:border-rose-400 focus:ring-rose-200' : '' }}"
                    maxlength="30"
                    required
                    autofocus
                    data-device-type-field="type_name"
                >
                <div class="device-type-modal-field-note">Nosaukumam jābūt unikālam un skaidri saprotamam lietotājiem.</div>

                <div
                    class="{{ $typeNameError ? '' : 'hidden ' }}device-type-modal-error"
                    data-device-type-field-error="type_name"
                >
                    {{ $typeNameError }}
                </div>
            </div>
        </div>
    </div>

    <div class="device-type-modal-actions">
        <div class="device-type-modal-actions-copy">
            <div class="device-type-modal-actions-title">{{ $mode === 'create' ? 'Saglabā jauno tipu' : 'Saglabā izmaiņas' }}</div>
            <div class="device-type-modal-actions-text">
                {{ $mode === 'create'
                    ? 'Pēc saglabāšanas tips uzreiz būs pieejams visās ierīču formās.'
                    : 'Nosaukuma izmaiņas uzreiz atspoguļosies visur, kur šis tips tiek izmantots.' }}
            </div>
        </div>

        <div class="device-type-modal-actions-buttons">
            <button type="button" class="btn-clear" x-on:click="$dispatch('close')">
                <x-icon name="clear" size="h-4 w-4" />
                <span>Atcelt</span>
            </button>

            <button type="submit" class="{{ $submitClass }}" data-device-type-submit>
                <x-icon name="save" size="h-4 w-4" />
                <span>{{ $submitLabel }}</span>
            </button>
        </div>
    </div>
</form>
