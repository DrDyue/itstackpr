@props([
    'room' => null,
    'useOldInput' => false,
    'identifierPrefix' => 'room-modal',
    'buildingOptions' => [],
    'userOptions' => [],
    'buildings' => collect(),
    'responsibleUsers' => collect(),
])

@php
    $value = function (string $field, mixed $default = '') use ($room, $useOldInput) {
        if ($useOldInput) {
            return old($field, $default);
        }

        return $room?->{$field} ?? $default;
    };

    $buildingValue = $value('building_id');
    $buildingLabel = $buildingValue !== null && $buildingValue !== ''
        ? optional($buildings->firstWhere('id', (int) $buildingValue))->building_name
        : null;
    $userValue = $value('user_id');
    $userLabel = $userValue !== null && $userValue !== ''
        ? optional($responsibleUsers->firstWhere('id', (int) $userValue))->full_name
        : null;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <x-ui.form-field label="Ēka" name="building_id" :required="true">
        <x-searchable-select
            name="building_id"
            query-name="building_query"
            identifier="{{ $identifierPrefix }}-building"
            :options="$buildingOptions"
            :selected="(string) $buildingValue"
            :query="$buildingLabel"
            placeholder="Izvēlies ēku"
            empty-message="Neviena ēka neatbilst meklējumam."
        />
    </x-ui.form-field>
    <x-ui.form-field label="Stāvs" name="floor_number" :required="true">
        <input type="number" name="floor_number" value="{{ $value('floor_number') }}" class="crud-control" required>
    </x-ui.form-field>
    <x-ui.form-field label="Telpas numurs" name="room_number" :required="true">
        <input type="text" name="room_number" value="{{ $value('room_number') }}" class="crud-control" required>
    </x-ui.form-field>
    <x-ui.form-field label="Telpas nosaukums" name="room_name">
        <input type="text" name="room_name" value="{{ $value('room_name') }}" class="crud-control">
    </x-ui.form-field>
    <x-ui.form-field label="Atbildīgais lietotājs" name="user_id">
        <x-searchable-select
            name="user_id"
            query-name="user_query"
            identifier="{{ $identifierPrefix }}-user"
            :options="$userOptions"
            :selected="(string) $userValue"
            :query="$userLabel"
            placeholder="Izvēlies atbildīgo"
            empty-message="Neviens lietotājs neatbilst meklējumam."
        />
    </x-ui.form-field>
    <x-ui.form-field label="Nodaļa" name="department">
        <input type="text" name="department" value="{{ $value('department') }}" class="crud-control">
    </x-ui.form-field>
    <x-ui.form-field class="md:col-span-2" label="Piezīmes" name="notes">
        <textarea name="notes" rows="3" class="crud-control">{{ $value('notes') }}</textarea>
    </x-ui.form-field>
</div>
