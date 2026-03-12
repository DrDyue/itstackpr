<x-app-layout>
    @php
        $filters = $filters ?? ['q' => '', 'category' => '', 'sort' => 'type_name', 'direction' => 'asc'];

        $sortUrl = function (string $column) use ($filters, $sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('device-types.index', array_filter([
                'q' => $filters['q'] ?: null,
                'category' => $filters['category'] ?: null,
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };
    @endphp

    <section class="type-shell">
        <div class="type-header">
            <div>
                <h1 class="device-page-title">Iericu tipi</h1>
                <p class="device-page-subtitle">Klasifikators ar meklēšanu un šķirošanu pēc nosaukuma un kategorijas.</p>
            </div>
            <a href="{{ route('device-types.create') }}" class="crud-btn-primary-inline">Pievienot tipu</a>
        </div>

        <div class="type-toolbar">
            <form method="GET" action="{{ route('device-types.index') }}" class="space-y-4">
                <div class="type-search-grid">
                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Meklet pec tipa nosaukuma</span>
                        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Piem. Monitors" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Meklet pec kategorijas</span>
                        <input type="text" name="category" value="{{ $filters['category'] }}" placeholder="Piem. Periferija" class="crud-control">
                    </label>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="crud-btn-primary">Meklet</button>
                        <a href="{{ route('device-types.index') }}" class="crud-btn-secondary">Notirit</a>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ $sortUrl('type_name') }}" class="type-sort-link">
                        Tipa nosaukums
                        <span>{{ $sort === 'type_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                    <a href="{{ $sortUrl('category') }}" class="type-sort-link">
                        Kategorija
                        <span>{{ $sort === 'category' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                    <span class="rounded-full bg-white px-3 py-1 text-sm font-medium text-slate-600 ring-1 ring-slate-200">
                        Atrasti tipi: {{ $types->total() }}
                    </span>
                </div>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="type-results-grid">
            @forelse ($types as $type)
                <article class="type-card">
                    <div class="type-card-grid">
                        <div>
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="type-chip bg-sky-100 text-sky-800 ring-sky-200">#{{ $type->id }}</span>
                                <span class="type-chip bg-amber-100 text-amber-800 ring-amber-200">{{ $type->category }}</span>
                                @if ($type->devices_count > 0)
                                    <span class="type-chip bg-emerald-100 text-emerald-800 ring-emerald-200">{{ $type->devices_count }} ierices</span>
                                @endif
                            </div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ $type->type_name }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $type->description ?: 'Apraksts nav pievienots.' }}</p>
                        </div>

                        <div class="space-y-3">
                            <div class="type-metric">
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategorija</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">{{ $type->category }}</div>
                            </div>
                            <div class="type-metric">
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kalposanas ilgums</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">
                                    {{ $type->expected_lifetime_years ? $type->expected_lifetime_years . ' gadi' : 'Nav noradits' }}
                                </div>
                            </div>
                            <div class="type-metric">
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Izveidots</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">{{ $type->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="flex items-start justify-end gap-3">
                            <a href="{{ route('device-types.edit', $type) }}" class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">Rediget</a>
                            <form method="POST" action="{{ route('device-types.destroy', $type) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Dzest so tipu?')" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">Dzest</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-sm text-slate-500">
                    Neviens ierices tips neatbilst atlasitajiem filtriem.
                </div>
            @endforelse
        </div>

        @if ($types->hasPages())
            <div class="mt-5">{{ $types->links() }}</div>
        @endif
    </section>
</x-app-layout>
