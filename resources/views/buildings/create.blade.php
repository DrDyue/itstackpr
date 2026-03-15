<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauna eka</h1>
            <a href="{{ route('buildings.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
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

        <form method="POST" action="{{ route('buildings.store') }}" class="crud-form-card">
            @csrf

            <div>
                <label class="crud-label">Nosaukums *</label>
                <input type="text" name="building_name" value="{{ old('building_name') }}" class="crud-control" required>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Pilseta</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Stavu skaits</label>
                    <input type="number" name="total_floors" value="{{ old('total_floors') }}" class="crud-control" min="0">
                </div>
            </div>

            <div>
                <label class="crud-label">Adrese</label>
                <input type="text" name="address" value="{{ old('address') }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezimes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Saglabat
                </button>
                <a href="{{ route('buildings.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                    Atcelt
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
