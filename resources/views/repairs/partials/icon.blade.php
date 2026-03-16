@php
    $class = $class ?? 'h-4 w-4';
@endphp

@switch($name ?? '')
    @case('clock')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25m5.25-2.25a9.75 9.75 0 1 1-19.5 0 9.75 9.75 0 0 1 19.5 0Z"/>
        </svg>
        @break

    @case('wrench')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 0 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 0 0-8.69-8.69Z"/>
        </svg>
        @break

    @case('check')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
        </svg>
        @break

    @case('x-mark')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
        </svg>
        @break

    @case('arrow-down')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v13.5m0 0-4.5-4.5m4.5 4.5 4.5-4.5"/>
        </svg>
        @break

    @case('bars')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5h13.5M5.25 12h13.5M5.25 16.5h13.5"/>
        </svg>
        @break

    @case('arrow-up')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 5.25 4.5 4.5M12 5.25l-4.5 4.5M12 5.25v13.5"/>
        </svg>
        @break

    @case('flame')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75c.92 2.58 3 4.4 3 7.125 0 1.684-.809 3.188-2.062 4.14A4.49 4.49 0 0 0 10.5 10.5c-2.012 1.17-3.375 3.4-3.375 5.813A4.875 4.875 0 0 0 12 21.188a4.875 4.875 0 0 0 4.875-4.875c0-3.305-2.08-5.307-4.875-7.688Z"/>
        </svg>
        @break

    @case('truck')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25h6m-12 0h1.5m0 0a1.5 1.5 0 1 0 3 0m-3 0a1.5 1.5 0 1 1 3 0m10.5 0h1.5m-1.5 0a1.5 1.5 0 1 0 3 0m-3 0a1.5 1.5 0 1 1 3 0M3 6.75A2.25 2.25 0 0 1 5.25 4.5h9A2.25 2.25 0 0 1 16.5 6.75v6h1.629a2.25 2.25 0 0 1 1.59.659l1.622 1.621a2.25 2.25 0 0 1 .659 1.59v.63h-5.25m-13.5 0V6.75Z"/>
        </svg>
        @break

    @default
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
        </svg>
@endswitch
