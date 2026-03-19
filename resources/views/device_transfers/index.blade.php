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
                    <div class="page-eyebrow"><x-icon name="transfer" size="h-4 w-4" /><span>Nodosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="transfer" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Iericu parsutisanas</h1>
                            <p class="page-subtitle">{{ $isAdmin ? 'Visi parsutisanas pieteikumi. Lemumu par apstiprinasanu pienem noraditais sanemejs.' : 'Tavi nosutitie un sanemtie parsutisanas pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('device-transfers.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns pieteikums</span></a>
            </div>
        </div>

        <form method="GET" action="{{ route('device-transfers.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierice, pieteicejs, sanemejs...">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <x-searchable-select
                    name="status"
                    query-name="status_query"
                    identifier="device-transfer-status-filter"
                    :options="$statusOptions"
                    :selected="$filters['status']"
                    :query="$selectedStatusLabel"
                    placeholder="Izvelies statusu"
                    empty-message="Neviens statuss neatbilst meklejumam."
                />
            </label>
            <div class="toolbar-actions">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('device-transfers.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Statuss', 'value' => $filters['status'] !== '' ? ($statusLabels[$filters['status']] ?? $filters['status']) : null],
            ]"
            :clear-url="route('device-transfers.index')"
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
            @forelse ($transfers as $transfer)
                <div class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $transfer->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($isAdmin)
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 ring-1 ring-emerald-200">
                                    <x-icon name="view" size="h-3.5 w-3.5" />
                                    <span>Vesture</span>
                                </span>
                            @endif
                            <x-status-pill context="request" :value="$transfer->status" :label="$statusLabels[$transfer->status] ?? null" />
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $transfer->transfer_reason }}</div>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                        <span>Ierices kods: {{ $transfer->device?->code ?: '-' }}</span>
                        <span>Izveidots: {{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}</span>
                        @if ($transfer->reviewedBy)
                            <span>Izskatija: {{ $transfer->reviewedBy->full_name }}</span>
                        @endif
                    </div>
                    @if ($transfer->review_notes)
                        <div class="mt-2 text-sm text-slate-500">Piezimes: {{ $transfer->review_notes }}</div>
                    @endif
                    @if (auth()->id() === $transfer->transfered_to_id && $transfer->status === 'submitted')
                        <form method="POST" action="{{ route('device-transfers.review', $transfer) }}" class="mt-4 space-y-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span class="crud-label">Lemums</span>
                                    <select name="status" class="crud-control">
                                        <option value="approved">Apstiprinat</option>
                                        <option value="rejected">Noraidit</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="crud-label">Piezimes</span>
                                    <textarea name="review_notes" rows="3" class="crud-control"></textarea>
                                </label>
                            </div>
                            <button type="submit" class="btn-approve"><x-icon name="check-circle" size="h-4 w-4" /><span>Saglabat lemumu</span></button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="surface-empty">Pieteikumu vel nav.</div>
            @endforelse
        </div>

        {{ $transfers->links() }}
    </section>
</x-app-layout>
