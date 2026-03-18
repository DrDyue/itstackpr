<x-app-layout>
    <section class="type-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Rediget ierices tipu</h1>
                <p class="device-page-subtitle">Atjauno klasifikatora ierakstu.</p>
            </div>
            <a href="{{ route('device-types.index') }}" class="type-back-link inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakal uz sarakstu
            </a>
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
            <div class="space-y-4">
                <div class="type-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pamata informacija</div>
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
                        <div class="device-form-section-name">Darbibas</div>
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
