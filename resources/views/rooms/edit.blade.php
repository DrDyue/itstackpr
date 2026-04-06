{--
    Lapa: Telpas rediģēšana.
    Atbildība: ļauj mainīt telpas datus, piesaisti ēkai vai atbildīgajam lietotājam.
    Datu avots: RoomController@edit, saglabāšana caur RoomController@update.
    Galvenās daļas:
    1. Hero zona.
    2. Validācijas paziņojumi.
    3. Telpas rediģēšanas forma.
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
        $selectedBuildingLabel = optional($buildings->firstWhere('id', (int) old('building_id', $room->building_id)))->building_name;
        $selectedUserLabel = old('user_id', $room->user_id) !== null && old('user_id', $room->user_id) !== ''
            ? optional($users->firstWhere('id', (int) old('user_id', $room->user_id)))->full_name
            : null;
    @endphp
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labošana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="room" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediģēt telpu</h1>
                            <p class="page-subtitle">Atjauno telpas datus un atbildīgo lietotāju.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="form-page-grid">
                <div class="form-page-main">
                    <div class="surface-card space-y-6 p-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="crud-label">Ēka</span>
                                <x-searchable-select
                                    name="building_id"
                                    query-name="building_query"
                                    identifier="room-edit-building"
                                    :options="$buildingOptions"
                                    :selected="(string) old('building_id', $room->building_id)"
                                    :query="$selectedBuildingLabel"
                                    placeholder="Izvēlies ēku"
                                    empty-message="Neviena ēka neatbilst meklējumam."
                                />
                            </label>
                            <label class="block">
                                <span class="crud-label">Stāvs</span>
                                <input type="number" name="floor_number" value="{{ old('floor_number', $room->floor_number) }}" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">Telpas numurs</span>
                                <input type="text" name="room_number" value="{{ old('room_number', $room->room_number) }}" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">Telpas nosaukums</span>
                                <input type="text" name="room_name" value="{{ old('room_name', $room->room_name) }}" class="crud-control">
                            </label>
                            <label class="block">
                                <span class="crud-label">Atbildīgais lietotājs</span>
                                <x-searchable-select
                                    name="user_id"
                                    query-name="user_query"
                                    identifier="room-edit-user"
                                    :options="$userOptions"
                                    :selected="(string) old('user_id', $room->user_id)"
                                    :query="$selectedUserLabel"
                                    placeholder="Izvēlies atbildīgo"
                                    empty-message="Neviens lietotājs neatbilst meklējumam."
                                />
                            </label>
                            <label class="block">
                                <span class="crud-label">Nodaļa</span>
                                <input type="text" name="department" value="{{ old('department', $room->department) }}" class="crud-control">
                            </label>
                            <label class="block md:col-span-2">
                                <span class="crud-label">Piezīmes</span>
                                <textarea name="notes" rows="4" class="crud-control">{{ old('notes', $room->notes) }}</textarea>
                            </label>
                        </div>
                    </div>
                </div>

                <aside class="form-page-aside">
                    <div class="form-page-note">
                        <div class="form-page-note-title">Pirms saglabāšanas</div>
                        <div class="form-page-note-copy">Ja maini ēku vai telpas numuru, tas uzreiz ietekmēs ierīču piesaisti un telpas meklēšanu citās sadaļās.</div>
                    </div>
                </aside>
            </div>

            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Saglabā telpas izmaiņas</div>
                    <div class="form-page-actions-text">Atjaunotie telpas dati būs redzami ierīču kartītēs, filtros un telpu sarakstā.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                    <a href="{{ route('rooms.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
