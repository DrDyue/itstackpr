@props([
    'mode' => 'create',
    'modalName',
    'building' => null,
])

@php
    $isEdit = $mode === 'edit' && $building;
    $modalForm = $isEdit ? 'building_edit_' . $building->id : 'building_create';
    $shouldUseOldInput = old('modal_form') === $modalForm;
    $action = $isEdit ? route('buildings.update', $building) : route('buildings.store');
    $title = $isEdit ? 'Rediģēt ēku' : 'Jauna ēka';
    $subtitle = $isEdit
        ? ($building->building_name ?: 'Atjauno ēkas pamatdatus, neizejot no saraksta.')
        : 'Pievieno ēkas ierakstu, neizejot no saraksta lapas.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Saglabāt';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
@endphp

<x-modal :name="$modalName" maxWidth="2xl" focusable>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>

        <form method="POST" action="{{ $action }}" class="mt-5 space-y-4">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <input type="hidden" name="modal_form" value="{{ $modalForm }}">

            @if ($shouldUseOldInput && $errors->any())
                <x-validation-summary
                    class="mb-5"
                    :title="$isEdit ? 'Neizdevās saglabāt ēkas izmaiņas' : 'Neizdevās izveidot ēku'"
                    :field-labels="[
                        'building_name' => 'Nosaukums',
                        'city' => 'Pilsēta',
                        'total_floors' => 'Stāvu skaits',
                        'address' => 'Adrese',
                        'notes' => 'Piezīmes',
                    ]"
                />
            @endif

            @include('buildings.partials.form-fields', [
                'building' => $building,
                'useOldInput' => $shouldUseOldInput,
            ])

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', '{{ $modalName }}')">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Atcelt</span>
                </button>
                <button type="submit" class="{{ $submitClass }}">
                    <x-icon name="save" size="h-4 w-4" />
                    <span>{{ $submitLabel }}</span>
                </button>
            </div>
        </form>
    </div>
</x-modal>
