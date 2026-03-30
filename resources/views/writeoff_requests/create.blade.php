{{--
    Lapa: Jauns norakstīšanas pieteikums.
    Atbildība: ļauj lietotājam iesniegt iemeslu, kāpēc ierīci vajadzētu norakstīt.
    Datu avots: WriteoffRequestController@create, saglabāšana caur WriteoffRequestController@store.
    Galvenās daļas:
    1. Hero zona ar paskaidrojumu.
    2. Ierīces izvēles bloks.
    3. Iemesla lauks un iesniegšanas darbības.
--}}
<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="writeoff" size="h-4 w-4" /><span>Jauns pieteikums</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-rose"><x-icon name="writeoff" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauns norakstīšanas pieteikums</h1>
                            <p class="page-subtitle">Izvēlies savu ierīci un norādi iemeslu.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('writeoff-requests.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        @if (! empty($featureMessage))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
        @endif

        <x-validation-summary />

        {{-- Lietotājs izvēlas savas ierīces un norāda norakstīšanas iemeslu. --}}
        <form method="POST" action="{{ route('writeoff-requests.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div>
                    <span class="crud-label">Ierīce</span>
                    <x-searchable-select
                        name="device_id"
                        query-name="device_query"
                        identifier="writeoff-request-device"
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
                    <div class="font-semibold text-slate-900">Pirms iesniegsanas</div>
                    <div class="mt-2 leading-6">
                        Izvēloties ierīci, redzēsi tipu, ražotāju ar modeli un telpu, lai norakstīšanas pieteikums tiktu piesaistīts pareizajam inventāram.
                    </div>
                </div>
            </div>
            <label class="block">
                <span class="crud-label">Iemesls</span>
                <textarea name="reason" rows="5" class="crud-control" required>{{ old('reason') }}</textarea>
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-danger-solid"><x-icon name="send" size="h-4 w-4" /><span>Nosutit</span></button>
                <a href="{{ route('writeoff-requests.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

