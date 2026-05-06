{{-- Vienotais teksta ievades lauka komponents. --}}
@props(['disabled' => false])

@php
    $fieldName = $attributes->get('name');
    $hasError = $fieldName && $errors->has($fieldName);
@endphp

<input
    @disabled($disabled)
    @if ($hasError) aria-invalid="true" @endif
    {{ $attributes->class(['form-input', 'error' => $hasError]) }}
>

