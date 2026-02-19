<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit User</title>
</head>
<body>
    <h1>Edit User</h1>
    <a href="{{ route('users.index') }}">← Back</a>

    @if ($errors->any())
        <div style="color: red; margin: 10px 0;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div style="margin: 15px 0;">
            <label>Employee *</label><br>
            <select name="employee_id" required>
                <option value="">-- Select Employee --</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" @selected($user->employee_id == $emp->id)>
                        {{ $emp->full_name }} ({{ $emp->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="margin: 15px 0;">
            <label>Password (leave empty to keep current)</label><br>
            <input type="password" name="password">
        </div>

        <div style="margin: 15px 0;">
            <label>Confirm Password</label><br>
            <input type="password" name="password_confirmation">
        </div>

        <div style="margin: 15px 0;">
            <label>Role *</label><br>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected($user->role == $role)>{{ $role }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin: 15px 0;">
            <label>
                <input type="checkbox" name="is_active" value="1" @checked($user->is_active)>
                Active
            </label>
        </div>

        <button type="submit">Update User</button>
        <a href="{{ route('users.index') }}">Cancel</a>
    </form>
</body>
</html>
