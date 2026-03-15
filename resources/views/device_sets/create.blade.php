<x-app-layout>
    @php
        $statusLabels = [
            'draft' => 'Melnraksts',
            'active' => 'Aktivs',
            'returned' => 'Atgriezts',
            'archived' => 'Arhivets',
        ];
    @endphp

    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauns komplekts</h1>
            <a href="{{ route('device-sets.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
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
                <label class="crud-label">Atbildiga persona</label>
                <input type="text" name="assigned_to" value="{{ old('assigned_to') }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezimes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Saglabat
                </button>
                <a href="{{ route('device-sets.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                    Atcelt
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
