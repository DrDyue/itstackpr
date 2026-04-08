@props([
    'label',
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
])

<label {{ $attributes->class(['block']) }}>
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

    @php
        $resolvedError = $error ?? ($name ? $errors->first($name) : null);
    @endphp
    @if (filled($resolvedError))
        <div class="mt-2 text-xs font-semibold text-rose-600">{{ $resolvedError }}</div>
    @endif
</label>
