<x-app-layout>
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Darbinieki</h1>
                <p class="text-sm text-gray-500">Darbinieku saraksts un kontaktinformâcija</p>
            </div>
            <a href="{{ route('employees.create') }}" class="crud-btn-primary-inline">Pievienot darbinieku</a>
        </div>

        <form method="GET" action="{{ route('employees.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Meklçt pçc vârda, e-pasta, telefona..." class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Meklçt</button>
                <a href="{{ route('employees.index') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Notîrît</a>
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
                            <th class="px-4 py-3 text-left">Vârds, uzvârds</th>
                            <th class="px-4 py-3 text-left">E-pasts</th>
                            <th class="px-4 py-3 text-left">Telefons</th>
                            <th class="px-4 py-3 text-left">Amats</th>
                            <th class="px-4 py-3 text-left">Darbinieks aktîvs</th>
                            <th class="px-4 py-3 text-left">Izveidots</th>
                            <th class="px-4 py-3 text-left">Darbîbas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $employee->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $employee->full_name }}</td>
                                <td class="px-4 py-3">{{ $employee->email ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $employee->phone ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $employee->job_title ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($employee->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Aktîvs</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Neaktîvs</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $employee->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('employees.edit', $employee) }}" class="text-blue-600 hover:text-blue-700">Rediìçt</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Dzçst ðo darbinieku?')" class="text-red-600 hover:text-red-700">Dzçst</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">Darbinieki vçl nav pievienoti.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-app-layout>


