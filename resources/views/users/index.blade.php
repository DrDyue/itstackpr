<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Lietotaji</h1>
                <p class="mt-2 text-sm text-slate-600">Pārvaldi sistēmas lietotājus un viņu lomas.</p>
            </div>
            <a href="{{ route('users.create') }}" class="crud-btn-primary inline-flex items-center gap-2">Jauns lietotajs</a>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4">
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
            <div class="md:col-span-4 flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Meklet</button>
                <a href="{{ route('users.index') }}" class="crud-btn-secondary">Notirit</a>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $managedUser->is_active ? 'Aktivs' : 'Neaktivs' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('users.edit', $managedUser) }}" class="crud-btn-secondary">Rediget</a>
                                    <form method="POST" action="{{ route('users.destroy', $managedUser) }}" onsubmit="return confirm('Dzest so lietotaju?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700" @disabled(auth()->id() === $managedUser->id)>Dzest</button>
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
