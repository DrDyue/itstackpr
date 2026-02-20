<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauna telpa</h1>
            <a href="{{ route('rooms.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpaka uz sarakstu</a>
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

        <form method="POST" action="{{ route('rooms.store') }}" class="crud-form-card">
            @csrf

            <div>
                <label class="crud-label">'ka *</label>
                <select name="building_id" class="crud-control" required>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected(old('building_id') == $building->id)>{{ $building->building_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Stvs *</label>
                    <input type="number" name="floor_number" value="{{ old('floor_number') }}" class="crud-control" required>
                </div>
                <div>
                    <label class="crud-label">Telpas numurs *</label>
                    <input type="text" name="room_number" value="{{ old('room_number') }}" class="crud-control" required>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Telpas nosaukums</label>
                    <input type="text" name="room_name" value="{{ old('room_name') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Atbildgais darbinieks</label>
                    <select name="employee_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="crud-label">Nodaa</label>
                <input type="text" name="department" value="{{ old('department') }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezmes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabt</button>
                <a href="{{ route('rooms.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


