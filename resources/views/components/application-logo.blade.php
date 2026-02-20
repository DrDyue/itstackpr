@php
    $crestPath = public_path('images/ludzas-logo.png');
    $hasCrest = file_exists($crestPath);
@endphp

<div class="flex items-center gap-3" {{ $attributes }}>
    @if ($hasCrest)
        <img
            src="{{ asset('images/ludzas-logo.png') }}"
            alt="Ludzas novads"
            class="h-14 w-14 rounded-lg object-contain bg-white p-1 shadow-sm ring-1 ring-gray-200"
        >
    @else
        <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center text-white font-bold text-2xl shadow-lg">
            IT
        </div>
    @endif
    <div class="flex flex-col leading-tight">
        <span class="text-xl font-bold text-gray-900">Ludzas novads</span>
        <span class="text-sm text-blue-600 font-semibold">IT inventra uzskaite</span>
    </div>
</div>


