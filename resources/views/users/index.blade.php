{{--
    Lapa: LietotĆ„Āju saraksts.
    AtbildĆ„Ā«ba: rĆ„Āda sistĆ„ā€mas lietotĆ„Ājus, viĆ…ā€ u lomas, statusus un pĆ„ā€dĆ„ā€jo pieslĆ„ā€gĆ…ļ£¼anos.
    Datu avots: UserController@index.
--}}
<x-app-layout>
    @php
        $roleFilterLinks = [
            ['label' => 'Admins', 'value' => 'admin', 'icon' => 'users', 'tone' => 'violet'],
            ['label' => 'Darbinieki', 'value' => 'user', 'icon' => 'profile', 'tone' => 'sky'],
        ];
        $selectedRoles = $filters['has_role_filter'] ? $filters['roles'] : [];
        $lastLoginOptions = [
            ['value' => 'today', 'label' => 'Ć…Ā odien', 'description' => 'PieslĆ„ā€dzĆ„Ās Ć…ļ£¼odien', 'search' => 'Ć…Ā odien pĆ„ā€dĆ„ā€jĆ„Ā pieslĆ„ā€gĆ…ļ£¼anĆ„Ās'],
            ['value' => 'recent', 'label' => 'PĆ„ā€dĆ„ā€jĆ„Ās 7 dienas', 'description' => 'AktĆ„Ā«vi pĆ„ā€dĆ„ā€jĆ„Ā nedĆ„ā€Ć„Ā¼Ć„Ā', 'search' => 'PĆ„ā€dĆ„ā€jĆ„Ās 7 dienas nesen'],
            ['value' => 'never', 'label' => 'Nav pieslĆ„ā€dzies', 'description' => 'LietotĆ„Ājs vĆ„ā€l nav pieslĆ„ā€dzies', 'search' => 'Nav pieslĆ„ā€dzies nekad'],
        ];
        $selectedLastLoginLabel = collect($lastLoginOptions)->firstWhere('value', $filters['last_login'])['label'] ?? null;
        $sortDirectionLabels = ['asc' => 'augoĆ…ļ£¼ajĆ„Ā secĆ„Ā«bĆ„Ā', 'desc' => 'dilstoĆ…ļ£¼ajĆ„Ā secĆ„Ā«bĆ„Ā'];
        $sortableHeaders = [
            'full_name' => ['label' => 'VĆ„Ārds un uzvĆ„Ārds', 'class' => 'table-col-person'],
            'email' => ['label' => 'E-pasts', 'class' => 'table-col-email'],
            'phone' => ['label' => 'TĆ„Ālrunis', 'class' => 'table-col-phone'],
            'role' => ['label' => 'Loma', 'class' => 'table-col-role'],
            'job_title' => ['label' => 'Amats', 'class' => 'table-col-person'],
            'is_active' => ['label' => 'Statuss', 'class' => 'table-col-status'],
            'last_login' => ['label' => 'PĆ„ā€dĆ„ā€jĆ„Ā pieslĆ„ā€gĆ…ļ£¼anĆ„Ās', 'class' => 'table-col-date'],
        ];
        $currentUserId = (int) auth()->id();
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="users" size="h-4 w-4" />
                            <span>LietotĆ„Āji</span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet">
                            <x-icon name="users" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">LietotĆ„Āji</h1>
                            <p class="page-subtitle">PĆ„Ārvaldi sistĆ„ā€mas lietotĆ„Ājus, lomas un piekĆ„Ā¼uves statusus.</p>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-create" x-data @click="$dispatch('open-modal', 'user-create-modal')">
                    <x-icon name="plus" size="h-4 w-4" />
                    <span>Jauns lietotĆ„Ājs</span>
                </button>
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
                            <span>MeklĆ„ā€Ć…ļ£¼ana</span>
                        </h3>
                        <div class="devices-search-group">
                            <label class="devices-search-label">
                                <span>MeklĆ„ā€t pĆ„ā€c vĆ„Ārda un uzvĆ„Ārda</span>
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $filters['search'] }}"
                                    class="devices-code-input"
                                    placeholder="Ievadi vĆ„Ārdu un uzvĆ„Ārdu"
                                    data-async-manual="true"
                                    data-table-manual-search="true"
                                    data-search-mode="contains"
                                >
                            </label>
                            <button type="button" class="devices-code-search-btn" data-table-search-submit="true" onclick="return window.runManualTableSearchFromTrigger(this);">
                                <x-icon name="search" size="h-4 w-4" />
                                <span>Atrast lietotĆ„Āju</span>
                            </button>
                        </div>
                    </div>

                    <div class="devices-filter-divider-vertical"></div>

                    <div class="devices-filter-section">
                        <h3 class="devices-filter-title">
                            <x-icon name="filter" size="h-4 w-4" />
                            <span>Filtri</span>
                        </h3>
                        <div class="users-filters-grid">
                            <label class="block">
                                <span class="crud-label">PĆ„ā€dĆ„ā€jĆ„Ā pieslĆ„ā€gĆ…ļ£¼anĆ„Ās</span>
                                <x-searchable-select
                                    name="last_login"
                                    query-name="last_login_query"
                                    identifier="user-last-login-filter"
                                    :options="$lastLoginOptions"
                                    :selected="$filters['last_login']"
                                    :query="$selectedLastLoginLabel"
                                    placeholder="IzvĆ„ā€lies periodu"
                                    empty-message="Neviens periods neatbilst meklĆ„ā€jumam."
                                />
                            </label>

                            <label class="block">
                                <span class="crud-label">Amats</span>
                                <input
                                    type="text"
                                    name="job_title_query"
                                    value="{{ $filters['job_title_query'] ?? '' }}"
                                    class="crud-control"
                                    placeholder="FiltrĆ„ā€t pĆ„ā€c amata"
                                >
                            </label>

                            <label class="block">
                                <span class="crud-label">E-pasts</span>
                                <input
                                    type="text"
                                    name="email_query"
                                    value="{{ $filters['email_query'] ?? '' }}"
                                    class="crud-control"
                                    placeholder="FiltrĆ„ā€t pĆ„ā€c e-pasta"
                                >
                            </label>
                        </div>
                    </div>
                </div>

                <div class="filter-toolbar-footer">
                    <div class="quick-filter-groups">
                        <div class="quick-filter-group">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">LietotĆ„Āja statuss</div>
                            <div class="quick-status-filters" x-data="{ value: @js($filters['is_active']) }">
                                <input type="hidden" name="is_active" :value="value">
                                <button type="button" class="quick-status-filter quick-status-filter-emerald" :class="value === '1' ? 'quick-status-filter-active' : ''" @click="value = value === '1' ? '' : '1'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="check-circle" size="h-4 w-4" />
                                    <span>AktĆ„Ā«vi</span>
                                    <span class="quick-filter-count">{{ $userSummary['active'] }}</span>
                                </button>
                                <button type="button" class="quick-status-filter quick-status-filter-rose" :class="value === '0' ? 'quick-status-filter-active' : ''" @click="value = value === '0' ? '' : '0'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>NeaktĆ„Ā«vi</span>
                                    <span class="quick-filter-count">{{ $userSummary['inactive'] }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="quick-filter-group">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">DroĆ…ļ£¼Ć„Ā«ba</div>
                            <div class="quick-status-filters" x-data="{ value: @js($filters['password_reset']) }">
                                <input type="hidden" name="password_reset" :value="value">
                                <button type="button" class="quick-status-filter quick-status-filter-amber" :class="value === '1' ? 'quick-status-filter-active' : ''" @click="value = value === '1' ? '' : '1'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="key" size="h-4 w-4" />
                                    <span>Paroles pieprasĆ„Ā«jumi</span>
                                    <span class="quick-filter-count">{{ $userSummary['password_reset'] }}</span>
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
                                        <span class="quick-filter-count">{{ $userSummary[$roleFilter['value']] ?? 0 }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-actions">
                        <a href="{{ route('users.index') }}" class="btn-clear" data-async-link="true">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>NotĆ„Ā«rĆ„Ā«t filtrus</span>
                        </a>
                    </div>
                </div>
            </form>

            <div class="mt-4">
            <x-active-filters
                :items="[
                    ['label' => 'VĆ„Ārds', 'value' => $filters['search']],
                    ['label' => 'Amats', 'value' => $filters['job_title_query']],
                    ['label' => 'E-pasts', 'value' => $filters['email_query']],
                    ['label' => 'Loma', 'value' => $filters['has_role_filter'] ? collect($filters['roles'])->map(fn ($role) => $roleLabels[$role] ?? $role)->implode(', ') : null],
                    ['label' => 'Statuss', 'value' => $filters['is_active'] === '1' ? 'AktĆ„Ā«vs' : ($filters['is_active'] === '0' ? 'NeaktĆ„Ā«vs' : null)],
                    ['label' => 'PĆ„ā€dĆ„ā€jĆ„Ā pieslĆ„ā€gĆ…ļ£¼anĆ„Ās', 'value' => $filters['last_login'] === 'today' ? 'Ć…Ā odien' : ($filters['last_login'] === 'recent' ? 'PĆ„ā€dĆ„ā€jĆ„Ās 7 dienas' : ($filters['last_login'] === 'never' ? 'Nav pieslĆ„ā€dzies' : null))],
                    ['label' => 'Paroles pieprasĆ„Ā«jums', 'value' => $filters['password_reset'] === '1' ? 'Gaida administratoru' : null],
                ]"
                :clear-url="route('users.index')"
            />
            </div>

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            <div class="app-table-shell mt-4">
                <div class="app-table-scroll users-table-scroll table-scroll-overlay-frame rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <div class="table-scroll-viewport">
                    <table class="app-table-content app-table-content-users min-w-full text-sm">
                        <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                            <tr>
                                @foreach ($sortableHeaders as $column => $header)
                                    @php
                                        $isCurrentSort = $sorting['sort'] === $column;
                                        $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
                                        $sortMessage = 'KĆ„Ārtots pĆ„ā€c ' . ($sortOptions[$column]['label'] ?? mb_strtolower($header['label'])) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
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
                                <th class="table-col-status px-4 py-3">PiesaistĆ„Ā«tĆ„Ās ierĆ„Ā«ces</th>
                                <th class="table-col-actions px-4 py-3">DarbĆ„Ā«bas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $managedUser)
                                @php
                                    $assignedDevicesUrl = route('devices.index', ['assigned_to_id' => $managedUser->id, 'assigned_to_query' => $managedUser->full_name]);
                                    $isCurrentUser = $currentUserId === (int) $managedUser->id;
                                    $hasAssignedDevices = (int) ($managedUser->assigned_devices_count ?? 0) > 0;
                                    $editUrl = $isCurrentUser
                                        ? route('profile.edit', ['profile_modal' => 'edit'])
                                        : route('users.index', ['user_modal' => 'edit', 'modal_user' => $managedUser->id]);
                                @endphp
                                <tr id="user-{{ $managedUser->id }}" class="request-notification-target app-table-row border-t border-slate-100 align-top {{ $managedUser->password_reset_requested_at ? 'app-table-row-password-request' : ($managedUser->role === 'admin' ? 'app-table-row-accent-violet' : 'app-table-row-accent-sky') }}" data-table-row-id="user-{{ $managedUser->id }}" data-table-search-value="{{ \Illuminate\Support\Str::lower(trim((string) $managedUser->full_name)) }}" data-table-search-highlight-style="{{ $managedUser->password_reset_requested_at ? 'outline' : 'background' }}">
                                    <td class="px-4 py-4">
                                        <div class="app-table-cell-strong">{{ $managedUser->full_name }}</div>
                                        @if ($isCurrentUser)
                                            <div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800">
                                                <x-icon name="profile" size="h-3.5 w-3.5" />
                                                <span>JĆ…Ā«su ieraksts</span>
                                            </div>
                                        @endif
                                        @if ($managedUser->password_reset_requested_at)
                                            <div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                <x-icon name="key" size="h-3.5 w-3.5" />
                                                <span>PieprasĆ„Ā«ta paroles maiĆ…ā€ a</span>
                                            </div>
                                        @endif
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
                                        @php($effectiveLastLogin = $managedUser->effective_last_login ?? $managedUser->last_login)
                                        <div class="font-semibold text-slate-900">{{ $effectiveLastLogin?->format('d.m.Y H:i') ?: 'Nav pieslĆ„ā€dzies' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $effectiveLastLogin ? $effectiveLastLogin->diffForHumans() : 'PirmĆ„Ā pieslĆ„ā€gĆ…ļ£¼anĆ„Ās vĆ„ā€l nav notikusi' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($hasAssignedDevices)
                                            <a
                                                href="{{ $assignedDevicesUrl }}"
                                                class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 transition hover:bg-sky-100"
                                            >
                                                {{ $managedUser->assigned_devices_count }} ierĆ„Ā«ces
                                            </a>
                                        @else
                                            <span class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500">
                                                0 ierĆ„Ā«ces
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div
                                            class="table-action-menu"
                                            x-data="createFloatingDropdown({ zIndex: 400 })"
                                            @keydown.escape.window="closePanel()"
                                            @resize.window="if (open) updatePosition()"
                                            @scroll.window="if (open) updatePosition()"
                                        >
                                            <button
                                                type="button"
                                                class="table-action-summary"
                                                x-ref="trigger"
                                                @click="togglePanel()"
                                                :aria-expanded="open.toString()"
                                            >
                                                <span>DarbĆ„Ā«bas</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            <template x-teleport="body">
                                                <div
                                                    class="table-action-list users-table-action-list"
                                                    data-floating-menu="manual"
                                                    x-ref="panel"
                                                    x-cloak
                                                    x-show="open"
                                                    x-transition.origin.top.right
                                                    x-bind:style="panelStyle"
                                                    @click.outside="closePanel()"
                                                >
                                                <div class="table-action-header">
                                                    <div class="table-action-header-title">DarbĆ„Ā«bas</div>
                                                </div>

                                                <div class="table-action-section">
                                                    <div class="table-action-section-title">PĆ„Ārskats</div>
                                                    <a href="{{ route('users.show', $managedUser) }}" class="table-action-item table-action-item-primary" @click="closePanel()">
                                                        <x-icon name="view" size="h-4 w-4" />
                                                        <span>Profils</span>
                                                    </a>
                                                </div>

                                                <div class="table-action-divider"></div>

                                                <div class="table-action-section">
                                                    <div class="table-action-section-title">PĆ„ĀrvaldĆ„Ā«ba</div>
                                                    <a href="{{ $editUrl }}" class="table-action-item table-action-item-amber" @click="closePanel()" @if (! $isCurrentUser) data-async-link="true" @endif>
                                                        <x-icon name="edit" size="h-4 w-4" />
                                                        <span>{{ $isCurrentUser ? 'RediĆ„Ā£Ć„ā€t profilu' : 'RediĆ„Ā£Ć„ā€t' }}</span>
                                                    </a>

                                                    @if ($managedUser->password_reset_requested_at)
                                                        <a href="{{ $editUrl }}" class="table-action-item table-action-item-violet" @click="closePanel()" @if (! $isCurrentUser) data-async-link="true" @endif>
                                                            <x-icon name="key" size="h-4 w-4" />
                                                            <span>MainĆ„Ā«t paroli</span>
                                                        </a>
                                                    @endif

                                                    <a href="{{ $assignedDevicesUrl }}" class="table-action-item" @click="closePanel()">
                                                        <x-icon name="device" size="h-4 w-4" />
                                                        <span>PiesaistĆ„Ā«tĆ„Ās ierĆ„Ā«ces</span>
                                                    </a>
                                                </div>

                                                <div class="table-action-divider"></div>

                                                <div class="table-action-section">
                                                    @if ($isCurrentUser || $hasAssignedDevices)
                                                        <button
                                                            type="button"
                                                            class="table-action-item table-action-item-rose opacity-50 cursor-not-allowed"
                                                            data-app-toast-title="DzĆ„ā€Ć…ļ£¼ana nav pieejama"
                                                            data-app-toast-message="{{ $isCurrentUser ? 'PaĆ…ļ£¼a lietotĆ„Āja kontu no Ć…ļ£¼Ć„Ā«s tabulas dzĆ„ā€st nevar. Izmanto citu administratora kontu, ja Ć…ļ£¼o profilu tieĆ…ļ£¼Ć„Ām vajag noĆ…ā€ emt.' : 'LietotĆ„Ājam ir piesaistĆ„Ā«tas ierĆ„Ā«ces. Vispirms pĆ„Ārvieto vai atsaisti tĆ„Ās.' }}"
                                                            data-app-toast-tone="info"
                                                            @click="closePanel()" onclick="event.preventDefault(); window.dispatchAppToast({ title: this.dataset.appToastTitle, message: this.dataset.appToastMessage, tone: this.dataset.appToastTone })"
                                                        >
                                                            <x-icon name="trash" size="h-4 w-4" />
                                                            <span>DzĆ„ā€st</span>
                                                        </button>
                                                    @else
                                                        <x-post-action-button
                                                            :action="route('users.destroy', $managedUser)"
                                                            method="DELETE"
                                                            form-class="table-action-form"
                                                            button-class="table-action-item table-action-item-rose"
                                                            data-app-confirm-title="DzĆ„ā€st lietotĆ„Āju?"
                                                            data-app-confirm-message="Vai tieĆ…ļ£¼Ć„Ām dzĆ„ā€st Ć…ļ£¼o lietotĆ„Āju?"
                                                            data-app-confirm-accept="JĆ„Ā, dzĆ„ā€st"
                                                            data-app-confirm-cancel="NĆ„ā€"
                                                            data-app-confirm-tone="danger"
                                                        >
                                                            <x-icon name="trash" size="h-4 w-4" />
                                                            <span>DzĆ„ā€st</span>
                                                        </x-post-action-button>
                                                    @endif
                                                </div>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6">
                                        <x-empty-state
                                            compact
                                            icon="users"
                                            title="LietotĆ„Āji vĆ„ā€l nav pievienoti"
                                            description="Kad sistĆ„ā€mĆ„Ā bĆ…Ā«s izveidoti lietotĆ„Āji, tie parĆ„ĀdĆ„Ā«sies Ć…ļ£¼ajĆ„Ā tabulĆ„Ā."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            {{ $users->links() }}
        </div>

        @include('users.partials.modal-form', [
            'mode' => 'create',
            'modalName' => 'user-create-modal',
            'user' => null,
            'roles' => $roles,
            'roleLabels' => $roleLabels,
        ])

        @foreach ($users as $managedUser)
            @include('users.partials.modal-form', [
                'mode' => 'edit',
                'modalName' => 'user-edit-modal-' . $managedUser->id,
                'user' => $managedUser,
                'roles' => $roles,
                'roleLabels' => $roleLabels,
            ])
        @endforeach

        @if (($selectedModalUser?->id ?? null) && ! $users->getCollection()->contains('id', $selectedModalUser->id))
            @include('users.partials.modal-form', [
                'mode' => 'edit',
                'modalName' => 'user-edit-modal-' . $selectedModalUser->id,
                'user' => $selectedModalUser,
                'roles' => $roles,
                'roleLabels' => $roleLabels,
            ])
        @endif

        @if (old('modal_form') === 'user_create')
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-create-modal' })));</script>
        @elseif (str_starts_with((string) old('modal_form'), 'user_edit_'))
            @php($userModalTarget = str_replace('user_edit_', '', (string) old('modal_form')))
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-edit-modal-{{ $userModalTarget }}' })));</script>
        @elseif (request()->query('user_modal') === 'create')
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-create-modal' })));</script>
        @elseif (request()->query('user_modal') === 'edit' && request()->query('modal_user'))
            <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-edit-modal-{{ request()->query('modal_user') }}' })));</script>
        @endif
    </section>
</x-app-layout>

