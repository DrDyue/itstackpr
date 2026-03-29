@props([
    'icon' => 'search',
    'title' => 'Sobrid nekas nav atrasts',
    'description' => null,
    'actionHref' => null,
    'actionLabel' => null,
    'compact' => false,
])

@php
    $wrapperClasses = $compact
        ? 'empty-state empty-state-compact'
        : 'empty-state';
@endphp

<div {{ $attributes->class($wrapperClasses) }}>
    <div class="empty-state-icon">
        <x-icon :name="$icon" size="h-5 w-5" />
    </div>

    <div class="empty-state-copy">
        <div class="empty-state-title">{{ $title }}</div>

        @if (filled($description))
            <div class="empty-state-description">{{ $description }}</div>
        @endif
    </div>

    @if ($actionHref && $actionLabel)
        <div class="empty-state-actions">
            <a href="{{ $actionHref }}" class="btn-clear">
                <x-icon name="clear" size="h-4 w-4" />
                <span>{{ $actionLabel }}</span>
            </a>
        </div>
    @endif
</div>
