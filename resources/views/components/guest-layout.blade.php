{{-- resources/views/components/guest-layout.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>IT inventra uzskaite</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="layout-body-reset app-bg">
        <main class="guest-main-card">
            {{ $slot }}
        </main>
    </body>
</html>


