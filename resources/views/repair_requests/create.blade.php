<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="repair-request" size="h-4 w-4" /><span>Jauns pieteikums</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="repair-request" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauns remonta pieteikums</h1>
                            <p class="page-subtitle">Izvelies savu ierici un apraksti problemu.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repair-requests.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakal</span></a>
            </div>
        </div>

        <form method="POST" action="{{ route('repair-requests.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <label class="block">
                <span class="crud-label">Ierice</span>
                <select name="device_id" class="crud-control" required>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>{{ $device->name }} ({{ $device->code ?: 'bez koda' }})</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Apraksts</span>
                <textarea name="description" rows="5" class="crud-control" required>{{ old('description') }}</textarea>
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-create"><x-icon name="send" size="h-4 w-4" /><span>Nosutit</span></button>
                <a href="{{ route('repair-requests.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

