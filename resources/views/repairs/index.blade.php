<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Remonti</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $canManageRepairs ? 'Faktiskie remontdarbi pēc apstiprinātiem pieteikumiem.' : 'Tavu ierīču remontu statuss.' }}</p>
            </div>
            @if ($canManageRepairs)
                <a href="{{ route('repairs.create') }}" class="crud-btn-primary">Jauns remonts</a>
            @endif
        </div>

        <form method="GET" action="{{ route('repairs.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Statuss</span>
                <select name="status" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Prioritate</span>
                <select name="priority" class="crud-control">
                    <option value="">Visas</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <select name="repair_type" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($repairTypes as $repairType)
                        <option value="{{ $repairType }}" @selected($filters['repair_type'] === $repairType)>{{ $typeLabels[$repairType] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="md:col-span-4 flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Meklet</button>
                <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Notirit</a>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Ierice</th>
                        <th class="px-4 py-3">Apraksts</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Prioritate</th>
                        <th class="px-4 py-3">Pieskirts</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($repairs as $repair)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $repair->device?->name ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $repair->device?->code ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $repair->description }}</td>
                            <td class="px-4 py-3">{{ $statusLabels[$repair->status] ?? $repair->status }}</td>
                            <td class="px-4 py-3">{{ $priorityLabels[$repair->priority] ?? $repair->priority }}</td>
                            <td class="px-4 py-3">{{ $repair->assignee?->full_name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($canManageRepairs)
                                    <a href="{{ route('repairs.edit', $repair) }}" class="crud-btn-secondary">Rediget</a>
                                @else
                                    <span class="text-slate-400">Tikai apskate</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Remonti nav atrasti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $repairs->links() }}
    </section>
</x-app-layout>
