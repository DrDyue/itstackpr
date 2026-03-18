<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="repair" size="h-4 w-4" /><span>Serviss</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="repair" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Remonti</h1>
                            <p class="page-subtitle">{{ $canManageRepairs ? 'Faktiskie remontdarbi pec apstiprinatiem pieteikumiem.' : 'Tavu iericu remontu statuss.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageRepairs)
                    <a href="{{ route('repairs.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns remonts</span></a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-4">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Ierice, apraksts, pakalpojuma sniedzejs...">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <select name="status" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Prioritate</span>
                <select name="priority" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <select name="repair_type" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($repairTypes as $repairType)
                        <option value="{{ $repairType }}" @selected($filters['repair_type'] === $repairType)>{{ $typeLabels[$repairType] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="toolbar-actions md:col-span-4">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('repairs.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Statuss', 'value' => $filters['status'] !== '' ? ($statusLabels[$filters['status']] ?? $filters['status']) : null],
                ['label' => 'Prioritate', 'value' => $filters['priority'] !== '' ? ($priorityLabels[$filters['priority']] ?? $filters['priority']) : null],
                ['label' => 'Tips', 'value' => $filters['repair_type'] !== '' ? ($typeLabels[$filters['repair_type']] ?? $filters['repair_type']) : null],
            ]"
            :clear-url="route('repairs.index')"
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

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Ierice</th>
                        <th class="px-4 py-3">Apraksts</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Prioritate</th>
                        <th class="px-4 py-3">Pieteicejs / apstiprinaja</th>
                        <th class="px-4 py-3">Termini</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($repairs as $repair)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $repair->device?->name ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $repair->device?->code ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $repair->device?->building?->building_name ?: '-' }} / {{ $repair->device?->room?->room_number ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div>{{ $repair->description }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    <span>Tips: {{ $typeLabels[$repair->repair_type] ?? $repair->repair_type }}</span>
                                    @if ($repair->request_id)
                                        <span> | Pieteikums #{{ $repair->request_id }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div><x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" /></div>
                                <div class="mt-2"><x-status-pill context="repair-type" :value="$repair->repair_type" :label="$typeLabels[$repair->repair_type] ?? null" /></div>
                            </td>
                            <td class="px-4 py-3">
                                <div><x-status-pill context="priority" :value="$repair->priority" :label="$priorityLabels[$repair->priority] ?? null" /></div>
                                <div class="mt-2 text-xs text-slate-500">Izmaksas: {{ $repair->cost !== null ? number_format((float) $repair->cost, 2, '.', ' ') . ' EUR' : '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div>Pieteica: {{ $repair->reporter?->full_name ?: '-' }}</div>
                                <div class="mt-1">Apstiprinaja: {{ $repair->acceptedBy?->full_name ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div>Sakums: {{ $repair->start_date?->format('d.m.Y') ?: '-' }}</div>
                                <div class="mt-1">Beigas: {{ $repair->end_date?->format('d.m.Y') ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($canManageRepairs)
                                    <a href="{{ route('repairs.edit', $repair) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediget</span></a>
                                @else
                                    <span class="text-slate-400">Tikai apskate</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Remonti nav atrasti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $repairs->links() }}
    </section>
</x-app-layout>

