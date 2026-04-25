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
        $selectedRoles = $filters['has_role_filter'] ? $filters['roles'] : [];
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
        $currentUserId = (int) auth()->id();
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

                <button type="button" class="btn-create" x-data @click="$dispatch('open-modal', 'user-create-modal')">
                    <x-icon name="plus" size="h-4 w-4" />
                    <span>Jauns lietotājs</span>
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
                            <span>Meklēšana</span>
                        </h3>
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
                            <button type="button" class="devices-code-search-btn" data-table-search-submit="true" onclick="return window.runManualTableSearchFromTrigger(this);">
                                <x-icon name="search" size="h-4 w-4" />
                                <span>Atrast lietotāju</span>
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
                                <button type="button" class="quick-status-filter quick-status-filter-emerald" :class="value === '1' ? 'quick-status-filter-active' : ''" @click="value = value === '1' ? '' : '1'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="check-circle" size="h-4 w-4" />
                                    <span>Aktīvi</span>
                                    <span class="quick-filter-count">{{ $userSummary['active'] }}</span>
                                </button>
                                <button type="button" class="quick-status-filter quick-status-filter-rose" :class="value === '0' ? 'quick-status-filter-active' : ''" @click="value = value === '0' ? '' : '0'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="x-circle" size="h-4 w-4" />
                                    <span>Neaktīvi</span>
                                    <span class="quick-filter-count">{{ $userSummary['inactive'] }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="quick-filter-group">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Drošība</div>
                            <div class="quick-status-filters" x-data="{ value: @js($filters['password_reset']) }">
                                <input type="hidden" name="password_reset" :value="value">
                                <button type="button" class="quick-status-filter quick-status-filter-amber" :class="value === '1' ? 'quick-status-filter-active' : ''" @click="value = value === '1' ? '' : '1'; $nextTick(() => $el.closest('form').requestSubmit())">
                                    <x-icon name="key" size="h-4 w-4" />
                                    <span>Paroles pieprasījumi</span>
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
                    ['label' => 'Paroles pieprasījums', 'value' => $filters['password_reset'] === '1' ? 'Gaida administratoru' : null],
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
                                <th class="table-col-status px-4 py-3">Piesaistītās ierīces</th>
                                <th class="table-col-actions px-4 py-3">Darbības</th>
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
                                                <span>Jūsu ieraksts</span>
                                            </div>
                                        @endif
                                        @if ($managedUser->password_reset_requested_at)
                                            <div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                <x-icon name="key" size="h-3.5 w-3.5" />
                                                <span>Pieprasīta paroles maiņa</span>
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
                                        <div class="font-semibold text-slate-900">{{ $effectiveLastLogin?->format('d.m.Y H:i') ?: 'Nav pieslēdzies' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $effectiveLastLogin ? $effectiveLastLogin->diffForHumans() : 'Pirmā pieslēgšanās vēl nav notikusi' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($hasAssignedDevices)
                                            <a
                                                href="{{ $assignedDevicesUrl }}"
                                                class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 transition hover:bg-sky-100"
                                            >
                                                {{ $managedUser->assigned_devices_count }} ierīces
                                            </a>
                                        @else
                                            <span class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500">
                                                0 ierīces
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
                                                <span>Darbības</span>
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
                                                    <div class="table-action-header-title">Darbības</div>
                                                </div>

                                                <div class="table-action-section">
                                                    <div class="table-action-section-title">Pārskats</div>
                                                    <a href="{{ route('users.show', $managedUser) }}" class="table-action-item table-action-item-primary" @click="closePanel()">
                                                        <x-icon name="view" size="h-4 w-4" />
                                                        <span>Profils</span>
                                                    </a>
                                                </div>

                                                <div class="table-action-divider"></div>

                                                <div class="table-action-section">
                                                    <div class="table-action-section-title">Pārvaldība</div>
                                                    <a href="{{ $editUrl }}" class="table-action-item table-action-item-amber" @click="closePanel()" @if (! $isCurrentUser) data-async-link="true" @endif>
                                                        <x-icon name="edit" size="h-4 w-4" />
                                                        <span>{{ $isCurrentUser ? 'Rediģēt profilu' : 'Rediģēt' }}</span>
                                                    </a>

                                                    @if ($managedUser->password_reset_requested_at)
                                                        <a href="{{ $editUrl }}" class="table-action-item table-action-item-violet" @click="closePanel()" @if (! $isCurrentUser) data-async-link="true" @endif>
                                                            <x-icon name="key" size="h-4 w-4" />
                                                            <span>Mainīt paroli</span>
                                                        </a>
                                                    @endif

                                                    <a href="{{ $assignedDevicesUrl }}" class="table-action-item" @click="closePanel()">
                                                        <x-icon name="device" size="h-4 w-4" />
                                                        <span>Piesaistītās ierīces</span>
                                                    </a>
                                                </div>

                                                <div class="table-action-divider"></div>

                                                <div class="table-action-section">
                                                    @if ($isCurrentUser || $hasAssignedDevices)
                                                        <button
                                                            type="button"
                                                            class="table-action-item table-action-item-rose opacity-50 cursor-not-allowed"
                                                            data-app-toast-title="Dzēšana nav pieejama"
                                                            data-app-toast-message="{{ $isCurrentUser ? 'Paša lietotāja kontu no šīs tabulas dzēst nevar. Izmanto citu administratora kontu, ja šo profilu tiešām vajag noņemt.' : 'Lietotājam ir piesaistītas ierīces. Vispirms pārvieto vai atsaisti tās.' }}"
                                                            data-app-toast-tone="info"
                                                            @click="closePanel()" onclick="event.preventDefault(); window.dispatchAppToast({ title: this.dataset.appToastTitle, message: this.dataset.appToastMessage, tone: this.dataset.appToastTone })"
                                                        >
                                                            <x-icon name="trash" size="h-4 w-4" />
                                                            <span>Dzēst</span>
                                                        </button>
                                                    @else
                                                        <x-post-action-button
                                                            :action="route('users.destroy', $managedUser)"
                                                            method="DELETE"
                                                            form-class="table-action-form"
                                                            button-class="table-action-item table-action-item-rose"
                                                            data-app-confirm-title="Dzēst lietotāju?"
                                                            data-app-confirm-message="Vai tiešām dzēst šo lietotāju?"
                                                            data-app-confirm-accept="Jā, dzēst"
                                                            data-app-confirm-cancel="Nē"
                                                            data-app-confirm-tone="danger"
                                                        >
                                                            <x-icon name="trash" size="h-4 w-4" />
                                                            <span>Dzēst</span>
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

