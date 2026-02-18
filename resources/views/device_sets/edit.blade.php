<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit set</title>
</head>
<body>
<h1>Edit set: {{ $set->set_code }}</h1>

@if (session('success'))
    <p style="color: green">{{ session('success') }}</p>
@endif

@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
@endif

<h2>Set info</h2>
<form method="POST" action="{{ route('device-sets.update', $set) }}">
    @csrf
    @method('PUT')

    <p>Set name*:<br><input name="set_name" value="{{ old('set_name', $set->set_name) }}"></p>
    <p>Set code*:<br><input name="set_code" value="{{ old('set_code', $set->set_code) }}"></p>

    <p>Status*:<br>
        <select name="status">
            @foreach($statuses as $st)
                <option value="{{ $st }}" @selected(old('status',$set->status)===$st)>{{ $st }}</option>
            @endforeach
        </select>
    </p>

    <p>Room:<br>
        <select name="room_id">
            <option value="">-- none --</option>
            @foreach($rooms as $r)
                <option value="{{ $r->id }}" @selected(old('room_id',$set->room_id)==$r->id)>
                    {{ $r->building?->building_name }} / {{ $r->room_number }}
                </option>
            @endforeach
        </select>
    </p>

    <p>Assigned to:<br><input name="assigned_to" value="{{ old('assigned_to', $set->assigned_to) }}"></p>
    <p>Notes:<br><textarea name="notes">{{ old('notes', $set->notes) }}</textarea></p>

    <button type="submit">Update</button>
    <a href="{{ route('device-sets.index') }}">Back</a>
</form>

<hr>

<h2>Items in this set</h2>

<table border="1" cellpadding="6">
    <thead>
    <tr>
        <th>Device</th>
        <th>Quantity</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($set->items as $it)
        <tr>
            <td>{{ $it->device?->code }} — {{ $it->device?->name }}</td>
            <td>{{ $it->quantity }}</td>
            <td>
                <form action="{{ route('device-sets.items.delete', [$set, $it]) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove item?')">Remove</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="3">No items yet</td></tr>
    @endforelse
    </tbody>
</table>

<h3>Add item</h3>
<form method="POST" action="{{ route('device-sets.items.add', $set) }}">
    @csrf

    <p>Device*:<br>
        <select name="device_id">
            @foreach($devices as $d)
                <option value="{{ $d->id }}">{{ $d->code }} — {{ $d->name }}</option>
            @endforeach
        </select>
    </p>

    <p>Quantity*:<br>
        <input type="number" name="quantity" value="1" min="1" max="999">
    </p>

    <button type="submit">Add</button>
</form>

</body>
</html>
