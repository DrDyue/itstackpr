<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Device Types</title>
</head>
<body>
    <h1>Device Types</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <p><a href="{{ route('device-types.create') }}">+ Add device type</a></p>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type name</th>
                <th>Category</th>
                <th>Lifetime (years)</th>
                <th>Icon</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($types as $t)
            <tr>
                <td>{{ $t->id }}</td>
                <td>{{ $t->type_name }}</td>
                <td>{{ $t->category }}</td>
                <td>{{ $t->expected_lifetime_years }}</td>
                <td>{{ $t->icon_name }}</td>
                <td>
                    <a href="{{ route('device-types.edit', $t) }}">Edit</a>

                    <form action="{{ route('device-types.destroy', $t) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No device types yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
