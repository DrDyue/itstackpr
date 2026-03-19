<x-app-layout>
    @php
        $statusTone = match ($repair->status) {
            'in-progress' => 'border-sky-200 bg-sky-50 text-sky-900',
            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'cancelled' => 'border-rose-200 bg-rose-50 text-rose-900',
            default => 'border-amber-200 bg-amber-50 text-amber-900',
        };
        $deviceThumbUrl = $repair->device?->deviceImageThumbUrl();
        $statusMessage = match ($repair->status) {
            'in-progress' => 'Remonts sobrid atrodas procesa. Statusu no si skata nevar rediget tiesi, izmanto zemak redzamas darbibas.',
            'completed' => 'Remonts ir pabeigts. Ja vajag turpinat darbu, vari to atgriezt atpakal uz procesa statusu.',
            'cancelled' => 'Remonts ir atcelts. Ja darbs tomer jatjauno, vari to parslēgt atpakal uz gaida vai procesa statusu.',
            default => 'Remonts sobrid gaida uzsakšanu. Kad darbs sakts, parslēdz to uz procesa statusu.',
        };
    @endphp

    <section
        class="app-shell max-w-6xl"
        x-data="repairProcess({
            repairId: {{ $repair->id }},
            repairType: @js(old('repair_type', $repair->repair_type ?? 'internal')),
            status: @js($repair->status),
            transitionBaseUrl: @js(url('/repairs')),
            csrfToken: @js(csrf_token()),
        })"
    >
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="repair" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediget remontu</h1>
                            <p class="page-subtitle">Atjauno remonta informaciju, bet statusu maini no remonta kolonnam.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repairs.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakal</span></a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[1.35fr_0.95fr]">
            <div class="surface-card space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        @if ($deviceThumbUrl)
                            <img src="{{ $deviceThumbUrl }}" alt="{{ $repair->device?->name ?: 'Ierice' }}" class="device-table-thumb shrink-0">
                        @else
                            <div class="device-table-thumb device-table-thumb-placeholder shrink-0">
                                <x-icon name="device" size="h-4 w-4" />
                            </div>
                        @endif
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta kopsavilkums</div>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ $repair->device?->name ?: 'Ierice nav atrasta' }}</h2>
                            <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-500">
                                <span>Kods: {{ $repair->device?->code ?: 'bez koda' }}</span>
                                @if ($repair->device?->room?->room_number)
                                    <span>Telpa {{ $repair->device->room->room_number }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" />
                </div>

                <div class="rounded-2xl border px-4 py-4 text-sm {{ $statusTone }}">
                    <div class="font-semibold">Statusa informacija</div>
                    <div class="mt-2">{{ $statusMessage }}</div>
                    <div class="mt-3 grid gap-2 text-sm md:grid-cols-3">
                        <div><strong>Pasreizejais statuss:</strong> {{ $statusLabels[$repair->status] ?? $repair->status }}</div>
                        <div><strong>Sakuma datums:</strong> {{ $repair->start_date?->format('d.m.Y') ?: '-' }}</div>
                        <div><strong>Beigu datums:</strong> {{ $repair->end_date?->format('d.m.Y') ?: '-' }}</div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-semibold text-slate-900">Statusa darbibas</div>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @if ($repair->status === 'waiting')
                            <button type="button" class="btn-edit" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                                <x-icon name="stats" size="h-4 w-4" />
                                <span>Sakt remontu</span>
                            </button>
                            <button type="button" class="btn-danger" @click="submitTransition({{ $repair->id }}, 'cancelled')">
                                <x-icon name="clear" size="h-4 w-4" />
                                <span>Atcelt</span>
                            </button>
                        @elseif ($repair->status === 'in-progress')
                            <button type="button" class="btn-clear" @click="submitTransition({{ $repair->id }}, 'waiting')">
                                <x-icon name="back" size="h-4 w-4" />
                                <span>Atpakal uz gaida</span>
                            </button>
                            <button type="button" class="btn-approve" @click="submitCompletion()">
                                <x-icon name="check-circle" size="h-4 w-4" />
                                <span>Pabeigt remontu</span>
                            </button>
                            <button type="button" class="btn-danger" @click="submitTransition({{ $repair->id }}, 'cancelled')">
                                <x-icon name="clear" size="h-4 w-4" />
                                <span>Atcelt</span>
                            </button>
                        @elseif ($repair->status === 'completed')
                            <button type="button" class="btn-edit" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                                <x-icon name="back" size="h-4 w-4" />
                                <span>Atgriezt procesa</span>
                            </button>
                        @elseif ($repair->status === 'cancelled')
                            <button type="button" class="btn-clear" @click="submitTransition({{ $repair->id }}, 'waiting')">
                                <x-icon name="back" size="h-4 w-4" />
                                <span>Atgriezt uz gaida</span>
                            </button>
                            <button type="button" class="btn-edit" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                                <x-icon name="stats" size="h-4 w-4" />
                                <span>Atgriezt procesa</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="surface-card p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ierices informacija</div>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div><strong class="text-slate-900">Ierice:</strong> {{ $repair->device?->name ?: '-' }}</div>
                        <div><strong class="text-slate-900">Kods:</strong> {{ $repair->device?->code ?: 'bez koda' }}</div>
                        <div><strong class="text-slate-900">Razotajs un modelis:</strong> {{ collect([$repair->device?->manufacturer, $repair->device?->model])->filter()->implode(' | ') ?: '-' }}</div>
                        <div><strong class="text-slate-900">Apstiprinaja:</strong> {{ $repair->approval_actor?->full_name ?: 'Nav noradits' }}</div>
                    </div>
                </div>

                @if ($repair->request)
                    <div class="surface-card p-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pieteikuma informacija</div>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <div><strong class="text-slate-900">Pieteica:</strong> {{ $repair->request->responsibleUser?->full_name ?: 'Nav noradits' }}</div>
                            <div>
                                <div class="font-semibold text-slate-900">Problemas apraksts</div>
                                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 leading-6 text-slate-700">
                                    {{ $repair->request->description ?: '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('repairs.update', $repair) }}" class="surface-card space-y-6 p-6">
            @csrf
            @method('PUT')
            @include('repairs.partials.form-fields', ['repair' => $repair])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                <div class="text-sm text-slate-500">Forma paredzeta remonta datu atjaunosanai. Statusu maini ar statusa darbibam augstak.</div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Saglabat</span></button>
                    <a href="{{ route('repairs.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('repairs.destroy', $repair) }}" onsubmit="return confirm('Dzest so remonta ierakstu?')" class="surface-card border-rose-200 p-6">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger-solid"><x-icon name="trash" size="h-4 w-4" /><span>Dzest remontu</span></button>
        </form>
    </section>
</x-app-layout>
