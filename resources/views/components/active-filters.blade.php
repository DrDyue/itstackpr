{{-- Komponents: Aktīvo filtru čipi ar atsevišķu filtru noņemšanu. --}}
@props([
    'items' => [],
    'clearUrl' => null,
])

@php
    $activeItems = collect($items)->filter(fn ($item) => filled($item['value'] ?? null));

    $removeUrl = function (array $item): ?string {
        if (! empty($item['removeUrl'])) {
            return $item['removeUrl'];
        }

        $removeKeys = \Illuminate\Support\Arr::wrap($item['remove'] ?? []);
        if ($removeKeys === []) {
            return null;
        }

        $query = request()->query();
        // Noņemot vienu filtru, apzināti notīrām arī `page` un `clear`,
        // lai lietotājs pēc URL pārbūves nonāktu konsekventā filtru stāvoklī no pirmās lapas.
        foreach ([...$removeKeys, 'page', 'clear'] as $key) {
            unset($query[$key]);
        }

        return url()->current() . ($query === [] ? '' : '?' . http_build_query($query));
    };
@endphp

@if ($activeItems->isNotEmpty())
    <div class="filter-summary">
        <div class="filter-summary-head">
            <x-icon name="search" size="h-4 w-4" />
            <span>Aktīvie filtri</span>
        </div>
        <div class="filter-summary-items">
            @foreach ($activeItems as $item)
                @php($itemRemoveUrl = $removeUrl($item))
                <span class="filter-chip">
                    <span class="filter-chip-label">{{ $item['label'] }}:</span>
                    <span>{{ $item['value'] }}</span>
                    @if ($itemRemoveUrl)
                        <a
                            href="{{ $itemRemoveUrl }}"
                            class="filter-chip-remove"
                            data-async-link="true"
                            aria-label="Noņemt filtru {{ $item['label'] }}"
                            title="Noņemt filtru"
                        >
                            <x-icon name="x-mark" size="h-3 w-3" />
                        </a>
                    @endif
                </span>
            @endforeach
            @if ($clearUrl)
                {{-- Pilnā filtru notīrīšana iet caur to pašu async link mehānismu kā pārējie saraksta filtri,
                     tāpēc nav vajadzīgs atsevišķs JavaScript katram skatam. --}}
                <a href="{{ str_contains($clearUrl, '?') ? $clearUrl . '&clear=1' : $clearUrl . '?clear=1' }}" class="filter-chip filter-chip-link" data-async-link="true" data-async-clear="true">
                    <x-icon name="clear" size="h-3.5 w-3.5" />
                    <span>Notīrīt filtrus</span>
                </a>
            @endif
        </div>
    </div>
@endif
