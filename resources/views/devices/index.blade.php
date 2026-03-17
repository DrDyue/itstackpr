<x-app-layout>
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Ierices</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $canManageDevices ? 'Pilns ierīču saraksts un pārvaldība.' : 'Tavas piesaistītās ierīces.' }}</p>
            </div>
            @if ($canManageDevices)
                <a href="{{ route('devices.create') }}" class="crud-btn-primary">Jauna ierice</a>
            @endif
        </div>

        <form method="GET" action="{{ route('devices.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-5">
            <label class="block">
                <span class="crud-label">Meklet</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Kods</span>
                <input type="text" name="code" value="{{ $filters['code'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Telpa</span>
                <input type="text" name="room" value="{{ $filters['room'] }}" class="crud-control">
            </label>
            <label class="block">
                <span class="crud-label">Tips</span>
                <select name="type" class="crud-control">
                    <option value="">Visi</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected($filters['type'] == $type->id)>{{ $type->type_name }}</option>
                    @endforeach
                </select>
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
            <div class="md:col-span-5 flex flex-wrap gap-3">
                <button type="submit" class="crud-btn-primary">Meklet</button>
                <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Notirit</a>
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
                        <th class="px-4 py-3">Kods</th>
                        <th class="px-4 py-3">Nosaukums</th>
                        <th class="px-4 py-3">Tips</th>
                        <th class="px-4 py-3">Statuss</th>
                        <th class="px-4 py-3">Pieskirta</th>
                        <th class="px-4 py-3">Darbibas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $device->code ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('devices.show', $device) }}" class="font-medium text-slate-900 hover:text-blue-700">{{ $device->name }}</a>
                                <div class="text-xs text-slate-500">{{ $device->model }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $device->type?->type_name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $statusLabels[$device->status] ?? $device->status }}</td>
                            <td class="px-4 py-3">{{ $device->assignedUser?->full_name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('devices.show', $device) }}" class="crud-btn-secondary">Skatit</a>
                                    @if ($canManageDevices)
                                        <a href="{{ route('devices.edit', $device) }}" class="crud-btn-secondary">Rediget</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ierices nav atrastas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $devices->links() }}
    </section>
</x-app-layout>
