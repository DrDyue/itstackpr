<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>IT inventra uzskaite</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="layout-body-reset app-bg bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),transparent_32%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.12),transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] text-slate-900">
        @include('layouts.navigation')

        <main class="pb-10">
            {{ $slot }}
        </main>
    </body>
</html>


