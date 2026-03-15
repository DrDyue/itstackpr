<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediget telpu</h1>
            <a href="{{ route('rooms.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakal uz sarakstu
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('rooms.update', $room) }}" class="crud-form-card">
            @csrf
            @method('PUT')

            <div>
                <label class="crud-label">Eka *</label>
                <select name="building_id" class="crud-control" required>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected(old('building_id', $room->building_id) == $building->id)>{{ $building->building_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Stavs *</label>
                    <input type="number" name="floor_number" value="{{ old('floor_number', $room->floor_number) }}" class="crud-control" required>
                </div>
                <div>
                    <label class="crud-label">Telpas numurs *</label>
                    <input type="text" name="room_number" value="{{ old('room_number', $room->room_number) }}" class="crud-control" required>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Telpas nosaukums</label>
                    <input type="text" name="room_name" value="{{ old('room_name', $room->room_name) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Atbildigais darbinieks</label>
                    <select name="employee_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id', $room->employee_id) == $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="crud-label">Nodala</label>
                <input type="text" name="department" value="{{ old('department', $room->department) }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezimes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes', $room->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Atjauninat
                </button>
                <a href="{{ route('rooms.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                    Atcelt
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
