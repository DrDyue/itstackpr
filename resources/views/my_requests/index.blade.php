<x-app-layout>
    <section class="app-shell max-w-7xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="repair-request" size="h-4 w-4" />
                        <span>Vienotais pieteikumu centrs</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="repair-request" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Mani pieteikumi</h1>
                            <p class="page-subtitle">
                                Te vienuviet redzi remonta, norakstisanas un nodosanas pieteikumus, kas saistiti ar tevi.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="{{ route('my-requests.create') }}" class="btn-create">
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Izveidot pieteikumu</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn-back">
                        <x-icon name="back" size="h-4 w-4" />
                        <span>Atpakal</span>
                    </a>
                </div>
            </div>
        </div>

        @php
            $statusOptions = collect($statusLabels)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
                'search' => $label,
            ])->values();
            $typeOptions = collect($typeLabels)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
                'search' => $label,
            ])->values();
        @endphp

        <section class="surface-card p-6">
            <form method="GET" class="grid gap-4 xl:grid-cols-[1.3fr_0.8fr_0.8fr_auto]">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Meklet</span>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] }}"
                        class="crud-control"
                        placeholder="Mekle pec ierices, apraksta vai lietotaja"
                    >
                </label>

                <div>
                    <span class="mb-2 block text-sm font-medium text-slate-700">Statuss</span>
                    <x-searchable-select
                        name="status"
                        queryName="status_query"
                        :options="$statusOptions"
                        :selected="$filters['status']"
                        :query="$statusLabels[$filters['status']] ?? ''"
                        identifier="my-requests-status"
                        placeholder="Visi statusi"
                    />
                </div>

                <div>
                    <span class="mb-2 block text-sm font-medium text-slate-700">Tips</span>
                    <x-searchable-select
                        name="type"
                        queryName="type_query"
                        :options="$typeOptions"
                        :selected="$filters['type']"
                        :query="$typeLabels[$filters['type']] ?? ''"
                        identifier="my-requests-type"
                        placeholder="Visi tipi"
                    />
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <button type="submit" class="btn-view">
                        <x-icon name="view" size="h-4 w-4" />
                        <span>Filtret</span>
                    </button>
                    <a href="{{ route('my-requests.index') }}" class="btn-clear">Notirit</a>
                </div>
            </form>
        </section>

        <section class="surface-card mt-6 p-6">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Pieteikumu saraksts</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Ja tev ir ienakoss nodosanas pieteikums, vari to apstiprinat vai noraidit uzreiz saja lapa.
                    </p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                    Kopa: {{ $items->total() }}
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($items as $item)
                    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_18px_40px_-28px_rgba(15,23,42,0.45)]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                                        {{ $typeLabels[$item['type']] ?? ucfirst($item['type']) }}
                                    </span>
                                    <x-status-pill context="request" :value="$item['status']" />
                                </div>
                                <div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $item['device_name'] }}</div>
                                    <div class="text-sm text-slate-500">{{ $item['device_code'] }}</div>
                                </div>
                            </div>
                            <div class="text-right text-sm text-slate-500">
                                <div>{{ $item['created_at']?->format('d.m.Y H:i') ?: '-' }}</div>
                                @if ($item['model']->device && (int) $item['model']->device->assigned_to_id === (int) $user->id)
                                    <a href="{{ route('devices.show', $item['model']->device_id) }}" class="mt-2 inline-flex text-sm font-medium text-sky-700 hover:text-sky-800">
                                        Atvert ierici
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_0.9fr]">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Virziens</div>
                                    <div class="mt-1 text-sm text-slate-700">{{ $item['direction'] }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Apraksts</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-700">{{ $item['summary'] ?: '-' }}</div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                        {{ $item['is_incoming'] ? 'Nosutija' : 'Saistitais lietotajs' }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-700">{{ $item['actor'] ?: '-' }}</div>
                                </div>
                                @if (! empty($item['meta']))
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Papildinformacija</div>
                                        <div class="mt-1 text-sm leading-6 text-slate-700">{{ $item['meta'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($item['type'] === 'transfer' && $item['is_incoming'] && $item['status'] === 'submitted')
                            <div class="mt-5 rounded-[1.5rem] border border-emerald-200 bg-emerald-50/80 p-4" x-data="{ keepCurrent: true }">
                                <div class="text-sm font-semibold text-emerald-900">Apstiprini ierices sanemsanu</div>
                                <div class="mt-1 text-sm text-emerald-800">
                                    Vari atstat ierici esosaja telpa vai uzreiz noradit jaunu atrasanas vietu.
                                </div>

                                <form method="POST" action="{{ route('device-transfers.review', $item['model']) }}" class="mt-4 space-y-4">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">

                                    <label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-700">
                                        <input type="checkbox" name="keep_current_room" value="1" checked x-model="keepCurrent" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>
                                            Atstat ierici esosaja telpa
                                            <span class="mt-1 block text-xs text-slate-500">
                                                Paslaik: {{ $item['model']->device?->room?->room_number ?: 'telpa nav noradita' }}
                                                @if ($item['model']->device?->building?->building_name)
                                                    | {{ $item['model']->device->building->building_name }}
                                                @endif
                                            </span>
                                        </span>
                                    </label>

                                    <div x-show="!keepCurrent" x-cloak>
                                        <div class="mb-2 text-sm font-medium text-slate-700">Jauna telpa</div>
                                        <x-searchable-select
                                            name="room_id"
                                            queryName="room_query_{{ $item['model']->id }}"
                                            :options="$roomOptions"
                                            :selected="old('room_id')"
                                            :query="''"
                                            identifier="incoming-transfer-room-{{ $item['model']->id }}"
                                            placeholder="Izvelies telpu"
                                        />
                                    </div>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-slate-700">Piezimes</span>
                                        <textarea name="review_notes" rows="2" class="crud-control" placeholder="Ja vajag, pievieno komentaru sanemsanai."></textarea>
                                    </label>

                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="btn-create">
                                            <x-icon name="check" size="h-4 w-4" />
                                            <span>Apstiprinat sanemsanu</span>
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('device-transfers.review', $item['model']) }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <div class="flex flex-wrap gap-2">
                                        <input type="text" name="review_notes" class="crud-control max-w-xl" placeholder="Komentars, ja noraidi pieteikumu">
                                        <button type="submit" class="btn-danger">
                                            <x-icon name="x-mark" size="h-4 w-4" />
                                            <span>Noraidit</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="dash-empty-block">Neviens pieteikums pec izveletajiem filtriem netika atrasts.</div>
                @endforelse
            </div>

            @if ($items->hasPages())
                <div class="mt-6">
                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </section>
</x-app-layout>
