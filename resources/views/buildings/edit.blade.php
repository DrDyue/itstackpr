{--
    Lapa: Ēkas rediģēšana.
    Atbildība: ļauj atjaunot ēkas pamatdatus, kuri ietekmē telpu un ierīču piesaisti.
    Datu avots: BuildingController@edit, saglabāšana caur BuildingController@update.
    Galvenās daļas:
    1. Hero ar rediģēšanas kontekstu.
    2. Validācijas ziņojumi.
    3. Ēkas rediģēšanas forma.
--}}
<x-app-layout>
    <section class="app-shell max-w-3xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labošana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="building" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediģēt ēku</h1>
                            <p class="page-subtitle">Atjauno ēkas pamata datus un piezīmes.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('buildings.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ uz sarakstu</span></a>
            </div>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('buildings.update', $building) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="form-page-grid">
                <div class="form-page-main">
                    <div class="surface-card space-y-4">
                        <div>
                            <label class="crud-label">Nosaukums *</label>
                            <input type="text" name="building_name" value="{{ old('building_name', $building->building_name) }}" class="crud-control" required>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="crud-label">Pilsēta</label>
                                <input type="text" name="city" value="{{ old('city', $building->city) }}" class="crud-control">
                            </div>
                            <div>
                                <label class="crud-label">Stāvu skaits</label>
                                <input type="number" name="total_floors" value="{{ old('total_floors', $building->total_floors) }}" class="crud-control" min="0">
                            </div>
                        </div>

                        <div>
                            <label class="crud-label">Adrese</label>
                            <input type="text" name="address" value="{{ old('address', $building->address) }}" class="crud-control">
                        </div>

                        <div>
                            <label class="crud-label">Piezīmes</label>
                            <textarea name="notes" rows="3" class="crud-control">{{ old('notes', $building->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <aside class="form-page-aside">
                    <div class="form-page-note">
                        <div class="form-page-note-title">Pirms saglabāšanas</div>
                        <div class="form-page-note-copy">Ja ēkas nosaukums vai adrese mainās, tas uzreiz ietekmēs saistīto telpu un ierīču attēlojumu visā sistēmā.</div>
                    </div>
                </aside>
            </div>

            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Atjaunini ēkas datus</div>
                    <div class="form-page-actions-text">Saglabā tikai pārbaudītu informāciju, jo ēkas ieraksts tiek izmantots filtros un piesaistes laukos.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Atjaunināt</span></button>
                    <a href="{{ route('buildings.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
