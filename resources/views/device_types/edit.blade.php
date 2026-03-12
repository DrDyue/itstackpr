<x-app-layout>
    <section class="type-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Rediget ierices tipu</h1>
                <p class="device-page-subtitle">Atjauno klasifikatora ierakstu, saglabajot skaidru strukturu un vienotu nosaukumu logiku.</p>
            </div>
            <a href="{{ route('device-types.index') }}" class="type-back-link">Atpakal uz sarakstu</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('device-types.update', $type) }}" class="type-form-grid">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div>
                            <div class="device-form-section-name">Pamata informacija</div>
                            <p class="type-form-note">Maini nosaukumu, kategoriju vai aprakstu, kas redzams klasifikatora saraksta.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Tipa nosaukums *</label>
                            <input type="text" name="type_name" value="{{ old('type_name', $type->type_name) }}" class="crud-control" required>
                            <p class="type-field-hint">Izmanto vienotu nosaukumu stilu visiem tipiem.</p>
                        </div>
                        <div>
                            <label class="crud-label">Kategorija *</label>
                            <input type="text" name="category" value="{{ old('category', $type->category) }}" class="crud-control" required>
                            <p class="type-field-hint">Kategorija tiek izmantota meklesana un skirosana.</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Apraksts</label>
                        <textarea name="description" rows="4" class="crud-control">{{ old('description', $type->description) }}</textarea>
                        <p class="type-field-hint">Apraksts nav obligats, bet uzlabo saprotamibu saraksta.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div>
                            <div class="device-form-section-name">Papildinformacija</div>
                            <p class="type-form-note">Papildu parametrs iericu dzives cikla planošanai.</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Paredzamais kalposanas ilgums</label>
                        <input type="number" name="expected_lifetime_years" value="{{ old('expected_lifetime_years', $type->expected_lifetime_years) }}" min="0" class="crud-control">
                        <p class="type-field-hint">Noradi gados. Ja nav skaidrs, vertibu var atstat tuksu vai 0.</p>
                    </div>
                </div>

                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div>
                            <div class="device-form-section-name">Darbibas</div>
                            <p class="type-form-note">Saglabajiet izmainas vai atgriezieties saraksta bez labojumiem.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary">Atjaunot</button>
                        <a href="{{ route('device-types.index') }}" class="crud-btn-secondary">Atcelt</a>
                    </div>
                    <div class="type-action-help">
                        Sis ieraksts saglabas saiti ar jau piesaistitajam iericem.
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
