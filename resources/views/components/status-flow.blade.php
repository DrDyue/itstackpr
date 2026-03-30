{{-- Secīgo statusu attēlojums kā neliela procesā josla. --}}
@props([
    'value',
    'context' => 'request',
])

@php
    $normalizedValue = strtolower((string) $value);

    if ($context === 'repair') {
        $steps = match ($normalizedValue) {
            'waiting' => [
                ['label' => 'Pienemts', 'icon' => 'check-circle', 'state' => 'current'],
                ['label' => 'Procesā', 'icon' => 'repair', 'state' => 'upcoming'],
                ['label' => 'Pabeigts', 'icon' => 'check', 'state' => 'upcoming'],
            ],
            'in-progress' => [
                ['label' => 'Pienemts', 'icon' => 'check-circle', 'state' => 'done'],
                ['label' => 'Procesā', 'icon' => 'repair', 'state' => 'current'],
                ['label' => 'Pabeigts', 'icon' => 'check', 'state' => 'upcoming'],
            ],
            'completed' => [
                ['label' => 'Pienemts', 'icon' => 'check-circle', 'state' => 'done'],
                ['label' => 'Procesā', 'icon' => 'repair', 'state' => 'done'],
                ['label' => 'Pabeigts', 'icon' => 'check', 'state' => 'current'],
            ],
            'cancelled' => [
                ['label' => 'Pienemts', 'icon' => 'check-circle', 'state' => 'done'],
                ['label' => 'Procesā', 'icon' => 'repair', 'state' => 'done'],
                ['label' => 'Atcelts', 'icon' => 'x-circle', 'state' => 'danger'],
            ],
            default => [],
        };
    } else {
        $steps = match ($normalizedValue) {
            'submitted' => [
                ['label' => 'Iesniegts', 'icon' => 'clock', 'state' => 'current'],
                ['label' => 'Izskatīts', 'icon' => 'audit', 'state' => 'upcoming'],
                ['label' => 'Gaida lēmumu', 'icon' => 'tag', 'state' => 'upcoming'],
            ],
            'approved' => [
                ['label' => 'Iesniegts', 'icon' => 'clock', 'state' => 'done'],
                ['label' => 'Izskatīts', 'icon' => 'audit', 'state' => 'done'],
                ['label' => 'Apstiprināts', 'icon' => 'check-circle', 'state' => 'current'],
            ],
            'rejected' => [
                ['label' => 'Iesniegts', 'icon' => 'clock', 'state' => 'done'],
                ['label' => 'Izskatīts', 'icon' => 'audit', 'state' => 'done'],
                ['label' => 'Noraidits', 'icon' => 'x-circle', 'state' => 'danger'],
            ],
            default => [],
        };
    }

    $stateClasses = [
        'done' => 'status-flow-step-done',
        'current' => 'status-flow-step-current',
        'danger' => 'status-flow-step-danger',
        'upcoming' => 'status-flow-step-upcoming',
    ];
@endphp

@if ($steps !== [])
    <div {{ $attributes->class('status-flow') }}>
        @foreach ($steps as $index => $step)
            <div class="status-flow-item">
                <div class="status-flow-step {{ $stateClasses[$step['state']] ?? $stateClasses['upcoming'] }}">
                    <span class="status-flow-step-icon">
                        <x-icon :name="$step['icon']" size="h-3.5 w-3.5" />
                    </span>
                    <span>{{ $step['label'] }}</span>
                </div>

                @if (! $loop->last)
                    <span class="status-flow-connector {{ in_array($step['state'], ['done', 'current', 'danger'], true) ? 'status-flow-connector-active' : '' }}"></span>
                @endif
            </div>
        @endforeach
    </div>
@endif
