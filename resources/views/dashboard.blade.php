<x-app-layout>
    <section class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Darbvirsma</h1>
                <p class="text-sm text-gray-500">Pārskats par IT inventāru, {{ now()->format('d.m.Y H:i') }}</p>
            </div>
            <a href="{{ route('devices.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Pievienot ierīci
            </a>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Kopā ierīču</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalDevices }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Aktīvas</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $activeDevices }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Remontā vai bojātas</p>
                <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $inRepairDevices + $brokenDevices }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Jaunas šomēnes</p>
                <p class="mt-1 text-2xl font-semibold text-blue-700">{{ $newThisMonth }}</p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('device-types.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300 hover:bg-blue-50">
                <p class="text-sm font-semibold text-gray-900">Ierīču tipi</p>
                <p class="mt-1 text-xs text-gray-500">Klasifikators jaunu tipu pievienošanai</p>
            </a>
            <a href="{{ route('employees.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300 hover:bg-blue-50">
                <p class="text-sm font-semibold text-gray-900">Darbinieki</p>
                <p class="mt-1 text-xs text-gray-500">Personu katalogs telpām un atbildībām</p>
            </a>
            <a href="{{ route('users.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300 hover:bg-blue-50">
                <p class="text-sm font-semibold text-gray-900">Lietotāji</p>
                <p class="mt-1 text-xs text-gray-500">Sistēmas konti, kas piesaistīti darbiniekiem</p>
            </a>
            <a href="{{ route('rooms.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-blue-300 hover:bg-blue-50">
                <p class="text-sm font-semibold text-gray-900">Telpas</p>
                <p class="mt-1 text-xs text-gray-500">&#274;ku un kabinetu struktūra</p>
            </a>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="rounded-xl border border-gray-200 bg-white lg:col-span-3">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h2 class="text-base font-semibold text-gray-900">&#274;kas</h2>
                    <a href="{{ route('buildings.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Skatīt visas</a>
                </div>
                <div class="space-y-2 p-3">
                    @forelse($buildings->take(8) as $building)
                        <a href="{{ route('buildings.edit', $building) }}" class="block rounded-lg border border-gray-200 px-3 py-2 hover:border-blue-300 hover:bg-blue-50">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $building->building_name }}</p>
                                <span class="text-xs text-gray-500">{{ $building->rooms_count ?? 0 }} telpas</span>
                            </div>
                            <p class="text-xs text-gray-500">Stāvi: {{ $building->total_floors ?? 1 }}</p>
                        </a>
                    @empty
                        <p class="px-1 py-2 text-sm text-gray-500">&#274;kas vēl nav pievienotas.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white lg:col-span-6">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h2 class="text-base font-semibold text-gray-900">Karstie punkti</h2>
                    <span class="text-xs text-gray-500">Pēdējie statusa atjauninājumi</span>
                </div>

                <div class="grid grid-cols-3 gap-2 border-b border-gray-100 p-3">
                    <div class="rounded-lg bg-blue-50 px-3 py-2 text-center">
                        <p class="text-xs text-blue-700">Remontā</p>
                        <p class="text-lg font-semibold text-blue-800">{{ $inRepairDevices }}</p>
                    </div>
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-center">
                        <p class="text-xs text-red-700">Bojātas</p>
                        <p class="text-lg font-semibold text-red-800">{{ $brokenDevices }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 px-3 py-2 text-center">
                        <p class="text-xs text-amber-700">Bez telpas</p>
                        <p class="text-lg font-semibold text-amber-800">{{ $withoutRoom }}</p>
                    </div>
                </div>

                <div class="space-y-2 p-3">
                    @forelse($hotDevices as $device)
                        @php
                            $badgeClasses = $device->status === 'broken' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700';
                            $statusText = $device->status === 'broken' ? 'Bojāta' : 'Remontā';
                        @endphp
                        <a href="{{ route('devices.edit', $device) }}" class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 hover:border-blue-300 hover:bg-blue-50">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $device->type?->type_name ?? 'Nezināms tips' }}</p>
                                <p class="text-xs text-gray-500">{{ $device->code }} | {{ optional($device->room)->room_number ?? 'Telpa nav norādīta' }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badgeClasses }}">{{ $statusText }}</span>
                        </a>
                    @empty
                        <p class="px-1 py-2 text-sm text-gray-500">Šobrīd nav ierīču ar kritisku statusu.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white lg:col-span-3">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h2 class="text-base font-semibold text-gray-900">Statistika</h2>
                </div>
                <div class="space-y-2 p-3">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span class="text-sm text-gray-600">Aktīvas</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $activeDevices }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span class="text-sm text-gray-600">Remontā</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $inRepairDevices }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span class="text-sm text-gray-600">Bojātas</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $brokenDevices }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span class="text-sm text-gray-600">&#274;kas</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $buildings->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-900">Jaunākās ierīces</h2>
                <a href="{{ route('devices.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Atvērt inventāru</a>
            </div>

            @if($recentDevices->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Kods</th>
                                <th class="px-4 py-3 text-left">Tips</th>
                                <th class="px-4 py-3 text-left">Statuss</th>
                                <th class="px-4 py-3 text-left">Atrašanās vieta</th>
                                <th class="px-4 py-3 text-left">Darbības</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recentDevices as $device)
                                @php
                                    $statusText = match($device->status) {
                                        'active' => 'Aktīva',
                                        'broken' => 'Bojāta',
                                        'repair' => 'Remontā',
                                        default => ucfirst((string) $device->status),
                                    };
                                    $badgeClasses = match($device->status) {
                                        'active' => 'bg-emerald-100 text-emerald-700',
                                        'broken' => 'bg-red-100 text-red-700',
                                        'repair' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $device->code }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $device->type?->type_name ?? 'Nezināms tips' }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badgeClasses }}">{{ $statusText }}</span></td>
                                    <td class="px-4 py-3 text-gray-700">{{ optional($device->room)->room_number ?? 'Nav norādīta' }}</td>
                                    <td class="px-4 py-3"><a href="{{ route('devices.edit', $device) }}" class="text-blue-600 hover:text-blue-700">Rediģēt</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 py-8 text-sm text-gray-500">Ierīces vēl nav pievienotas.</div>
            @endif
        </div>
    </section>
</x-app-layout>

