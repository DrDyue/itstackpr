{{--
    Lapa: Jauns remonta ieraksts.
    Atbildība: ļauj administratoram izveidot faktisko remonta darbu arī bez atsevišķa lietotāja pieteikuma.
    Datu avots: RepairController@create, saglabāšana caur RepairController@store.
    Galvenās daļas:
    1. Hero zona ar remonta izveides kontekstu.
    2. Validācijas kopsavilkums un iespējamais feature paziņojums.
    3. Kopīgais remonta formas partialis.
--}}
<x-app-layout>
    <section class="app-shell max-w-5xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="repair" size="h-4 w-4" /><span>Jauns remonts</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="repair" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauns remonts</h1>
                            <p class="page-subtitle">Izveido faktisko remonta ierakstu.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repairs.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        <x-validation-summary />
        @if (! empty($featureMessage))
            <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
        @endif

        <form
            method="POST"
            action="{{ route('repairs.store') }}"
            class="surface-card space-y-6 p-6"
            x-data="repairProcess({
                repairId: null,
                repairType: @js(old('repair_type', 'internal')),
                status: 'waiting',
                priority: @js(old('priority', 'medium')),
                description: @js(old('description', '')),
                vendorName: @js(old('vendor_name', '')),
                vendorContact: @js(old('vendor_contact', '')),
                invoiceNumber: @js(old('invoice_number', '')),
                cost: @js(old('cost', '')),
                transitionBaseUrl: @js(url('/repairs')),
                csrfToken: @js(csrf_token()),
            })"
        >
            @csrf
            @include('repairs.partials.form-fields', ['repair' => null])
            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Saglabā jauno remonta ierakstu</div>
                    <div class="form-page-actions-text">Pirms saglabāšanas pārbaudi ierīci, remonta tipu un prioritāti. Pārējos laukus vajadzības gadījumā varēsi papildināt vēlāk.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-create"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                    <a href="{{ route('repairs.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>

