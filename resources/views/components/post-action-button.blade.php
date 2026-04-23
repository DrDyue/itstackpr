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
    $normalizedMethod = strtoupper($method);
    $buttonAttributes = $buttonAttributes instanceof \Illuminate\View\ComponentAttributeBag
        ? $buttonAttributes
        : new \Illuminate\View\ComponentAttributeBag($buttonAttributes);
@endphp

<form method="POST" action="{{ $action }}" {{ $attributes->class([$formClass]) }}>
    @csrf

    @if ($normalizedMethod !== 'POST')
        @method($normalizedMethod)
    @endif

    {{ $fields }}

    <button {{ $buttonAttributes->merge(['type' => $buttonType, 'class' => $buttonClass]) }}>
        {{ $slot }}
    </button>
</form>
