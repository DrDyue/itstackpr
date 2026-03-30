{{--
    Lapa: Jaunas telpas izveide.
    Atbildība: ļauj administratoram izveidot telpu, piešaistīt to ēkai un vajadzības gadījumā atbildīgajam.
    Datu avots: RoomController@create, saglabāšana caur RoomController@store.
    Galvenās daļas:
    1. Hero ar lapas nozīmi.
    2. Validācijas kopsavilkums.
    3. Telpas datu forma.
--}}
<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="room" size="h-4 w-4" /><span>Jauna telpa</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="room" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauna telpa</h1>
                            <p class="page-subtitle">Pievieno telpu un, ja vajag, piešaisti atbildīgo lietotāju.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        <x-validation-summary />

        {{-- Telpas forma piešaista telpu ēkai, stāvam un atbildīgajam lietotājam. --}}
        <form method="POST" action="{{ route('rooms.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Ēka</span>
                    <select name="building_id" class="crud-control" required>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected(old('building_id') == $building->id)>{{ $building->building_name }}</option>
                        @endforeach
                    </select>
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
                    <select name="user_id" class="crud-control">
                        <option value="">Nav piešaistīts</option>
                        @foreach ($users as $roomUser)
                            <option value="{{ $roomUser->id }}" @selected(old('user_id') == $roomUser->id)>{{ $roomUser->full_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Nodala</span>
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

