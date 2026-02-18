<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create room</title>
</head>
<body>
    <h1>Create room</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('rooms.store') }}">
        @csrf

        <p>
            Building*:<br>
            <select name="building_id">
                @foreach ($buildings as $b)
                    <option value="{{ $b->id }}" @selected(old('building_id') == $b->id)>
                        {{ $b->building_name }}
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            Floor*:<br>
            <input type="number" name="floor_number" value="{{ old('floor_number') }}">
        </p>

        <p>
            Room number*:<br>
            <input name="room_number" value="{{ old('room_number') }}">
        </p>

        <p>
            Room name:<br>
            <input name="room_name" value="{{ old('room_name') }}">
        </p>

        <p>
            Responsible employee:<br>
            <select name="employee_id">
                <option value="">-- none --</option>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>
                        {{ $e->full_name }}
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            Department:<br>
            <input name="department" value="{{ old('department') }}">
        </p>

        <p>
            Notes:<br>
            <input name="notes" value="{{ old('notes') }}">
        </p>

        <button type="submit">Save</button>
        <a href="{{ route('rooms.index') }}">Back</a>
    </form>
</body>
</html>
