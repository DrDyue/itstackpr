<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Device Sets</title>
</head>
<body>
<h1>Device Sets</h1>

@if (session('success'))
    <p style="color: green">{{ session('success') }}</p>
@endif

<p><a href="{{ route('device-sets.create') }}">+ Add set</a></p>

<table border="1" cellpadding="6">
    <thead>
    <tr>
        <th>ID</th>
        <th>Set code</th>
        <th>Name</th>
        <th>Status</th>
        <th>Room</th>
        <th>Assigned to</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($sets as $s)
        <tr>
            <td>{{ $s->id }}</td>
            <td>{{ $s->set_code }}</td>
            <td>{{ $s->set_name }}</td>
            <td>{{ $s->status }}</td>
            <td>{{ $s->room?->room_number }}</td>
            <td>{{ $s->assigned_to }}</td>
            <td>
                <a href="{{ route('device-sets.edit', $s) }}">Edit</a>
                <form action="{{ route('device-sets.destroy', $s) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete set?')">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7">No sets yet</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
