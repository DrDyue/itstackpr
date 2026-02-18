<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create device</title>
</head>
<body>
    <h1>Create device</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('devices.store') }}">
        @csrf

        <p>Code:<br><input name="code" value="{{ old('code') }}"></p>
        <p>Name*:<br><input name="name" value="{{ old('name') }}"></p>

        <p>Type*:<br>
            <select name="device_type_id">
                @foreach ($types as $t)
                    <option value="{{ $t->id }}" @selected(old('device_type_id') == $t->id)>{{ $t->type_name }}</option>
                @endforeach
            </select>
        </p>

        <p>Model*:<br><input name="model" value="{{ old('model') }}"></p>

        <p>Status*:<br>
            <select name="status_id">
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(old('status_id', 'active') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </p>

        <p>Building:<br>
            <select name="building_id">
                <option value="">-- none --</option>
                @foreach ($buildings as $b)
                    <option value="{{ $b->id }}" @selected(old('building_id') == $b->id)>{{ $b->building_name }}</option>
                @endforeach
            </select>
        </p>

        <p>Room:<br>
            <select name="room_id">
                <option value="">-- none --</option>
                @foreach ($rooms as $r)
                    <option value="{{ $r->id }}" @selected(old('room_id') == $r->id)>
                        {{ $r->building?->building_name }} / {{ $r->room_number }}
                    </option>
                @endforeach
            </select>
        </p>

        <p>Assigned to:<br><input name="assigned_to" value="{{ old('assigned_to') }}"></p>

        <p>Purchase date*:<br><input type="date" name="purchase_date" value="{{ old('purchase_date') }}"></p>
        <p>Purchase price:<br><input name="purchase_price" value="{{ old('purchase_price') }}"></p>

        <p>Warranty until:<br><input type="date" name="warranty_until" value="{{ old('warranty_until') }}"></p>
        <p>Warranty photo name:<br><input name="warranty_photo_name" value="{{ old('warranty_photo_name') }}"></p>

        <p>Serial number:<br><input name="serial_number" value="{{ old('serial_number') }}"></p>
        <p>Manufacturer:<br><input name="manufacturer" value="{{ old('manufacturer') }}"></p>

        <p>Notes:<br><textarea name="notes">{{ old('notes') }}</textarea></p>
        <p>Image URL:<br><textarea name="device_image_url">{{ old('device_image_url') }}</textarea></p>

        <button type="submit">Save</button>
        <a href="{{ route('devices.index') }}">Back</a>
    </form>
</body>
</html>
