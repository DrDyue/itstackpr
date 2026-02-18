<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create set</title>
</head>
<body>
<h1>Create device set</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('device-sets.store') }}">
    @csrf

    <p>Set name*:<br><input name="set_name" value="{{ old('set_name') }}"></p>
    <p>Set code* (e.g. KIT-0001):<br><input name="set_code" value="{{ old('set_code') }}"></p>

    <p>Status*:<br>
        <select name="status">
            @foreach($statuses as $st)
                <option value="{{ $st }}" @selected(old('status','draft')===$st)>{{ $st }}</option>
            @endforeach
        </select>
    </p>

    <p>Room:<br>
        <select name="room_id">
            <option value="">-- none --</option>
            @foreach($rooms as $r)
                <option value="{{ $r->id }}" @selected(old('room_id')==$r->id)>
                    {{ $r->building?->building_name }} / {{ $r->room_number }}
                </option>
            @endforeach
        </select>
    </p>

    <p>Assigned to:<br><input name="assigned_to" value="{{ old('assigned_to') }}"></p>
    <p>Notes:<br><textarea name="notes">{{ old('notes') }}</textarea></p>

    <button type="submit">Save</button>
    <a href="{{ route('device-sets.index') }}">Back</a>
</form>
</body>
</html>
