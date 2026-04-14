{{-- Universāls modāļa komponents --}}
@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="{{ $show ? 'block' : 'hidden' }} fixed inset-0 z-[70] overflow-y-auto px-4 py-6 sm:px-0"
>
    <!-- Fons -->
    <div
        @click="show = false"
        class="fixed inset-0 bg-gray-500 opacity-75 transition-opacity"
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.18),rgba(15,23,42,0.7))] backdrop-blur-[2px]"></div>
    </div>

    <!-- Modāļa lodziņš -->
    <div
        x-show="show"
        @click.stop
        class="modal-liquid-panel modal-liquid-motion relative mx-auto mb-6 transform overflow-hidden rounded-[1.4rem] bg-white shadow-xl transition-all sm:mx-auto sm:w-full {{ $maxWidth }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
