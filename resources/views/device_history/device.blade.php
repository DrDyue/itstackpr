<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Device history</title>
</head>
<body>
    <h1>History: {{ $device->code }} — {{ $device->name }}</h1>

    <p><a href="{{ route('devices.index') }}">Back to devices</a></p>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Time</th>
                <th>Action</th>
                <th>Field</th>
                <th>Old</th>
                <th>New</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($history as $h)
            <tr>
                <td>{{ $h->timestamp }}</td>
                <td>{{ $h->action }}</td>
                <td>{{ $h->field_changed }}</td>
                <td>{{ $h->old_value }}</td>
                <td>{{ $h->new_value }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No history for this device yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
