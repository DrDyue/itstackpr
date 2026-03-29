{{--
    Layout: Viesu sadaļas karkass.
    Atbildiba: apkalpo autorizācijas, paroles atjaunošanas un citas lapas, kur lietotājs vēl nav ielogojies.
    Kāpēc tas ir svarīgi:
    1. Uztur vieglāku vizuālo izkārtojumu bez iekšējās navigācijas.
    2. Vienā vietā definē visu auth lapu kopējo stilu un fonu.
    3. Nodrošina vienotu ievades pieredzi login, reģistrācijas un paroles atjaunošanas skatam.
--}}
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
                    <h2>IT inventara sistema</h2>
                </div>
            </div>
        </div>
    </body>
</html>
