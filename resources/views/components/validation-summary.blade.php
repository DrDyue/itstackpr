@props([
    'title' => 'Parbaudi ievaditos datus',
    'bag' => null,
])

@php
    $errorBag = $bag ?? $errors;
    $errorKeys = collect($errorBag->keys());
    $tips = $errorKeys
        ->flatMap(function (string $field) {
            return match (true) {
                str_contains($field, 'device') => ['Izvelies ierici no saraksta velreiz. Ja ta vairs nav pieejama, atsvaidzini lapu vai nomaini darbibu.'],
                str_contains($field, 'room') || str_contains($field, 'building') => ['Parbaudi atrasanas vietu. Aktivai iericei jabut piesaistitai telpai, un telpai jabut derigai izveletajai ekai.'],
                str_contains($field, 'assigned') || $field === 'user_id' || $field === 'transfered_to_id' => ['Parbaudi atbildigo personu vai sanemeju un izvelies ierakstu no meklejama saraksta velreiz.'],
                str_contains($field, 'status') => ['Izvelies vienu no piedavatajiem statusiem. Ja ieraksts jau ir apstradats, atver sarakstu no jauna un parbaudi aktivo stavokli.'],
                str_contains($field, 'image') || str_contains($field, 'file') => ['Parbaudi failu. Drikst izmantot tikai atbalstitu faila tipu, un failam jabut pietiekami mazam augshupladei.'],
                str_contains($field, 'date') => ['Parbaudi datumus. Ja aizpildi vairakus datumus, tiem savstarpeji jasaskan.'],
                default => ['Izlabo iezimeto lauku un megini velreiz. Ja problema atkartoas, atver ierakstu no jauna un parbaudi, vai saistitie dati vel ir pieejami.'],
            };
        })
        ->unique()
        ->values();
@endphp

@if ($errorBag->any())
    <div {{ $attributes->class('validation-summary') }}>
        <div class="validation-summary-head">
            <span class="validation-summary-icon">
                <x-icon name="exclamation-triangle" size="h-5 w-5" />
            </span>
            <div>
                <div class="validation-summary-title">{{ $title }}</div>
                <div class="validation-summary-subtitle">
                    Atrastas {{ $errorBag->count() }} {{ \Illuminate\Support\Str::plural('problema', $errorBag->count()) }}. Zemak redzams, kas janolabo un ko darit talak.
                </div>
            </div>
        </div>

        <div class="validation-summary-grid">
            <div>
                <div class="validation-summary-section">Kadas kludas atrastas</div>
                <ul class="validation-summary-list">
                    @foreach ($errorBag->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>

            @if ($tips->isNotEmpty())
                <div>
                    <div class="validation-summary-section">Ko darit talak</div>
                    <ul class="validation-summary-tips">
                        @foreach ($tips as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
