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

        @if (! empty($featureMessage))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
        @endif

        <x-validation-summary />

        <form method="POST" action="{{ route('repair-requests.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div>
                    <span class="crud-label">Ierice</span>
                    <x-searchable-select
                        name="device_id"
                        query-name="device_query"
                        identifier="repair-request-device"
                        :options="$deviceOptions"
                        :selected="old('device_id', $selectedDeviceId ?? '')"
                        :query="old('device_query', $selectedDeviceLabel ?? '')"
                        placeholder="Mekle pec nosaukuma, koda vai telpas"
                        empty-message="Neviena ierice neatbilst meklejumam."
                    />
                    @error('device_id')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <div class="font-semibold text-slate-900">Ieteikums</div>
                    <div class="mt-2 leading-6">
                        Iericu saraksta redzesi nosaukumu, kodu, tipu, razotaju ar modeli un atrasanas vietu, lai butu vieglak izveleties isto ierici.
                    </div>
                </div>
            </div>
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

