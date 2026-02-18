<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create device type</title>
</head>
<body>
    <h1>Create device type</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('device-types.store') }}">
        @csrf

        <p>
            Type name*:<br>
            <input name="type_name" value="{{ old('type_name') }}">
        </p>

        <p>
            Category*:<br>
            <input name="category" value="{{ old('category') }}">
        </p>

        <p>
            Icon name:<br>
            <input name="icon_name" value="{{ old('icon_name') }}">
        </p>

        <p>
            Expected lifetime years:<br>
            <input type="number" name="expected_lifetime_years" value="{{ old('expected_lifetime_years', 5) }}">
        </p>

        <p>
            Description:<br>
            <textarea name="description">{{ old('description') }}</textarea>
        </p>

        <button type="submit">Save</button>
        <a href="{{ route('device-types.index') }}">Back</a>
    </form>
</body>
</html>
