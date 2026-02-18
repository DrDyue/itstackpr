<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rooms</title>
</head>
<body>
    <h1>Rooms</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <p><a href="{{ route('rooms.create') }}">+ Add room</a></p>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>ID</th>
                <th>Building</th>
                <th>Floor</th>
                <th>Room №</th>
                <th>Name</th>
                <th>Responsible</th>
                <th>Department</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($rooms as $r)
            <tr>
                <td>{{ $r->id }}</td>
                <td>{{ $r->building?->building_name }}</td>
                <td>{{ $r->floor_number }}</td>
                <td>{{ $r->room_number }}</td>
                <td>{{ $r->room_name }}</td>
                <td>{{ $r->employee?->full_name }}</td>
                <td>{{ $r->department }}</td>
                <td>
                    <a href="{{ route('rooms.edit', $r) }}">Edit</a>

                    <form action="{{ route('rooms.destroy', $r) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">No rooms yet</td></tr>
        @endforelse
        </tbody>
    </table>

    <p><a href="{{ url('/') }}">Home</a></p>
</body>
</html>
