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

    @case('device')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75A2.25 2.25 0 0 1 6.75 4.5Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 16.5h6"/>
        </svg>
        @break

    @case('building')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V6.75A2.25 2.25 0 0 1 7.5 4.5h9A2.25 2.25 0 0 1 18.75 6.75V21"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25h.008v.008H9V8.25Zm0 3h.008v.008H9v-.008Zm0 3h.008v.008H9v-.008Zm6-6h.008v.008H15V8.25Zm0 3h.008v.008H15v-.008Zm0 3h.008v.008H15v-.008Z"/>
        </svg>
        @break

    @case('room')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21H3M6 21V7.5A1.5 1.5 0 0 1 7.5 6h9A1.5 1.5 0 0 1 18 7.5V21"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 12.75h3"/>
        </svg>
        @break

    @case('user')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.93 17.93 0 0 1 12 21.75a17.93 17.93 0 0 1-7.5-1.632Z"/>
        </svg>
        @break

    @case('users')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-6 0m6 0v.75m-6-.75v.75m6-8.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0ZM10.5 18.75a3 3 0 0 0-6 0m6 0v.75m-6-.75v.75m6-8.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
        </svg>
        @break

    @case('calendar')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75v3m7.5-3v3M3.75 8.25h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v11.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/>
        </svg>
        @break

    @case('money')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m3.75-9.75H10.5a2.25 2.25 0 0 0 0 4.5h3a2.25 2.25 0 0 1 0 4.5H8.25"/>
        </svg>
        @break

    @case('document')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25V8.625a2.625 2.625 0 0 0-.769-1.856l-3.75-3.75A2.625 2.625 0 0 0 13.125 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15A2.25 2.25 0 0 0 6.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-5.25Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3v4.5H18"/>
        </svg>
        @break

    @case('note')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h15v15h-15z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9h7.5M8.25 12.75h7.5M8.25 16.5h4.5"/>
        </svg>
        @break

    @case('tag')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 3.75 3.75-9.75 9.75H6.75v-3.75L16.5 6.75Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 8.25h.008v.008H15V8.25Z"/>
        </svg>
        @break

    @default
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
        </svg>
@endswitch
