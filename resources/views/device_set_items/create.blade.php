<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Item to Device Set</title>
</head>
<body>
    <h1>Add Item to Device Set</h1>
    <a href="{{ route('device-set-items.index') }}">← Back</a>

    @if ($errors->any())
        <div style="color: red; margin: 10px 0;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('device-set-items.store') }}">
        @csrf

        <div style="margin: 15px 0;">
            <label>Device Set *</label><br>
            <select name="device_set_id" required>
                <option value="">-- Select Device Set --</option>
                @foreach ($deviceSets as $set)
                    <option value="{{ $set->id }}" @selected(old('device_set_id') == $set->id)>
                        {{ $set->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="margin: 15px 0;">
            <label>Device *</label><br>
            <select name="device_id" required>
                <option value="">-- Select Device --</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>
                        {{ $device->name }} ({{ $device->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="margin: 15px 0;">
            <label>Role in Set</label><br>
            <input type="text" name="role" value="{{ old('role') }}" maxlength="50" placeholder="e.g., Main Computer, Monitor, Mouse">
        </div>

        <div style="margin: 15px 0;">
            <label>Description</label><br>
            <textarea name="description">{{ old('description') }}</textarea>
        </div>

        <button type="submit">Add Item</button>
        <a href="{{ route('device-set-items.index') }}">Cancel</a>
    </form>
</body>
</html>
