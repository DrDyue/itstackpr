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
                <p class="device-page-subtitle">Klasifikators ar meklesanu un skirosanu.</p>
            </div>
            <a href="{{ route('device-types.create') }}" class="crud-btn-primary-inline">Pievienot tipu</a>
        </div>

        <div class="type-toolbar">
            <form method="GET" action="{{ route('device-types.index') }}" class="space-y-4">
                <div class="type-category-chips">
                    <a href="{{ route('device-types.index', array_filter([
                        'q' => $filters['q'] ?: null,
                        'sort' => $sort,
                        'direction' => $direction,
                    ])) }}"
                       class="type-category-chip {{ $filters['category'] === '' ? 'type-category-chip-active' : 'type-category-chip-idle' }}">
                        Visas kategorijas
                    </a>
                    @foreach ($categoryOptions as $categoryOption)
                        <a href="{{ route('device-types.index', array_filter([
                            'q' => $filters['q'] ?: null,
                            'category' => $categoryOption->category,
                            'sort' => $sort,
                            'direction' => $direction,
                        ])) }}"
                           class="type-category-chip {{ $filters['category'] === $categoryOption->category ? 'type-category-chip-active' : 'type-category-chip-idle' }}">
                            {{ $categoryOption->category }}
                            <span class="type-category-count">{{ $categoryOption->total }}</span>
                        </a>
                    @endforeach
                </div>

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
                        <span>{{ $sort === 'type_name' ? ($direction === 'asc' ? 'ASC' : 'DESC') : 'SORT' }}</span>
                    </a>
                    <a href="{{ $sortUrl('category') }}" class="type-sort-link">
                        Kategorija
                        <span>{{ $sort === 'category' ? ($direction === 'asc' ? 'ASC' : 'DESC') : 'SORT' }}</span>
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
                        <div class="min-w-0">
                            <div class="type-card-top">
                                <div class="min-w-0">
                                    <div class="type-card-header">
                                        <span class="type-chip bg-sky-100 text-sky-800 ring-sky-200">ID {{ $type->id }}</span>
                                        <span class="type-chip bg-blue-100 text-blue-800 ring-blue-200">Kategorija: {{ $type->category }}</span>
                                        <span class="type-chip bg-emerald-100 text-emerald-800 ring-emerald-200">Ar so tipu: {{ $type->devices_count }} ierices</span>
                                    </div>
                                    <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $type->type_name }}</h2>
                                </div>

                                <div class="flex items-start justify-end gap-2">
                                    <a href="{{ route('device-types.edit', $type) }}" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5"/></svg>
                                        Rediget
                                    </a>
                                    <form method="POST" action="{{ route('device-types.destroy', $type) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Dzest so tipu?')" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                            Dzest
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $type->description ?: 'Apraksts nav pievienots.' }}</p>

                            <div class="type-meta-row">
                                <div class="type-metric">
                                    <div class="type-inline-meta"><strong>Kategorija:</strong> {{ $type->category }}</div>
                                </div>
                                <div class="type-metric">
                                    <div class="type-inline-meta"><strong>Kalposanas ilgums:</strong> {{ $type->expected_lifetime_years ? $type->expected_lifetime_years . ' gadi' : 'Nav noradits' }}</div>
                                </div>
                                <div class="type-metric">
                                    <div class="type-inline-meta"><strong>Izveidots:</strong> {{ $type->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                                </div>
                            </div>
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
