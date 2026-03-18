@props([
    'items' => [],
    'clearUrl' => null,
])

@php
    $activeItems = collect($items)->filter(function ($item) {
        return filled($item['value'] ?? null);
    });
@endphp

@if ($activeItems->isNotEmpty())
    <div class="filter-summary">
        <div class="filter-summary-head">
            <x-icon name="search" size="h-4 w-4" />
            <span>Aktivie filtri</span>
        </div>
        <div class="filter-summary-items">
            @foreach ($activeItems as $item)
                <span class="filter-chip">
                    <span class="filter-chip-label">{{ $item['label'] }}:</span>
                    <span>{{ $item['value'] }}</span>
                </span>
            @endforeach
            @if ($clearUrl)
                <a href="{{ $clearUrl }}" class="filter-chip filter-chip-link">
                    <x-icon name="clear" size="h-3.5 w-3.5" />
                    <span>Notirit filtrus</span>
                </a>
            @endif
        </div>
    </div>
@endif
