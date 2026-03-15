<x-app-layout>
    @php
        $formatBytes = function (?int $bytes): string {
            $bytes = max(0, (int) $bytes);
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;
            $value = $bytes > 0 ? $bytes / (1024 ** $power) : 0;

            return number_format($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
        };

        $formatDuration = function (?int $milliseconds): string {
            if (! $milliseconds) return '-';
            if ($milliseconds < 1000) return $milliseconds . ' ms';
            $seconds = $milliseconds / 1000;
            return $seconds >= 60 ? number_format($seconds / 60, 1) . ' min' : number_format($seconds, 1) . ' s';
        };

        $creatorLabel = function ($backup): string {
            if ($backup->trigger_type === 'scheduled' || $backup->creator_type === 'system') return 'Sistema';
            return $backup->created_by_name ?: ($backup->created_by_user_id ? 'Lietotajs #' . $backup->created_by_user_id : 'Manuali');
        };

        $triggerLabel = fn (string $trigger) => match ($trigger) {
            'scheduled' => 'Automatiska',
            'uploaded' => 'Importeta',
            default => 'Manuala',
        };

        $triggerTone = fn (string $trigger) => match ($trigger) {
            'scheduled' => 'bg-amber-100 text-amber-800',
            'uploaded' => 'bg-violet-100 text-violet-800',
            default => 'bg-sky-100 text-sky-700',
        };

        $queryLink = function (array $overrides = []) use ($filters): string {
            return route('backups.index', array_filter(array_merge($filters, $overrides), fn ($value) => $value !== ''));
        };
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 overflow-hidden rounded-[2rem] border border-sky-100 bg-gradient-to-br from-white via-sky-50 to-cyan-100 shadow-sm">
            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1.3fr)_320px] lg:p-8">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M6 3.75h12a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 16.5h4.5"/>
                        </svg>
                        Rezerves kopiju centrs
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Datubazes dublesana, atjaunosana un kontrole</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
                        Veido manualas kopijas, plano automatiskas kopijas, importee failus no datora un parvaldi visu backup vesturi ar meklēšanu, filtriem un skaidram darbibu pogam.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        <span class="rounded-full bg-white px-3 py-2 ring-1 ring-slate-200">JSON katalogs</span>
                        <span class="rounded-full bg-white px-3 py-2 ring-1 ring-slate-200">Pilna DB atjaunosana</span>
                        <span class="rounded-full bg-white px-3 py-2 ring-1 ring-slate-200">Faili uz servera</span>
                    </div>
                </div>

                <div class="flex flex-col justify-between gap-3 rounded-[1.75rem] border border-white/70 bg-white/85 p-5 shadow-sm backdrop-blur">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Atras piekluves</p>
                        <p class="mt-2 text-sm text-slate-600">Atver vesturi, izveido jaunu kopiju vai uzreiz atjauno datubazi no saglabata faila.</p>
                    </div>
                    <form method="POST" action="{{ route('backups.store') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Izveidot manualo kopiju
                        </button>
                    </form>
                    <a href="#backup-history" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h9"/>
                        </svg>
                        Atvert vesturi
                    </a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18l9-13.5"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2.25 2.25 0 0 0 1.93 3.375h16.5A2.25 2.25 0 0 0 22.18 18L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-sky-100 bg-gradient-to-br from-white to-sky-50 p-5 shadow-sm">
                <div class="flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M6 3.75h12a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 16.5h4.5"/></svg></span><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kopiju skaits</p><p class="mt-1 text-3xl font-semibold text-slate-900">{{ $summary['count'] }}</p></div></div>
                <p class="mt-4 text-sm text-slate-600">Manualas: {{ $summary['manual_count'] }} | Autom.: {{ $summary['scheduled_count'] }} | Importetas: {{ $summary['uploaded_count'] }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-amber-100 bg-gradient-to-br from-white to-amber-50 p-5 shadow-sm">
                <div class="flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 2.25-2.25m0 0L21 6.75m-2.25-2.25V15a3 3 0 0 1-3 3h-9"/><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 17.25-2.25 2.25m0 0L3 17.25m2.25 2.25V9a3 3 0 0 1 3-3h9"/></svg></span><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pedeja kopija</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ $summary['latest']?->created_at?->format('d.m.Y H:i') ?? 'Nav izveidota' }}</p></div></div>
                <p class="mt-4 text-sm text-slate-600">{{ $summary['latest'] ? $triggerLabel($summary['latest']->trigger_type) . ' | ' . $creatorLabel($summary['latest']) : 'Sagaida pirmo kopiju.' }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-violet-100 bg-gradient-to-br from-white to-violet-50 p-5 shadow-sm">
                <div class="flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg></span><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kopejais apjoms</p><p class="mt-1 text-3xl font-semibold text-slate-900">{{ $formatBytes($summary['total_size']) }}</p></div></div>
                <p class="mt-4 text-sm text-slate-600">Visas kopijas glabajas atseviski no galvenas datubazes shemas.</p>
            </div>
            <div class="rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-white to-emerald-50 p-5 shadow-sm">
                <div class="flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></span><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Aktiva versija</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ $summary['current']?->name ?? 'Nav fikseta' }}</p></div></div>
                <p class="mt-4 text-sm text-slate-600">{{ $summary['current']?->last_restored_at ? 'Atjaunota ' . $summary['current']->last_restored_at->format('d.m.Y H:i') : 'Vel nav veikta atjaunosana.' }}</p>
            </div>
        </div>

        <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm" x-data="{ frequency: '{{ old('frequency', $settings->frequency) }}' }">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                    <div><h2 class="text-xl font-semibold text-slate-900">Automatiskais grafiks</h2><p class="mt-1 text-sm text-slate-500">Izvelies biezumu un laiku nakamajai automatiskajai rezerves kopijai.</p></div>
                    <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-600"><span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nakamais starts</span><strong class="mt-1 block text-slate-900">{{ $summary['next_run_at']?->format('d.m.Y H:i') ?? 'Izslegts' }}</strong></div>
                </div>
                <form method="POST" action="{{ route('backups.settings.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700"><input type="checkbox" name="enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('enabled', $settings->enabled))><span>Aktivet automatiskas rezerves kopijas</span></label>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 ring-1 ring-slate-200">{{ $frequencyLabel }}</span>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block"><span class="user-filter-label">Biezums</span><select name="frequency" x-model="frequency" class="crud-control"><option value="daily">Katru dienu</option><option value="weekly">Katru nedelu</option><option value="monthly">Reizi menesi</option></select></label>
                        <label class="block"><span class="user-filter-label">Laiks</span><input type="time" name="run_at" value="{{ old('run_at', substr((string) $settings->run_at, 0, 5)) }}" class="crud-control"></label>
                        <label class="block" x-show="frequency === 'weekly'" x-cloak><span class="user-filter-label">Nedelas diena</span><select name="weekly_day" class="crud-control">@foreach ([1 => 'Pirmdiena', 2 => 'Otrdiena', 3 => 'Tresdiena', 4 => 'Ceturtdiena', 5 => 'Piektdiena', 6 => 'Sestdiena', 7 => 'Svetdiena'] as $day => $label)<option value="{{ $day }}" @selected((int) old('weekly_day', $settings->weekly_day) === $day)>{{ $label }}</option>@endforeach</select></label>
                        <label class="block" x-show="frequency === 'monthly'" x-cloak><span class="user-filter-label">Menesa datums</span><select name="monthly_day" class="crud-control">@for ($day = 1; $day <= 31; $day++)<option value="{{ $day }}" @selected((int) old('monthly_day', $settings->monthly_day) === $day)>{{ $day }}. datums</option>@endfor</select></label>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18l9-13.5"/></svg><span>Saglabat grafiku</span></button>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"><span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pedejais automatiskais starts</span><strong class="mt-1 block text-slate-900">{{ $settings->last_scheduled_backup_at?->format('d.m.Y H:i') ?? 'Vel nav bijis' }}</strong></div>
                    </div>
                </form>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-white to-violet-50 p-6 shadow-sm">
                <div class="mb-5"><h2 class="text-xl font-semibold text-slate-900">Atjaunot no datora faila</h2><p class="mt-1 text-sm text-slate-500">Augshuplade pilnu datubazes eksportu, saglaba to serveri un uzreiz pievieno backup vesturei.</p></div>
                <div class="mb-5 space-y-3 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-violet-100"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">1</span><span>Izvelies rezerves kopijas failu no datora.</span></div>
                    <div class="flex items-start gap-3 rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-violet-100"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">2</span><span>Fails tiks saglabats serveri un paradisies vesture.</span></div>
                    <div class="flex items-start gap-3 rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-violet-100"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">3</span><span>Sistema uzreiz veiks datubazes atjaunosanu no shi eksporta.</span></div>
                </div>
                <form method="POST" action="{{ route('backups.upload-restore') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <label class="block rounded-[1.5rem] border border-dashed border-violet-200 bg-white/80 p-4"><span class="user-filter-label">Rezerves kopijas fails</span><input type="file" name="backup_file" accept=".json,.bak,.backup,.txt" class="crud-control block w-full cursor-pointer file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"></label>
                    <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"><svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2.25 2.25 0 0 0 1.93 3.375h16.5A2.25 2.25 0 0 0 22.18 18L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z"/></svg><span>Pirms atjaunosanas parliecinies, ka fails ir pilna un korekta shis sistemas rezerves kopija.</span></div>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-700" onclick="return confirm('Atjaunot datubazi no augshupladeta faila?')"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15"/></svg><span>Augshupladet un atjaunot</span></button>
                </form>
            </div>
        </div>

        <div id="backup-history" class="mb-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div><h2 class="text-xl font-semibold text-slate-900">Rezerves kopiju vesture</h2><p class="mt-1 text-sm text-slate-500">Mekle un filtre pec nosaukuma, veida, autora un datuma.</p></div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">Atrasti ieraksti: {{ $summary['filtered_count'] }}</div>
            </div>
            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ $queryLink(['trigger' => '']) }}" class="user-role-chip {{ $filters['trigger'] === '' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Visas kopijas</a>
                <a href="{{ $queryLink(['trigger' => 'manual']) }}" class="user-role-chip {{ $filters['trigger'] === 'manual' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Manualas</a>
                <a href="{{ $queryLink(['trigger' => 'scheduled']) }}" class="user-role-chip {{ $filters['trigger'] === 'scheduled' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Automatiskas</a>
                <a href="{{ $queryLink(['trigger' => 'uploaded']) }}" class="user-role-chip {{ $filters['trigger'] === 'uploaded' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">Importetas</a>
            </div>
            <form method="GET" action="{{ route('backups.index') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_auto]">
                <label class="block"><span class="user-filter-label">Meklet pec nosaukuma</span><div class="relative"><svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg><input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Piem. Manuala kopija vai datubaze" class="crud-control pl-10"></div></label>
                <label class="block"><span class="user-filter-label">Veidotaja tips</span><select name="creator_scope" class="crud-control"><option value="">Visi</option><option value="user" @selected($filters['creator_scope'] === 'user')>Lietotaji</option><option value="system" @selected($filters['creator_scope'] === 'system')>Sistema</option></select></label>
                <label class="block"><span class="user-filter-label">Veidotaja vards</span><input type="text" name="creator_name" value="{{ $filters['creator_name'] }}" placeholder="Piem. Janis Berzins" class="crud-control"></label>
                <label class="block"><span class="user-filter-label">No datuma</span><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="crud-control"></label>
                <label class="block"><span class="user-filter-label">Lidz datumam</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="crud-control"></label>
                <input type="hidden" name="trigger" value="{{ $filters['trigger'] }}">
                <div class="flex items-end gap-2"><button type="submit" class="crud-btn-primary inline-flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg><span>Meklet</span></button><a href="{{ route('backups.index') }}" class="crud-btn-secondary inline-flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg><span>Notirit</span></a></div>
            </form>
        </div>

        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-[0.18em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Nosaukums un tips</th>
                            <th class="px-4 py-3 text-left">Veidotajs</th>
                            <th class="px-4 py-3 text-left">Datums un apjoms</th>
                            <th class="px-4 py-3 text-left">Saturs</th>
                            <th class="px-4 py-3 text-left">Statuss</th>
                            <th class="px-4 py-3 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($backups as $backup)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-4 align-top"><div class="font-semibold text-slate-900">{{ $backup->name }}</div><div class="mt-2 flex flex-wrap gap-2 text-xs"><span class="{{ $triggerTone($backup->trigger_type) }} inline-flex rounded-full px-2.5 py-1 font-semibold">{{ $triggerLabel($backup->trigger_type) }}</span><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">{{ strtoupper($backup->database_driver) }}</span>@if ($backup->database_name)<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">{{ $backup->database_name }}</span>@endif</div></td>
                                <td class="px-4 py-4 align-top"><div class="font-semibold text-slate-900">{{ $creatorLabel($backup) }}</div><div class="mt-1 text-sm text-slate-500">{{ $backup->trigger_type === 'uploaded' ? 'Fails no datora' : ($backup->trigger_type === 'scheduled' ? 'Automatiski izveidota' : 'Lietotaja izveidota') }}</div></td>
                                <td class="px-4 py-4 align-top"><div class="font-semibold text-slate-900">{{ $backup->created_at?->format('d.m.Y H:i') ?? '-' }}</div><div class="mt-1 text-sm text-slate-500">{{ $formatBytes($backup->file_size_bytes) }} | {{ $formatDuration($backup->duration_ms) }}</div></td>
                                <td class="px-4 py-4 align-top"><div class="font-semibold text-slate-900">{{ $backup->total_tables }} tabulas</div><div class="mt-1 text-sm text-slate-500">{{ number_format($backup->total_rows, 0, ',', ' ') }} ieraksti</div></td>
                                <td class="px-4 py-4 align-top">@if ($backup->is_current)<span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktiva atjaunosana</span>@else<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Arhivets snapshots</span>@endif<div class="mt-2 text-sm text-slate-500">{{ $backup->last_restored_at?->format('d.m.Y H:i') ? 'Pedeja atjaunosana ' . $backup->last_restored_at->format('d.m.Y H:i') : 'Vel nav atjaunota' }}</div></td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('backups.download', ['backup' => $backup->id]) }}" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15"/></svg><span>Lejupieladet</span></a>
                                        <form method="POST" action="{{ route('backups.restore', ['backup' => $backup->id]) }}">@csrf<button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100" onclick="return confirm('Atjaunot datubazi no si faila?')"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 2.25-2.25m0 0L21 6.75m-2.25-2.25V15a3 3 0 0 1-3 3h-9"/><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 17.25-2.25 2.25m0 0L3 17.25m2.25 2.25V9a3 3 0 0 1 3-3h9"/></svg><span>Atjaunot</span></button></form>
                                        <form method="POST" action="{{ route('backups.destroy', ['backup' => $backup->id]) }}">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100" onclick="return confirm('Dzest so rezerves kopiju?')" @disabled($backup->is_current) title="{{ $backup->is_current ? 'Aktivu kopiju dzest nedrikst.' : 'Dzest kopiju' }}"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.11 0 0 0-7.5 0"/></svg><span>Dzest</span></button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Rezerves kopiju vel nav. Izveido pirmo manualo kopiju vai augshuplade eksportu no datora.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($backups->hasPages())
            <div class="mt-5">{{ $backups->links() }}</div>
        @endif
    </section>
</x-app-layout>
