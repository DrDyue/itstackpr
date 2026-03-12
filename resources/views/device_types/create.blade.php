<x-app-layout>
    <section class="type-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Jauns ierices tips</h1>
                <p class="device-page-subtitle">Pievieno jaunu klasifikatora ierakstu.</p>
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

        <form method="POST" action="{{ route('device-types.store') }}" class="type-form-grid">
            @csrf
            <div class="space-y-4">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pamata informacija</div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Tipa nosaukums *</label>
                            <input type="text" name="type_name" value="{{ old('type_name') }}" class="crud-control" required>
                        </div>
                        <div>
                            <label class="crud-label">Kategorija *</label>
                            <input type="text" name="category" value="{{ old('category') }}" class="crud-control" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Apraksts</label>
                        <textarea name="description" rows="4" class="crud-control">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Papildinformacija</div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Paredzamais kalposanas ilgums, gadi</label>
                        <input type="number" name="expected_lifetime_years" value="{{ old('expected_lifetime_years', 5) }}" min="0" class="crud-control">
                    </div>
                </div>

                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbibas</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary">Saglabat</button>
                        <a href="{{ route('device-types.index') }}" class="crud-btn-secondary">Atcelt</a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
