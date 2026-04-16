{{--
    Lapa: Jaunas ierīces izveide.
    Atbildība: ļauj administratoram pievienot jaunu inventāra vienību sistēmā.
    Datu avots: DeviceController@create, saglabāšana caur DeviceController@store.
    Galvenās daļas:
    1. Hero ar paskaidrojumu par izveidi.
    2. Validācijas kopsavilkums, ja forma aizpildīta kļūdaini.
    3. Kopīgais ierīces formas partialis un saglabāšanas darbību zona.
--}}
<x-app-layout>
    <section class="app-shell max-w-5xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Jauns ieraksts</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Jauna ierīce</h1>
                            <p class="page-subtitle">Pievieno jaunu ierīci ar skaidri sakārtotu informāciju par modeli, atrašanās vietu un atbildīgo personu.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('devices.index') }}" class="btn-back">
                    <x-icon name="back" size="h-4 w-4" />
                    <span>Atpakaļ</span>
                </a>
            </div>
        </div>

        <x-validation-summary />

        {{-- Forma izmanto kopīgo partiali, lai create un edit skatam būtu viena struktūra. --}}
        <form method="POST" action="{{ route('devices.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('devices.partials.form-fields', ['device' => null])
            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Pirms saglabāšanas pārbaudi pamata datus</div>
                    <div class="form-page-actions-text">Svarīgākais ir kods, nosaukums, tips un modelis. Pārējo informāciju vari papildināt arī vēlāk.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-create">
                        <x-icon name="save" size="h-4 w-4" />
                        <span>Saglabāt</span>
                    </button>
                    <a href="{{ route('devices.index') }}" class="btn-clear">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Atcelt</span>
                    </a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
