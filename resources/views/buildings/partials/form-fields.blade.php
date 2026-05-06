@props([
    'building' => null,
    'useOldInput' => false,
])

@php
    // `value()` izvēlas starp `old()` un esošo modeļa vērtību.
    // Tas saglabā lietotāja ievadi, ja forma pēc validācijas kļūdas tiek atvērta vēlreiz.
    $value = function (string $field, mixed $default = '') use ($building, $useOldInput) {
        if ($useOldInput) {
            return old($field, $default);
        }

        return $building?->{$field} ?? $default;
    };
@endphp

<x-ui.form-field label="Nosaukums" name="building_name" :required="true">
    <input type="text" name="building_name" value="{{ $value('building_name') }}" maxlength="100" class="crud-control" required>
</x-ui.form-field>

<div class="grid gap-4 sm:grid-cols-2">
    <x-ui.form-field label="Pilsēta" name="city">
        <input type="text" name="city" value="{{ $value('city') }}" maxlength="100" class="crud-control">
    </x-ui.form-field>
    <x-ui.form-field label="Stāvu skaits" name="total_floors">
        <input type="number" min="0" max="200" step="1" name="total_floors" value="{{ $value('total_floors') }}" class="crud-control">
    </x-ui.form-field>
</div>

<x-ui.form-field label="Adrese" name="address">
    <input type="text" name="address" value="{{ $value('address') }}" maxlength="200" class="crud-control">
</x-ui.form-field>

<x-ui.form-field label="Piezīmes" name="notes">
    <textarea name="notes" rows="3" maxlength="200" class="crud-control">{{ $value('notes') }}</textarea>
</x-ui.form-field>
