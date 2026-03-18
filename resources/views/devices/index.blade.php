<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="device" size="h-4 w-4" />
                        <span>Inventars</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="device" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierices</h1>
                            <p class="page-subtitle">{{ $canManageDevices ? 'Pilns iericu saraksts un parvaldiba.' : 'Tavas piesaistitas ierices.' }}</p>
                        </div>
                    </div>
                </div>
                @if ($canManageDevices)
                    <div class="page-actions">
                        <a href="{{ route('devices.create') }}" class="btn-create">
                            <x-icon name="plus" size="h-4 w-4" />
                            <span>Jauna ierice</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('devices.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-5">
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
            <div class="toolbar-actions md:col-span-5">
                <button type="submit" class="btn-search">
                    <x-icon name="search" size="h-4 w-4" />
                    <span>Meklet</span>
                </button>
                <a href="{{ route('devices.index') }}" class="btn-clear">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Notirit</span>
                </a>
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
                            <td class="px-4 py-3">
                                <span class="status-pill {{ $device->status === 'active' ? 'status-pill-success' : ($device->status === 'repair' ? 'status-pill-warning' : 'status-pill-danger') }}">
                                    {{ $statusLabels[$device->status] ?? $device->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $device->assignedTo?->full_name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('devices.show', $device) }}" class="btn-view">
                                        <x-icon name="view" size="h-4 w-4" />
                                        <span>Skatit</span>
                                    </a>
                                    @if ($canManageDevices)
                                        <a href="{{ route('devices.edit', $device) }}" class="btn-edit">
                                            <x-icon name="edit" size="h-4 w-4" />
                                            <span>Rediget</span>
                                        </a>
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

