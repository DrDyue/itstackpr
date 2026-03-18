<x-app-layout>
    <section class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Rediget remontu</h1>
                <p class="mt-2 text-sm text-slate-600">Atjauno remonta darbu un statusu.</p>
            </div>
            <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Atpakal</a>
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

        <form method="POST" action="{{ route('repairs.update', $repair) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            @include('repairs.partials.form-fields', ['repair' => $repair])
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Saglabat</button>
                <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>

        <form method="POST" action="{{ route('repairs.transition', $repair) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="text-lg font-semibold text-slate-900">Ātra statusa maiņa</h2>
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
            <button type="submit" class="crud-btn-primary">Mainit statusu</button>
        </form>

        <form method="POST" action="{{ route('repairs.destroy', $repair) }}" onsubmit="return confirm('Dzest so remonta ierakstu?')" class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700">Dzest remontu</button>
        </form>
    </section>
</x-app-layout>
