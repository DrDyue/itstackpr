<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediģēt telpu</h1>
            <a href="{{ route('rooms.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
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
                <label class="crud-label">&#274;ka *</label>
                <select name="building_id" class="crud-control" required>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected(old('building_id', $room->building_id) == $building->id)>{{ $building->building_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Stāvs *</label>
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
                    <label class="crud-label">Atbildīgais darbinieks</label>
                    <select name="employee_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id', $room->employee_id) == $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="crud-label">Nodaļa</label>
                <input type="text" name="department" value="{{ old('department', $room->department) }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezīmes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes', $room->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Atjaunināt</button>
                <a href="{{ route('rooms.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


