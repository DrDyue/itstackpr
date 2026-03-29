{{--
    Lapa: Mans profils.
    Atbildība: ļauj lietotājam atjaunot savus personīgos datus un nomainīt paroli.
    Datu avots: ProfileController@edit, saglabāšana caur ProfileController@update un PasswordController@update.
    Galvenās daļas:
    1. Hero ar profila kopsavilkumu.
    2. Kreisais bloks ar personīgās informācijas formu.
    3. Labais bloks ar paroles maiņu.
--}}
<x-app-layout>
    <section class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700 ring-1 ring-sky-200">
                        Profils
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Mans profils</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Te vari atjaunot savu vārdu, kontaktinformāciju un nomainīt paroli.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Loma</div>
                        <div class="mt-2 text-base font-semibold text-slate-900">{{ $user->role }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Statuss</div>
                        <div class="mt-2 text-base font-semibold {{ $user->is_active ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $user->is_active ? 'Aktivs' : 'Neaktivs' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kreisajā pusē ir profila dati, labajā pusē paroles maiņas panelis. --}}
            <div class="grid gap-5 px-5 py-5 sm:px-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.9fr)]">
                <div class="space-y-5 rounded-[2rem] bg-slate-100/80 p-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
                    @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="space-y-5 xl:sticky xl:top-6 xl:self-start rounded-[2rem] bg-sky-50/70 p-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
