{{--
    Komponents: Aktīvo filtru čipi.
    Atbildība: parāda, kuri filtri saraksta lapā pašlaik ir ieslēgti, un piedāvā ātru atiestatīšanu.
    Kāpēc tas ir svarīgi:
    1. Lietotājs nepazaudē kontekstu, kāpēc tabulā redzams tieši šāds rezultāts.
    2. Ļauj ātri noņemt visus filtrus un atgriezties pilnajā sarakstā.
    3. Uzlabo lietojamību garos sarakstos ar daudziem filtru nosacījumiem.
--}}
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
            <span>Aktīvie filtri</span>
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
                    <span>Notīrīt filtrus</span>
                </a>
            @endif
        </div>
    </div>
@endif
