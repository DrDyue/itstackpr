@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-4">
    <div class="flex items-center justify-between gap-4 mb-4">
        <h1 class="text-2xl font-bold">Repairs</h1>

        <a href="{{ route('repairs.create') }}"
           class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
            + New Repair
        </a>
    </div>

    <form method="GET" action="{{ route('repairs.index') }}" class="mb-4 flex gap-2">
        <input type="text" name="q" value="{{ $q ?? '' }}"
               placeholder="Search (description / vendor / invoice)..."
               class="w-full border rounded px-3 py-2" />
        <button class="px-4 py-2 rounded bg-gray-800 text-white hover:bg-gray-900">
            Search
        </button>
        <a href="{{ route('repairs.index') }}"
           class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
            Reset
        </a>
    </form>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
            <tr class="text-left">
                <th class="p-3">#</th>
                <th class="p-3">Device</th>
                <th class="p-3">Status</th>
                <th class="p-3">Type</th>
                <th class="p-3">Priority</th>
                <th class="p-3">Start</th>
                <th class="p-3">Est.</th>
                <th class="p-3">Actual</th>
                <th class="p-3">Cost</th>
                <th class="p-3">Vendor</th>
                <th class="p-3 w-40">Actions</th>
            </tr>
            </thead>

            <tbody>
            @forelse($repairs as $repair)
                <tr class="border-t">
                    <td class="p-3">{{ $repair->id }}</td>

                    <td class="p-3">
                        @if($repair->device)
                            <div class="font-medium">
                                {{ $repair->device->code ?? ('Device #' . $repair->device->id) }}
                            </div>
                            <div class="text-gray-600">
                                {{ $repair->device->name ?? '' }}
                            </div>
                        @else
                            <span class="text-gray-500">—</span>
                        @endif
                    </td>

                    <td class="p-3">{{ $repair->status ?? 'waiting' }}</td>
                    <td class="p-3">{{ $repair->repair_type }}</td>
                    <td class="p-3">{{ $repair->priority ?? 'medium' }}</td>

                    <td class="p-3">{{ $repair->start_date }}</td>
                    <td class="p-3">{{ $repair->estimated_completion ?? '—' }}</td>
                    <td class="p-3">{{ $repair->actual_completion ?? '—' }}</td>

                    <td class="p-3">
                        {{ $repair->cost !== null ? number_format((float)$repair->cost, 2) . ' €' : '—' }}
                    </td>

                    <td class="p-3">
                        <div class="font-medium">{{ $repair->vendor_name ?? '—' }}</div>
                        <div class="text-gray-600">{{ $repair->vendor_contact ?? '' }}</div>
                        <div class="text-gray-600">{{ $repair->invoice_number ?? '' }}</div>
                    </td>

                    <td class="p-3">
                        <div class="flex gap-2">
                            <a href="{{ route('repairs.edit', $repair) }}"
                               class="px-3 py-1 rounded bg-yellow-500 text-white hover:bg-yellow-600">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('repairs.destroy', $repair) }}"
                                  onsubmit="return confirm('Delete this repair?');">
                                @csrf
                                @method('DELETE')
                                <button class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <tr class="border-t bg-gray-50">
                    <td class="p-3 text-gray-500" colspan="11">
                        <span class="font-medium text-gray-700">Description:</span>
                        {{ $repair->description }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="p-6 text-center text-gray-500" colspan="11">
                        No repairs found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
