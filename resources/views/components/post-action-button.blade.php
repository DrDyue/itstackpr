{{-- Vienots POST darbību komponents, lai POST/DELETE/PATCH darbības netiktu renderētas kā saites. --}}
@props([
    'action',
    'method' => 'POST',
    'formClass' => null,
    'buttonClass' => null,
    'buttonType' => 'submit',
    'buttonAttributes' => [],
    'fields' => null,
])

@php
    // HTML formas atbalsta tikai GET/POST, tāpēc Laravel PATCH/PUT/DELETE darbības
    // tiek sūtītas kā POST ar slēpto `_method` lauku.
    $normalizedMethod = strtoupper($method);
    $buttonAttributes = $buttonAttributes instanceof \Illuminate\View\ComponentAttributeBag
        ? $buttonAttributes
        : new \Illuminate\View\ComponentAttributeBag($buttonAttributes);
@endphp

<form method="POST" action="{{ $action }}" {{ $attributes->class([$formClass]) }}>
    @csrf

    @if ($normalizedMethod !== 'POST')
        {{-- Metodes spoofing ļauj izmantot REST maršrutus, nezaudējot CSRF aizsardzību. --}}
        @method($normalizedMethod)
    @endif

    {{-- `fields` slots paredzēts slēptajiem laukiem, piemēram review statusam `approved/rejected`. --}}
    {{ $fields }}

    {{-- Pogai ļaujam padot atribūtus no ārpuses, lai confirm/toast JS var pieslēgties bez jauna komponenta. --}}
    <button {{ $buttonAttributes->merge(['type' => $buttonType, 'class' => $buttonClass]) }}>
        {{ $slot }}
    </button>
</form>
