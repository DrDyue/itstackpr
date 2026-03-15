<x-app-layout>
    @php
        $moduleReady = $moduleReady ?? true;

        $formatBytes = function (?int $bytes): string {
            $bytes = max(0, (int) $bytes);
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;
            $value = $bytes > 0 ? $bytes / (1024 ** $power) : 0;

            return number_format($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
        };

        $formatDuration = function (?int $milliseconds): string {
            if (! $milliseconds) {
                return '-';
            }

            if ($milliseconds < 1000) {
                return $milliseconds . ' ms';
            }

            $seconds = $milliseconds / 1000;

            return $seconds >= 60
                ? number_format($seconds / 60, 1) . ' min'
                : number_format($seconds, 1) . ' s';
        };

        $creatorLabel = function ($backup): string {
            if ($backup->trigger_type === 'scheduled' || $backup->creator_type === 'system') {
                return 'Sistema';
            }

            return $backup->created_by_name
                ?: ($backup->created_by_user_id ? 'Lietotajs #' . $backup->created_by_user_id : 'Manuali');
        };

        $triggerLabel = function (string $trigger): string {
            return match ($trigger) {
                'scheduled' => 'Automatiska',
                'uploaded' => 'Importeta',
                default => 'Manuala',
            };
        };

        $frequencyLabel = match (old('frequency', $settings->frequency)) {
            'weekly' => 'Nedelas kopija',
            'monthly' => 'Menesa kopija',
            default => 'Ikdienas kopija',
        };
    @endphp

    <section class="user-shell">
        <div class="user-header">
            <div>
                <h1 class="device-page-title">Rezerves kopijas</h1>
                <p class="device-page-subtitle">Pilna datubazes dublesana, lejupielade, atjaunosana un automatisko kopiju grafiks.</p>
            </div>

            <form method="POST" action="{{ route('backups.store') }}">
                @csrf
                <button type="submit" class="crud-btn-primary-inline inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Izveidot manualo kopiju
                </button>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kopiju skaits</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['count'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Visas serveri saglabatas kopijas.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pedeja kopija</p>
                <p class="mt-3 text-lg font-semibold text-slate-900">{{ $summary['latest']?->created_at?->format('d.m.Y H:i') ?? 'Nav izveidota' }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $summary['latest'] ? $triggerLabel($summary['latest']->trigger_type) . ' | ' . $creatorLabel($summary['latest']) : 'Sagaida pirmo rezerves kopiju.' }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kopejais apjoms</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $formatBytes($summary['total_size']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Rezerves kopiju faili uz servera.</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Aktiva versija</p>
                <p class="mt-3 text-lg font-semibold text-slate-900">{{ $summary['current']?->name ?? 'Nav fikseta' }}</p>
                <p class="mt-2 text-sm text-slate-500">
                    @if ($summary['current']?->last_restored_at)
                        Atjaunota {{ $summary['current']->last_restored_at->format('d.m.Y H:i') }}
                    @else
                        Nav veikta atjaunosana no saglabatas kopijas.
                    @endif
                </p>
            </div>
        </div>

        <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div
                x-data="{ frequency: '{{ old('frequency', $settings->frequency) }}' }"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Automatiskais grafiks</h2>
                        <p class="mt-1 text-sm text-slate-500">Izvelies, cik biezi sistema pati izveidos pilnu datubazes kopiju.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-100 px-3 py-2 text-sm text-slate-600">
                        Nakamais starts: {{ $summary['next_run_at']?->format('d.m.Y H:i') ?? 'Izslegts' }}
                    </div>
                </div>

                <form method="POST" action="{{ route('backups.settings.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('enabled', $settings->enabled))>
                        Ieslegt automatiskas rezerves kopijas
                    </label>

                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="block">
                            <span class="user-filter-label">Biezums</span>
                            <select name="frequency" x-model="frequency" class="crud-control">
                                <option value="daily">Katru dienu</option>
                                <option value="weekly">Katru nedelu</option>
                                <option value="monthly">Reizi menesi</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="user-filter-label">Laiks</span>
                            <input type="time" name="run_at" value="{{ old('run_at', substr((string) $settings->run_at, 0, 5)) }}" class="crud-control">
                        </label>

                        <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                            <p class="font-semibold">{{ $frequencyLabel }}</p>
                            <p class="mt-1 text-sky-700">Serverim japalaiz `php artisan schedule:run` ik minuti, lai grafiks stradatu automatiski.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block" x-show="frequency === 'weekly'" x-cloak>
                            <span class="user-filter-label">Nedelas diena</span>
                            <select name="weekly_day" class="crud-control">
                                @foreach ([1 => 'Pirmdiena', 2 => 'Otrdiena', 3 => 'Tresdiena', 4 => 'Ceturtdiena', 5 => 'Piektdiena', 6 => 'Sestdiena', 7 => 'Svetdiena'] as $day => $label)
                                    <option value="{{ $day }}" @selected((int) old('weekly_day', $settings->weekly_day) === $day)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block" x-show="frequency === 'monthly'" x-cloak>
                            <span class="user-filter-label">Menesa datums</span>
                            <select name="monthly_day" class="crud-control">
                                @for ($day = 1; $day <= 31; $day++)
                                    <option value="{{ $day }}" @selected((int) old('monthly_day', $settings->monthly_day) === $day)>{{ $day }}. datums</option>
                                @endfor
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18l9-13.5"/>
                            </svg>
                            Saglabat grafiku
                        </button>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            Pedejais automatiskais starts: {{ $settings->last_scheduled_backup_at?->format('d.m.Y H:i') ?? 'Vel nav bijis' }}
                        </div>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-xl font-semibold text-slate-900">Atjaunot no datora faila</h2>
                    <p class="mt-1 text-sm text-slate-500">Augshuplade rezerves kopijas failu, saglaba to serveri un uzreiz atjauno datubazi no shi eksporta.</p>
                </div>

                <form method="POST" action="{{ route('backups.upload-restore') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <label class="block">
                        <span class="user-filter-label">Rezerves kopijas fails</span>
                        <input type="file" name="backup_file" accept=".json,.bak,.backup,.txt" class="crud-control block w-full cursor-pointer file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                    </label>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Pirms atjaunosanas parliecinies, ka fails ir pilna datubazes kopija no shis sistemas. Pec augshuplades tas paradisies kopiju vesture.
                    </div>

                    <button type="submit" class="crud-btn-primary inline-flex items-center gap-2" onclick="return confirm('Atjaunot datubazi no augshupladeta faila?')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15"/>
                        </svg>
                        Augshupladet un atjaunot
                    </button>
                </form>
            </div>
        </div>

        <div class="user-table-wrap">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Rezerves kopiju vesture</h2>
                    <p class="text-sm text-slate-500">Izmers, ilgums, ierakstu skaits, autors, lejupielade, atjaunosana un dzesana.</p>
                </div>
                <div class="rounded-2xl bg-slate-100 px-3 py-2 text-sm text-slate-600">
                    Kopiju skaits: {{ $backups->total() }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="user-table">
                    <thead class="user-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left">Nosaukums</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Avots</th>
                            <th class="px-4 py-3 text-left">Izmers</th>
                            <th class="px-4 py-3 text-left">Ilgums</th>
                            <th class="px-4 py-3 text-left">Tabulas / ieraksti</th>
                            <th class="px-4 py-3 text-left">Statuss</th>
                            <th class="px-4 py-3 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="user-table-body">
                        @forelse ($backups as $backup)
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-slate-900">{{ $backup->name }}</div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ strtoupper($backup->database_driver) }}</span>
                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-sky-700">{{ $triggerLabel($backup->trigger_type) }}</span>
                                        @if ($backup->is_current)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-emerald-700">Aktiva atjaunosana</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div>{{ $backup->created_at?->format('d.m.Y H:i') ?? '-' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $backup->database_name ?: 'Datubaze nav noradita' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div>{{ $creatorLabel($backup) }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $backup->trigger_type === 'uploaded' ? 'Fails no datora' : ($backup->trigger_type === 'scheduled' ? 'Automatiski' : 'Manuali') }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $formatBytes($backup->file_size_bytes) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $formatDuration($backup->duration_ms) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div>{{ $backup->total_tables }} tabulas</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ number_format($backup->total_rows, 0, ',', ' ') }} ieraksti</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div>Atjaunota {{ $backup->restore_count }} reizes</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $backup->last_restored_at?->format('d.m.Y H:i') ?? 'Vel nav atjaunota' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('backups.download', ['backup' => $backup->id]) }}" class="user-action user-action-edit">
                                            Lejupieladet
                                        </a>
                                        <form method="POST" action="{{ route('backups.restore', ['backup' => $backup->id]) }}">
                                            @csrf
                                            <button type="submit" class="user-action user-action-edit" onclick="return confirm('Atjaunot datubazi no si faila?')">
                                                Atjaunot
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('backups.destroy', ['backup' => $backup->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="user-action user-action-delete"
                                                onclick="return confirm('Dzest so rezerves kopiju?')"
                                                @disabled($backup->is_current)
                                                title="{{ $backup->is_current ? 'Aktivu kopiju dzest nedrikst.' : 'Dzest kopiju' }}"
                                            >
                                                Dzest
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                                    Rezerves kopiju vel nav. Izveido pirmo manualo kopiju vai augshuplade eksportu no datora.
                                </td>
                            </tr>
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
