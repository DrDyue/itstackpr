<x-app-layout>
    <section class="user-shell">
        <div class="user-header">
            <div>
                <h1 class="device-page-title">Lietotaji</h1>
                <p class="device-page-subtitle">Sistemas kontu parvaldiba un piekluves lomas.</p>
            </div>
            @if (auth()->user()?->role === 'admin')
                <a href="{{ route('users.create') }}" class="crud-btn-primary-inline inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Pievienot lietotaju
                </a>
            @endif
        </div>

        <div class="user-toolbar">
            <form method="GET" action="{{ route('users.index') }}" class="space-y-4">
                <div class="user-role-chips">
                    <a href="{{ route('users.index', array_filter([
                        'employee' => $filters['employee'] ?: null,
                        'email' => $filters['email'] ?: null,
                        'employee_active' => $filters['employee_active'] !== '' ? $filters['employee_active'] : null,
                        'is_active' => $filters['is_active'] !== '' ? $filters['is_active'] : null,
                        'sort' => $sort,
                        'direction' => $direction,
                    ], fn ($value) => $value !== null)) }}"
                       class="user-role-chip {{ $filters['role'] === '' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">
                        Visas lomas
                    </a>
                    @foreach ($roles as $role)
                        <a href="{{ route('users.index', array_filter([
                            'employee' => $filters['employee'] ?: null,
                            'email' => $filters['email'] ?: null,
                            'role' => $role,
                            'employee_active' => $filters['employee_active'] !== '' ? $filters['employee_active'] : null,
                            'is_active' => $filters['is_active'] !== '' ? $filters['is_active'] : null,
                            'sort' => $sort,
                            'direction' => $direction,
                        ], fn ($value) => $value !== null)) }}"
                           class="user-role-chip {{ $filters['role'] === $role ? 'user-role-chip-active' : 'user-role-chip-idle' }}">
                            {{ $role }}
                        </a>
                    @endforeach
                </div>

                <div class="user-role-chips">
                    <a href="{{ route('users.index', array_filter([
                        'employee' => $filters['employee'] ?: null,
                        'email' => $filters['email'] ?: null,
                        'role' => $filters['role'] ?: null,
                        'employee_active' => $filters['employee_active'] !== '' ? $filters['employee_active'] : null,
                        'sort' => $sort,
                        'direction' => $direction,
                    ], fn ($value) => $value !== null)) }}"
                       class="user-role-chip {{ $filters['is_active'] === '' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">
                        Visi konti
                    </a>
                    <a href="{{ route('users.index', array_filter([
                        'employee' => $filters['employee'] ?: null,
                        'email' => $filters['email'] ?: null,
                        'role' => $filters['role'] ?: null,
                        'employee_active' => $filters['employee_active'] !== '' ? $filters['employee_active'] : null,
                        'is_active' => '1',
                        'sort' => $sort,
                        'direction' => $direction,
                    ], fn ($value) => $value !== null)) }}"
                       class="user-role-chip {{ $filters['is_active'] === '1' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">
                        Aktivi konti
                    </a>
                    <a href="{{ route('users.index', array_filter([
                        'employee' => $filters['employee'] ?: null,
                        'email' => $filters['email'] ?: null,
                        'role' => $filters['role'] ?: null,
                        'employee_active' => $filters['employee_active'] !== '' ? $filters['employee_active'] : null,
                        'is_active' => '0',
                        'sort' => $sort,
                        'direction' => $direction,
                    ], fn ($value) => $value !== null)) }}"
                       class="user-role-chip {{ $filters['is_active'] === '0' ? 'user-role-chip-active' : 'user-role-chip-idle' }}">
                        Neaktivi konti
                    </a>
                </div>

                <div class="user-search-grid">
                    <label class="block">
                        <span class="user-filter-label">Darbinieks</span>
                        <input type="text" name="employee" value="{{ $filters['employee'] }}" placeholder="Piem. Janis Berzins" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="user-filter-label">E-pasts</span>
                        <input type="text" name="email" value="{{ $filters['email'] }}" placeholder="Piem. janis@example.com" class="crud-control">
                    </label>
                    <label class="block">
                        <span class="user-filter-label">Loma</span>
                        <select name="role" class="crud-control">
                            <option value="">Visas lomas</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="user-filter-label">Darbinieks aktivs</span>
                        <select name="employee_active" class="crud-control">
                            <option value="">Visi</option>
                            <option value="1" @selected($filters['employee_active'] === '1')>Aktivi</option>
                            <option value="0" @selected($filters['employee_active'] === '0')>Neaktivi</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="user-filter-label">Konts aktivs</span>
                        <select name="is_active" class="crud-control">
                            <option value="">Visi</option>
                            <option value="1" @selected($filters['is_active'] === '1')>Aktivi</option>
                            <option value="0" @selected($filters['is_active'] === '0')>Neaktivi</option>
                        </select>
                    </label>
                    <div class="user-filter-actions">
                        <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>
                            Meklet
                        </button>
                        <a href="{{ route('users.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Notirit
                        </a>
                    </div>
                </div>

                <div class="user-toolbar-meta">
                    <label class="block min-w-[180px]">
                        <span class="user-filter-label">Kartot pec</span>
                        <select name="sort" class="crud-control">
                            <option value="created_at" @selected($sort === 'created_at')>Izveidots</option>
                            <option value="last_login" @selected($sort === 'last_login')>Pedeja pieslegsanas</option>
                        </select>
                    </label>
                    <label class="block min-w-[180px]">
                        <span class="user-filter-label">Seciba</span>
                        <select name="direction" class="crud-control">
                            <option value="asc" @selected($direction === 'asc')>Augosa</option>
                            <option value="desc" @selected($direction === 'desc')>Dilstosa</option>
                        </select>
                    </label>
                    <span class="user-results-chip">Atrasti lietotaji: {{ $users->total() }}</span>
                </div>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="user-table-wrap">
            <div class="overflow-x-auto">
                <table class="user-table">
                    <thead class="user-table-head">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Darbinieks</th>
                            <th class="px-4 py-3 text-left">E-pasts</th>
                            <th class="px-4 py-3 text-left">Loma</th>
                            <th class="px-4 py-3 text-left">Darbinieks aktivs</th>
                            <th class="px-4 py-3 text-left">Konts aktivs</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Pedeja pieslegsanas</th>
                            <th class="px-4 py-3 text-left">Darbibas</th>
                        </tr>
                    </thead>
                    <tbody class="user-table-body">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-500">ID {{ $user->id }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $user->employee?->full_name ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $user->employee?->email ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="user-role-badge {{ $user->role === 'admin' ? 'user-role-admin' : 'user-role-user' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->employee?->is_active)
                                        <span class="user-status user-status-active">Aktivs</span>
                                    @else
                                        <span class="user-status user-status-inactive">Neaktivs</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->is_active)
                                        <span class="user-status user-status-active">Aktivs</span>
                                    @else
                                        <span class="user-status user-status-inactive">Neaktivs</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $user->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $user->last_login?->format('d.m.Y H:i') ?: 'Nav piesledzies' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="user-actions-row">
                                        <a href="{{ route('users.edit', $user) }}" class="user-action user-action-edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5"/></svg>
                                            Rediget
                                        </a>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzest so lietotaju?')" class="user-action user-action-delete" @disabled(auth()->id() === $user->id)>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.11 0 0 0-7.5 0"/></svg>
                                                Dzest
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">Lietotaji vel nav pievienoti vai neatbilst filtriem.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($users->hasPages())
            <div class="mt-5">{{ $users->links() }}</div>
        @endif
    </section>
</x-app-layout>
