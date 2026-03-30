{{--
    Lapa: Ierīces tipa rediģēšana.
    Atbildība: ļauj atjaunot tipa nosaukumu, kategoriju un aprakstu.
    Datu avots: DeviceTypeController@edit, saglabāšana caur DeviceTypeController@update.
    Galvenās daļas:
    1. Hero zona.
    2. Validācijas paziņojumi.
    3. Rediģēšanas forma.
--}}
<x-app-layout>
    <section class="type-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Rediģēt ierīces tipu</h1>
                <p class="device-page-subtitle">Atjauno klasifikatora ierakstu.</p>
            </div>
            <a href="{{ route('device-types.index') }}" class="type-back-link inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakaļ uz sarakstu
            </a>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('device-types.update', $type) }}" class="type-form-grid">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pamata informācija</div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Tipa nosaukums *</label>
                            <input type="text" name="type_name" value="{{ old('type_name', $type->type_name) }}" class="crud-control" required>
                        </div>
                        <div>
                            <label class="crud-label">Kategorija *</label>
                            <input type="text" name="category" value="{{ old('category', $type->category) }}" class="crud-control" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Apraksts</label>
                        <textarea name="description" rows="4" class="crud-control">{{ old('description', $type->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbības</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="btn-edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            Atjaunot
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
