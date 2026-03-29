{{-- Projekta logotipa komponents navigācijai un autentifikācijas skatam. --}}
@php
    $crestPath = public_path('images/ludzas-logo.png');
    $hasCrest = file_exists($crestPath);
@endphp

<div class="app-brand flex min-w-0 items-center gap-3" {{ $attributes }}>
    @if ($hasCrest)
        <img
            src="{{ asset('images/ludzas-logo.png') }}"
            alt="Ludzas novads"
            class="app-brand-crest h-11 w-11 shrink-0 rounded-lg object-contain bg-white p-1 shadow-sm ring-1 ring-gray-200"
        >
    @else
        <div class="app-brand-fallback flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-xl font-bold text-white shadow-lg">
            IT
        </div>
    @endif
    <div class="min-w-0 leading-tight">
        <span class="app-brand-title block truncate text-lg font-bold text-gray-900">Ludzas novads</span>
        <span class="app-brand-subtitle hidden truncate text-xs font-semibold text-blue-600 xl:block">IT inventra uzskaite</span>
    </div>
</div>
