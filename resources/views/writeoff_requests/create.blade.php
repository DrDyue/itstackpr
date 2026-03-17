<x-app-layout>
    <section class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Jauns norakstisanas pieteikums</h1>
                <p class="mt-2 text-sm text-slate-600">Izvēlies savu ierīci un norādi iemeslu.</p>
            </div>
            <a href="{{ route('writeoff-requests.index') }}" class="crud-btn-secondary">Atpakal</a>
        </div>

        <form method="POST" action="{{ route('writeoff-requests.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
                <span class="crud-label">Iemesls</span>
                <textarea name="reason" rows="5" class="crud-control" required>{{ old('reason') }}</textarea>
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Nosutit</button>
                <a href="{{ route('writeoff-requests.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
