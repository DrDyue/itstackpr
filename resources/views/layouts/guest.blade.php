<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>IT Inventory System</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/cssfamily=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="layout-body-reset">
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="auth-container auth-container-compact">
                    <div class="auth-header">
                        <a href="/" class="auth-logo-link">
                            <x-application-logo />
                        </a>
                        <h1>IT Inventory System</h1>
                        <p>
                            @if(request()->routeIs('login'))
                                Laipni ludzam atpakal
                            @elseif(request()->routeIs('register'))
                                Izveidojiet darbinieka kontu
                            @else
                                Atiestatiet savu paroli
                            @endif
                        </p>
                    </div>

                    <div class="auth-card">
                        <div class="auth-card-header">
                            <h2>
                                @if(request()->routeIs('login'))
                                    Pierakstisanas
                                @elseif(request()->routeIs('register'))
                                    Registracija
                                @elseif(request()->routeIs('password.reset'))
                                    Atiestatit paroli
                                @else
                                    Aizmirsta parole
                                @endif
                            </h2>
                        </div>

                        <div class="auth-card-body">
                            {{ $slot }}
                        </div>

                        <div class="auth-footer">
                            <p>IT Inventory System  2026</p>
                        </div>
                    </div>

                    <div class="auth-info">
                        <p>
                            @if(request()->routeIs('login'))
                                Demo: artis.berzins@ludzas.lv | Parole: password
                            @else
                                Jaunu lietotaju izveido administrators.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="auth-right">
                <div class="auth-right-content">
                    <h2>IT Inventory System</h2>
                </div>
            </div>
        </div>
    </body>
</html>


