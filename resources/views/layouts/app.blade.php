{{--
    Layout: Galvenais autorizetais karkass.
    Atbildiba: nodrošina kopējo lapas ietvaru visām iekšējām sistēmas lapām pēc ielogošanās.
    Kāpēc tas ir svarīgi:
    1. Vienā vietā ielādē stilus un JavaScript.
    2. Iekļauj augšējo navigāciju un vietu, kur katra lapa ievieto savu saturu.
    3. Uztur reāllaika paziņojumu konteineru, ko izmanto admins un lietotājs.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>IT inventra uzskaite</title>

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
    <body class="layout-body-reset app-bg bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),transparent_32%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.12),transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] text-slate-900">
        @include('layouts.navigation')

        <main class="pb-10">
            {{ $slot }}
        </main>

        @php
            $flashMessage = session('success') ?? session('status');
            $flashTone = session('success') ? 'success' : (session('status') ? 'info' : null);
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
                        @else
                            <x-icon name="information-circle" size="h-4 w-4" />
                        @endif
                    </div>
                    <div class="flash-toast-body">
                        <div class="flash-toast-title">{{ $flashTone === 'success' ? 'Veiksmigi' : 'Pazinojums' }}</div>
                        <div class="flash-toast-message">{{ $flashMessage }}</div>
                    </div>
                    <button type="button" class="flash-toast-close" @click="open = false" aria-label="Aizvert">
                        <x-icon name="x-mark" size="h-4 w-4" />
                    </button>
                </div>
            </div>
        @endif

        @auth
            {{-- Šeit frontend polling režīmā saņem un attēlo toast paziņojumus. --}}
            @php
                $liveNotificationPageKind = request()->routeIs('repair-requests.index')
                    ? 'repair-requests'
                    : (request()->routeIs('writeoff-requests.index')
                        ? 'writeoff-requests'
                        : (request()->routeIs('device-transfers.index') ? 'device-transfers' : ''));
            @endphp
            <div
                x-data="liveRequestNotifications({
                    endpoint: @js(route('live-notifications.index')),
                    storageKey: @js('live-request-notifications:' . auth()->id() . ':' . (auth()->user()?->currentViewMode() ?? 'user')),
                    pollSeconds: 10,
                    pageKind: @js($liveNotificationPageKind),
                })"
                class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-[min(30rem,calc(100vw-1.5rem))] flex-col gap-3 sm:bottom-6 sm:right-6"
            >
                <template x-for="notification in items" :key="notification.id">
                    <div
                        x-cloak
                        x-show="notification.visible !== false"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="translate-y-5 scale-[0.96] opacity-0"
                        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                        x-transition:leave="transition ease-in duration-250"
                        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                        x-transition:leave-end="translate-y-4 scale-[0.97] opacity-0"
                        :class="accentClasses(notification.accent)"
                        class="notification-toast pointer-events-auto rounded-[1.5rem] border p-4 shadow-2xl backdrop-blur"
                    >
                        <button
                            type="button"
                            class="notification-toast-close inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-black/5 bg-white/80 text-slate-500 transition hover:bg-white hover:text-slate-900"
                            @click.stop="dismiss(notification.id)"
                        >
                            <x-icon name="x-mark" size="h-4 w-4" />
                        </button>

                        <button type="button" class="notification-toast-main min-w-0 w-full text-left" @click="open(notification)">
                                <div class="notification-toast-head">
                                    <div class="notification-toast-head-main min-w-0">
                                        <div class="notification-toast-topline">
                                            <span
                                                :class="badgeClasses(notification.accent)"
                                                class="notification-toast-badge inline-flex items-center justify-center rounded-2xl px-3 text-xs font-extrabold uppercase tracking-[0.14em] shadow-sm"
                                                x-text="badgeText(notification.type)"
                                            ></span>
                                            <template x-if="notification.details?.wait_label">
                                                <span class="notification-toast-wait" x-text="notification.details.wait_label"></span>
                                            </template>
                                        </div>
                                        <div class="notification-toast-title" x-text="notification.title"></div>
                                        <div class="notification-toast-copy" x-text="notification.message"></div>
                                    </div>
                                </div>

                                <template x-if="notification.details">
                                    <div class="notification-toast-body">
                                        <div class="notification-toast-meta">
                                            <div class="notification-toast-meta-item notification-toast-meta-item-wide">
                                                <div class="notification-toast-section-label">Ierice</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.device_name"></div>
                                            </div>
                                            <div class="notification-toast-meta-item">
                                                <div class="notification-toast-section-label">Pieteicejs</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.submitted_by"></div>
                                            </div>
                                            <div class="notification-toast-meta-item">
                                                <div class="notification-toast-section-label">Iesniegts</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.submitted_at || '-'"></div>
                                            </div>
                                            <div class="notification-toast-meta-item">
                                                <div class="notification-toast-section-label">Kods</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.device_code || '-'"></div>
                                            </div>
                                            <div class="notification-toast-meta-item">
                                                <div class="notification-toast-section-label">Serija</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.serial_number || '-'"></div>
                                            </div>
                                            <div class="notification-toast-meta-item notification-toast-meta-item-wide">
                                                <div class="notification-toast-section-label">Vieta</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.device_location"></div>
                                            </div>
                                            <div class="notification-toast-meta-item notification-toast-meta-item-wide" x-show="notification.details.recipient">
                                                <div class="notification-toast-section-label">Sanemejs</div>
                                                <div class="notification-toast-meta-value" x-text="notification.details.recipient"></div>
                                            </div>
                                            <div class="notification-toast-meta-item notification-toast-meta-item-wide">
                                                <div class="notification-toast-section-label" x-text="notification.details.reason_label"></div>
                                                <div class="notification-toast-meta-copy" x-text="notification.details.reason_value"></div>
                                            </div>
                                        </div>

                                        <div class="notification-toast-footer">
                                            <span class="notification-toast-link" x-text="notification.details.cta_label || 'Atvert pieprasijumu'"></span>
                                            <span class="notification-toast-link-arrow" aria-hidden="true">></span>
                                        </div>
                                    </div>
                                </template>
                        </button>

                        <template x-if="Array.isArray(notification.actions) && notification.actions.length > 0">
                            <div class="notification-toast-actions mt-4 flex flex-wrap gap-2 border-t border-black/5 pt-3">
                                <template x-for="action in notification.actions" :key="notification.id + '-' + action.label">
                                    <button
                                        type="button"
                                        :class="actionClasses(action.tone)"
                                        class="inline-flex items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold shadow-sm transition disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="notification.busy"
                                        @click.stop="runAction(notification, action)"
                                        x-text="notification.busy ? 'Apstrada...' : action.label"
                                    ></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        @endauth
    </body>
</html>
