<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Datubāzes kļūda' }} | IT inventāra uzskaite</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <section class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
                <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <x-icon name="exclamation-triangle" size="h-6 w-6" />
                </div>

                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Sistēmas paziņojums</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950 sm:text-3xl">{{ $title ?? 'Datubāzes kļūda' }}</h1>
                <p class="mt-4 text-sm leading-6 text-slate-600">{{ $message }}</p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                        Atgriezties
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Uz sākumu
                    </a>
                </div>
            </section>
        </main>
    </body>
</html>
