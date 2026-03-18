<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Remonta pieteikumi</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $canReview ? 'Visi lietotāju remonta pieteikumi.' : 'Tavi remonta pieteikumi.' }}</p>
            </div>
            <a href="{{ route('repair-requests.create') }}" class="crud-btn-primary">Jauns pieteikums</a>
        </div>

        <form method="GET" action="{{ route('repair-requests.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <select name="status" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-3">
                <button type="submit" class="crud-btn-primary">Meklet</button>
                <a href="{{ route('repair-requests.index') }}" class="crud-btn-secondary">Notirit</a>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="space-y-4">
            @forelse ($requests as $request)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $request->responsibleUser?->full_name ?: '-' }} | {{ $request->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $request->status }}</span>
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $request->description }}</div>
                    @if ($request->review_notes)
                        <div class="mt-3 text-sm text-slate-500">Piezīmes: {{ $request->review_notes }}</div>
                    @endif
                    @if ($canReview && $request->status === 'submitted')
                        <form method="POST" action="{{ route('repair-requests.review', $request) }}" class="mt-4 grid gap-4 rounded-xl bg-slate-50 p-4 md:grid-cols-5">
                            @csrf
                            <label class="block">
                                <span class="crud-label">Lemums</span>
                                <select name="status" class="crud-control">
                                    <option value="approved">Apstiprinat</option>
                                    <option value="rejected">Noraidit</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="crud-label">Tips</span>
                                <select name="repair_type" class="crud-control">
                                    <option value="internal">Ieksejais</option>
                                    <option value="external">Arejais</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="crud-label">Prioritate</span>
                                <select name="priority" class="crud-control">
                                    <option value="medium">Videja</option>
                                    <option value="low">Zema</option>
                                    <option value="high">Augsta</option>
                                    <option value="critical">Kritiska</option>
                                </select>
                            </label>
                            <label class="block md:col-span-5">
                                <span class="crud-label">Piezimes</span>
                                <textarea name="review_notes" rows="3" class="crud-control"></textarea>
                            </label>
                            <div class="md:col-span-5">
                                <button type="submit" class="crud-btn-primary">Saglabat lemumu</button>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm">Pieteikumu vēl nav.</div>
            @endforelse
        </div>

        {{ $requests->links() }}
    </section>
</x-app-layout>
