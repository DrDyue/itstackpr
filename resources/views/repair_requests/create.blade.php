{{--
    Lapa: Jauns remonta pieteikums.
    Atbildība: ļauj lietotājam izvēlēties savu ierīci un pieteikt remonta problēmu.
    Datu avots: RepairRequestController@create, saglabāšana caur RepairRequestController@store.
    Galvenās daļas:
    1. Hero ar īsu paskaidrojumu.
    2. Ierīces izvēles lauks ar meklējamu dropdown.
    3. Apraksta lauks un iesniegšanas pogas.
--}}
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
                            <p class="page-subtitle">Izvēlies savu ierīci un apraksti problēmu.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repair-requests.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        @if (! empty($featureMessage))
            <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
        @endif

        <x-validation-summary />

        {{-- Formā lietotājs izvēlas ierīci un apraksta problēmu, ko admins vēlāk izskata. --}}
        <form method="POST" action="{{ route('repair-requests.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div>
                    <span class="crud-label">Ierīce</span>
                    <x-searchable-select
                        name="device_id"
                        query-name="device_query"
                        identifier="repair-request-device"
                        :options="$deviceOptions"
                        :selected="old('device_id', $selectedDeviceId ?? '')"
                        :query="old('device_query', $selectedDeviceLabel ?? '')"
                        placeholder="Meklē pēc nosaukuma, koda vai telpas"
                        empty-message="Neviena ierīce neatbilst meklējumam."
                    />
                    @error('device_id')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <div class="font-semibold text-slate-900">Ieteikums</div>
                    <div class="mt-2 leading-6">
                        Ierīču saraksta redzēsi nosaukumu, kodu, tipu, ražotāju ar modeli un atrašanās vietu, lai butu vieglak izvēleties isto ierīci.
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

