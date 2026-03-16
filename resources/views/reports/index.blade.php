<x-app-layout>
    @php
        $summaryCards = [
            ['label' => 'Kopa iericu', 'value' => $summary['total_devices'], 'note' => 'Aktualais inventars', 'tone' => 'sky'],
            ['label' => 'Aktivie remonti', 'value' => $summary['active_repairs'], 'note' => $repairScope['label'], 'tone' => 'amber'],
            ['label' => 'Kavejie remonti', 'value' => $summary['overdue_repairs'], 'note' => 'Prasa uzmanibu', 'tone' => 'rose'],
            ['label' => 'Pabeigti saja menesi', 'value' => $summary['completed_repairs_this_month'], 'note' => 'Noslegtie darbi', 'tone' => 'emerald'],
            ['label' => 'Notikumi sodien', 'value' => $summary['audit_today'], 'note' => 'Auditora ieraksti', 'tone' => 'violet'],
            ['label' => 'Bridinajumi 30 dienas', 'value' => $summary['warning_activity'], 'note' => 'Svarigakie pazinojumi', 'tone' => 'slate'],
        ];

        $toneClasses = [
            'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
            'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
        ];

        $reportCards = [
            [
                'title' => 'Iericu sadalijumi',
                'subtitle' => 'Statusi, tipi, ekas, telpas un problemierices.',
                'route' => route('reports.devices'),
                'cta' => 'Atvert iericu skatu',
                'tone' => 'sky',
                'items' => ['Statusu proporcijas', 'Eku noslodze', 'Top telpas'],
            ],
            [
                'title' => 'Remontu analitika',
                'subtitle' => 'Statusi, prioritates, termini un noslodze.',
                'route' => route('reports.repairs'),
                'cta' => 'Atvert remontu skatu',
                'tone' => 'amber',
                'items' => ['Atvertie pret pabeigtajiem', 'Kritiskie un arejie', 'Noslodze pa lietotajiem'],
            ],
            [
                'title' => 'Aktivitate un vesture',
                'subtitle' => 'Auditora plusts, remonta notikumi un izmainas.',
                'route' => route('reports.activity'),
                'cta' => 'Atvert aktivitates skatu',
                'tone' => 'violet',
                'items' => ['Darbibu sadalijums', 'Pedejie remonta ieraksti', 'Iericu vesture'],
            ],
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700 ring-1 ring-sky-200">
                        Skati
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Atskaites un statistika</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Centrala vieta, kur atri apskatit inventaru, remontu dinamiku un pedejas sistemas darbibas.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">{{ now()->format('d.m.Y') }}</span>
                    <span class="rounded-full bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 ring-1 ring-amber-200">{{ $repairScope['label'] }}</span>
                </div>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                @include('reports.partials.nav')

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('reports.devices') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Iericu skats
                    </a>
                    <a href="{{ route('reports.repairs') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Remontu skats
                    </a>
                    <a href="{{ route('reports.activity') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Aktivitates skats
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($summaryCards as $card)
                <div class="rounded-3xl border p-5 shadow-sm {{ $toneClasses[$card['tone']] }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em]">{{ $card['label'] }}</p>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $card['value'] }}</div>
                    <p class="mt-2 text-sm text-slate-600">{{ $card['note'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Galvenie skati</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Svarigakie analitikas griezumi</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">{{ count($reportCards) }} lapas</span>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach ($reportCards as $card)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] {{ $card['tone'] === 'amber' ? 'bg-amber-100 text-amber-800' : ($card['tone'] === 'violet' ? 'bg-violet-100 text-violet-800' : 'bg-sky-100 text-sky-800') }}">
                                {{ $card['title'] }}
                            </div>
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $card['subtitle'] }}</p>

                            <div class="mt-4 space-y-2">
                                @foreach ($card['items'] as $item)
                                    <div class="flex items-start gap-2 text-sm text-slate-700">
                                        <span class="mt-1 h-2 w-2 rounded-full {{ $card['tone'] === 'amber' ? 'bg-amber-400' : ($card['tone'] === 'violet' ? 'bg-violet-400' : 'bg-sky-400') }}"></span>
                                        <span>{{ $item }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <a href="{{ $card['route'] }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-900 transition hover:text-sky-700">
                                {{ $card['cta'] }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Ko redzet uzreiz</p>
                    <div class="mt-4 space-y-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Bojatas ierices</p>
                                    <p class="mt-1 text-sm text-slate-600">Ierices, kuram vajag atraku parskatu.</p>
                                </div>
                                <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-700">{{ $summary['broken_devices'] }}</span>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Bez telpas piesaistes</p>
                                    <p class="mt-1 text-sm text-slate-600">Ierices, kuram japarskata izvietojums.</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">{{ $summary['devices_without_room'] }}</span>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Remontu tverums</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $repairScope['description'] }}</p>
                                </div>
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-sm font-semibold text-sky-700">{{ $summary['active_repairs'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Atrie celi</p>
                    <div class="mt-4 grid gap-3">
                        <a href="{{ route('devices.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                            <span class="text-sm font-semibold text-slate-900">Pilna iericu tabula</span>
                            <span class="text-xs font-medium text-slate-500">Atvert</span>
                        </a>
                        <a href="{{ route('repairs.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                            <span class="text-sm font-semibold text-slate-900">Pilna remontu lenta</span>
                            <span class="text-xs font-medium text-slate-500">Atvert</span>
                        </a>
                        <a href="{{ route('audit-log.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                            <span class="text-sm font-semibold text-slate-900">Auditora zurnals</span>
                            <span class="text-xs font-medium text-slate-500">Atvert</span>
                        </a>
                        <a href="{{ route('device-history.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                            <span class="text-sm font-semibold text-slate-900">Iericu vesture</span>
                            <span class="text-xs font-medium text-slate-500">Atvert</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
