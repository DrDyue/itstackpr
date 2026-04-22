{{-- Vienotais formu etiķetes komponents. --}}
@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label mb-1.5 block text-sm font-semibold text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>


