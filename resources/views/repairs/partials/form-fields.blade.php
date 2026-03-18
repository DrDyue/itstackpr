@php
    $currentRepair = $repair;
@endphp

<div
    class="grid gap-4 md:grid-cols-2"
    x-data="{ repairType: @js(old('repair_type', $currentRepair?->repair_type ?? 'internal')), repairStatus: @js($currentRepair?->status ?? 'waiting') }"
>
    <label class="block">
        <span class="crud-label">Ierice</span>
        <select name="device_id" class="crud-control" required>
            @foreach ($devices as $device)
                <option value="{{ $device->id }}" @selected(old('device_id', $currentRepair?->device_id ?? $preselectedDeviceId ?? null) == $device->id)>{{ $device->name }} ({{ $device->code ?: 'bez koda' }}){{ $device->assignedTo ? ' | ' . $device->assignedTo->full_name : '' }}{{ $device->room ? ' | telpa ' . $device->room->room_number : '' }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="crud-label">Izpilditajs</span>
        <select name="issue_reported_by" class="crud-control">
            <option value="">Nav noradits</option>
            @foreach ($users as $repairUser)
                <option value="{{ $repairUser->id }}" @selected(old('issue_reported_by', $currentRepair?->issue_reported_by ?? $defaultExecutorId ?? null) == $repairUser->id)>{{ $repairUser->full_name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block md:col-span-2">
        <span class="crud-label">Apraksts</span>
        <textarea name="description" rows="4" class="crud-control" required>{{ old('description', $currentRepair?->description) }}</textarea>
    </label>

    <input type="hidden" name="status" value="{{ $currentRepair?->status ?? 'waiting' }}">

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Remonts sakas ar <strong>Gaida</strong>, bet sakuma un beigu datumi tiek aizpilditi automatiski, parvietojot remontu starp kolonnam.
    </div>

    <label class="block">
        <span class="crud-label">Remonta tips</span>
        <select name="repair_type" class="crud-control" required x-model="repairType">
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
        <span class="crud-label">Izmaksas</span>
        <input type="number" step="0.01" name="cost" value="{{ old('cost', $currentRepair?->cost) }}" class="crud-control">
    </label>

    @if ($currentRepair)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <div><strong class="text-slate-900">Pasreizejais statuss:</strong> {{ $statusLabels[$currentRepair->status] ?? $currentRepair->status }}</div>
            <div class="mt-1"><strong class="text-slate-900">Sakuma datums:</strong> {{ $currentRepair->start_date?->format('d.m.Y') ?: 'Tiks ielikts, kad remonts saksies' }}</div>
            <div class="mt-1"><strong class="text-slate-900">Beigu datums:</strong> {{ $currentRepair->end_date?->format('d.m.Y') ?: 'Tiks ielikts, kad remonts tiks pabeigts' }}</div>
        </div>
    @endif

    <div
        class="md:col-span-2 grid gap-4 md:grid-cols-3"
        x-cloak
        x-show="repairType === 'external' && repairStatus === 'in-progress'"
    >
        <label class="block">
            <span class="crud-label">Pakalpojuma sniedzejs</span>
            <input type="text" name="vendor_name" value="{{ old('vendor_name', $currentRepair?->vendor_name) }}" class="crud-control">
        </label>
        <label class="block">
            <span class="crud-label">Vendora kontakts</span>
            <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $currentRepair?->vendor_contact) }}" class="crud-control">
        </label>
        <label class="block">
            <span class="crud-label">Rekina numurs</span>
            <input type="text" name="invoice_number" value="{{ old('invoice_number', $currentRepair?->invoice_number) }}" class="crud-control">
        </label>
    </div>

    <div
        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 md:col-span-2"
        x-cloak
        x-show="repairType === 'external' && repairStatus !== 'in-progress'"
    >
        Areja remonta vendoru lauki paradisies tikai tad, kad remonts tiks parvietots uz <strong>Procesa</strong>.
    </div>

    @if ($currentRepair?->request_id)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 md:col-span-2">
            Saistitais remonta pieteikums: #{{ $currentRepair->request_id }}
        </div>
    @endif
</div>
