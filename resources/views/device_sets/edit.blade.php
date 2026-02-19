<x-app-layout>
    @php
        $statusLabels = [
            'draft' => 'Melnraksts',
            'active' => 'Aktīvs',
            'returned' => 'Atgriezts',
            'archived' => 'Arhivēts',
        ];
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediģēt komplektu: {{ $set->set_name }}</h1>
            <a href="{{ route('device-sets.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('device-sets.update', $set) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Komplekta nosaukums *</label>
                    <input type="text" name="set_name" value="{{ old('set_name', $set->set_name) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Statuss *</label>
                        <select name="status" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $set->status) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Telpa</label>
                        <select name="room_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Nav</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $set->room_id) == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Atbildīgā persona</label>
                    <input type="text" name="assigned_to" value="{{ old('assigned_to', $set->assigned_to) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Piezīmes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $set->notes) }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Atjaunināt</button>
                    <a href="{{ route('device-sets.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Atcelt</a>
                </div>
            </form>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h2 class="text-base font-semibold text-gray-900">Komplekta ierīces</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Ierīce</th>
                                <th class="px-4 py-3 text-left">Daudzums</th>
                                <th class="px-4 py-3 text-left">Darbības</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($set->items as $item)
                                <tr>
                                    <td class="px-4 py-3">{{ $item->device?->code }} - {{ $item->device?->name }}</td>
                                    <td class="px-4 py-3">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('device-sets.items.delete', [$set, $item]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Noņemt ierīci no komplekta?')" class="text-red-600 hover:text-red-700">Noņemt</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Komplekts ir tukšs.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="add-device-form" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-base font-semibold text-gray-900">Pievienot ierīci komplektam</h2>
                <form method="POST" action="{{ route('device-sets.items.add', $set) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Ierīce *</label>
                        <select name="device_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->code }} - {{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Daudzums *</label>
                        <input type="number" name="quantity" value="1" min="1" max="999" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Pievienot</button>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>

