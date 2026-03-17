<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Stack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16">
        <div class="grid gap-8 md:grid-cols-[1.2fr_0.8fr]">
            <section class="space-y-6">
                <div class="inline-flex rounded-full border border-cyan-400/30 bg-cyan-400/10 px-4 py-1 text-sm font-semibold text-cyan-200">
                    IT inventara parvaldiba
                </div>
                <div class="space-y-4">
                    <h1 class="text-4xl font-black tracking-tight text-white md:text-6xl">
                        Vienota iericu, lietotaju un pieprasijumu vide.
                    </h1>
                    <p class="max-w-2xl text-lg text-slate-300">
                        Sistema apvieno lietotajus viena tabula, atbalsta remontu, norakstisanas un iericu nodosanas pieprasijumus, un dod IT komandai vienu parskatamu darba plsmu.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                        Pieslegties
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-2xl border border-slate-700 px-5 py-3 text-sm font-semibold text-slate-100 transition hover:border-slate-500 hover:bg-slate-900">
                        Atvert paneli
                    </a>
                </div>
            </section>

            <section class="grid gap-4 rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-2xl shadow-cyan-950/30">
                <article class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                    <h2 class="text-lg font-semibold text-white">Lomas</h2>
                    <p class="mt-2 text-sm text-slate-300">`admin`, `it_worker`, `user` ar skaidri atdalitam tiesibam.</p>
                </article>
                <article class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                    <h2 class="text-lg font-semibold text-white">Lietotaja skats</h2>
                    <p class="mt-2 text-sm text-slate-300">Redz tikai savas ierices un var pieteikt remontu, norakstisanu vai nodosanu.</p>
                </article>
                <article class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                    <h2 class="text-lg font-semibold text-white">IT darba plusma</h2>
                    <p class="mt-2 text-sm text-slate-300">IT darbinieki un administratori izskata pieprasijumus, izveido remontus un maina iericu statusus.</p>
                </article>
            </section>
        </div>
    </main>
</body>
</html>
