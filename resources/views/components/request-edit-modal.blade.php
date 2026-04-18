@props([
    'type',
    'modalName',
    'requestModel',
    'fieldName',
    'fieldLabel',
    'action',
])

@php
    $typeLabels = [
        'repair' => 'remonta pieteikumu',
        'writeoff' => 'norakstīšanas pieteikumu',
        'transfer' => 'pārsūtīšanas pieteikumu',
    ];

    $iconMap = [
        'repair' => 'repair-request',
        'writeoff' => 'writeoff',
        'transfer' => 'transfer',
    ];

    $titleMap = [
        'repair' => 'Labot remonta pieteikumu',
        'writeoff' => 'Labot norakstīšanas pieteikumu',
        'transfer' => 'Labot pārsūtīšanas pieteikumu',
    ];

    $subtitleMap = [
        'repair' => 'Vari mainīt tikai problēmas aprakstu, kamēr pieteikums vēl nav izskatīts.',
        'writeoff' => 'Vari mainīt tikai norakstīšanas iemeslu, kamēr pieteikums vēl nav izskatīts.',
        'transfer' => 'Vari mainīt tikai nodošanas iemeslu, kamēr pieteikums vēl nav izskatīts.',
    ];

    $modalForm = $type . '_request_edit_' . $requestModel->id;
    $title = $titleMap[$type] ?? 'Labot pieprasījumu';
    $subtitle = $subtitleMap[$type] ?? 'Vari labot tikai vienu teksta lauku.';
    $icon = $iconMap[$type] ?? 'edit';
@endphp

<x-modal :name="$modalName" maxWidth="3xl">
    <form method="POST" action="{{ $action }}" class="flex max-h-[calc(100vh-2.5rem)] flex-col overflow-hidden">
        @csrf
        @method('PATCH')

        <input type="hidden" name="modal_form" value="{{ $modalForm }}">

        <div class="device-type-modal-head">
            <div class="device-type-modal-head-copy">
                <div class="device-type-modal-badge">
                    <x-icon name="edit" size="h-4 w-4" />
                    <span>Rediģēšana</span>
                </div>
                <div class="device-type-modal-title-row">
                    <div class="device-type-modal-icon">
                        <x-icon :name="$icon" size="h-6 w-6" />
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
            @if (old('modal_form') === $modalForm && $errors->any())
                <x-validation-summary
                    class="mb-5"
                    :title="'Neizdevās saglabāt ' . ($typeLabels[$type] ?? 'pieprasījumu')"
                    :field-labels="[$fieldName => $fieldLabel]"
                />
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ierīce</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">{{ $requestModel->device?->name ?: 'Ierīce nav atrasta' }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $requestModel->device?->code ?: 'bez koda' }}</div>
                </div>
                <div class="rounded-[1.5rem] border border-sky-200 bg-sky-50/80 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-800">Piezīme</div>
                    <div class="mt-2 text-sm leading-6 text-sky-900">
                        Šeit vari mainīt tikai tekstu. Ierīce un pārējie saistītie dati paliek nemainīgi.
                    </div>
                </div>
            </div>

            <label class="mt-5 block">
                <span class="crud-label">{{ $fieldLabel }}</span>
                <textarea name="{{ $fieldName }}" rows="7" class="crud-control" required>{{ old($fieldName, (string) ($requestModel->{$fieldName} ?? '')) }}</textarea>
                @error($fieldName)
                    @if (old('modal_form') === $modalForm)
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @endif
                @enderror
            </label>
        </div>

        <div class="device-type-modal-actions justify-between gap-4">
            <div class="text-sm text-slate-500">
                Tiks atjaunots tikai pieprasījuma teksts.
            </div>

            <div class="device-type-modal-actions-buttons">
                <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', '{{ $modalName }}')">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Atcelt</span>
                </button>
                <button type="submit" class="btn-create">
                    <x-icon name="save" size="h-4 w-4" />
                    <span>Saglabāt izmaiņas</span>
                </button>
            </div>
        </div>
    </form>
</x-modal>
