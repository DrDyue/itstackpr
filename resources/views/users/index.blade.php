{{--
    Lapa: Lietotāju saraksts.
    Atbildība: rāda sistēmas lietotājus, viņu lomas, statusus un pēdējo pieslēgšanos.
    Datu avots: UserController@index.
--}}
<x-app-layout>
    @php
        $roleFilterLinks = [
            ['label' => 'Admins', 'value' => 'admin', 'icon' => 'users', 'tone' => 'violet'],
            ['label' => 'Darbinieki', 'value' => 'user', 'icon' => 'profile', 'tone' => 'sky'],
        ];
        $selectedRoles = $filters['has_role_filter'] ? $filters['roles'] : collect($roleFilterLinks)->pluck('value')->all();
        $lastLoginOptions = [
            ['value' => 'today', 'label' => 'Šodien', 'description' => 'Pieslēdzās šodien', 'search' => 'Šodien pēdējā pieslēgšanās'],
            ['value' => 'recent', 'label' => 'Pēdējās 7 dienas', 'description' => 'Aktīvi pēdējā nedēļā', 'search' => 'Pēdējās 7 dienas nesen'],
            ['value' => 'never', 'label' => 'Nav pieslēdzies', 'description' => 'Lietotājs vēl nav pieslēdzies', 'search' => 'Nav pieslēdzies nekad'],
        ];
        $selectedLastLoginLabel = collect($lastLoginOptions)->firstWhere('value', $filters['last_login'])['label'] ?? null;
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $sortableHeaders = [
            'full_name' => ['label' => 'Vārds un uzvārds', 'class' => 'table-col-person'],
            'email' => ['label' => 'E-pasts', 'class' => 'table-col-email'],
            'phone' => ['label' => 'Tālrunis', 'class' => 'table-col-phone'],
            'role' => ['label' => 'Loma', 'class' => 'table-col-role'],
            'job_title' => ['label' => 'Amats', 'class' => 'table-col-person'],
            'is_active' => ['label' => 'Statuss', 'class' => 'table-col-status'],
            'last_login' => ['label' => 'Pēdējā pieslēgšanās', 'class' => 'table-col-date'],
        ];
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="users" size="h-4 w-4" />
                            <span>Lietotāji</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="users" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopā</span>
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
                        <div class="page-title-icon page-title-icon-violet">
                            <x-icon name="users" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Lietotāji</h1>
                            <p class="page-subtitle">Pārvaldi sistēmas lietotājus, lomas un piekļuves statusus.</p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('users.create') }}" class="btn-create">
                    <x-icon name="plus" size="h-4 w-4" />
                    <span>Jauns lietotājs</span>
                </a>
            </div>
        </div>

        <div id="users-index-root" data-async-table-root>
            <form
                method="GET"
                action="{{ route('users.index') }}"
                class="devices-filter-surface devices-filter-surface-elevated"
                data-async-table-form
                data-async-root="#users-index-root"
                data-search-endpoint="{{ route('users.find-by-name') }}"
            >
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <div class="devices-filter-header">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Meklēšana</span>
                        </h3>
                        <div class="devices-filters-grid">
                            <div class="devices-search-group">
                                <label class="devices-search-label">
                                    <span>Meklēt pēc vārda un uzvārda</span>
                                    <input
                                        type="text"
                                        name="search"
                                        value="{{ $filters['search'] }}"
                                        class="devices-code-input"
                                        placeholder="Ievadi vārdu un uzvārdu"
                                        data-async-manual="true"
                                        data-table-manual-search="true"
                                        data-search-mode="contains"
                                    >
                                </label>
                                <button type="submit" class="devices-code-search-btn" data-table-search-submit="true">
                                    <x-icon name="search" size="h-4 w-4" />
                                    <span>Atrast lietotāju</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="devices-filter-divider"></div>

                <div class="devices-filter-header">
                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="filter" size="h-4 w-4" />
                            <span>Filtri</span>
                        </h3>
                        <div class="devices-filters-grid">
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

                            <label class="block">
                                <span class="crud-label">Amats</span>
                                <input
                                    type="text"
                                    name="job_title_query"
                                    value="{{ $filters['job_title_query'] ?? '' }}"
                                    class="crud-control"
                                    placeholder="Filtrēt pēc amata"
                                >
                            </label>

                            <label class="block">
                                <span class="crud-label">E-pasts</span>
                                <input
                                    type="text"
                                    name="email_query"
                                    value="{{ $filters['email_query'] ?? '' }}"
                                    class="crud-control"
                                    placeholder="Filtrēt pēc e-pasta"
                                >
                            </label>
                        </div>
                    </div>
                </div>

                <div class="filter-toolbar-footer">
                    <div class="quick-filter-groups">
                        <div class="quick-filter-group">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Lietotāja statuss</div>
                            <div class="quick-status-filters" x-data="{ value: @js($filters['is_active']) }">
                                <input type="hidden" name="is_active" :value="value">
                                <button type="button" class="quick-status-filter quick-status-filter-emerald" :class="value === '1' ? 'quick-status-filter-active' : ''" @click="value = '1'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="check-circle" size="h-4 w-4" />
                                    <span>Aktīvi</span>
                                </button>
                                <button type="button" class="quick-status-filter quick-status-filter-slate" :class="value === '' ? 'quick-status-filter-active' : ''" @click="value = ''; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="filter" size="h-4 w-4" />
                                    <span>Visi</span>
                                </button>
                                <button type="button" class="quick-status-filter quick-status-filter-rose" :class="value === '0' ? 'quick-status-filter-active' : ''" @click="value = '0'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>Neaktīvi</span>
                                </button>
                            </div>
                        </div>

                        <div class="quick-filter-group">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Loma</div>
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
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('users.index') }}" class="btn-clear" data-async-link="true">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>Notīrīt filtrus</span>
                        </a>
                    </div>
                </div>
            </form>

            <div class="mt-4">
            <x-active-filters
                :items="[
                    ['label' => 'Vārds', 'value' => $filters['search']],
                    ['label' => 'Amats', 'value' => $filters['job_title_query']],
                    ['label' => 'E-pasts', 'value' => $filters['email_query']],
                    ['label' => 'Loma', 'value' => $filters['has_role_filter'] ? collect($filters['roles'])->map(fn ($role) => $roleLabels[$role] ?? $role)->implode(', ') : null],
                    ['label' => 'Statuss', 'value' => $filters['is_active'] === '1' ? 'Aktīvs' : ($filters['is_active'] === '0' ? 'Neaktīvs' : null)],
                    ['label' => 'Pēdējā pieslēgšanās', 'value' => $filters['last_login'] === 'today' ? 'Šodien' : ($filters['last_login'] === 'recent' ? 'Pēdējās 7 dienas' : ($filters['last_login'] === 'never' ? 'Nav pieslēdzies' : null))],
                ]"
                :clear-url="route('users.index')"
            />
            </div>

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            <div class="app-table-shell mt-4">
                <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <table class="app-table-content app-table-content-users min-w-full text-sm">
                        <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                            <tr>
                                @foreach ($sortableHeaders as $column => $header)
                                    @php
                                        $isCurrentSort = $sorting['sort'] === $column;
                                        $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
                                        $sortMessage = 'Kārtots pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($header['label'])) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                                    @endphp
                                    <th class="{{ $header['class'] }} px-4 py-3">
                                        <button
                                            type="button"
                                            class="device-sort-trigger {{ $isCurrentSort ? 'device-sort-trigger-active' : '' }}"
                                            data-sort-trigger="true"
                                            data-sort-field="{{ $column }}"
                                            data-sort-direction="{{ $nextDirection }}"
                                            data-sort-toast="{{ $sortMessage }}"
                                        >
                                            <span>{{ $header['label'] }}</span>
                                            <span class="device-sort-icon" aria-hidden="true">
                                                <svg class="h-[1.05em] w-[1.05em]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 9 3.75-3.75L15.75 9" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15-3.75 3.75L8.25 15" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>
                                @endforeach
                                <th class="table-col-actions px-4 py-3">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $managedUser)
                                @php
                                    $assignedDevicesUrl = route('devices.index', ['assigned_to_id' => $managedUser->id, 'assigned_to_query' => $managedUser->full_name]);
                                @endphp
                                <tr class="app-table-row border-t border-slate-100 align-top {{ $managedUser->role === 'admin' ? 'app-table-row-accent-violet' : 'app-table-row-accent-sky' }}" data-table-row-id="user-{{ $managedUser->id }}" data-table-search-value="{{ \Illuminate\Support\Str::lower(trim((string) $managedUser->full_name)) }}">
                                    <td class="px-4 py-4">
                                        <div class="app-table-cell-strong">{{ $managedUser->full_name }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">{{ $managedUser->email }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $managedUser->phone ?: '-' }}</td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="user-role" :value="$managedUser->role" :label="$roleLabels[$managedUser->role] ?? null" />
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">{{ $managedUser->job_title ?: '-' }}</td>
                                    <td class="px-4 py-4">
                                        <x-status-pill context="user-active" :value="$managedUser->is_active" />
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        <div class="font-semibold text-slate-900">{{ $managedUser->last_login?->format('d.m.Y H:i') ?: 'Nav pieslēdzies' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $managedUser->last_login ? $managedUser->last_login->diffForHumans() : 'Pirmā pieslēgšanās vēl nav notikusi' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="table-action-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                                            <button type="button" class="table-action-summary" @click="open = ! open" :aria-expanded="open.toString()">
                                                <span>Darbības</span>
                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            <div class="table-action-list" x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false">
                                                <a href="{{ route('users.show', $managedUser) }}" class="table-action-item" @click="open = false">
                                                    <x-icon name="view" size="h-4 w-4" />
                                                    <span>Profils</span>
                                                </a>

                                                <a href="{{ route('users.edit', $managedUser) }}" class="table-action-item table-action-item-amber" @click="open = false">
                                                    <x-icon name="edit" size="h-4 w-4" />
                                                    <span>Rediģēt</span>
                                                </a>

                                                <a href="{{ $assignedDevicesUrl }}" class="table-action-item" @click="open = false">
                                                    <x-icon name="device" size="h-4 w-4" />
                                                    <span>Apskatīt piesaistītās ierīces</span>
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="{{ route('users.destroy', $managedUser) }}"
                                                    class="contents"
                                                    data-app-confirm-title="Dzēst lietotāju?"
                                                    data-app-confirm-message="Vai tiešām dzēst šo lietotāju?"
                                                    data-app-confirm-accept="Jā, dzēst"
                                                    data-app-confirm-cancel="Nē"
                                                    data-app-confirm-tone="danger"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="{{ auth()->id() === $managedUser->id ? 'button' : 'submit' }}"
                                                        class="{{ auth()->id() === $managedUser->id ? 'btn-disabled' : 'table-action-button table-action-button-rose' }}"
                                                        @if (auth()->id() === $managedUser->id)
                                                            data-app-toast-title="Dzēšana nav pieejama"
                                                            data-app-toast-message="Paša lietotāja kontu no šīs tabulas dzēst nevar. Izmanto citu administratora kontu, ja šo profilu tiešām vajag noņemt."
                                                            data-app-toast-tone="info"
                                                        @endif
                                                    >
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
                                    <td colspan="8" class="px-4 py-6">
                                        <x-empty-state
                                            compact
                                            icon="users"
                                            title="Lietotāji vēl nav pievienoti"
                                            description="Kad sistēmā būs izveidoti lietotāji, tie parādīsies šajā tabulā."
                                        />
                                    </td>
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
