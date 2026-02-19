<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IT Inventāra uzskaite') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
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
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                color: white;
                padding: 40px;
                display: none;
            }

            .auth-right-content h2 {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 16px;
            }

            .auth-right-content p {
                font-size: 16px;
                color: rgba(255, 255, 255, 0.9);
                margin-bottom: 32px;
                line-height: 1.6;
            }

            .auth-features {
                display: flex;
                flex-direction: column;
                gap: 16px;
                margin-top: 32px;
            }

            .feature-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }

            .feature-icon {
                font-size: 24px;
                flex-shrink: 0;
            }

            .feature-text {
                font-size: 14px;
            }

            @media (min-width: 1024px) {
                .auth-right {
                    display: flex;
                }

                .auth-left {
                    flex: 1;
                }
            }

            @media (max-width: 768px) {
                .auth-wrapper {
                    flex-direction: column;
                }

                .auth-left {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 0;">
        <div class="auth-wrapper">
            <!-- Left Side: Auth Form -->
            <div class="auth-left">
                <div class="auth-container" style="min-height: auto; padding: 0;">
                    <!-- Header -->
                    <div class="auth-header">
                        <a href="/" style="display: inline-block; margin-bottom: 24px; transition: transform 0.3s;">
                            <x-application-logo />
                        </a>
                        <h1>{{ config('app.name', 'IT Inventāra uzskaite') }}</h1>
                        <p>
                            @if(request()->routeIs('login'))
                                Laipni lūdzam atpakaļ
                            @elseif(request()->routeIs('register'))
                                Izveidojiet darbinieka kontu
                            @else
                                AtiestAtiet savu paroli
                            @endif
                        </p>
                    </div>

                    <!-- Main Card -->
                    <div class="auth-card">
                        <!-- Header -->
                        <div class="auth-card-header">
                            <h2>
                                @if(request()->routeIs('login'))
                                    Pierakstīšanās
                                @elseif(request()->routeIs('register'))
                                    Reģistrācija
                                @elseif(request()->routeIs('password.reset'))
                                    Atiestatīt paroli
                                @else
                                    Aizmirta parole
                                @endif
                            </h2>
                        </div>
                        
                        <!-- Form Content -->
                        <div class="auth-card-body">
                            {{ $slot }}
                        </div>

                        <!-- Footer -->
                        <div class="auth-footer">
                            <p>{{ config('app.name', 'LDZ IT Inventāra Sistēma') }} © 2024</p>
                        </div>
                    </div>

                    <!-- Info Text -->
                    <div class="auth-info">
                        <p>
                            @if(request()->routeIs('login'))
                                Admin lietotājs: admin@example.com | Parole: password
                            @else
                                Jums nepieciešams piekļuves atslēga, lai reģistrētos
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Info (Desktop only) -->
            <div class="auth-right">
                <div class="auth-right-content">
                    <h2>IT Inventāra Sistēma</h2>
                    <p>Ludzas Novada Pašvaldības moderni un efektīvi IT ierīču uzskaites risinājums</p>
                    
                    <div class="auth-features">
                        <div class="feature-item">
                            <span class="feature-icon">📊</span>
                            <div class="feature-text">
                                <strong>Reāllaikā Dati</strong><br>
                                Skatiet un pārvaldiet visas IT ierīces vienā vietā
                            </div>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">🏢</span>
                            <div class="feature-text">
                                <strong>Ēku Pārvaldība</strong><br>
                                Organizējiet ierīces pēc ēkām, stāviem un kabinetiem
                            </div>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📋</span>
                            <div class="feature-text">
                                <strong>Audita Žurnāls</strong><br>
                                Izsekojiet visas izmaiņas un darbības sistēmā
                            </div>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">🔧</span>
                            <div class="feature-text">
                                <strong>Remontu Pārvaldība</strong><br>
                                Efektīvi vadiet remontu un apkopes procesus
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
