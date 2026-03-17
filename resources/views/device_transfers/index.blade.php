<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Iericu parsutisanas</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $canReview ? 'Visi pārsūtīšanas pieteikumi.' : 'Tavi nosūtītie un saņemtie pārsūtīšanas pieteikumi.' }}</p>
            </div>
            <a href="{{ route('device-transfers.create') }}" class="crud-btn-primary">Jauns pieteikums</a>
        </div>

        <div class="space-y-4">
            @forelse ($transfers as $transfer)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ $transfer->device?->name ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $transfer->responsibleUser?->full_name ?: '-' }} -> {{ $transfer->transferTo?->full_name ?: '-' }}</div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $transfer->status }}</span>
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $transfer->transfer_reason }}</div>
                    @if ($transfer->review_notes)
                        <div class="mt-2 text-sm text-slate-500">Piezīmes: {{ $transfer->review_notes }}</div>
                    @endif
                    @if (($canReview || auth()->id() === $transfer->transfer_to_user_id) && $transfer->status === 'pending')
                        <form method="POST" action="{{ route('device-transfers.review', $transfer) }}" class="mt-4 space-y-4 rounded-xl bg-slate-50 p-4">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span class="crud-label">Lemums</span>
                                    <select name="status" class="crud-control">
                                        <option value="approved">Apstiprinat</option>
                                        <option value="denied">Noraidit</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="crud-label">Piezimes</span>
                                    <textarea name="review_notes" rows="3" class="crud-control"></textarea>
                                </label>
                            </div>
                            <button type="submit" class="crud-btn-primary">Saglabat lemumu</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm">Pieteikumu vēl nav.</div>
            @endforelse
        </div>

        {{ $transfers->links() }}
    </section>
</x-app-layout>
