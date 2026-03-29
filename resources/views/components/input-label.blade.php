{{-- Vienotais formu etiķetes komponents. --}}
@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label']) }}>
    {{ $value ?? $slot }}
</label>


