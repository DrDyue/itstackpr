<x-app-layout>
    @php
        $filters = $filters ?? ['first_name' => '', 'last_name' => '', 'phone' => '', 'job_title' => '', 'is_active' => ''];

        $sortUrl = function (string $column) use ($filters, $sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('employees.index', array_filter([
                'first_name' => $filters['first_name'] ?: null,
                'last_name' => $filters['last_name'] ?: null,
                'phone' => $filters['phone'] ?: null,
                'job_title' => $filters['job_title'] ?: null,
                'is_active' => $filters['is_active'] !== '' ? $filters['is_active'] : null,
                'sort' => $column,
                'direction' => $nextDirection,
            ], fn ($value) => $value !== null));
        };
    @endphp

    <section class="employee-shell">
        <div class="employee-header">
            <div>
                <h1 class="device-page-title">Darbinieki</h1>
                <p class="device-page-subtitle">Pilns darbinieku saraksts ar kontaktinformaciju un amatiem.</p>
            </div>
            <a href="{{ route('employees.create') }}" class="crud-btn-primary-inline inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Pievienot darbinieku
            </a>
        </div>

        <div class="employee-toolbar">
            <form method="GET" action="{{ route('employees.index') }}" class="space-y-4">
                <div class="employee-search-grid">
                    <label class="block">
                        <span class="employee-filter-label">Vards</span>
                        <input type="text" name="first_name" value="{{ $filters['first_name'] }}" placeholder="Piem. Janis" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="employee-filter-label">Uzvards</span>
                        <input type="text" name="last_name" value="{{ $filters['last_name'] }}" placeholder="Piem. Berzins" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="employee-filter-label">Telefons</span>
                        <input type="text" name="phone" value="{{ $filters['phone'] }}" placeholder="Piem. 20000000" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="employee-filter-label">Amats</span>
                        <select name="job_title" class="crud-control">
                            <option value="">Visi amati</option>
                            @foreach ($jobTitles as $jobTitle)
                                <option value="{{ $jobTitle }}" @selected($filters['job_title'] === $jobTitle)>{{ $jobTitle }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="employee-filter-label">Aktivitate</span>
                        <select name="is_active" class="crud-control">
                            <option value="">Visi</option>
                            <option value="1" @selected($filters['is_active'] === '1')>Aktivi</option>
                            <option value="0" @selected($filters['is_active'] === '0')>Neaktivi</option>
                        </select>
                    </label>
                    <div class="employee-filter-actions">
                        <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                            Meklet
                        </button>
                        <a href="{{ route('employees.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Notirit
                        </a>
                    </div>
                </div>

                <div class="employee-toolbar-meta">
                    <label class="block min-w-[180px]">
                        <span class="employee-filter-label">Kartot pec</span>
                        <select name="sort" class="crud-control">
                            <option value="created_at" @selected($sort === 'created_at')>Izveidots</option>
                            <option value="full_name" @selected($sort === 'full_name')>Vards, uzvards</option>
                            <option value="phone" @selected($sort === 'phone')>Telefons</option>
                            <option value="job_title" @selected($sort === 'job_title')>Amats</option>
                            <option value="is_active" @selected($sort === 'is_active')>Aktivitate</option>
                        </select>
                    </label>
                    <label class="block min-w-[180px]">
                        <span class="employee-filter-label">Seciba</span>
                        <select name="direction" class="crud-control">
                            <option value="asc" @selected($direction === 'asc')>Augosa</option>
                            <option value="desc" @selected($direction === 'desc')>Dilstosa</option>
                        </select>
                    </label>
                    <span class="employee-results-chip">Atrasti darbinieki: {{ $employees->total() }}</span>
                </div>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="employee-table-wrap">
            <div class="overflow-x-auto">
                <table class="employee-table">
                    <thead class="employee-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('full_name') }}" class="employee-sort-link">
                                    Vards, uzvards
                                    @if ($sort === 'full_name')
                                        <span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left">E-pasts</th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('phone') }}" class="employee-sort-link">
                                    Telefons
                                    @if ($sort === 'phone')
                                        <span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('job_title') }}" class="employee-sort-link">
                                    Amats
                                    @if ($sort === 'job_title')
                                        <span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('is_active') }}" class="employee-sort-link">
                                    Darbinieks aktivs
                                    @if ($sort === 'is_active')
                                        <span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <a href="{{ $sortUrl('created_at') }}" class="employee-sort-link">
                                    Izveidots
                                    @if ($sort === 'created_at')
                                        <span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="employee-table-body">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-500">ID {{ $employee->id }}</td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900">{{ $employee->full_name }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $employee->email ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $employee->phone ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $employee->job_title ?: '-' }}</td>
                                <td class="px-4 py-4">
                                    @if ($employee->is_active)
                                        <span class="employee-status employee-status-active">Aktivs</span>
                                    @else
                                        <span class="employee-status employee-status-inactive">Neaktivs</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $employee->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="employee-actions-row">
                                        <a href="{{ route('employees.edit', $employee) }}" class="employee-action employee-action-edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5"/></svg>
                                            Rediget
                                        </a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzest so darbinieku?')" class="employee-action employee-action-delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.11 0 0 0-7.5 0"/></svg>
                                                Dzest
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">Darbinieki vel nav pievienoti vai neatbilst filtriem.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($employees->hasPages())
            <div class="mt-5">{{ $employees->links() }}</div>
        @endif
    </section>
</x-app-layout>
