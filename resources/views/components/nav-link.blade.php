{{-- Desktop navigācijas šaite ar aktīvā stāvokļa noformējumu. --}}
@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl bg-sky-50 px-3.5 py-2.5 text-sm font-semibold text-sky-800 ring-1 ring-sky-300 shadow-sm transition duration-200'
            : 'inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:shadow-sm hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>


