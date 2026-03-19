<x-app-layout>
    @php
        $roleFilterLinks = [
            ['label' => 'Admins', 'value' => 'admin', 'icon' => 'users', 'tone' => 'violet'],
            ['label' => 'Darbinieki', 'value' => 'user', 'icon' => 'profile', 'tone' => 'sky'],
        ];
        $selectedRoles = $filters['has_role_filter'] ? $filters['roles'] : collect($roleFilterLinks)->pluck('value')->all();
        $statusOptions = [
            ['value' => '1', 'label' => 'Aktivi', 'description' => 'Rada tikai aktivus lietotajus', 'search' => 'Aktivi aktivi lietotaji'],
            ['value' => '0', 'label' => 'Neaktivi', 'description' => 'Rada tikai neaktivus lietotajus', 'search' => 'Neaktivi neaktivie lietotaji'],
        ];
        $lastLoginOptions = [
            ['value' => 'today', 'label' => 'Sodien', 'description' => 'Piesledzas sodien', 'search' => 'Sodien pedeja pieslegsanas'],
            ['value' => 'recent', 'label' => 'Pedejas 7 dienas', 'description' => 'Aktivi pedeja nedela', 'search' => 'Pedejas 7 dienas nesen'],
            ['value' => 'never', 'label' => 'Nav piesledzies', 'description' => 'Lietotajs vel nav piesledzies', 'search' => 'Nav piesledzies nekad'],
        ];
        $selectedStatusLabel = collect($statusOptions)->firstWhere('value', $filters['is_active'])['label'] ?? null;
        $selectedLastLoginLabel = collect($lastLoginOptions)->firstWhere('value', $filters['last_login'])['label'] ?? null;
    @endphp

    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="users" size="h-4 w-4" /><span>Lietotaji</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet"><x-icon name="users" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Lietotaji</h1>
                            <p class="page-subtitle">Parvaldi sistemas lietotajus, lomas un piekluves statusus.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Jauns lietotajs</span></a>
            </div>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-3 xl:grid-cols-4">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Vards, e-pasts, talrunis, amats...">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <x-searchable-select
                    name="is_active"
                    query-name="is_active_query"
                    identifier="user-status-filter"
                    :options="$statusOptions"
                    :selected="$filters['is_active']"
                    :query="$selectedStatusLabel"
                    placeholder="Izvelies statusu"
                    empty-message="Neviens statuss neatbilst meklejumam."
                />
            </label>
            <label class="block">
                <span class="crud-label">Pedeja pieslegsanas</span>
                <x-searchable-select
                    name="last_login"
                    query-name="last_login_query"
                    identifier="user-last-login-filter"
                    :options="$lastLoginOptions"
                    :selected="$filters['last_login']"
                    :query="$selectedLastLoginLabel"
                    placeholder="Izvelies periodu"
                    empty-message="Neviens periods neatbilst meklejumam."
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
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('users.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
                </div>
            </div>
        </form>

        <x-active-filters
            :items="[
                ['label' => 'Meklet', 'value' => $filters['q']],
                ['label' => 'Loma', 'value' => $filters['has_role_filter'] ? collect($filters['roles'])->map(fn ($role) => $roleLabels[$role] ?? $role)->implode(', ') : null],
                ['label' => 'Statuss', 'value' => $filters['is_active'] === '1' ? 'Aktivs' : ($filters['is_active'] === '0' ? 'Neaktivs' : null)],
                ['label' => 'Pedeja pieslegsanas', 'value' => $filters['last_login'] === 'today' ? 'Sodien' : ($filters['last_login'] === 'recent' ? 'Pedejas 7 dienas' : ($filters['last_login'] === 'never' ? 'Nav piesledzies' : null))],
            ]"
            :clear-url="route('users.index')"
        />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Vards</th>
                        <th class="px-4 py-3">E-pasts</th>
                        <th class="px-4 py-3">Talrunis</th>
                        <th class="px-4 py-3">Loma</th>
                        <th class="px-4 py-3">Amats</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Pedeja pieslegsanas</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $managedUser)
                        <tr class="border-t border-slate-100 {{ $managedUser->role === 'admin' ? 'bg-violet-50/40' : 'bg-sky-50/30' }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $managedUser->full_name }}</div>
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
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('users.edit', $managedUser) }}" class="btn-edit"><x-icon name="edit" size="h-4 w-4" /><span>Rediget</span></a>
                                    <form method="POST" action="{{ route('users.destroy', $managedUser) }}" onsubmit="return confirm('Dzest so lietotaju?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger" @disabled(auth()->id() === $managedUser->id)><x-icon name="trash" size="h-4 w-4" /><span>Dzest</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Lietotaji vel nav pievienoti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </section>
</x-app-layout>
