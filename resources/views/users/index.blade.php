<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Lietotāji</h1>
                <p class="text-sm text-gray-500">Sistēmas kontu pārvaldība</p>
            </div>
            @if(auth()->user()?->role === 'admin')
                <a href="{{ route('users.create') }}" class="crud-btn-primary-inline">Pievienot lietotāju</a>
            @endif
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Meklēt pēc lomas vai darbinieka..." class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Meklēt</button>
                <a href="{{ route('users.index') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Notīrīt</a>
            </div>
        </form>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Darbinieks</th>
                            <th class="px-4 py-3 text-left">E-pasts</th>
                            <th class="px-4 py-3 text-left">Loma</th>
                            <th class="px-4 py-3 text-left">Darbinieks aktīvs</th>
                            <th class="px-4 py-3 text-left">Konts aktīvs</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Pēdējā pieslēgšanās</th>
                            <th class="px-4 py-3 text-left">Darbības</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $user->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $user->employee?->full_name ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $user->employee?->email ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $user->role }}</td>
                                <td class="px-4 py-3">
                                    @if($user->employee?->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Jā</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Nē</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($user->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Aktīvs</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Neaktīvs</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $user->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $user->last_login?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzēst šo lietotāju?')" class="text-red-600 hover:text-red-700">Dzēst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Lietotāji vēl nav pievienoti.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


