{{-- SVG ikonu komponents visam interfeisam. --}}
@props(['name', 'size' => 'h-5 w-5'])

@switch($name)
    @case('dashboard')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12a2.25 2.25 0 0 1 2.25-2.25h2.25A2.25 2.25 0 0 1 10.5 12v6A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-6Zm9.75-6A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18V6Zm-4.5 3.75A2.25 2.25 0 0 1 11.25 12v6A2.25 2.25 0 0 1 9 20.25H6.75A2.25 2.25 0 0 1 4.5 18v-6A2.25 2.25 0 0 1 6.75 9.75H9Z" />
        </svg>
        @break

    @case('device')
    @case('devices')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75A2.25 2.25 0 0 1 6.75 4.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 16.5h6" />
        </svg>
        @break

    @case('repair-request')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6.75l4.5 4.5V18A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75V8.25H18.75M8.25 12h7.5M8.25 15.75h5.25" />
        </svg>
        @break

    @case('repair')
    @case('wrench')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 1 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 1 0-8.69-8.69Z" />
        </svg>
        @break

    @case('writeoff')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        @break

    @case('transfer')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h11.25m0 0-3-3m3 3-3 3M16.5 16.5H5.25m0 0 3 3m-3-3 3-3" />
        </svg>
        @break

    @case('building')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M6.75 21V6.75A2.25 2.25 0 0 1 9 4.5h6A2.25 2.25 0 0 1 17.25 6.75V21" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 8.25h.008v.008H9.75V8.25Zm0 3h.008v.008H9.75v-.008Zm0 3h.008v.008H9.75v-.008Zm4.5-6h.008v.008H14.25V8.25Zm0 3h.008v.008H14.25v-.008Zm0 3h.008v.008H14.25v-.008Z" />
        </svg>
        @break

    @case('room')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 21h15M6.75 21V7.5A2.25 2.25 0 0 1 9 5.25h6A2.25 2.25 0 0 1 17.25 7.5V21" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 12.75h3" />
        </svg>
        @break

    @case('type')
    @case('tag')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 3.75 3.75-9.75 9.75H6.75v-3.75L16.5 6.75Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 8.25h.008v.008H15V8.25Z" />
        </svg>
        @break

    @case('user')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.93 17.93 0 0 1 12 21.75a17.93 17.93 0 0 1-7.5-1.632Z" />
        </svg>
        @break

    @case('users')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a3 3 0 0 0-6 0m6 0v.75m-6-.75v.75m6-8.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0ZM10.5 18.75a3 3 0 0 0-6 0m6 0v.75m-6-.75v.75m6-8.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
        </svg>
        @break

    @case('audit')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-9-5.25h12A2.25 2.25 0 0 1 20.25 6.75v5.568a5.25 5.25 0 0 1-2.06 4.164l-4.44 3.33a2.25 2.25 0 0 1-2.7 0l-4.44-3.33a5.25 5.25 0 0 1-2.06-4.164V6.75A2.25 2.25 0 0 1 6 4.5Z" />
        </svg>
        @break

    @case('profile')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0A9 9 0 1 0 6.018 18.725m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        @break

    @case('logout')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15 15 12m0 0-3-3m3 3H3.75" />
        </svg>
        @break

    @case('plus')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        @break

    @case('search')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
        </svg>
        @break

    @case('clear')
    @case('x-mark')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
        @break

    @case('back')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        @break

    @case('edit')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5" />
        </svg>
        @break

    @case('view')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
        </svg>
        @break

    @case('trash')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        @break

    @case('save')
    @case('check')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
        @break

    @case('send')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.27 3.873a.75.75 0 0 1 1.05-.91L21 12 4.32 21.037a.75.75 0 0 1-1.05-.91L6 12Zm0 0h7.5" />
        </svg>
        @break

    @case('clock')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25m5.25-2.25a9.75 9.75 0 1 1-19.5 0 9.75 9.75 0 0 1 19.5 0Z" />
        </svg>
        @break

    @case('calendar')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75v3m7.5-3v3M3.75 8.25h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v11.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" />
        </svg>
        @break

    @case('mail')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9m19.5 0A2.25 2.25 0 0 0 19.5 5.25h-15A2.25 2.25 0 0 0 2.25 7.5m19.5 0-8.69 5.517a2.25 2.25 0 0 1-2.12 0L2.25 7.5" />
        </svg>
        @break

    @case('key')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a4.5 4.5 0 1 1-4.243 6.01L4.5 18.268V21h2.732l7.007-7.007A4.5 4.5 0 0 1 15.75 5.25Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8.25h.008v.008H18V8.25Z" />
        </svg>
        @break

    @case('check-circle')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        @break

    @case('x-circle')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.25 9.75-4.5 4.5m0-4.5 4.5 4.5m6-2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        @break

    @case('exclamation-triangle')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm8.625 2.228L13.81 4.26a2.062 2.062 0 0 0-3.62 0L3.375 18.732A2.062 2.062 0 0 0 5.186 21.75h13.628a2.062 2.062 0 0 0 1.811-3.018Z" />
        </svg>
        @break

    @case('stats')
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v18m16.5-9-4.5-4.5-4.5 4.5-3-3L3.75 12" />
        </svg>
        @break

    @default
        <svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->class($size) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
@endswitch
