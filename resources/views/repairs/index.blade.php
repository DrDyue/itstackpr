<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Repairs</title>
</head>
<body>
    <h1>Repairs</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <form method="GET" action="{{ route('repairs.index') }}">
        <label>Status:</label>
        <select name="status">
            <option value="">-- all --</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
        <a href="{{ route('repairs.index') }}">Reset</a>
    </form>

    <p><a href="{{ route('repairs.create') }}">+ Add repair</a></p>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>ID</th>
                <th>Device</th>
                <th>Status</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Start</th>
                <th>Est. complete</th>
                <th>Cost</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($repairs as $r)
            <tr>
                <td>{{ $r->id }}</td>
                <td>
                    {{ $r->device?->code }} — {{ $r->device?->name }}
                </td>
                <td>{{ $r->status }}</td>
                <td>{{ $r->repair_type }}</td>
                <td>{{ $r->priority }}</td>
                <td>{{ $r->start_date }}</td>
                <td>{{ $r->estimated_completion }}</td>
                <td>{{ $r->cost }}</td>
                <td>
                    <a href="{{ route('repairs.edit', $r) }}">Edit</a>
                    <form action="{{ route('repairs.destroy', $r) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="9">No repairs yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
