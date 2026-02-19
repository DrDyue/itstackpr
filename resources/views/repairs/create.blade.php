@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Create Repair</h1>
        <a href="{{ route('repairs.index') }}" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
            <div class="font-semibold mb-1">Validation errors:</div>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('repairs.store') }}" class="bg-white border rounded p-4 space-y-4">
        @csrf

        <div>
            <label class="block font-medium mb-1">Device *</label>
            <select name="device_id" class="w-full border rounded px-3 py-2" required>
                <option value="">-- select device --</option>
                @foreach($devices as $device)
                    <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>
                        {{ $device->code ?? ('Device #' . $device->id) }} - {{ $device->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-medium mb-1">Description *</label>
            <textarea name="description" rows="4" class="w-full border rounded px-3 py-2" required>{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block font-medium mb-1">Status</label>
                <select name="status" class="w-full border rounded px-3 py-2">
                    <option value="">(default: waiting)</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(old('status') == $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-medium mb-1">Repair type *</label>
                <select name="repair_type" class="w-full border rounded px-3 py-2" required>
                    <option value="">-- select --</option>
                    @foreach($repairTypes as $t)
                        <option value="{{ $t }}" @selected(old('repair_type') == $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-medium mb-1">Priority</label>
                <select name="priority" class="w-full border rounded px-3 py-2">
                    <option value="">(default: medium)</option>
                    @foreach($priorities as $p)
                        <option value="{{ $p }}" @selected(old('priority') == $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block font-medium mb-1">Start date *</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}"
                       class="w-full border rounded px-3 py-2" required />
            </div>

            <div>
                <label class="block font-medium mb-1">Estimated completion</label>
                <input type="date" name="estimated_completion" value="{{ old('estimated_completion') }}"
                       class="w-full border rounded px-3 py-2" />
            </div>

            <div>
                <label class="block font-medium mb-1">Actual completion</label>
                <input type="date" name="actual_completion" value="{{ old('actual_completion') }}"
                       class="w-full border rounded px-3 py-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block font-medium mb-1">Cost (EUR)</label>
                <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}"
                       class="w-full border rounded px-3 py-2" />
            </div>

            <div>
                <label class="block font-medium mb-1">Invoice number</label>
                <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number') }}"
                       class="w-full border rounded px-3 py-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block font-medium mb-1">Vendor name</label>
                <input type="text" name="vendor_name" maxlength="100" value="{{ old('vendor_name') }}"
                       class="w-full border rounded px-3 py-2" />
            </div>

            <div>
                <label class="block font-medium mb-1">Vendor contact</label>
                <input type="text" name="vendor_contact" maxlength="100" value="{{ old('vendor_contact') }}"
                       class="w-full border rounded px-3 py-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block font-medium mb-1">Issue reported by (user)</label>
                <select name="issue_reported_by" class="w-full border rounded px-3 py-2">
                    <option value="">-- none --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(old('issue_reported_by') == $u->id)>
                            {{ $u->name ?? ('User #' . $u->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-medium mb-1">Assigned to (user)</label>
                <select name="assigned_to" class="w-full border rounded px-3 py-2">
                    <option value="">-- none --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(old('assigned_to') == $u->id)>
                            {{ $u->name ?? ('User #' . $u->id) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                Save
            </button>
            <a href="{{ route('repairs.index') }}" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
