<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Device Set Items</title>
</head>
<body>
    <h1>Device Set Items</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <form method="GET" action="{{ route('device-set-items.index') }}">
        <input name="device_set_id" placeholder="Filter by device set ID..." value="{{ $deviceSetId }}">
        <button type="submit">Filter</button>
        <a href="{{ route('device-set-items.index') }}">Reset</a>
    </form>

    <p><a href="{{ route('device-set-items.create') }}">+ Add Item to Set</a></p>

    <table border="1" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>Device Set</th>
            <th>Device</th>
            <th>Role in Set</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->deviceSet?->name }}</td>
                <td>{{ $item->device?->name }} ({{ $item->device?->code }})</td>
                <td>{{ $item->role }}</td>
                <td>{{ $item->description }}</td>
                <td>
                    <a href="{{ route('device-set-items.edit', $item) }}">Edit</a>
                    <form action="{{ route('device-set-items.destroy', $item) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Remove from set?')">Remove</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No items in sets yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
