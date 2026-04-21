{{--
    Layout: Viesu sadaļas karkass.
    Atbildība: apkalpo autorizācijas, paroles atjaunošanas un citas lapas, kur lietotājs vēl nav ielogojies.
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

        <title>IT inventāra uzskaites sistēma</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/cssfamily=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script>
            (() => {
                try {
                    const savedTheme = window.localStorage.getItem('itstack-theme');
                    const theme = savedTheme === 'dark' ? 'dark' : 'light';
                    document.documentElement.dataset.theme = theme;
                    document.documentElement.style.colorScheme = theme;
                } catch (error) {
                    document.documentElement.dataset.theme = 'light';
                    document.documentElement.style.colorScheme = 'light';
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="layout-body-reset guest-bg bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.14),transparent_28%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_100%)]">
        @include('layouts.loading-indicator')
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="auth-container auth-container-compact">
                    <div class="auth-header">
                        <a href="/" class="auth-logo-link">
                            <x-application-logo />
                        </a>
                        <h1>IT inventāra uzskaites sistēma</h1>
                        <p>
                            @if (request()->routeIs('login'))
                                Laipni lūdzam atpakaļ
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
                                    Pierakstīšanās
                                @elseif (request()->routeIs('register'))
                                    Reģistrācija
                                @elseif (request()->routeIs('password.reset'))
                                    Atiestatīt paroli
                                @else
                                    Aizmirsta parole
                                @endif
                            </h2>
                        </div>

                        <div class="auth-card-body">
                            {{ $slot }}
                        </div>

                        <div class="auth-footer">
                            <p>IT inventāra uzskaites sistēma 2026</p>
                        </div>
                    </div>

                    <div class="auth-info space-y-1">
                        @if (request()->routeIs('login'))
                            <p>Demo admins: artis.berzins@ludzas.lv | Parole: password</p>
                            <p>Demo darbinieks: ilze.strautina@ludzas.lv | Parole: password</p>
                        @else
                            <p>Jaunu lietotāju izveido administrators.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="auth-right">
                <div class="auth-right-content max-w-md">
                    <h2>IT inventāra sistēma</h2>
                </div>
            </div>
        </div>

        @php
            $flashMessage = session('success') ?? session('error') ?? session('status');
            $flashTone = session('success') ? 'success' : (session('error') ? 'error' : (session('status') ? 'info' : null));
        @endphp
        @if ($flashMessage)
            <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 3800)" class="pointer-events-none fixed bottom-4 right-4 z-[70] flex w-[min(26rem,calc(100vw-1.5rem))] flex-col sm:bottom-6 sm:right-6">
                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-y-5 scale-[0.96] opacity-0"
                    x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                    x-transition:leave="transition ease-in duration-250"
                    x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                    x-transition:leave-end="translate-y-4 scale-[0.97] opacity-0"
                    class="flash-toast flash-toast-{{ $flashTone }}"
                >
                    <div class="flash-toast-icon">
                        @if ($flashTone === 'success')
                            <x-icon name="check-circle" size="h-4 w-4" />
                        @elseif ($flashTone === 'error')
                            <x-icon name="x-circle" size="h-4 w-4" />
                        @else
                            <x-icon name="information-circle" size="h-4 w-4" />
                        @endif
                    </div>
                    <div class="flash-toast-body">
                        <div class="flash-toast-title">{{ $flashTone === 'success' ? 'Veiksmīgi' : 'Paziņojums' }}</div>
                        <div class="flash-toast-message">{{ $flashMessage }}</div>
                    </div>
                    <button type="button" class="flash-toast-close" @click="open = false" aria-label="Aizvērt">
                        <x-icon name="x-mark" size="h-4 w-4" />
                    </button>
                </div>
            </div>
        @endif
    </body>
</html>
