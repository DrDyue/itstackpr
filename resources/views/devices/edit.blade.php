<x-app-layout>
    @php
        $statusLabels = [
            'active' => 'Aktīva',
            'reserve' => 'Rezervē',
            'broken' => 'Bojāta',
            'repair' => 'Remontā',
            'retired' => 'Norakstīta',
            'kitting' => 'Komplektācijā',
        ];
    @endphp

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediģēt ierīci</h1>
            <a href="{{ route('devices.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
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

        <form method="POST" action="{{ route('devices.update', $device) }}" class="crud-form-card">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Kods</label>
                    <input type="text" name="code" value="{{ old('code', $device->code) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Nosaukums *</label>
                    <input type="text" name="name" value="{{ old('name', $device->name) }}" required class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="crud-label">Tips *</label>
                    <select name="device_type_id" required class="crud-control">
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('device_type_id', $device->device_type_id) == $type->id)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Modelis *</label>
                    <input type="text" name="model" value="{{ old('model', $device->model) }}" required class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Statuss *</label>
                    <select name="status" required class="crud-control">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $device->status) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">&#274;ka</label>
                    <select name="building_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected(old('building_id', $device->building_id) == $building->id)>{{ $building->building_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Telpa</label>
                    <select name="room_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $device->room_id) == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Piešķirta personai</label>
                    <input type="text" name="assigned_to" value="{{ old('assigned_to', $device->assigned_to) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Ražotājs</label>
                    <input type="text" name="manufacturer" value="{{ old('manufacturer', $device->manufacturer) }}" class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="crud-label">Pirkuma datums *</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', $device->purchase_date) }}" required class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Cena</label>
                    <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $device->purchase_price) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Garantija līdz</label>
                    <input type="date" name="warranty_until" value="{{ old('warranty_until', $device->warranty_until) }}" class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Garantijas faila nosaukums</label>
                    <input type="text" name="warranty_photo_name" value="{{ old('warranty_photo_name', $device->warranty_photo_name) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Sērijas numurs</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}" class="crud-control">
                </div>
            </div>

            <div>
                <label class="crud-label">Piezīmes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes', $device->notes) }}</textarea>
            </div>

            <div>
                <label class="crud-label">Attēla URL</label>
                <input type="text" name="device_image_url" value="{{ old('device_image_url', $device->device_image_url) }}" class="crud-control">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Atjaunināt</button>
                <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


