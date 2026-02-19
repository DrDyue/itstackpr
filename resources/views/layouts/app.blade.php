<!DOCTYPE html>
<html lang="lv">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>IT inventra uzskaite</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body style="margin: 0; padding: 0; background: #f5f5f7;">
        @include('layouts.navigation')

        <main>
            {{ $slot }}
        </main>
    </body>
</html>
