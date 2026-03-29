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
                            <h1 class="page-title">Jauna ierice</h1>
                            <p class="page-subtitle">Pievieno jaunu ierici un piesaisti to lietotajam.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('devices.index') }}" class="btn-back">
                    <x-icon name="back" size="h-4 w-4" />
                    <span>Atpakal</span>
                </a>
            </div>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('devices.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('devices.partials.form-fields', ['device' => null])
            <div class="surface-card flex flex-wrap items-center justify-between gap-4 p-6">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Pirms saglabasanas parbaudi pamatdatus</div>
                    <div class="mt-1 text-sm text-slate-500">Svarigakais ir kods, nosaukums, tips un modelis. Parejo informaciju vari papildinat ari velak.</div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-create">
                        <x-icon name="save" size="h-4 w-4" />
                        <span>Saglabat</span>
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
