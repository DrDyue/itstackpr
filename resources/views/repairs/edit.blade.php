<x-app-layout>
    <section class="app-shell max-w-5xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="repair" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediget remontu</h1>
                            <p class="page-subtitle">Atjauno remonta darbu un statusu.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repairs.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakal</span></a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('repairs.update', $repair) }}" class="surface-card space-y-6 p-6">
            @csrf
            @method('PUT')
            @include('repairs.partials.form-fields', ['repair' => $repair])
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Saglabat</span></button>
                <a href="{{ route('repairs.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>

        <form method="POST" action="{{ route('repairs.transition', $repair) }}" class="surface-card space-y-4 p-6">
            @csrf
            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
                <x-icon name="stats" size="h-5 w-5" class="text-sky-600" />
                <span>Atra statusa maina</span>
            </h2>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="block">
                    <span class="crud-label">Jaunais statuss</span>
                    <select name="target_status" class="crud-control">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ $statusLabels[$status] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Beigu datums</span>
                    <input type="date" name="end_date" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Izmaksas</span>
                    <input type="number" step="0.01" name="cost" class="crud-control">
                </label>
            </div>
            <button type="submit" class="btn-submit"><x-icon name="save" size="h-4 w-4" /><span>Mainit statusu</span></button>
        </form>

        <form method="POST" action="{{ route('repairs.destroy', $repair) }}" onsubmit="return confirm('Dzest so remonta ierakstu?')" class="surface-card border-rose-200 p-6">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger-solid"><x-icon name="trash" size="h-4 w-4" /><span>Dzest remontu</span></button>
        </form>
    </section>
</x-app-layout>

