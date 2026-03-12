<x-app-layout>
    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Vestures skats</p>
                <h1 class="text-3xl font-semibold text-slate-900">{{ $device->code }} {{ $device->name }}</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('devices.show', $device) }}" class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ierices detalas</a>
                <a href="{{ route('devices.index') }}" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Atpakal</a>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($history as $h)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $h->action }}</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ $h->field_changed ?: 'Vispareja izmaina' }}</div>
                        </div>
                        <div class="text-sm text-slate-500">{{ $h->timestamp?->format('d.m.Y H:i') ?: '-' }}</div>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Ieprieksejais</div>
                            <div class="mt-2 break-words text-sm text-slate-700">{{ $h->old_value ?? '-' }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jaunais</div>
                            <div class="mt-2 break-words text-sm text-slate-700">{{ $h->new_value ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                    Sai iericei vestures ierakstu vel nav.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
