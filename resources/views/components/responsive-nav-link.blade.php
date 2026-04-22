{{-- Mobilās navigācijas šaite ar aktīvo stāvokli. --}}
@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full whitespace-nowrap rounded-2xl bg-sky-50 px-4 py-3 text-start text-base font-semibold text-sky-800 ring-1 ring-sky-300 shadow-sm transition duration-150 ease-in-out'
            : 'block w-full whitespace-nowrap rounded-2xl px-4 py-3 text-start text-base font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-slate-100 hover:shadow-sm hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>


