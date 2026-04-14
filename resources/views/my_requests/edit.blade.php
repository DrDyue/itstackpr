{{--
    Lapa: Lietotāja pieprasījuma labošanas skats.
    Atbildība: Ļauj labot tikai iesniegta pieprasījuma aprakstošo lauku, kamēr admins to vēl nav izskatījis.
    Datu avots: UserRequestCenterController@edit, saglabāšana caur UserRequestCenterController@update.
    Galvenās daļas:
    1. Hero ar pieprasījuma tipu.
    2. Rediģējamais teksta lauks.
    3. Saglabāšanas un atcelšanas darbības.
--}}
<x-app-layout>
    <section class="app-shell max-w-5xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon :name="$icon" size="h-4 w-4" />
                        <span>{{ $typeLabel }}</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon :name="$icon" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">{{ $pageTitle }}</h1>
                            <p class="page-subtitle">{{ $pageSubtitle }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route(match ($requestType) {
                    'repair' => 'repair-requests.index',
                    'writeoff' => 'writeoff-requests.index',
                    'transfer' => 'device-transfers.index',
                }) }}" class="btn-back">
                    <x-icon name="back" size="h-4 w-4" />
                    <span>Atpakaļ</span>
                </a>
            </div>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('my-requests.update', ['requestType' => $requestType, 'requestId' => $editableRequest->id]) }}" class="surface-card space-y-6 p-6">
            @csrf
            @method('PATCH')

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ierīce</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">{{ $editableRequest->device?->name ?: 'Ierīce nav atrasta' }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $editableRequest->device?->code ?: 'bez koda' }}</div>
                </div>
                <div class="form-page-note border-sky-200 bg-sky-50/80">
                    <div class="form-page-note-title text-sky-800">Piezīme</div>
                    <div class="form-page-note-copy text-sky-900">
                        Šeit vari mainīt tikai tekstu. Ierīci un citas saistītās vērtības sistēma saglabā nemainīgas.
                    </div>
                </div>
            </div>

            <label class="block">
                <span class="crud-label">{{ $fieldLabel }}</span>
                <textarea name="{{ $fieldName }}" rows="7" class="crud-control" required>{{ old($fieldName, $fieldValue) }}</textarea>
                @error($fieldName)
                    <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                @enderror
            </label>

            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Saglabā labojumus</div>
                    <div class="form-page-actions-text">Tiks atjaunots tikai pieprasījuma teksts, kamēr administrators to vēl nav izskatījis.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-create">
                        <x-icon name="check" size="h-4 w-4" />
                        <span>Saglabāt izmaiņas</span>
                    </button>
                    <a href="{{ route(match ($requestType) {
                        'repair' => 'repair-requests.index',
                        'writeoff' => 'writeoff-requests.index',
                        'transfer' => 'device-transfers.index',
                    }) }}" class="btn-clear">
                        <x-icon name="clear" size="h-4 w-4" />
                        <span>Atcelt</span>
                    </a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
