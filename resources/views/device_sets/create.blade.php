<x-app-layout>
    @php
        $statusLabels = [
            'draft' => 'Melnraksts',
            'active' => 'Aktīvs',
            'returned' => 'Atgriezts',
            'archived' => 'Arhivēts',
        ];
    @endphp

    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauns komplekts</h1>
            <a href="{{ route('device-sets.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
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

        <form method="POST" action="{{ route('device-sets.store') }}" class="crud-form-card">
            @csrf

            <div>
                <label class="crud-label">Komplekta nosaukums *</label>
                <input type="text" name="set_name" value="{{ old('set_name') }}" required class="crud-control">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Statuss *</label>
                    <select name="status" required class="crud-control">
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Telpa</label>
                    <select name="room_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="crud-label">Atbildīgā persona</label>
                <input type="text" name="assigned_to" value="{{ old('assigned_to') }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezīmes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabāt</button>
                <a href="{{ route('device-sets.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


