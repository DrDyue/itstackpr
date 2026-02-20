<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauns ierīces tips</h1>
            <a href="{{ route('device-types.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
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

        <form method="POST" action="{{ route('device-types.store') }}" class="crud-form-card">
            @csrf
            <div>
                <label class="crud-label">Tipa nosaukums *</label>
                <input type="text" name="type_name" value="{{ old('type_name') }}" class="crud-control" required>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Kategorija *</label>
                    <input type="text" name="category" value="{{ old('category') }}" class="crud-control" required>
                </div>
                <div>
                    <label class="crud-label">Ikonas nosaukums</label>
                    <input type="text" name="icon_name" value="{{ old('icon_name') }}" class="crud-control">
                </div>
            </div>
            <div>
                <label class="crud-label">Paredzamais kalpošanas ilgums (gadi)</label>
                <input type="number" name="expected_lifetime_years" value="{{ old('expected_lifetime_years', 5) }}" min="0" class="crud-control">
            </div>
            <div>
                <label class="crud-label">Apraksts</label>
                <textarea name="description" rows="3" class="crud-control">{{ old('description') }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabāt</button>
                <a href="{{ route('device-types.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


