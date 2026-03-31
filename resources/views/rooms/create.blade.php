{{--
    Lapa: Jaunas telpas izveide.
    Atbildība: ļauj administratoram izveidot telpu, piesaistīt to ēkai un vajadzības gadījumā atbildīgajam.
    Datu avots: RoomController@create, saglabāšana caur RoomController@store.
    Galvenās daļas:
    1. Hero ar lapas nozīmi.
    2. Validācijas kopsavilkums.
    3. Telpas datu forma.
--}}
<x-app-layout>
    @php
        $buildingOptions = $buildings->map(fn ($building) => [
            'value' => (string) $building->id,
            'label' => $building->building_name,
            'description' => $building->city ?: '',
            'search' => implode(' ', array_filter([$building->building_name, $building->city, $building->address])),
        ])->values();
        $userOptions = $users->map(fn ($roomUser) => [
            'value' => (string) $roomUser->id,
            'label' => $roomUser->full_name,
            'description' => $roomUser->job_title ?: '',
            'search' => implode(' ', array_filter([$roomUser->full_name, $roomUser->job_title, $roomUser->email])),
        ])->values();
        $selectedBuildingLabel = optional($buildings->firstWhere('id', (int) old('building_id')))->building_name;
        $selectedUserLabel = old('user_id') !== '' ? optional($users->firstWhere('id', (int) old('user_id')))->full_name : null;
    @endphp
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="room" size="h-4 w-4" /><span>Jauna telpa</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="room" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauna telpa</h1>
                            <p class="page-subtitle">Pievieno telpu un, ja vajag, piesaisti atbildīgo lietotāju.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        <x-validation-summary />

        {{-- Telpas forma piesaista telpu ēkai, stāvam un atbildīgajam lietotājam. --}}
        <form method="POST" action="{{ route('rooms.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Ēka</span>
                    <x-searchable-select
                        name="building_id"
                        query-name="building_query"
                        identifier="room-create-building"
                        :options="$buildingOptions"
                        :selected="(string) old('building_id')"
                        :query="$selectedBuildingLabel"
                        placeholder="Izvēlies ēku"
                        empty-message="Neviena ēka neatbilst meklējumam."
                    />
                </label>
                <label class="block">
                    <span class="crud-label">Stavs</span>
                    <input type="number" name="floor_number" value="{{ old('floor_number') }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Telpas numurs</span>
                    <input type="text" name="room_number" value="{{ old('room_number') }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Telpas nosaukums</span>
                    <input type="text" name="room_name" value="{{ old('room_name') }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Atbildīgais lietotājs</span>
                    <x-searchable-select
                        name="user_id"
                        query-name="user_query"
                        identifier="room-create-user"
                        :options="$userOptions"
                        :selected="(string) old('user_id')"
                        :query="$selectedUserLabel"
                        placeholder="Izvēlies atbildīgo"
                        empty-message="Neviens lietotājs neatbilst meklējumam."
                    />
                </label>
                <label class="block">
                    <span class="crud-label">Nodaļa</span>
                    <input type="text" name="department" value="{{ old('department') }}" class="crud-control">
                </label>
                <label class="block md:col-span-2">
                    <span class="crud-label">Piezīmes</span>
                    <textarea name="notes" rows="4" class="crud-control">{{ old('notes') }}</textarea>
                </label>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-create"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                <a href="{{ route('rooms.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

