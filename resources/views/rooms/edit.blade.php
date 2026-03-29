{{--
    Lapa: Telpas rediģēšana.
    Atbildība: ļauj mainīt telpas datus, piesaisti ēkai vai atbildīgajam lietotājam.
    Datu avots: RoomController@edit, saglabāšana caur RoomController@update.
    Galvenās daļas:
    1. Hero zona.
    2. Validācijas paziņojumi.
    3. Telpas rediģēšanas forma.
--}}
<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="room" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediget telpu</h1>
                            <p class="page-subtitle">Atjauno telpas datus un atbildigo lietotaju.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakal</span></a>
            </div>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('rooms.update', $room) }}" class="surface-card space-y-6 p-6">
            @csrf
            @method('PUT')
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Eka</span>
                    <select name="building_id" class="crud-control" required>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected(old('building_id', $room->building_id) == $building->id)>{{ $building->building_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Stavs</span>
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
                    <span class="crud-label">Atbildigais lietotajs</span>
                    <select name="user_id" class="crud-control">
                        <option value="">Nav piesaistits</option>
                        @foreach ($users as $roomUser)
                            <option value="{{ $roomUser->id }}" @selected(old('user_id', $room->user_id) == $roomUser->id)>{{ $roomUser->full_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Nodala</span>
                    <input type="text" name="department" value="{{ old('department', $room->department) }}" class="crud-control">
                </label>
                <label class="block md:col-span-2">
                    <span class="crud-label">Piezimes</span>
                    <textarea name="notes" rows="4" class="crud-control">{{ old('notes', $room->notes) }}</textarea>
                </label>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Saglabat</span></button>
                <a href="{{ route('rooms.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

