<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>IT Inventara uzskaites sistema</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/cssfamily=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="layout-body-reset bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.14),transparent_28%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_100%)]">
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="auth-container auth-container-compact">
                    <div class="auth-header">
                        <a href="/" class="auth-logo-link">
                            <x-application-logo />
                        </a>
                        <h1>IT Inventara uzskaites sistema</h1>
                        <p>
                            @if (request()->routeIs('login'))
                                Laipni ludzam atpakal
                            @elseif (request()->routeIs('register'))
                                Izveidojiet darbinieka kontu
                            @else
                                Atiestatiet savu paroli
                            @endif
                        </p>
                    </div>

                    <div class="auth-card">
                        <div class="auth-card-header">
                            <h2>
                                @if (request()->routeIs('login'))
                                    Pierakstisanas
                                @elseif (request()->routeIs('register'))
                                    Registracija
                                @elseif (request()->routeIs('password.reset'))
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
                            <p>IT Inventara uzskaites sistema 2026</p>
                        </div>
                    </div>

                    <div class="auth-info space-y-1">
                        @if (request()->routeIs('login'))
                            <p>Demo admin: artis.berzins@ludzas.lv | Parole: password</p>
                            <p>Demo user: ilze.strautina@ludzas.lv | Parole: password</p>
                        @else
                            <p>Jaunu lietotaju izveido administrators.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="auth-right">
                <div class="auth-right-content max-w-md">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/90 ring-1 ring-white/20">
                        <x-icon name="dashboard" size="h-4 w-4" />
                        <span>IT inventara sistema</span>
                    </div>
                    <h2 class="mt-6">Parskatama inventara vide ikdienas darbam</h2>
                    <div class="mt-6 grid gap-3">
                        <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/20">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-white">
                                <x-icon name="device" size="h-4 w-4" />
                                <span>Ierices un telpas</span>
                            </div>
                            <p class="mt-2 text-sm text-white/80">Vienuviet redzama iericu piesaiste, telpas un atbildigie lietotaji.</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/20">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-white">
                                <x-icon name="repair-request" size="h-4 w-4" />
                                <span>Pieteikumu plusmas</span>
                            </div>
                            <p class="mt-2 text-sm text-white/80">Remonts, norakstisana un parsutisanas ir uzreiz atrodamas un saprotamas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

