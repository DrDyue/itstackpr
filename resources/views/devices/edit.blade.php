<x-app-layout>
    <section class="app-shell max-w-5xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="edit" size="h-4 w-4" />
                        <span>Labosana</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Rediget ierici</h1>
                            <p class="page-subtitle">Atjauno ierices datus, statusu un piesaisti.</p>
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

        <form method="POST" action="{{ route('devices.update', $device) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('devices.partials.form-fields', ['device' => $device])
            <div class="surface-card flex flex-wrap items-center justify-between gap-4 p-6">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Saglabasanas zona</div>
                    <div class="mt-1 text-sm text-slate-500">Atjauno tikai izmainitos laukus. Ja ierice ir norakstita, piesaistes lauki paliek bloķeti.</div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-edit">
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
