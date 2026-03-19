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

        <section class="surface-card p-6">
            <form method="GET" class="grid gap-5 xl:grid-cols-[1.3fr_1fr_auto]">
                <input type="hidden" name="statuses_filter" value="1">
                <input type="hidden" name="types_filter" value="1">
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

                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <span class="mb-2 block text-sm font-medium text-slate-700">Statuss</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($statusLabels as $value => $label)
                                @php $selected = in_array($value, $filters['statuses'], true); @endphp
                                <label class="{{ $selected ? 'border-sky-300 bg-sky-50 text-sky-900 shadow-[0_16px_36px_-28px_rgba(14,165,233,0.6)]' : 'border-slate-200 bg-white text-slate-600' }} inline-flex cursor-pointer items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition hover:border-sky-200 hover:text-slate-900">
                                    <input type="checkbox" name="statuses[]" value="{{ $value }}" class="sr-only" @checked($selected)>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ $selected ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                        <x-icon :name="$selected ? 'check' : 'plus'" size="h-3.5 w-3.5" />
                                    </span>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <span class="mb-2 block text-sm font-medium text-slate-700">Tips</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($typeLabels as $value => $label)
                                @php $selected = in_array($value, $filters['types'], true); @endphp
                                <label class="{{ $selected ? 'border-emerald-300 bg-emerald-50 text-emerald-900 shadow-[0_16px_36px_-28px_rgba(16,185,129,0.55)]' : 'border-slate-200 bg-white text-slate-600' }} inline-flex cursor-pointer items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition hover:border-emerald-200 hover:text-slate-900">
                                    <input type="checkbox" name="types[]" value="{{ $value }}" class="sr-only" @checked($selected)>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ $selected ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                        <x-icon :name="$selected ? 'check' : 'plus'" size="h-3.5 w-3.5" />
                                    </span>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
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
