@php
    $current = $device;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="crud-label">Kods *</span>
        <input type="text" name="code" value="{{ old('code', $current?->code) }}" class="crud-control" required>
    </label>
    <label class="block">
        <span class="crud-label">Nosaukums</span>
        <input type="text" name="name" value="{{ old('name', $current?->name) }}" class="crud-control" required>
    </label>
    <label class="block">
        <span class="crud-label">Tips</span>
        <select name="device_type_id" class="crud-control" required>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected(old('device_type_id', $current?->device_type_id) == $type->id)>{{ $type->type_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Modelis</span>
        <input type="text" name="model" value="{{ old('model', $current?->model) }}" class="crud-control" required>
    </label>
    <label class="block">
        <span class="crud-label">Statuss</span>
        <select name="status" class="crud-control" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $current?->status ?? 'active') === $status)>{{ $statusLabels[$status] }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Pieskirtais lietotajs</span>
        <select name="assigned_to_id" class="crud-control">
            <option value="">Nav pieskirts</option>
            @foreach ($users as $assignedUser)
                <option value="{{ $assignedUser->id }}" @selected(old('assigned_to_id', $current?->assigned_to_id) == $assignedUser->id)>{{ $assignedUser->full_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Eka</span>
        <select name="building_id" class="crud-control">
            <option value="">Nav noradita</option>
            @foreach ($buildings as $building)
                <option value="{{ $building->id }}" @selected(old('building_id', $current?->building_id) == $building->id)>{{ $building->building_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Telpa</span>
        <select name="room_id" class="crud-control">
            <option value="">Nav noradita</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" @selected(old('room_id', $current?->room_id) == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Iegades datums</span>
        <input type="date" name="purchase_date" value="{{ old('purchase_date', $current?->purchase_date?->format('Y-m-d')) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Iegades cena</span>
        <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $current?->purchase_price) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Garantija lidz</span>
        <input type="date" name="warranty_until" value="{{ old('warranty_until', $current?->warranty_until?->format('Y-m-d')) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Serijas numurs</span>
        <input type="text" name="serial_number" value="{{ old('serial_number', $current?->serial_number) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Razotajs</span>
        <input type="text" name="manufacturer" value="{{ old('manufacturer', $current?->manufacturer) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Ierices attels</span>
        <input type="file" name="device_image" class="crud-control">
    </label>
    @if ($current)
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="remove_device_image" value="1" class="rounded border-gray-300 text-blue-600">
            <span class="text-sm text-slate-700">Nonemt ierices attelu</span>
        </label>
    @endif
    <label class="block md:col-span-2">
        <span class="crud-label">Piezimes</span>
        <textarea name="notes" rows="4" class="crud-control">{{ old('notes', $current?->notes) }}</textarea>
    </label>
</div>
