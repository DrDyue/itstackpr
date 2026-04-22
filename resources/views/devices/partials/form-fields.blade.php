@php
    $formKey = $formKey ?? ($device?->id ? 'device-edit-'.$device->id : 'device-create');
    $useOldInput = $useOldInput ?? true;
    $fieldValue = fn (string $field, mixed $default = null) => $useOldInput ? old($field, $default) : $default;
    $current = $device;
    $isCreating = ! $current;
    $isWrittenOff = ($current?->status ?? null) === \App\Models\Device::STATUS_WRITEOFF;
    $deviceImageUrl = $current?->deviceImageUrl();
    $selectedTypeId = (string) $fieldValue('device_type_id', $current?->device_type_id ?? '');
    $selectedTypeLabel = $fieldValue(
        'device_type_query',
        optional($types->firstWhere('id', (int) $selectedTypeId))->type_name ?? ''
    );
    $typeOptions = $types->map(fn ($type) => [
        'value' => (string) $type->id,
        'label' => $type->type_name,
        'description' => 'Ierīces tips',
        'search' => $type->type_name,
    ])->values();
    $selectedAssignedToId = $fieldValue('assigned_to_id', $current?->assigned_to_id ?? $defaultAssignedToId ?? null);
    $selectedBuildingId = $fieldValue('building_id', $current?->building_id ?? $defaultBuildingId ?? null);
    $selectedRoomId = $fieldValue('room_id', $current?->room_id ?? $defaultRoomId ?? null);
    $selectedStatus = $fieldValue('status', $current?->status ?? \App\Models\Device::STATUS_ACTIVE);
    $roomOptions = $rooms->map(fn ($room) => [
        'value' => (string) $room->id,
        'label' => $room->room_number . ($room->room_name ? ' - ' . $room->room_name : ''),
        'description' => collect([
            $room->building?->building_name,
            $room->floor_number !== null ? $room->floor_number . '. stāvs' : null,
            $room->department,
        ])->filter()->implode(' | '),
        'search' => implode(' ', array_filter([
            $room->room_number,
            $room->room_name,
            $room->building?->building_name,
            $room->department,
            $room->floor_number,
        ])),
    ])->values();
    $assignedUserOptions = $users->map(fn ($assignedUser) => [
        'value' => (string) $assignedUser->id,
        'label' => $assignedUser->full_name,
        'description' => $assignedUser->job_title ?: $assignedUser->email,
        'search' => implode(' ', array_filter([
            $assignedUser->full_name,
            $assignedUser->job_title,
            $assignedUser->email,
        ])),
    ])->values();
    $statusOptions = collect($statuses)->map(fn ($status) => [
        'value' => (string) $status,
        'label' => $statusLabels[$status] ?? ucfirst($status),
        'description' => match ($status) {
            \App\Models\Device::STATUS_ACTIVE => 'Ierīce ir lietosana',
            \App\Models\Device::STATUS_REPAIR => 'Ierīce atrodas remontā',
            \App\Models\Device::STATUS_WRITEOFF => 'Ierīce ir norakstīta',
            default => '',
        },
        'search' => implode(' ', array_filter([
            $status,
            $statusLabels[$status] ?? ucfirst($status),
        ])),
    ])->values();
    $selectedAssignedToLabel = $fieldValue(
        'assigned_to_query',
        optional($users->firstWhere('id', (int) $selectedAssignedToId))->full_name ?? ''
    );
    $selectedRoom = $rooms->firstWhere('id', (int) $selectedRoomId);
    $selectedRoomLabel = $fieldValue(
        'room_query',
        $selectedRoom?->room_number
            ? $selectedRoom->room_number . ($selectedRoom->room_name ? ' - ' . $selectedRoom->room_name : '')
            : ''
    );
    $selectedStatusLabel = $fieldValue('status_query', $statusLabels[$selectedStatus] ?? 'Aktīva');
@endphp

<div class="space-y-6">
    @if ($isWrittenOff)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
            Norakstītai ierīcei var labot tikai informācijas laukus. Statuss, piesaiste un telpa netiek mainīti.
        </div>
    @endif

    @include('devices.partials.form-sections.identity-fields')
    @include('devices.partials.form-sections.assignment-fields')
    @include('devices.partials.form-sections.meta-fields')
</div>
