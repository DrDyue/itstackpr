<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create repair</title>
</head>
<body>
    <h1>Create repair</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('repairs.store') }}">
        @csrf

        <p>Device*:<br>
            <select name="device_id">
                @foreach ($devices as $d)
                    <option value="{{ $d->id }}" @selected(old('device_id') == $d->id)>
                        {{ $d->code }} — {{ $d->name }}
                    </option>
                @endforeach
            </select>
        </p>

        <p>Description*:<br>
            <textarea name="description">{{ old('description') }}</textarea>
        </p>

        <p>Status*:<br>
            <select name="status">
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(old('status', 'waiting') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </p>

        <p>Repair type*:<br>
            <select name="repair_type">
                @foreach ($types as $t)
                    <option value="{{ $t }}" @selected(old('repair_type', 'internal') === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </p>

        <p>Priority*:<br>
            <select name="priority">
                @foreach ($priorities as $p)
                    <option value="{{ $p }}" @selected(old('priority', 'medium') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </p>

        <p>Start date*:<br>
            <input type="date" name="start_date" value="{{ old('start_date') }}">
        </p>

        <p>Estimated completion:<br>
            <input type="date" name="estimated_completion" value="{{ old('estimated_completion') }}">
        </p>

        <p>Actual completion:<br>
            <input type="date" name="actual_completion" value="{{ old('actual_completion') }}">
        </p>

        <p>Cost:<br>
            <input name="cost" value="{{ old('cost') }}">
        </p>

        <p>Vendor name:<br>
            <input name="vendor_name" value="{{ old('vendor_name') }}">
        </p>

        <p>Vendor contact:<br>
            <input name="vendor_contact" value="{{ old('vendor_contact') }}">
        </p>

        <p>Invoice number:<br>
            <input name="invoice_number" value="{{ old('invoice_number') }}">
        </p>

        <button type="submit">Save</button>
        <a href="{{ route('repairs.index') }}">Back</a>
    </form>
</body>
</html>
