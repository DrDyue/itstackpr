{{--
    Lapa: Ēku saraksts.
    Atbildība: rāda visas ēkas, kurās sistēmā tiek organizētas telpas un ierīces.
    Datu avots: BuildingController@index.
    Galvenās daļas:
    1. Hero un darbības.
    2. Filtri pēc meklēšanas un stāvu skaita.
    3. Ēku tabula ar telpu un ierīču skaitu.
--}}
<x-app-layout>
    @php
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $sortableHeaders = [
            'building_name' => ['label' => 'Nosaukums'],
            'address' => ['label' => 'Adrese'],
            'total_floors' => ['label' => 'Stāvu skaits'],
            'created_at' => ['label' => 'Izveidots'],
        ];
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="building" size="h-4 w-4" /><span>Infrastruktūra</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-slate"><x-icon name="building" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Ēkas</h1>
                            <p class="page-subtitle">Ēku saraksts un pamata dati ar ātru pieeju telpu pārvaldībai.</p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="{{ route('rooms.create') }}" class="btn-view"><x-icon name="room" size="h-4 w-4" /><span>Pievienot telpu</span></a>
                    <a href="{{ route('buildings.create') }}" class="btn-create"><x-icon name="plus" size="h-4 w-4" /><span>Pievienot ēku</span></a>
                </div>
            </div>
        </div>

        <div id="buildings-index-root" data-async-table-root>
            <form
                method="GET"
                action="{{ route('buildings.index') }}"
                class="surface-toolbar grid gap-4 md:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]"
                data-async-table-form
                data-async-root="#buildings-index-root"
                data-search-endpoint="{{ route('buildings.find-by-name') }}"
            >
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">

                <label class="block">
                    <span class="crud-label">Ēkas nosaukums vai adrese</span>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            name="search"
                            value="{{ $filters['search'] }}"
                            class="crud-control"
                            placeholder="Ievadi ēkas nosaukumu vai adresi"
                            data-async-manual="true"
                            data-table-manual-search="true"
                            data-search-mode="contains"
                        >
                        <button type="submit" class="btn-search shrink-0" data-table-search-submit="true">
                            <x-icon name="search" size="h-4 w-4" />
                            <span>Meklēt</span>
                        </button>
                    </div>
                </label>

                <label class="block">
                    <span class="crud-label">Stāvu skaits</span>
                    <input
                        type="number"
                        name="total_floors"
                        value="{{ $filters['total_floors'] }}"
                        class="crud-control"
                        placeholder="Ievadi stāvu skaitu"
                        min="0"
                        step="1"
                        inputmode="numeric"
                    >
                </label>

                <div class="toolbar-actions md:col-span-2">
                    <a href="{{ route('buildings.index') }}" class="btn-clear" data-async-link="true"><x-icon name="clear" size="h-4 w-4" /><span>Notīrīt</span></a>
                </div>
            </form>

            <x-active-filters
                :items="[
                    ['label' => 'Stāvu skaits', 'value' => $filters['total_floors'] !== '' ? $filters['total_floors'] : null],
                ]"
                :clear-url="route('buildings.index')"
            />

            @if (session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            <div class="app-table-shell">
                <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <table class="app-table-content app-table-content-compact min-w-full text-sm">
                        <thead class="app-table-head bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                @foreach ($sortableHeaders as $column => $header)
                                    @php
                                        $isCurrentSort = $sorting['sort'] === $column;
                                        $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
                                        $sortMessage = 'Kārtots pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($header['label'])) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                                    @endphp
                                    <th class="px-4 py-3 text-left">
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
                                <th class="px-4 py-3 text-left">Resursi</th>
                                <th class="px-4 py-3 text-left">Pilsēta</th>
                                <th class="px-4 py-3 text-left">Piezīmes</th>
                                <th class="px-4 py-3 text-left">Darbības</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($buildings as $building)
                                @php
                                    $canDelete = (int) $building->rooms_count === 0 && (int) $building->devices_count === 0;
                                    $deleteMessage = ((int) $building->rooms_count > 0 || (int) $building->devices_count > 0)
                                        ? 'Ēku nevar dzēst, kamēr tai ir piesaistītas telpas vai ierīces.'
                                        : '';
                                @endphp
                                <tr
                                    class="app-table-row"
                                    data-table-row-id="building-{{ $building->id }}"
                                    data-table-search-value="{{ \Illuminate\Support\Str::lower(trim(implode(' ', array_filter([(string) $building->building_name, (string) $building->address])))) }}"
                                >
                                    <td class="px-4 py-3 app-table-cell-strong">{{ $building->building_name }}</td>
                                    <td class="px-4 py-3">{{ $building->address ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $building->total_floors ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $building->created_at?->format('d.m.Y H:i') ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <div>Telpas: {{ $building->rooms_count }}</div>
                                        <div>Ierīces: {{ $building->devices_count }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $building->city ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $building->notes ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('buildings.edit', $building) }}" class="btn-edit">
                                                <x-icon name="edit" size="h-4 w-4" />
                                                <span>Rediģēt</span>
                                            </a>

                                            @if ($canDelete)
                                                <form
                                                    method="POST"
                                                    action="{{ route('buildings.destroy', $building) }}"
                                                    data-app-confirm-title="Dzēst ēku?"
                                                    data-app-confirm-message="Vai tiešām dzēst šo ēku?"
                                                    data-app-confirm-accept="Jā, dzēst"
                                                    data-app-confirm-cancel="Nē"
                                                    data-app-confirm-tone="danger"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">
                                                        <x-icon name="trash" size="h-4 w-4" />
                                                        <span>Dzēst</span>
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn-disabled"
                                                    data-app-toast-title="Dzēšana nav pieejama"
                                                    data-app-toast-message="{{ $deleteMessage }}"
                                                    data-app-toast-tone="info"
                                                >
                                                    <x-icon name="trash" size="h-4 w-4" />
                                                    <span>Dzēst</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6">
                                        <x-empty-state
                                            compact
                                            icon="building"
                                            title="Ēkas vēl nav pievienotas"
                                            description="Kad pievienosi pirmo ēku, tā parādīsies šajā sarakstā."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $buildings->links() }}
        </div>
    </section>
</x-app-layout>
