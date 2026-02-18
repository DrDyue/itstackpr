<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employees</title>
</head>
<body>
<h1>Employees</h1>

@if (session('success'))
    <p style="color: green">{{ session('success') }}</p>
@endif

<form method="GET" action="{{ route('employees.index') }}">
    <input name="q" value="{{ $q }}" placeholder="Search...">
    <button type="submit">Search</button>
    <a href="{{ route('employees.index') }}">Reset</a>
</form>

<p><a href="{{ route('employees.create') }}">+ Add employee</a></p>

<table border="1" cellpadding="6">
    <thead>
    <tr>
        <th>ID</th>
        <th>Full name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Job title</th>
        <th>Active</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($employees as $e)
        <tr>
            <td>{{ $e->id }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ $e->email }}</td>
            <td>{{ $e->phone }}</td>
            <td>{{ $e->job_title }}</td>
            <td>{{ $e->is_active ? 'Yes' : 'No' }}</td>
            <td>
                <a href="{{ route('employees.edit', $e) }}">Edit</a>
                <form action="{{ route('employees.destroy', $e) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete employee?')">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7">No employees yet</td></tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
