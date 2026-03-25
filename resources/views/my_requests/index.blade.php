<x-app-layout>
    @php
        $typeSections = [
            'repair' => [
                'title' => 'Remonta pieteikumi',
                'subtitle' => 'Visi remonta iesniegumi vienkopus.',
                'accent' => 'amber',
                'icon' => 'repair-request',
            ],
            'writeoff' => [
                'title' => 'Norakstisanas pieteikumi',
                'subtitle' => 'Iesniegumi iericu norakstisanai.',
                'accent' => 'rose',
                'icon' => 'writeoff',
            ],
            'transfer' => [
                'title' => 'Nodosanas pieteikumi',
                'subtitle' => 'Ienakosie un izejosie iericu nodosanas pieprasijumi.',
                'accent' => 'emerald',
                'icon' => 'transfer',
            ],
        ];
        $groupedItems = $items->getCollection()
            ->groupBy('type')
            ->sortBy(fn ($_, $type) => array_search($type, array_keys($typeSections), true));
        $statusFilterOptions = collect($statusLabels)->map(fn ($label, $value) => [
            'value' => (string) $value,
            'label' => $label,
            'icon' => match ($value) {
                'submitted' => 'clock',
                'approved' => 'check-circle',
                'rejected' => 'x-circle',
                default => 'view',
            },
            'selected' => in_array($value, $filters['statuses'], true),
            'activeClasses' => match ($value) {
                'submitted' => 'border-amber-300 bg-amber-50 text-amber-950 shadow-[0_16px_36px_-28px_rgba(245,158,11,0.6)]',
                'approved' => 'border-emerald-300 bg-emerald-50 text-emerald-950 shadow-[0_16px_36px_-28px_rgba(16,185,129,0.6)]',
                'rejected' => 'border-rose-300 bg-rose-50 text-rose-950 shadow-[0_16px_36px_-28px_rgba(244,63,94,0.55)]',
                default => 'border-sky-300 bg-sky-50 text-sky-900',
            },
            'inactiveClasses' => 'border-slate-200 bg-white text-slate-600',
            'activeIconClasses' => match ($value) {
                'submitted' => 'bg-amber-500 text-white',
                'approved' => 'bg-emerald-600 text-white',
                'rejected' => 'bg-rose-600 text-white',
                default => 'bg-sky-600 text-white',
            },
            'inactiveIconClasses' => 'bg-slate-100 text-slate-400',
        ])->values();
        $typeFilterOptions = collect($typeLabels)->map(fn ($label, $value) => [
            'value' => (string) $value,
            'label' => $label,
            'icon' => match ($value) {
                'repair' => 'repair-request',
                'writeoff' => 'writeoff',
                'transfer' => 'transfer',
                default => 'view',
            },
            'selected' => in_array($value, $filters['types'], true),
            'activeClasses' => match ($value) {
                'repair' => 'border-amber-300 bg-amber-50 text-amber-950 shadow-[0_16px_36px_-28px_rgba(245,158,11,0.6)]',
                'writeoff' => 'border-rose-300 bg-rose-50 text-rose-950 shadow-[0_16px_36px_-28px_rgba(244,63,94,0.55)]',
                'transfer' => 'border-emerald-300 bg-emerald-50 text-emerald-950 shadow-[0_16px_36px_-28px_rgba(16,185,129,0.6)]',
                default => 'border-slate-300 bg-slate-50 text-slate-900',
            },
            'inactiveClasses' => 'border-slate-200 bg-white text-slate-600',
            'activeIconClasses' => match ($value) {
                'repair' => 'bg-amber-500 text-white',
                'writeoff' => 'bg-rose-600 text-white',
                'transfer' => 'bg-emerald-600 text-white',
                default => 'bg-slate-700 text-white',
            },
            'inactiveIconClasses' => 'bg-slate-100 text-slate-400',
        ])->values();
    @endphp
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
                    <a href="{{ route('devices.index') }}" class="btn-back">
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
                    <div x-data="filterChipGroup({ selected: @js($filters['statuses']), minimum: 1 })">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Statuss</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($statusFilterOptions as $option)
                                <button
                                    type="button"
                                    @click="toggle(@js($option['value']))"
                                    :class="isSelected(@js($option['value'])) ? @js($option['activeClasses']) : @js($option['inactiveClasses'])"
                                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition hover:border-slate-300 hover:text-slate-900"
                                >
                                    <span
                                        :class="isSelected(@js($option['value'])) ? @js($option['activeIconClasses']) : @js($option['inactiveIconClasses'])"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full transition"
                                    >
                                        <x-icon :name="$option['icon']" size="h-3.5 w-3.5" />
                                    </span>
                                    <span>{{ $option['label'] }}</span>
                                </button>
                            @endforeach
                            <template x-for="value in selected" :key="'my-request-status-' + value">
                                <input type="hidden" name="statuses[]" :value="value">
                            </template>
                        </div>
                    </div>

                    <div x-data="filterChipGroup({ selected: @js($filters['types']), minimum: 1 })">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Tips</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($typeFilterOptions as $option)
                                <button
                                    type="button"
                                    @click="toggle(@js($option['value']))"
                                    :class="isSelected(@js($option['value'])) ? @js($option['activeClasses']) : @js($option['inactiveClasses'])"
                                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition hover:border-slate-300 hover:text-slate-900"
                                >
                                    <span
                                        :class="isSelected(@js($option['value'])) ? @js($option['activeIconClasses']) : @js($option['inactiveIconClasses'])"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full transition"
                                    >
                                        <x-icon :name="$option['icon']" size="h-3.5 w-3.5" />
                                    </span>
                                    <span>{{ $option['label'] }}</span>
                                </button>
                            @endforeach
                            <template x-for="value in selected" :key="'my-request-type-' + value">
                                <input type="hidden" name="types[]" :value="value">
                            </template>
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

        @if (session('success'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

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
                @forelse ($groupedItems as $type => $typeItems)
                    @php
                        $section = $typeSections[$type] ?? [
                            'title' => $typeLabels[$type] ?? ucfirst($type),
                            'subtitle' => 'Pieteikumu grupa.',
                            'accent' => 'slate',
                            'icon' => 'repair-request',
                        ];
                    @endphp
                    <div class="request-type-section">
                        <div class="request-type-divider request-type-divider-{{ $section['accent'] }}">
                            <div class="request-type-divider-line"></div>
                            <div class="request-type-divider-chip">
                                <span class="request-type-divider-icon">
                                    <x-icon :name="$section['icon']" size="h-4 w-4" />
                                </span>
                                <span>{{ $section['title'] }}</span>
                                <span class="request-type-divider-count">{{ $typeItems->count() }}</span>
                            </div>
                            <div class="request-type-divider-line"></div>
                        </div>
                        <p class="request-type-divider-note">{{ $section['subtitle'] }}</p>

                        <div class="space-y-4">
                            @foreach ($typeItems as $item)
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

                                    @if (! $item['is_incoming'] && $item['status'] === 'submitted')
                                        <div class="mt-5 flex flex-wrap gap-3">
                                            <a href="{{ route('my-requests.edit', ['requestType' => $item['type'], 'requestId' => $item['model']->id]) }}" class="btn-view">
                                                <x-icon name="view" size="h-4 w-4" />
                                                <span>Labot tekstu</span>
                                            </a>
                                            <form method="POST" action="{{ route('my-requests.destroy', ['requestType' => $item['type'], 'requestId' => $item['model']->id]) }}" onsubmit="return confirm('Vai tiesam atcelt so pieteikumu?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger">
                                                    <x-icon name="x-mark" size="h-4 w-4" />
                                                    <span>Atcelt pieteikumu</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    @if ($item['type'] === 'transfer' && $item['is_incoming'] && $item['status'] === 'submitted')
                                        <div class="mt-5 rounded-[1.5rem] border border-emerald-200 bg-emerald-50/80 p-4">
                                            <div class="text-sm font-semibold text-emerald-900">Apstiprini ierices sanemsanu</div>
                                            <div class="mt-1 text-sm text-emerald-800">
                                                Vari atstat ierici esosaja telpa vai uzreiz noradit jaunu atrasanas vietu.
                                            </div>

                                            <form method="POST" action="{{ route('device-transfers.review', $item['model']) }}" class="mt-4">
                                                @csrf
                                                <input type="hidden" name="status" value="approved">
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="submit" class="btn-create">
                                                        <x-icon name="check" size="h-4 w-4" />
                                                        <span>Apstiprinat</span>
                                                    </button>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('device-transfers.review', $item['model']) }}" class="mt-3">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn-danger">
                                                    <x-icon name="x-mark" size="h-4 w-4" />
                                                    <span>Noraidit</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
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
