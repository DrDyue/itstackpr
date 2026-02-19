<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Users</title>
</head>
<body>
    <h1>Users Management</h1>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    <form method="GET" action="{{ route('users.index') }}">
        <input name="q" placeholder="Search username/role..." value="{{ $q }}">
        <button type="submit">Search</button>
        <a href="{{ route('users.index') }}">Reset</a>
    </form>

    <p><a href="{{ route('users.create') }}">+ Add User</a></p>

    <table border="1" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($users as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->employee?->full_name }}</td>
                <td>{{ $u->employee?->email }}</td>
                <td>{{ $u->role }}</td>
                <td>{{ $u->is_active ? 'Active' : 'Inactive' }}</td>
                <td>{{ $u->last_login?->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="{{ route('users.edit', $u) }}">Edit</a>
                    <form action="{{ route('users.destroy', $u) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No users yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
