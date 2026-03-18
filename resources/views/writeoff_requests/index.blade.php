<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="writeoff" size="h-4 w-4" /><span>Norakstisana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-rose"><x-icon name="writeoff" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Norakstisanas pieteikumi</h1>
                            <p class="page-subtitle">{{ $canReview ? 'Visi lietotaju norakstisanas pieteikumi.' : 'Tavi norakstisanas pieteikumi.' }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('writeoff-requests.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns pieteikums</span></a>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($requests as $request)
                <div class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $request->responsibleUser?->full_name ?: '-' }} | {{ $request->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <span class="status-pill {{ $request->status === 'approved' ? 'status-pill-success' : ($request->status === 'rejected' ? 'status-pill-danger' : 'status-pill-info') }}">{{ $request->status }}</span>
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $request->reason }}</div>
                    @if ($request->review_notes)
                        <div class="mt-3 text-sm text-slate-500">Piezimes: {{ $request->review_notes }}</div>
                    @endif
                    @if ($canReview && $request->status === 'submitted')
                        <form method="POST" action="{{ route('writeoff-requests.review', $request) }}" class="mt-4 space-y-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
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

        {{ $requests->links() }}
    </section>
</x-app-layout>

