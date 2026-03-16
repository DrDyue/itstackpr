@php
    $crestPath = public_path('images/ludzas-logo.png');
    $hasCrest = file_exists($crestPath);
@endphp

<div class="flex min-w-0 items-center gap-3" {{ $attributes }}>
    @if ($hasCrest)
        <img
            src="{{ asset('images/ludzas-logo.png') }}"
            alt="Ludzas novads"
            class="h-11 w-11 shrink-0 rounded-lg object-contain bg-white p-1 shadow-sm ring-1 ring-gray-200"
        >
    @else
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-xl font-bold text-white shadow-lg">
            IT
        </div>
    @endif
    <div class="min-w-0 leading-tight">
        <span class="block truncate text-lg font-bold text-gray-900">Ludzas novads</span>
        <span class="hidden truncate text-xs font-semibold text-blue-600 xl:block">IT inventra uzskaite</span>
    </div>
</div>

