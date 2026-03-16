<x-app-layout>
    @php
        $summaryCards = [
            ['label' => 'Kopā ierīču', 'value' => $summary['total_devices'], 'note' => 'Visas inventāra vienības', 'tone' => 'sky'],
            ['label' => 'Telpu pārklājums', 'value' => $summary['coverage_percent'] . '%', 'note' => $summary['rooms_with_devices'] . ' telpas ar ierīcēm', 'tone' => 'emerald'],
            ['label' => 'Bez telpas', 'value' => $summary['devices_without_room'], 'note' => 'Jāpārskata izvietojums', 'tone' => 'amber'],
            ['label' => 'Tukšas telpas', 'value' => $summary['rooms_without_devices'], 'note' => 'Bez piesaistītām ierīcēm', 'tone' => 'slate'],
        ];

        $toneClasses = [
            'sky' => ['card' => 'border-sky-200 bg-sky-50', 'bar' => 'bg-sky-500', 'text' => 'text-sky-700'],
            'emerald' => ['card' => 'border-emerald-200 bg-emerald-50', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
            'amber' => ['card' => 'border-amber-200 bg-amber-50', 'bar' => 'bg-amber-500', 'text' => 'text-amber-700'],
            'rose' => ['card' => 'border-rose-200 bg-rose-50', 'bar' => 'bg-rose-500', 'text' => 'text-rose-700'],
            'violet' => ['card' => 'border-violet-200 bg-violet-50', 'bar' => 'bg-violet-500', 'text' => 'text-violet-700'],
            'slate' => ['card' => 'border-slate-200 bg-slate-50', 'bar' => 'bg-slate-500', 'text' => 'text-slate-700'],
        ];
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="max-w-3xl">
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700 ring-1 ring-sky-200">
                        Ierīču skats
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Ierīču sadalījumi un izvietojums</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Statusi, telpu pārklājums, ēku noslodze un problēmierīces vienuviet, lai ātri ieraudzītu inventāra ainu.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('devices.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Pilna tabula
                    </a>
                    <a href="{{ route('devices.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Pievienot ierīci
                    </a>
                </div>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                @include('reports.partials.nav')
            </div>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Ātrie rādītāji</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">Ierīču stāvoklis īsumā</h2>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Inventāra skats</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($summaryCards as $card)
                    <div class="rounded-3xl border p-5 shadow-sm {{ $toneClasses[$card['tone']]['card'] }}">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $toneClasses[$card['tone']]['text'] }}">{{ $card['label'] }}</p>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $card['value'] }}</div>
                        <p class="mt-2 text-sm text-slate-600">{{ $card['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.95fr)]">
            <div class="space-y-5 rounded-[2rem] bg-slate-100/80 p-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Pieaugums</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Ierīču pievienošana pa mēnešiem</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">Pēdējie 6 mēneši</span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-6">
                        @foreach ($deviceTrend as $item)
                            @php $height = $deviceTrendMax > 0 ? max(10, (int) round(($item['count'] / $deviceTrendMax) * 160)) : 10; @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-44 items-end justify-center rounded-2xl bg-white px-3 py-4">
                                    <div class="w-full rounded-t-2xl bg-sky-500" style="height: {{ $height }}px;"></div>
                                </div>
                                <div class="mt-3 text-center">
                                    <div class="text-lg font-semibold text-slate-900">{{ $item['count'] }}</div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Statusi</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Ierīču statuss un proporcijas</h2>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($statusMetrics as $metric)
                            @php $tone = $toneClasses[$metric['tone']] ?? $toneClasses['slate']; @endphp
                            <div class="rounded-3xl border p-5 {{ $tone['card'] }}">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $metric['label'] }}</p>
                                    <span class="text-sm font-semibold {{ $tone['text'] }}">{{ $metric['share'] }}%</span>
                                </div>
                                <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $metric['count'] }}</div>
                                <div class="mt-4 h-2.5 rounded-full bg-white/80">
                                    <div class="h-2.5 rounded-full {{ $tone['bar'] }}" style="width: {{ min(100, $metric['share']) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Ēkas</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Ēku noslodze un statuss</h2>
                        </div>
                        <a href="{{ route('buildings.index') }}" class="text-sm font-semibold text-sky-700 transition hover:text-sky-800">Atvērt ēkas</a>
                    </div>

                    <div class="space-y-3">
                        @forelse ($buildingBreakdown as $building)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-900">{{ $building->building_name }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $building->rooms_count }} telpas | {{ $building->devices_count }} ierīces</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">{{ $building->devices_count }}</span>
                                </div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-emerald-200">
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Aktīvās</div>
                                        <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $building->active_devices_count }}</div>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-sky-200">
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Remonta</div>
                                        <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $building->repair_devices_count }}</div>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-rose-200">
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Bojātās</div>
                                        <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $building->broken_devices_count }}</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Ēku sadalījumam pagaidām nav datu.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-5 xl:sticky xl:top-6 xl:self-start rounded-[2rem] bg-sky-50/70 p-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Tipi</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Biežākie ierīču tipi</h2>
                    </div>

                    <div class="space-y-3">
                        @forelse ($typeBreakdown as $type)
                            @php $share = $summary['total_devices'] > 0 ? (int) round(($type->devices_count / $summary['total_devices']) * 100) : 0; @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-slate-900">{{ $type->type_name }}</div>
                                    <div class="text-sm font-semibold text-slate-500">{{ $type->devices_count }}</div>
                                </div>
                                <div class="mt-3 h-2.5 rounded-full bg-white">
                                    <div class="h-2.5 rounded-full bg-violet-500" style="width: {{ min(100, $share) }}%;"></div>
                                </div>
                                <div class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500">{{ $share }}%</div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Ierīču tipu statistika vēl nav pieejama.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Telpas</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Top telpas pēc ierīču skaita</h2>
                    </div>

                    <div class="space-y-3">
                        @forelse ($topRooms as $room)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $room->room_number }} @if ($room->room_name) | {{ $room->room_name }} @endif</div>
                                        <div class="mt-1 text-sm text-slate-500">{{ $room->building?->building_name ?: 'Ēka nav piesaistīta' }}</div>
                                    </div>
                                    <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">{{ $room->devices_count }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Top telpām pagaidām nav datu.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Problēmierīces</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Kas prasa uzmanību</h2>
                        </div>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">{{ $problemDevices->count() }} ierīces</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($problemDevices as $device)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    @if ($device->deviceImageThumbUrl())
                                        <img src="{{ $device->deviceImageThumbUrl() }}" alt="Ierīces attēls" class="h-14 w-14 rounded-2xl object-cover ring-1 ring-slate-200">
                                    @else
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-200 text-xs font-semibold text-slate-500">Nav</div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900">{{ $device->name }}</div>
                                                <div class="mt-1 text-sm text-slate-500">
                                                    {{ $device->type?->type_name ?: 'Tips nav norādīts' }}
                                                    @if ($device->code)
                                                        | {{ $device->code }}
                                                    @endif
                                                </div>
                                            </div>
                                            <a href="{{ route('devices.edit', $device) }}" class="text-sm font-semibold text-sky-700 transition hover:text-sky-800">Atvērt</a>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                                            @if ($device->status === 'broken')
                                                <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">Bojāta</span>
                                            @endif
                                            @if ($device->status === 'repair')
                                                <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-700">Remonta</span>
                                            @endif
                                            @if (! $device->room_id)
                                                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800">Bez telpas</span>
                                            @endif
                                            @if (! $device->device_image_url)
                                                <span class="rounded-full bg-slate-200 px-3 py-1 text-slate-700">Bez attēla</span>
                                            @endif
                                        </div>

                                        <div class="mt-3 text-sm text-slate-600">
                                            {{ $device->building?->building_name ?: 'Ēka nav piesaistīta' }}
                                            @if ($device->room)
                                                | {{ $device->room->room_number }} @if ($device->room->room_name) {{ $device->room->room_name }} @endif
                                            @endif
                                            @if ($device->activeRepair)
                                                | Aktīvs remonts #{{ $device->activeRepair->id }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Problēmierīces pagaidām nav atrastas.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
