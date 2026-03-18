<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="transfer" size="h-4 w-4" /><span>Nodosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="transfer" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Iericu parsutisanas</h1>
                            <p class="page-subtitle">{{ $isAdmin ? 'Visi parsutisanas pieteikumi.' : 'Tavi nosutitie un sanemtie parsutisanas pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('device-transfers.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns pieteikums</span></a>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($transfers as $transfer)
                <div class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $transfer->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}</div>
                        </div>
                        <span class="status-pill {{ $transfer->status === 'approved' ? 'status-pill-success' : ($transfer->status === 'rejected' ? 'status-pill-danger' : 'status-pill-violet') }}">{{ $transfer->status }}</span>
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $transfer->transfer_reason }}</div>
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

