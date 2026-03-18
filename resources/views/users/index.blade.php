<x-app-layout>
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

        <form method="GET" action="{{ route('users.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-4">
            <label class="block">
                <span class="crud-label">Vards</span>
                <input type="text" name="name" value="{{ $filters['name'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">E-pasts</span>
                <input type="text" name="email" value="{{ $filters['email'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Loma</span>
                <select name="role" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <select name="is_active" class="crud-control">
                    <option value="">Visi</option>
                    <option value="1" @selected($filters['is_active'] === '1')>Aktivi</option>
                    <option value="0" @selected($filters['is_active'] === '0')>Neaktivi</option>
                </select>
            </label>
            <div class="toolbar-actions md:col-span-4">
                <button type="submit" class="btn-search"><x-icon name="search" size="h-4 w-4" /><span>Meklet</span></button>
                <a href="{{ route('users.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Notirit</span></a>
            </div>
        </form>

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
                        <th class="px-4 py-3">Loma</th>
                        <th class="px-4 py-3">Amats</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $managedUser)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $managedUser->full_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->role }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $managedUser->job_title ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="status-pill {{ $managedUser->is_active ? 'status-pill-success' : 'status-pill-danger' }}">
                                    {{ $managedUser->is_active ? 'Aktivs' : 'Neaktivs' }}
                                </span>
                            </td>
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
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Lietotaji vel nav pievienoti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </section>
</x-app-layout>

