{{--
    Lapa: Lietotāju saraksts.
    Atbildība: rāda sistēmas lietotājus, viņu lomas, aktivitāti un pēdējo pieslēgšanos.
    Datu avots: UserController@index.
    Galvenās daļas:
    1. Hero ar lietotāju kopsavilkumu.
    2. Filtri pēc meklēšanas, lomas un aktivitātes.
    3. Lietotāju tabula ar darbībām.
--}}
<x-app-layout>
    @php
        $roleFilterLinks = [
            ['label' => 'Admins', 'value' => 'admin', 'icon' => 'users', 'tone' => 'violet'],
            ['label' => 'Darbinieki', 'value' => 'user', 'icon' => 'profile', 'tone' => 'sky'],
        ];
        $selectedRoles = $filters['has_role_filter'] ? $filters['roles'] : collect($roleFilterLinks)->pluck('value')->all();
        $statusOptions = [
            ['value' => '1', 'label' => 'Aktīvi', 'description' => 'Rāda tikai aktīvus lietotājus', 'search' => 'Aktīvi aktīvi lietotāji'],
            ['value' => '0', 'label' => 'Neaktīvi', 'description' => 'Rāda tikai neaktīvus lietotājus', 'search' => 'Neaktīvi neaktīvie lietotāji'],
        ];
        $lastLoginOptions = [
            ['value' => 'today', 'label' => 'Šodien', 'description' => 'Pieslēdzas šodien', 'search' => 'Šodien pēdējā pieslēgšanās'],
            ['value' => 'recent', 'label' => 'Pēdējās 7 dienas', 'description' => 'Aktīvi pēdējā nedēļā', 'search' => 'Pēdējās 7 dienas nesen'],
            ['value' => 'never', 'label' => 'Nav pieslēdzies', 'description' => 'Lietotājs vēl nav pieslēdzies', 'search' => 'Nav pieslēdzies nēkad'],
        ];
        $selectedStatusLabel = collect($statusOptions)->firstWhere('value', $filters['is_active'])['label'] ?? null;
        $selectedLastLoginLabel = collect($lastLoginOptions)->firstWhere('value', $filters['last_login'])['label'] ?? null;
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow"><x-icon name="users" size="h-4 w-4" /><span>Lietotāji</span></div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="users" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopa</span>
                                <span class="inventory-inline-value">{{ $userSummary['total'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-violet">
                                <x-icon name="users" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Admini</span>
                                <span class="inventory-inline-value">{{ $userSummary['admin'] }}</span>
                            </span>
                            <span class="inventory-inline-chip inventory-inline-chip-sky">
                                <x-icon name="profile" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Darbinieki</span>
                                <span class="inventory-inline-value">{{ $userSummary['user'] }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet"><x-icon name="users" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Lietotāji</h1>
                            <p class="page-subtitle">Pārvaldi sistēmas lietotājus, lomas un piekļuves statusus.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns lietotājs</span></a>
            </div>
        </div>

        <div id="users-index-root" data-async-table-root>
        <form method="GET" action="{{ route('users.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-3 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)_minmax(0,1fr)]" data-async-table-form data-async-root="#users-index-root" data-search-endpoint="{{ route('users.find-by-name') }}">
            <label class="block">
                <span class="crud-label">Vārds un uzvārds</span>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="crud-control"
                        placeholder="Ievadi vārdu un uzvārdu"
                        data-async-manual="true"
                        data-table-manual-search="true"
                        data-search-mode="contains"
                    >
                    <button type="submit" class="btn-search shrink-0" data-table-search-submit="true">
                        <x-icon name="search" size="h-4 w-4" />
                        <span>Meklēt</span>
                    </button>
                </div>
            </label>
            <div class="block">
                <span class="crud-label">Statuss</span>
                <div class="status-segmented-control" x-data="{ value: @js($filters['is_active']) }">
                    <input type="hidden" name="is_active" :value="value">
                    <button type="button" class="status-segment status-segment-emerald" :class="value === '1' ? 'status-segment-active' : ''" @click="value = '1'; $nextTick(() => $el.closest('form').requestSubmit())">
                        <x-icon name="check-circle" size="h-4 w-4" />
                        <span>Aktīvi</span>
                    </button>
                    <button type="button" class="status-segment status-segment-slate" :class="value === '' ? 'status-segment-active' : ''" @click="value = ''; $nextTick(() => $el.closest('form').requestSubmit())">
                        <x-icon name="filter" size="h-4 w-4" />
                        <span>Visi</span>
                    </button>
                    <button type="button" class="status-segment status-segment-rose" :class="value === '0' ? 'status-segment-active' : ''" @click="value = '0'; $nextTick(() => $el.closest('form').requestSubmit())">
                        <x-icon name="x-circle" size="h-4 w-4" />
                        <span>Neaktīvi</span>
                    </button>
                </div>
            </div>
            <label class="block">
                <span class="crud-label">Pēdējā pieslēgšanās</span>
                <x-searchable-select
                    name="last_login"
                    query-name="last_login_query"
                    identifier="user-last-login-filter"
                    :options="$lastLoginOptions"
                    :selected="$filters['last_login']"
                    :query="$selectedLastLoginLabel"
                    placeholder="Izvēlies periodu"
                    empty-message="Neviens periods neatbilst meklējumam."
                />
            </label>

            <div class="filter-toolbar-footer md:col-span-3 xl:col-span-4">
                <div class="quick-status-filters">
                    @foreach ($roleFilterLinks as $roleFilter)
                        @php
                            $query = request()->except('page', 'role');
                            $roleValues = collect($selectedRoles);
                            $isActive = $roleValues->contains($roleFilter['value']);
                            $nextRoles = $isActive
                                ? $roleValues->reject(fn ($value) => $value === $roleFilter['value'])->values()->all()
                                : $roleValues->push($roleFilter['value'])->unique()->values()->all();

                            if (count($nextRoles) === 0 || count($nextRoles) === count($roleFilterLinks)) {
                                unset($query['role']);
                            } else {
                                $query['role'] = $nextRoles;
                            }
                        @endphp
                        <a
                            href="{{ route('users.index', $query) }}"
                            class="quick-status-filter quick-status-filter-{{ $roleFilter['tone'] }} {{ $isActive ? 'quick-status-filter-active' : '' }}"
                        >
                            <x-icon :name="$roleFilter['icon']" size="h-4 w-4" />
                            <span>{{ $roleFilter['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="toolbar-actions justify-end">
                <a href="{{ route('users.index') }}" class="btn-clear" data-async-link="true"><x-icon name="clear" size="h-4 w-4" /><span>Notīrīt</span></a>
                </div>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Loma', 'value' => $filters['has_role_filter'] ? collect($filters['roles'])->map(fn ($role) => $roleLabels[$role] ?? $role)->implode(', ') : null],
                ['label' => 'Statuss', 'value' => $filters['is_active'] === '1' ? 'Aktīvs' : ($filters['is_active'] === '0' ? 'Neaktīvs' : null)],
                ['label' => 'Pēdējā pieslēgšanās', 'value' => $filters['last_login'] === 'today' ? 'Šodien' : ($filters['last_login'] === 'recent' ? 'Pēdējās 7 dienas' : ($filters['last_login'] === 'never' ? 'Nav pieslēdzies' : null))],
            ]"
            :clear-url="route('users.index')"
        />

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="app-table-shell">
            <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="app-table-content app-table-content-compact min-w-full text-sm">
                <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Vārds</th>
                        <th class="px-4 py-3">E-pasts</th>
                        <th class="px-4 py-3">Tālrunis</th>
                        <th class="px-4 py-3">Loma</th>
                        <th class="px-4 py-3">Amats</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Pēdējā pieslēgšanās</th>
                        <th class="px-4 py-3">Darbības</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $managedUser)
                        <tr class="app-table-row border-t border-slate-100 {{ $managedUser->role === 'admin' ? 'app-table-row-accent-violet' : 'app-table-row-accent-sky' }}" data-table-search-value="{{ \Illuminate\Support\Str::lower(trim((string) $managedUser->full_name)) }}">
                            <td class="px-4 py-3">
                                <div class="app-table-cell-strong">{{ $managedUser->full_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->phone ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <x-status-pill context="user-role" :value="$managedUser->role" :label="$roleLabels[$managedUser->role] ?? null" />
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->job_title ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <x-status-pill context="user-active" :value="$managedUser->is_active" />
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->last_login?->format('d.m.Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                    <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                        <span>Darbības</span>
                                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div class="table-action-list" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                        <a href="{{ route('users.edit', $managedUser) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                            <x-icon name="edit" size="h-4 w-4" />
                                            <span>Rediģēt</span>
                                        </a>

                                        <a
                                            href="{{ route('devices.index', ['assigned_to_id' => $managedUser->id, 'assigned_to_query' => $managedUser->full_name]) }}"
                                            class="table-action-item"
                                            @click="open = false"
                                        >
                                            <x-icon name="device" size="h-4 w-4" />
                                            <span>Apskatīt piesaistītās ierīces</span>
                                        </a>

                                        <form method="POST" action="{{ route('users.destroy', $managedUser) }}" onsubmit="return confirm('Dzēst šo lietotāju?')" class="contents">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-button table-action-button-rose" @disabled(auth()->id() === $managedUser->id)">
                                                <x-icon name="trash" size="h-4 w-4" />
                                                <span>Dzēst</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Lietotāji vēl nav pievienoti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{ $users->links() }}
        </div>
    </section>
</x-app-layout>
