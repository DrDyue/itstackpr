@props([
    'mode' => 'create',
    'modalName',
    'room' => null,
    'buildingOptions' => [],
    'userOptions' => [],
    'buildings' => collect(),
    'responsibleUsers' => collect(),
])

@php
    $isEdit = $mode === 'edit' && $room;
    $modalForm = $isEdit ? 'room_edit_' . $room->id : 'room_create';
    $shouldUseOldInput = old('modal_form') === $modalForm;
    $action = $isEdit ? route('rooms.update', $room) : route('rooms.store');
    $title = $isEdit ? 'Rediģēt telpu' : 'Jauna telpa';
    $subtitle = $isEdit
        ? trim($room->room_number . ($room->room_name ? ' — ' . $room->room_name : ''))
        : 'Izveido telpu tieši no saraksta lapas.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Saglabāt';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
    $identifierPrefix = $isEdit ? 'room-modal-edit-' . $room->id : 'room-modal-create';
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
                    :title="$isEdit ? 'Neizdevās saglabāt telpas izmaiņas' : 'Neizdevās izveidot telpu'"
                    :field-labels="[
                        'building_id' => 'Ēka',
                        'floor_number' => 'Stāvs',
                        'room_number' => 'Telpas numurs',
                        'room_name' => 'Telpas nosaukums',
                        'user_id' => 'Atbildīgais lietotājs',
                        'department' => 'Nodaļa',
                        'notes' => 'Piezīmes',
                    ]"
                />
            @endif

            @include('rooms.partials.form-fields', [
                'room' => $room,
                'useOldInput' => $shouldUseOldInput,
                'identifierPrefix' => $identifierPrefix,
                'buildingOptions' => $buildingOptions,
                'userOptions' => $userOptions,
                'buildings' => $buildings,
                'responsibleUsers' => $responsibleUsers,
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
