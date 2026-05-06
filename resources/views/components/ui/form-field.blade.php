@props([
    'label',
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
])

@php
    // Ja komponentei nav padota konkrēta kļūda, tā pati mēģina nolasīt kļūdu pēc `name`.
    // Tas samazina atkārtošanos formās un ļauj vienoti iezīmēt visu lauka bloku.
    $resolvedError = $error ?? ($name ? $errors->first($name) : null);
@endphp

{{-- Kopīgs formas lauka apvalks nodrošina vienādu label, required zvaigznītes,
     hint un kļūdas attēlojumu visās modāļu formās. --}}
<label {{ $attributes->class(['block', 'form-field-error' => filled($resolvedError)]) }}>
    <span class="crud-label">
        {{ $label }}
        @if ($required)
            *
        @endif
    </span>

    {{ $slot }}

    @if ($hint)
        <div class="mt-2 text-xs text-slate-500">{{ $hint }}</div>
    @endif

    @if (filled($resolvedError))
        <div class="form-field-error-message">{{ $resolvedError }}</div>
    @endif
</label>
