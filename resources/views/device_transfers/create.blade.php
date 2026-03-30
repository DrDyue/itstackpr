{{--
    Lapa: Jauns ierīces nodošanas pieprasījums.
    Atbildība: ļauj izvēlēties ierīci, saņēmēju un nodošanas iemeslu.
    Datu avots: DeviceTransferController@create, saglabāšana caur DeviceTransferController@store.
    Galvenās daļas:
    1. Hero zona ar paskaidrojumu par nodošanas loģiku.
    2. Ierīces un saņēmēja izvēles bloki.
    3. Iemesla lauks un saglabāšanas darbības.
--}}
<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="transfer" size="h-4 w-4" /><span>Jauns pieteikums</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="transfer" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauna ierīces pārsūtīšana</h1>
                            <p class="page-subtitle">{{ ($isAdmin ?? false) ? 'Admins var izveidot pārsūtīšanu jebkurai aktīvai un piešķirtai ierīcei. Apstiprina saņēmējs.' : 'Izvēlies savu ierīci un saņēmēju.' }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('device-transfers.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        @if (! empty($featureMessage))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
        @endif

        <x-validation-summary />

        {{-- Formā tiek izvēlēta ierīce, saņēmējs un nodošanas iemesls. --}}
        <form method="POST" action="{{ route('device-transfers.store') }}" class="surface-card space-y-6 p-6">
            @csrf
            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <span class="crud-label">Ierīce</span>
                    <x-searchable-select
                        name="device_id"
                        query-name="device_query"
                        identifier="device-transfer-device"
                        :options="$deviceOptions"
                        :selected="old('device_id', $selectedDeviceId ?? '')"
                        :query="old('device_query', $selectedDeviceLabel ?? '')"
                        placeholder="Meklē pēc nosaukuma, koda vai lietotāja"
                        empty-message="Neviena ierīce neatbilst meklējumam."
                    />
                    @error('device_id')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <span class="crud-label">Kam nodot</span>
                    <x-searchable-select
                        name="transfered_to_id"
                        query-name="transfered_to_query"
                        identifier="device-transfer-recipient"
                        :options="$recipientOptions"
                        :selected="old('transfered_to_id')"
                        :query="old('transfered_to_query', '')"
                        placeholder="Meklē lietotāju"
                        empty-message="Neviens lietotājs neatbilst meklējumam."
                    />
                    @error('transfered_to_id')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <label class="block">
                <span class="crud-label">Iemesls</span>
                <textarea name="transfer_reason" rows="5" class="crud-control" required>{{ old('transfer_reason') }}</textarea>
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-create"><x-icon name="send" size="h-4 w-4" /><span>Nosutit</span></button>
                <a href="{{ route('device-transfers.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

