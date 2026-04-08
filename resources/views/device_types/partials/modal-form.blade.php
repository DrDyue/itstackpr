@props([
    'mode' => 'create',
    'action',
    'modalName',
    'type' => null,
    'title' => '',
    'subtitle' => '',
    'submitLabel' => 'Saglabat',
    'submitClass' => 'btn-create',
])

@php
    $typeNameError = $errors->first('type_name');
    $hasServerErrors = $errors->has('type_name');
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="device-type-modal-shell"
    data-device-type-modal-form="true"
    data-modal-name="{{ $modalName }}"
    data-success-message="{{ $mode === 'create' ? 'Ierīces tips veiksmīgi pievienots.' : 'Ierīces tips veiksmīgi atjaunināts.' }}"
>
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    <div class="device-type-modal-head">
        <div class="device-type-modal-head-copy">
            <div class="device-type-modal-badge">
                <x-icon name="type" size="h-4 w-4" />
                <span>{{ $mode === 'create' ? 'Jauns ieraksts' : 'Rediģēšana' }}</span>
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

        <button type="button" class="device-type-modal-close" data-close-modal aria-label="Aizvērt">
            <x-icon name="x-mark" size="h-4 w-4" />
        </button>
    </div>

    <div class="device-type-modal-body">
        <div
            class="device-type-form-errors hidden mx-5 mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
            data-device-type-form-errors
        >
            <div class="font-semibold">Kļūda formas aizpildīšanā</div>
            <ul class="mt-2 list-disc pl-5" data-device-type-error-list>
            </ul>
        </div>

        <div class="device-type-modal-card">
            <div class="device-type-modal-card-head">
                <div class="device-type-modal-card-title">Tipa informācija</div>
                <div class="device-type-modal-card-copy">Nosaukums būs pieejams ierīču formās, filtros un sarakstos.</div>
            </div>

            <div class="device-type-modal-field-wrap">
                <label class="crud-label" for="device-type-input-{{ $mode }}">Tipa nosaukums *</label>
                <input
                    id="device-type-input-{{ $mode }}"
                    type="text"
                    name="type_name"
                    value="{{ old('type_name', $type?->type_name ?? '') }}"
                    class="crud-control"
                    maxlength="30"
                    required
                    autofocus
                    data-device-type-input
                    placeholder="Piemēram: Dators, Monitors, Klaviatūra"
                >
                <div class="device-type-modal-field-note">Nosaukumam jābūt unikālam un skaidri saprotamam lietotājiem.</div>

                <div
                    class="device-type-modal-error hidden"
                    data-device-type-input-error
                >
                </div>
            </div>
        </div>
    </div>

    <div class="device-type-modal-actions">
        <div class="device-type-modal-actions-copy">
            <div class="device-type-modal-actions-title">{{ $mode === 'create' ? 'Saglabāt jauno tipu' : 'Saglabāt izmaiņas' }}</div>
            <div class="device-type-modal-actions-text">
                {{ $mode === 'create'
                    ? 'Pēc saglabāšanas tips uzreiz būs pieejams visās ierīču formās.'
                    : 'Nosaukuma izmaiņas uzreiz atspogulosies visur, kur šis tips tiek izmantots.' }}
            </div>
        </div>

        <div class="device-type-modal-actions-buttons">
            <button type="button" class="btn-clear" data-close-modal>
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
