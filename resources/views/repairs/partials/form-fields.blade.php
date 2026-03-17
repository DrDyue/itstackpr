@php
    $currentRepair = $repair;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="crud-label">Ierice</span>
        <select name="device_id" class="crud-control" required>
            @foreach ($devices as $device)
                <option value="{{ $device->id }}" @selected(old('device_id', $currentRepair?->device_id ?? $preselectedDeviceId ?? null) == $device->id)>{{ $device->name }} ({{ $device->code ?: 'bez koda' }})</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Pieteicejs</span>
        <select name="reported_by_user_id" class="crud-control">
            <option value="">Nav noradits</option>
            @foreach ($users as $repairUser)
                <option value="{{ $repairUser->id }}" @selected(old('reported_by_user_id', $currentRepair?->reported_by_user_id ?? $defaultReporterId ?? null) == $repairUser->id)>{{ $repairUser->full_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block md:col-span-2">
        <span class="crud-label">Apraksts</span>
        <textarea name="description" rows="4" class="crud-control" required>{{ old('description', $currentRepair?->description) }}</textarea>
    </label>
    <label class="block">
        <span class="crud-label">Statuss</span>
        <select name="status" class="crud-control">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $currentRepair?->status ?? 'waiting') === $status)>{{ $statusLabels[$status] }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Remonta tips</span>
        <select name="repair_type" class="crud-control" required>
            @foreach ($repairTypes as $repairType)
                <option value="{{ $repairType }}" @selected(old('repair_type', $currentRepair?->repair_type ?? 'internal') === $repairType)>{{ $typeLabels[$repairType] }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Prioritate</span>
        <select name="priority" class="crud-control">
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $currentRepair?->priority ?? 'medium') === $priority)>{{ $priorityLabels[$priority] }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Pieskirtais lietotajs</span>
        <select name="assigned_to_user_id" class="crud-control">
            <option value="">Nav pieskirts</option>
            @foreach ($users as $repairUser)
                <option value="{{ $repairUser->id }}" @selected(old('assigned_to_user_id', $currentRepair?->assigned_to_user_id) == $repairUser->id)>{{ $repairUser->full_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="crud-label">Sakuma datums</span>
        <input type="date" name="start_date" value="{{ old('start_date', $currentRepair?->start_date?->format('Y-m-d')) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Planotais beigu datums</span>
        <input type="date" name="estimated_completion" value="{{ old('estimated_completion', $currentRepair?->estimated_completion?->format('Y-m-d')) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Faktiskais beigu datums</span>
        <input type="date" name="actual_completion" value="{{ old('actual_completion', $currentRepair?->actual_completion?->format('Y-m-d')) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Izmaksas</span>
        <input type="number" step="0.01" name="cost" value="{{ old('cost', $currentRepair?->cost) }}" class="crud-control">
    </label>
    <label class="block md:col-span-2">
        <span class="crud-label">Diagnostika</span>
        <textarea name="diagnosis" rows="3" class="crud-control">{{ old('diagnosis', $currentRepair?->diagnosis) }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="crud-label">Atrisinajuma piezimes</span>
        <textarea name="resolution_notes" rows="3" class="crud-control">{{ old('resolution_notes', $currentRepair?->resolution_notes) }}</textarea>
    </label>
    <label class="block">
        <span class="crud-label">Pakalpojuma sniedzejs</span>
        <input type="text" name="vendor_name" value="{{ old('vendor_name', $currentRepair?->vendor_name) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Kontakts</span>
        <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $currentRepair?->vendor_contact) }}" class="crud-control">
    </label>
    <label class="block">
        <span class="crud-label">Rekina numurs</span>
        <input type="text" name="invoice_number" value="{{ old('invoice_number', $currentRepair?->invoice_number) }}" class="crud-control">
    </label>
</div>
