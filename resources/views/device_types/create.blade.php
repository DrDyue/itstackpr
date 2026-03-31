{{--
    Lapa: Jauna ierīces tipa izveide.
    Atbildība: ļauj pievienot jaunu tipu, ko vēlāk izmanto ierīces formās un filtros.
    Datu avots: DeviceTypeController@create, saglabāšana caur DeviceTypeController@store.
    Galvenās daļas:
    1. Hero.
    2. Kļūdu kopsavilkums.
    3. Vienkāršota forma ar viena lauka ievadi.
--}}
<x-app-layout>
    <section class="type-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Jauns ierīces tips</h1>
                <p class="device-page-subtitle">Pievieno jaunu ierīces tipa nosaukumu klasifikatoram.</p>
            </div>
            <a href="{{ route('device-types.index') }}" class="type-back-link inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakaļ uz sarakstu
            </a>
        </div>

        <form method="POST" action="{{ route('device-types.store') }}" class="type-form-grid">
            @csrf

            <div class="space-y-4">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pamata informācija</div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Tipa nosaukums *</label>
                        <input type="text" name="type_name" value="{{ old('type_name') }}" class="crud-control @error('type_name') border-rose-300 bg-rose-50/60 focus:border-rose-400 focus:ring-rose-200 @enderror" required>
                        <div class="mt-2 text-xs text-slate-500">Katram ierīces tipam jābūt ar unikālu nosaukumu.</div>
                        @error('type_name')
                            <div class="mt-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbības</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="btn-create">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            Saglabāt
                        </button>
                        <a href="{{ route('device-types.index') }}" class="btn-clear">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Atcelt
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
