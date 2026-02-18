<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Device history</title>
</head>
<body>
    <h1>Device history (latest 300)</h1>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Time</th>
                <th>Device</th>
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
                <td>
                    {{ $h->device?->code }} — {{ $h->device?->name }}
                    @if($h->device)
                        (<a href="{{ route('devices.history', $h->device) }}">history</a>)
                    @endif
                </td>
                <td>{{ $h->action }}</td>
                <td>{{ $h->field_changed }}</td>
                <td>{{ $h->old_value }}</td>
                <td>{{ $h->new_value }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No history yet</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
