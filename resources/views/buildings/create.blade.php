{{--
    Lapa: Jaunas ēkas izveide.
    Atbildība: ļauj administratoram sagatavot jaunu ēkas ierakstu telpu un ierīču piesaistei.
    Datu avots: BuildingController@create, saglabāšana caur BuildingController@store.
    Galvenās daļas:
    1. Hero ar lapas skaidrojumu.
    2. Validācijas kopsavilkums.
    3. Ēkas datu forma.
--}}
<x-app-layout>
    <section class="app-shell max-w-3xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="building" size="h-4 w-4" /><span>Jauna ēka</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="building" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauna ēka</h1>
                            <p class="page-subtitle">Pievieno jaunu ēku un sagatavo to telpu piesaistei.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('buildings.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ uz sarakstu</span></a>
            </div>
        </div>

        <x-validation-summary />

        {{-- Ēkas forma satur pamatdatus, kurus vēlāk izmantos telpu un ierīču piesaistei. --}}
        <form method="POST" action="{{ route('buildings.store') }}" class="space-y-6">
            @csrf

            <div class="form-page-grid">
                <div class="form-page-main">
                    <div class="surface-card space-y-4">
                        <div>
                            <label class="crud-label">Nosaukums *</label>
                            <input type="text" name="building_name" value="{{ old('building_name') }}" class="crud-control" required>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="crud-label">Pilsēta</label>
                                <input type="text" name="city" value="{{ old('city') }}" class="crud-control">
                            </div>
                            <div>
                                <label class="crud-label">Stāvu skaits</label>
                                <input type="number" name="total_floors" value="{{ old('total_floors') }}" class="crud-control" min="0">
                            </div>
                        </div>

                        <div>
                            <label class="crud-label">Adrese</label>
                            <input type="text" name="address" value="{{ old('address') }}" class="crud-control">
                        </div>

                        <div>
                            <label class="crud-label">Piezīmes</label>
                            <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <aside class="form-page-aside">
                    <div class="form-page-note">
                        <div class="form-page-note-title">Kas ir svarīgākais</div>
                        <div class="form-page-note-copy">Ēkas nosaukums un stāvu skaits vēlāk ietekmēs telpu filtrēšanu un saistīto ierīču pārskatus.</div>
                    </div>
                </aside>
            </div>

            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Saglabā ēkas ierakstu</div>
                    <div class="form-page-actions-text">Kad ēka būs izveidota, tai varēsi piesaistīt telpas un pēc tam arī inventāru.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-create"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                    <a href="{{ route('buildings.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
