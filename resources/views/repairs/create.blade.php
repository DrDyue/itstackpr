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
            <h1 class="text-2xl font-semibold text-gray-900">Jauns remonts</h1>
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

        <form method="POST" action="{{ route('repairs.store') }}" class="crud-form-card">
            @csrf

            <div>
                <label class="crud-label">Ierīce *</label>
                <select name="device_id" required class="crud-control">
                    <option value="">Izvēlieties ierīci</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>{{ $device->code ?? ('Ierīce #' . $device->id) }} - {{ $device->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="crud-label">Apraksts *</label>
                <textarea name="description" rows="4" required class="crud-control">{{ old('description') }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="crud-label">Statuss</label>
                    <select name="status" class="crud-control">
                        <option value="">Noklusējums (gaida)</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Remonta tips *</label>
                    <select name="repair_type" required class="crud-control">
                        <option value="">Izvēlieties</option>
                        @foreach($repairTypes as $type)
                            <option value="{{ $type }}" @selected(old('repair_type') === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Prioritāte</label>
                    <select name="priority" class="crud-control">
                        <option value="">Noklusējums (vidēja)</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="crud-label">Sākuma datums *</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Plānotais beigums</label>
                    <input type="date" name="estimated_completion" value="{{ old('estimated_completion') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Faktiskais beigums</label>
                    <input type="date" name="actual_completion" value="{{ old('actual_completion') }}" class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Izmaksas (EUR)</label>
                    <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Rēķina numurs</label>
                    <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number') }}" class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Piegādātājs</label>
                    <input type="text" name="vendor_name" maxlength="100" value="{{ old('vendor_name') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Piegādātāja kontakts</label>
                    <input type="text" name="vendor_contact" maxlength="100" value="{{ old('vendor_contact') }}" class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Ziņoja lietotājs</label>
                    <select name="issue_reported_by" class="crud-control">
                        <option value="">Nav</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('issue_reported_by') == $user->id)>{{ $user->employee?->full_name ?? ('Lietotājs #' . $user->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Piešķirts lietotājam</label>
                    <select name="assigned_to" class="crud-control">
                        <option value="">Nav</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>{{ $user->employee?->full_name ?? ('Lietotājs #' . $user->id) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabāt</button>
                <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>



