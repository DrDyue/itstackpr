<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="repair-request" size="h-4 w-4" />
                        <span>Pieteikumu centrs</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="repair-request" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Remonta pieteikumi</h1>
                            <p class="page-subtitle">{{ $canReview ? 'Visi lietotaju remonta pieteikumi. Admins apstiprina vai noraida katru iesniegumu.' : 'Tavi remonta pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repair-requests.create') }}" class="btn-create">
                    <x-icon name="plus" size="h-4 w-4" />
                    <span>Jauns pieteikums</span>
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('repair-requests.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierice, kods, pieteicejs...">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <select name="status" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                    @endforeach
                </select>
            </label>
            <div class="toolbar-actions">
                <button type="submit" class="btn-search">
                    <x-icon name="search" size="h-4 w-4" />
                    <span>Meklet</span>
                </button>
                <a href="{{ route('repair-requests.index') }}" class="btn-clear">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Notirit</span>
                </a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Statuss', 'value' => $filters['status'] !== '' ? ($statusLabels[$filters['status']] ?? $filters['status']) : null],
            ]"
            :clear-url="route('repair-requests.index')"
        />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif
        @if (! empty($featureMessage))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $featureMessage }}</div>
        @endif

        <div class="space-y-4">
            @forelse ($requests as $request)
                <div class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $request->responsibleUser?->full_name ?: '-' }} | {{ $request->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <x-status-pill context="request" :value="$request->status" :label="$statusLabels[$request->status] ?? null" />
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $request->description }}</div>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                        <span>Ierices kods: {{ $request->device?->code ?: '-' }}</span>
                        @if ($request->reviewedBy)
                            <span>Izskatija: {{ $request->reviewedBy->full_name }}</span>
                        @endif
                        @if ($request->repair)
                            <span>Izveidots remonts #{{ $request->repair->id }}</span>
                        @endif
                    </div>
                    @if ($request->review_notes)
                        <div class="mt-3 text-sm text-slate-500">Piezimes: {{ $request->review_notes }}</div>
                    @endif
                    @if ($canReview && $request->status === 'submitted')
                        <form method="POST" action="{{ route('repair-requests.review', $request) }}" class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-4">
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
                                <label class="block md:col-span-4">
                                    <span class="crud-label">Piezimes</span>
                                    <textarea name="review_notes" rows="3" class="crud-control"></textarea>
                                </label>
                                <div class="md:col-span-4 flex flex-wrap gap-3">
                                    <button type="submit" class="btn-approve">
                                        <x-icon name="check-circle" size="h-4 w-4" />
                                        <span>Saglabat lemumu</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="surface-empty">Pieteikumu vel nav.</div>
            @endforelse
        </div>

        {{ $requests->links() }}
    </section>
</x-app-layout>

