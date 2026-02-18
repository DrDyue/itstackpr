<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Devices</title>
</head>
<body>
    <h1>Devices</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <form method="GET" action="{{ route('devices.index') }}">
        <input name="q" placeholder="Search code/name/serial..." value="{{ $q }}">
        <button type="submit">Search</button>
        <a href="{{ route('devices.index') }}">Reset</a>
    </form>

    <p><a href="{{ route('devices.create') }}">+ Add device</a></p>

    <table border="1" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Building</th>
            <th>Room</th>
            <th>Serial</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($devices as $d)
            <tr>
                <td>{{ $d->id }}</td>
                <td>{{ $d->code }}</td>
                <td>{{ $d->name }}</td>
                <td>{{ $d->type?->type_name }}</td>
                <td>{{ $d->status_id }}</td>
                <td>{{ $d->building?->building_name }}</td>
                <td>{{ $d->room?->room_number }}</td>
                <td>{{ $d->serial_number }}</td>
                <td>
                    <a href="{{ route('devices.edit', $d) }}">Edit</a>
                    <form action="{{ route('devices.destroy', $d) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="9">No devices yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
