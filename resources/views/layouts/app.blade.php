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

        @auth
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
                                        <template x-if="notification.details">
                                            <div class="notification-toast-overview">
                                                <div class="notification-toast-overview-row">
                                                    <span class="notification-toast-overview-label">Pieteicejs</span>
                                                    <span class="notification-toast-overview-value" x-text="notification.details.submitted_by || 'Lietotajs'"></span>
                                                </div>
                                                <div class="notification-toast-overview-row">
                                                    <span class="notification-toast-overview-label">Ierice</span>
                                                    <span class="notification-toast-overview-value" x-text="notification.details.device_name || 'Ierice'"></span>
                                                </div>
                                                <template x-if="notification.details.recipient">
                                                    <div class="notification-toast-overview-row">
                                                        <span class="notification-toast-overview-label">Sanemejs</span>
                                                        <span class="notification-toast-overview-value" x-text="notification.details.recipient"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!notification.details">
                                            <div class="notification-toast-copy" x-text="notification.message"></div>
                                        </template>
                                    </div>
                                </div>

                                <template x-if="notification.details">
                                    <div class="notification-toast-panel">
                                        <div class="notification-toast-grid">
                                            <div class="notification-toast-info notification-toast-info-wide">
                                                <div class="notification-toast-section-label">Ierice</div>
                                                <div class="notification-toast-device-name" x-text="notification.details.device_name"></div>
                                                <div class="notification-toast-device-line">
                                                    <span x-text="'Kods: ' + (notification.details.device_code || '-')"></span>
                                                    <span aria-hidden="true">|</span>
                                                    <span x-text="'Serija: ' + (notification.details.serial_number || '-')"></span>
                                                </div>
                                                <div class="notification-toast-device-meta" x-text="notification.details.device_meta"></div>
                                                <div class="notification-toast-device-meta" x-text="notification.details.device_location"></div>
                                            </div>

                                            <template x-if="notification.details.recipient">
                                                <div class="notification-toast-info">
                                                    <div class="notification-toast-section-label">Sanemejs</div>
                                                    <div class="notification-toast-info-value" x-text="notification.details.recipient"></div>
                                                </div>
                                            </template>

                                            <template x-if="notification.details.submitted_at">
                                                <div class="notification-toast-info">
                                                    <div class="notification-toast-section-label">Iesniegts</div>
                                                    <div class="notification-toast-info-value" x-text="notification.details.submitted_at"></div>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="notification-toast-reason">
                                            <div class="notification-toast-section-label" x-text="notification.details.reason_label"></div>
                                            <div class="notification-toast-reason-copy" x-text="notification.details.reason_value"></div>
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


