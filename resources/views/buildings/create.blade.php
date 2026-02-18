<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create building</title>
</head>
<body>
    <h1>Create building</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('buildings.store') }}">
        @csrf

        <p>
            Name*:<br>
            <input name="building_name" value="{{ old('building_name') }}">
        </p>

        <p>
            City:<br>
            <input name="city" value="{{ old('city') }}">
        </p>

        <p>
            Address:<br>
            <input name="address" value="{{ old('address') }}">
        </p>

        <p>
            Floors:<br>
            <input type="number" name="total_floors" value="{{ old('total_floors') }}">
        </p>

        <p>
            Notes:<br>
            <input name="notes" value="{{ old('notes') }}">
        </p>

        <button type="submit">Save</button>
        <a href="{{ route('buildings.index') }}">Back</a>
    </form>
</body>
</html>
