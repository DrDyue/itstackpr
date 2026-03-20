<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="repair-request" size="h-4 w-4" />
                            <span>Pieteikumu centrs</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="repair-request" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $requestSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-amber">
                                <x-icon name="clock" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Iesniegti</span>
                                <span class="inventory-inline-value">{{ $requestSummary['submitted'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-emerald">
                                <x-icon name="check-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Apstiprinati</span>
                                <span class="inventory-inline-value">{{ $requestSummary['approved'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-rose">
                                <x-icon name="x-circle" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Noraiditi</span>
                                <span class="inventory-inline-value">{{ $requestSummary['rejected'] }}</span>
                            </span>
                        </div>
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

        <form method="GET" action="{{ route('repair-requests.index') }}" class="surface-toolbar grid gap-4 xl:grid-cols-[1.2fr_1fr_auto] xl:items-end">
            <input type="hidden" name="statuses_filter" value="1">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierice, kods, pieteicejs...">
            </label>
            <div>
                <span class="crud-label">Statuss</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($statuses as $status)
                        @php $selected = in_array($status, $filters['statuses'], true); @endphp
                        <label class="{{ $selected ? 'border-sky-300 bg-sky-50 text-sky-900 shadow-[0_16px_36px_-28px_rgba(14,165,233,0.6)]' : 'border-slate-200 bg-white text-slate-600' }} inline-flex cursor-pointer items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition hover:border-sky-200 hover:text-slate-900">
                            <input type="checkbox" name="status[]" value="{{ $status }}" class="sr-only" @checked($selected)>
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ $selected ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <x-icon :name="$selected ? 'check' : 'plus'" size="h-3.5 w-3.5" />
                            </span>
                            <span>{{ $statusLabels[$status] ?? $status }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="toolbar-actions xl:justify-end">
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
                ['label' => 'Statuss', 'value' => collect($filters['statuses'])->map(fn ($status) => $statusLabels[$status] ?? $status)->implode(', ')],
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
                @php
                    $deviceThumbUrl = $request->device?->deviceImageThumbUrl();
                    $deviceTypeName = $request->device?->type?->type_name ?: 'Ierice';
                    $deviceMeta = collect([$request->device?->manufacturer, $request->device?->model])
                        ->filter(fn ($value) => filled($value))
                        ->implode(' | ');
                @endphp
                <div class="surface-card">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                                    {{ $deviceTypeName }}
                                </span>
                                <div class="text-lg font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $request->created_at?->format('d.m.Y H:i') }}</div>
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
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Iesniegums</div>
                            <div class="mt-3 text-sm text-slate-600">
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
                                <div class="mt-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Problemas apraksts</div>
                                    <div class="mt-2 rounded-2xl bg-white px-4 py-3 leading-6 text-slate-700 ring-1 ring-slate-200">
                                        {{ $request->description }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ierice</div>
                            <div class="mt-3 grid gap-4 text-sm text-slate-600">
                                <div class="flex items-start gap-4">
                                    @if ($deviceThumbUrl)
                                        <img src="{{ $deviceThumbUrl }}" alt="{{ $request->device?->name ?: 'Ierice' }}" class="device-table-thumb shrink-0">
                                    @else
                                        <div class="device-table-thumb device-table-thumb-placeholder shrink-0">
                                            <x-icon name="device" size="h-4 w-4" />
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Nosaukums</div>
                                        <div class="mt-1 text-base font-semibold text-slate-900">{{ $request->device?->name ?: '-' }}</div>
                                        <div class="mt-1 text-sm text-slate-500">{{ $deviceMeta !== '' ? $deviceMeta : '-' }}</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Kods</div>
                                    <div class="mt-1 text-slate-700">{{ $request->device?->code ?: '-' }}</div>
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

                    @if ($canReview && $request->status === 'submitted')
                        <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                            <form method="POST" action="{{ route('repair-requests.review', $request) }}">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn-approve">
                                    <x-icon name="check-circle" size="h-4 w-4" />
                                    <span>Apstiprinat</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('repair-requests.review', $request) }}">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn-reject">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>Noraidit</span>
                                </button>
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
