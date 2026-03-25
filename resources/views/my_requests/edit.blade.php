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
                <a href="{{ route('my-requests.index') }}" class="btn-back">
                    <x-icon name="back" size="h-4 w-4" />
                    <span>Atpakal</span>
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('my-requests.update', ['requestType' => $requestType, 'requestId' => $editableRequest->id]) }}" class="surface-card space-y-6 p-6">
            @csrf
            @method('PATCH')

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ierice</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">{{ $editableRequest->device?->name ?: 'Ierice nav atrasta' }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $editableRequest->device?->code ?: 'bez koda' }}</div>
                </div>
                <div class="rounded-[1.5rem] border border-sky-200 bg-sky-50/80 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Piezime</div>
                    <div class="mt-2 text-sm leading-6 text-sky-900">
                        Seit vari mainit tikai tekstu. Ierici un citas saistitas vertibas sistema saglaba nemainigas.
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

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-create">
                    <x-icon name="check" size="h-4 w-4" />
                    <span>Saglabat izmainas</span>
                </button>
                <a href="{{ route('my-requests.index') }}" class="btn-clear">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Atcelt</span>
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
