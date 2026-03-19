<x-app-layout>
    @php
        $statusOptions = collect($statuses)->map(fn ($status) => [
            'value' => (string) $status,
            'label' => $statusLabels[$status] ?? $status,
            'description' => 'Filtrs pec pieteikuma statusa',
            'search' => ($statusLabels[$status] ?? $status) . ' ' . $status,
        ])->values();
        $selectedStatusLabel = $filters['status'] !== '' ? ($statusLabels[$filters['status']] ?? $filters['status']) : null;
    @endphp
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
                @unless ($canReview)
                    <a href="{{ route('repair-requests.create') }}" class="btn-create">
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Jauns pieteikums</span>
                    </a>
                @endunless
            </div>
        </div>

        <form method="GET" action="{{ route('repair-requests.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierice, kods, pieteicejs...">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <x-searchable-select
                    name="status"
                    query-name="status_query"
                    identifier="repair-request-status-filter"
                    :options="$statusOptions"
                    :selected="$filters['status']"
                    :query="$selectedStatusLabel"
                    placeholder="Izvelies statusu"
                    empty-message="Neviens statuss neatbilst meklejumam."
                />
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
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $request->responsibleUser?->full_name ?: '-' }} | {{ $request->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-pill context="request" :value="$request->status" :label="$statusLabels[$request->status] ?? null" />
                            @if ($request->device)
                                <a href="{{ route('devices.show', $request->device) }}" class="btn-view">
                                    <x-icon name="view" size="h-4 w-4" />
                                    <span>Apskatit ierici</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/90 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Lietotaja iesniegtais pieteikums</div>
                            <div class="mt-3 text-sm text-slate-600">
                                <div class="mb-3">
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Problema</div>
                                    <div class="mt-2 rounded-2xl bg-white px-4 py-3 leading-6 text-slate-700 ring-1 ring-slate-200">
                                        {{ $request->description }}
                                    </div>
                                </div>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Pieteicejs</div>
                                        <div class="mt-1 text-slate-700">{{ $request->responsibleUser?->full_name ?: '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Statuss</div>
                                        <div class="mt-1 text-slate-700">{{ $statusLabels[$request->status] ?? $request->status }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices informacija</div>
                            <div class="mt-3 grid gap-3 text-sm text-slate-600">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Kods</div>
                                    <div class="mt-1 text-slate-700">{{ $request->device?->code ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Atbildigais lietotajs</div>
                                    <div class="mt-1 text-slate-700">{{ $request->device?->assignedTo?->full_name ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Saistitais remonts</div>
                                    <div class="mt-1 text-slate-700">
                                        @if ($request->repair)
                                            Izveidots remonts #{{ $request->repair->id }}
                                        @else
                                            Vel nav izveidots
                                        @endif
                                    </div>
                                </div>
                                @if ($request->reviewedBy)
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Izskatija</div>
                                        <div class="mt-1 text-slate-700">{{ $request->reviewedBy->full_name }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($request->review_notes)
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">Admina piezimes:</span> {{ $request->review_notes }}
                        </div>
                    @endif
                    @if ($canReview && $request->status === 'submitted')
                        <div class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3 text-sm font-semibold text-slate-900">Admina lemums</div>
                            <div class="mb-4 text-sm text-slate-600">
                                Apstiprinot pieteikumu, tiks automātiski izveidots jauns remonta ieraksts statusa `Gaida` ar saiti uz šo pieteikumu.
                            </div>

                            <form method="POST" action="{{ route('repair-requests.review', $request) }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <label class="block">
                                    <span class="crud-label">Piezimes lietotajam</span>
                                    <textarea name="review_notes" rows="3" class="crud-control" placeholder="Ja vajag, pievieno skaidrojumu apstiprinasanai."></textarea>
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="btn-approve">
                                        <x-icon name="check-circle" size="h-4 w-4" />
                                        <span>Apstiprinat un izveidot remontu</span>
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('repair-requests.review', $request) }}" class="mt-3 space-y-4">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <label class="block">
                                    <span class="crud-label">Noraidijuma piezimes</span>
                                    <textarea name="review_notes" rows="3" class="crud-control" placeholder="Apraksti, kapec pieteikums tiek noraidits."></textarea>
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="btn-reject">
                                        <x-icon name="x-circle" size="h-4 w-4" />
                                        <span>Noraidit pieteikumu</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="surface-empty">Pieteikumu vel nav.</div>
            @endforelse
        </div>

        {{ $requests->links() }}
    </section>
</x-app-layout>
