<x-app-layout>
    @php
        $statusLabels = [
            'waiting' => 'Gaida',
            'in-progress' => 'Procesā',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
        $typeLabels = [
            'internal' => 'Iekšējais',
            'external' => '&#256;rējais',
        ];
        $priorityLabels = [
            'low' => 'Zema',
            'medium' => 'Vidēja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
    @endphp

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediģēt remontu #{{ $repair->id }}</h1>
            <a href="{{ route('repairs.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('repairs.update', $repair) }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Ierīce *</label>
                <select name="device_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Izvēlieties ierīci</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}" @selected(old('device_id', $repair->device_id) == $device->id)>{{ $device->code ?? ('Ierīce #' . $device->id) }} - {{ $device->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Apraksts *</label>
                <textarea name="description" rows="4" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $repair->description) }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Statuss</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Noklusējums (gaida)</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $repair->status) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Remonta tips *</label>
                    <select name="repair_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Izvēlieties</option>
                        @foreach($repairTypes as $type)
                            <option value="{{ $type }}" @selected(old('repair_type', $repair->repair_type) === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Prioritāte</label>
                    <select name="priority" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Noklusējums (vidēja)</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', $repair->priority) === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Sākuma datums *</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $repair->start_date) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Plānotais beigums</label>
                    <input type="date" name="estimated_completion" value="{{ old('estimated_completion', $repair->estimated_completion) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Faktiskais beigums</label>
                    <input type="date" name="actual_completion" value="{{ old('actual_completion', $repair->actual_completion) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Izmaksas (EUR)</label>
                    <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $repair->cost) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Rēķina numurs</label>
                    <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number', $repair->invoice_number) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Piegādātājs</label>
                    <input type="text" name="vendor_name" maxlength="100" value="{{ old('vendor_name', $repair->vendor_name) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Piegādātāja kontakts</label>
                    <input type="text" name="vendor_contact" maxlength="100" value="{{ old('vendor_contact', $repair->vendor_contact) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Ziņoja lietotājs</label>
                    <select name="issue_reported_by" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Nav</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('issue_reported_by', $repair->issue_reported_by) == $user->id)>{{ $user->employee?->full_name ?? ('Lietotājs #' . $user->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Piešķirts lietotājam</label>
                    <select name="assigned_to" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Nav</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('assigned_to', $repair->assigned_to) == $user->id)>{{ $user->employee?->full_name ?? ('Lietotājs #' . $user->id) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Atjaunināt</button>
                <a href="{{ route('repairs.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
