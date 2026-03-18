<x-app-layout>
    <section class="app-shell max-w-3xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="building" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediget eku</h1>
                            <p class="page-subtitle">Atjauno ekas pamata datus un piezimes.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('buildings.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakal uz sarakstu</span></a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('buildings.update', $building) }}" class="surface-card space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="crud-label">Nosaukums *</label>
                <input type="text" name="building_name" value="{{ old('building_name', $building->building_name) }}" class="crud-control" required>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Pilseta</label>
                    <input type="text" name="city" value="{{ old('city', $building->city) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Stavu skaits</label>
                    <input type="number" name="total_floors" value="{{ old('total_floors', $building->total_floors) }}" class="crud-control" min="0">
                </div>
            </div>

            <div>
                <label class="crud-label">Adrese</label>
                <input type="text" name="address" value="{{ old('address', $building->address) }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezimes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes', $building->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Atjauninat</span></button>
                <a href="{{ route('buildings.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

