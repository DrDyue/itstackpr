<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Buildings</title>
</head>
<body>
    <h1>Buildings</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <p><a href="{{ route('buildings.create') }}">+ Add building</a></p>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>City</th>
                <th>Address</th>
                <th>Floors</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($buildings as $b)
            <tr>
                <td>{{ $b->id }}</td>
                <td>{{ $b->building_name }}</td>
                <td>{{ $b->city }}</td>
                <td>{{ $b->address }}</td>
                <td>{{ $b->total_floors }}</td>
                <td>
                    <a href="{{ route('buildings.edit', $b) }}">Edit</a>

                    <form action="{{ route('buildings.destroy', $b) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No buildings yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
