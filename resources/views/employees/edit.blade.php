<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit employee</title>
</head>
<body>
<h1>Edit employee</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('employees.update', $employee) }}">
    @csrf
    @method('PUT')

    <p>Full name*:<br>
        <input name="full_name" value="{{ old('full_name', $employee->full_name) }}">
    </p>

    <p>Email:<br>
        <input name="email" value="{{ old('email', $employee->email) }}">
    </p>

    <p>Phone:<br>
        <input name="phone" value="{{ old('phone', $employee->phone) }}">
    </p>

    <p>Job title:<br>
        <input name="job_title" value="{{ old('job_title', $employee->job_title) }}">
    </p>

    <p>
        <label>
            <input type="checkbox" name="is_active" @checked(old('is_active', (bool)$employee->is_active))>
            Active
        </label>
    </p>

    <button type="submit">Update</button>
    <a href="{{ route('employees.index') }}">Back</a>
</form>
</body>
</html>
