@props([
    'device',
    'secondary' => null,
    'primaryClass' => '',
    'secondaryClass' => '',
])

@php
    $isWrittenOff = ($device?->status ?? null) === \App\Models\Device::STATUS_WRITEOFF;
    $tooltip = $isWrittenOff ? 'Norakstītām ierīcēm atbildīgais darbinieks vairs netiek rādīts.' : null;

    $primaryLabel = $isWrittenOff
        ? 'Vairs nav piešķirta'
        : ($device?->assignedTo?->full_name ?: 'Nav piešķirta');

    $secondaryLabel = match ($secondary) {
        'job_title' => $isWrittenOff
            ? 'Norakstīta ierīce'
            : ($device?->assignedTo?->job_title ?: 'Nav amata'),
        'email' => $isWrittenOff
            ? 'Norakstīta ierīce'
            : ($device?->assignedTo?->email ?: 'Lietotājs nav norādīts'),
        default => null,
    };
@endphp

<div @if ($tooltip) title="{{ $tooltip }}" @endif>
    <div @class([$primaryClass])>{{ $primaryLabel }}</div>
    @if ($secondaryLabel !== null)
        <div @class([$secondaryClass])>{{ $secondaryLabel }}</div>
    @endif
</div>
