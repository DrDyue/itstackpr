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

        <style>
            .auth-wrapper {
                display: flex;
                min-height: 100vh;
                background: linear-gradient(135deg, #f5f5f7 0%, #ffffff 100%);
            }

            .auth-left {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 40px;
            }

            .auth-right {
                flex: 1;
                background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                display: none;
                align-items: center;
                justify-content: center;
                color: white;
                padding: 40px;
            }

            .auth-right-content h2 {
                font-size: 32px;
                font-weight: 700;
                margin: 0;
            }

            @media (min-width: 1024px) {
                .auth-right {
                    display: flex;
                }
            }

            @media (max-width: 768px) {
                .auth-left {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 0;">
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="auth-container" style="min-height: auto; padding: 0;">
                    <div class="auth-header">
                        <a href="/" style="display: inline-block; margin-bottom: 24px; transition: transform 0.3s;">
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
