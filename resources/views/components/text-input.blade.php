{{-- Vienotais teksta ievades lauka komponents. --}}
@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-input']) }}>


