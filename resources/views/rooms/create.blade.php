<x-app-layout>
    <section class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Jauna telpa</h1>
                <p class="mt-2 text-sm text-slate-600">Pievieno telpu un, ja vajag, piesaisti atbildīgo lietotāju.</p>
            </div>
            <a href="{{ route('rooms.index') }}" class="crud-btn-secondary">Atpakal</a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('rooms.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Eka</span>
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
                    <span class="crud-label">Atbildigais lietotajs</span>
                    <select name="user_id" class="crud-control">
                        <option value="">Nav piesaistits</option>
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
                    <span class="crud-label">Piezimes</span>
                    <textarea name="notes" rows="4" class="crud-control">{{ old('notes') }}</textarea>
                </label>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Saglabat</button>
                <a href="{{ route('rooms.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
