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
            <div
                x-data="liveRequestNotifications({
                    endpoint: @js(route('live-notifications.index')),
                    storageKey: @js('live-request-notifications:' . auth()->id() . ':' . (auth()->user()?->currentViewMode() ?? 'user')),
                    pollSeconds: 10,
                })"
                class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3 sm:bottom-6 sm:right-6"
            >
                <template x-for="notification in items" :key="notification.id">
                    <div
                        x-cloak
                        :class="accentClasses(notification.accent)"
                        class="pointer-events-auto rounded-[1.5rem] border p-4 shadow-2xl backdrop-blur"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                :class="badgeClasses(notification.accent)"
                                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-2xl px-3 text-xs font-extrabold uppercase tracking-[0.14em] shadow-sm"
                                x-text="badgeText(notification.type)"
                            ></span>
                            <button type="button" class="min-w-0 flex-1 text-left" @click="open(notification)">
                                <div class="text-sm font-semibold" x-text="notification.title"></div>
                                <div class="mt-1 text-sm leading-6 opacity-90" x-text="notification.message"></div>
                                <div class="mt-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] opacity-75">
                                    <span>Atvert pieprasijumu</span>
                                    <span aria-hidden="true">></span>
                                </div>
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-black/5 bg-white/80 text-slate-500 transition hover:bg-white hover:text-slate-900"
                                @click="dismiss(notification.id)"
                            >
                                <x-icon name="x-mark" size="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        @endauth
    </body>
</html>


