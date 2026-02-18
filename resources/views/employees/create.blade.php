<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create employee</title>
</head>
<body>
<h1>Create employee</h1>

@if ($errors->any())
    <ul style="color:red">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('employees.store') }}">
    @csrf

    <p>Full name*:<br>
        <input name="full_name" value="{{ old('full_name') }}">
    </p>

    <p>Email:<br>
        <input name="email" value="{{ old('email') }}">
    </p>

    <p>Phone:<br>
        <input name="phone" value="{{ old('phone') }}">
    </p>

    <p>Job title:<br>
        <input name="job_title" value="{{ old('job_title') }}">
    </p>

    <p>
        <label>
            <input type="checkbox" name="is_active" @checked(old('is_active', true))>
            Active
        </label>
    </p>

    <button type="submit">Save</button>
    <a href="{{ route('employees.index') }}">Back</a>
</form>
</body>
</html>
