<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit log</title>
</head>
<body>

<h1>Audit Log (latest 300)</h1>

<table border="1" cellpadding="6">
    <thead>
        <tr>
            <th>Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Entity</th>
            <th>ID</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($logs as $log)
        <tr>
            <td>{{ $log->created_at }}</td>
            <td>{{ $log->user_id }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ $log->entity_type }}</td>
            <td>{{ $log->entity_id }}</td>
            <td>{{ $log->description }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No logs yet</td></tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
