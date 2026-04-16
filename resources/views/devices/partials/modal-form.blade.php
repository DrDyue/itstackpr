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
        : 'Pievieno jaunu ierīci ar skaidri sakārtotu informāciju par modeli, atrašanās vietu un atbildīgo personu.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Izveidot ierīci';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
    $summaryTitle = $isEdit ? 'Saglabāt ierīces izmaiņas' : 'Saglabāt jauno ierīci';
    $summaryText = $isEdit
        ? 'Pārbaudi tikai mainītos laukus. Statusa un piesaistes lauki var būt bloķēti.'
        : 'Svarīgākais ir kods, nosaukums, tips, modelis, atbildīgā persona un telpa. Pārējo vari papildināt arī vēlāk.';
    $badgeLabel = $isEdit ? 'Rediģēšana' : 'Jauns ieraksts';
    $iconName = $isEdit ? 'edit' : 'plus';
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

        <div class="device-type-modal-body overflow-y-auto">
            @if ($shouldUseOldInput && $errors->any())
                <x-validation-summary class="mb-5" />
            @endif

            <div class="mb-5 rounded-[1.7rem] border border-sky-100 bg-white/85 px-5 py-4 text-sm leading-6 text-slate-600 shadow-sm">
                <div class="font-semibold text-slate-900">Lauku secība ir sakārtota pēc darba plūsmas</div>
                <div class="mt-1">Vispirms ievadi ierīces identitāti (kods, nosaukums, tips, modelis), tad piesaisti personu un telpu.</div>
            </div>

            @include('devices.partials.form-fields', [
                'device' => $device,
                'formKey' => $modalForm,
                'useOldInput' => $shouldUseOldInput,
            ])
        </div>

        <div class="device-type-modal-actions">
            <div class="device-type-modal-actions-copy">
                <div class="device-type-modal-actions-title">{{ $summaryTitle }}</div>
                <div class="device-type-modal-actions-text">{{ $summaryText }}</div>
            </div>

            <div class="device-type-modal-actions-buttons">
                <button type="button" class="btn-clear" data-close-modal>
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
