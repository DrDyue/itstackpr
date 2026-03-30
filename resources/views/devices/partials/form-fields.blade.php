{{--
    Partialis: Ierīces formas lauki.
    Atbildība: glabā visus ievades laukus, ko izmanto gan jaunās ierīces izveide, gan esošas ierīces rediģēšana.
    Kāpēc tas ir svarīgi:
    1. Viena un tā pati biznesa loģika netiek dublēta create un edit lapās.
    2. Šeit tiek sagatavoti noklusējumi, dropdown izvēles un datu piesaistes vērtības.
    3. Tieši šeit ir redzams, kuri lauki ir obligāti un kā tie ir sasaistīti ar backend validāciju.
--}}
@php
    $current = $device;
    $isCreating = ! $current;
    $isWrittenOff = ($current?->status ?? null) === \App\Models\Device::STATUS_WRITEOFF;
    $deviceImageUrl = $current?->deviceImageUrl();
    $selectedTypeId = (string) old('device_type_id', $current?->device_type_id ?? '');
    $selectedTypeLabel = old(
        'device_type_query',
        optional($types->firstWhere('id', (int) $selectedTypeId))->type_name ?? ''
    );
    $typeOptions = $types->map(fn ($type) => [
        'value' => (string) $type->id,
        'label' => $type->type_name,
        'description' => $type->category ?: ($type->description ?: 'Ierīces tips'),
        'search' => implode(' ', array_filter([$type->type_name, $type->category, $type->description])),
    ])->values();
    $selectedAssignedToId = old('assigned_to_id', $current?->assigned_to_id ?? $defaultAssignedToId ?? null);
    $selectedBuildingId = old('building_id', $current?->building_id ?? $defaultBuildingId ?? null);
    $selectedRoomId = old('room_id', $current?->room_id ?? $defaultRoomId ?? null);
    $selectedStatus = old('status', $current?->status ?? \App\Models\Device::STATUS_ACTIVE);
    $showBuildingField = $buildings->count() > 1;
    $buildingOptions = $buildings->map(fn ($building) => [
        'value' => (string) $building->id,
        'label' => $building->building_name,
        'description' => collect([
            $building->city,
            $building->address,
            $building->total_floors ? $building->total_floors . ' stāvi' : null,
        ])->filter()->implode(' | '),
        'search' => implode(' ', array_filter([
            $building->building_name,
            $building->city,
            $building->address,
        ])),
    ])->values();
    $roomOptions = $rooms->map(fn ($room) => [
        'value' => (string) $room->id,
        'label' => $room->room_number . ($room->room_name ? ' - ' . $room->room_name : ''),
        'description' => collect([
            $room->building?->building_name,
            $room->floor_number !== null ? $room->floor_number . '. stāvs' : null,
            $room->department,
        ])->filter()->implode(' | '),
        'search' => implode(' ', array_filter([
            $room->room_number,
            $room->room_name,
            $room->building?->building_name,
            $room->department,
            $room->floor_number,
        ])),
    ])->values();
    $assignedUserOptions = $users->map(fn ($assignedUser) => [
        'value' => (string) $assignedUser->id,
        'label' => $assignedUser->full_name,
        'description' => $assignedUser->job_title ?: $assignedUser->email,
        'search' => implode(' ', array_filter([
            $assignedUser->full_name,
            $assignedUser->job_title,
            $assignedUser->email,
        ])),
    ])->values();
    $statusOptions = collect($statuses)->map(fn ($status) => [
        'value' => (string) $status,
        'label' => $statusLabels[$status] ?? ucfirst($status),
        'description' => match ($status) {
            \App\Models\Device::STATUS_ACTIVE => 'Ierīce ir lietosana',
            \App\Models\Device::STATUS_REPAIR => 'Ierīce atrodas remonta',
            \App\Models\Device::STATUS_WRITEOFF => 'Ierīce ir norakstīta',
            default => '',
        },
        'search' => implode(' ', array_filter([
            $status,
            $statusLabels[$status] ?? ucfirst($status),
        ])),
    ])->values();
    $selectedAssignedToLabel = old(
        'assigned_to_query',
        optional($users->firstWhere('id', (int) $selectedAssignedToId))->full_name ?? ''
    );
    $selectedBuildingLabel = old(
        'building_query',
        optional($buildings->firstWhere('id', (int) $selectedBuildingId))->building_name ?? ''
    );
    $selectedRoomLabel = old(
        'room_query',
        optional($rooms->firstWhere('id', (int) $selectedRoomId))->room_number
            ? optional($rooms->firstWhere('id', (int) $selectedRoomId))->room_number . (optional($rooms->firstWhere('id', (int) $selectedRoomId))->room_name ? ' - ' . optional($rooms->firstWhere('id', (int) $selectedRoomId))->room_name : '')
            : ''
    );
    $selectedStatusLabel = old('status_query', $statusLabels[$selectedStatus] ?? 'Aktīva');
@endphp

{{-- Forma sadalīta pa semantiskām kartītēm: pamata dati, piesaiste, finanses, attēls un piezīmes. --}}
<div class="device-form-grid">
    <div class="space-y-6">
        @if ($isWrittenOff)
            <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
                Norakstītai ierīcei var labot tikai informācijas laukus. Statuss, piesaiste lietotājam un telpa vairs netiek mainīti.
            </div>
        @endif

        {{-- Ierīces identitāte: kods, nosaukums, tips, modelis un sērijas numurs. --}}
        <section class="device-form-card">
            <div class="device-form-section-header">
                <div class="device-form-section-icon bg-sky-50 text-sky-700 ring-sky-200">
                    <x-icon name="device" size="h-5 w-5" />
                </div>
                <div class="device-form-section-copy">
                    <div class="device-form-section-name">Pamata dati</div>
                    <div class="device-form-section-note">Ievadi galveno informāciju, pēc kuras ierīci atradīsi un atpazīsi sistēmā.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="crud-label">Kods *</span>
                    <input type="text" name="code" value="{{ old('code', $current?->code) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Nosaukums *</span>
                    <input type="text" name="name" value="{{ old('name', $current?->name) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Tips *</span>
                    <x-searchable-select
                        name="device_type_id"
                        query-name="device_type_query"
                        identifier="device-type-form-select"
                        :options="$typeOptions"
                        :selected="$selectedTypeId"
                        :query="$selectedTypeLabel"
                        placeholder="Izvēlies ierīces tipu"
                        empty-message="Neviens ierīces tips neatbilst meklējumam."
                    />
                </label>
                <label class="block">
                    <span class="crud-label">Modelis *</span>
                    <input type="text" name="model" value="{{ old('model', $current?->model) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Ražotājs</span>
                    <input type="text" name="manufacturer" value="{{ old('manufacturer', $current?->manufacturer) }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Sērijas numurs</span>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $current?->serial_number) }}" class="crud-control">
                </label>
            </div>
        </section>

        {{-- Statuss, atbildīgais lietotājs un fiziskā atrašanās vieta. --}}
        <section class="device-form-card">
            <div class="device-form-section-header">
                <div class="device-form-section-icon bg-emerald-50 text-emerald-700 ring-emerald-200">
                    <x-icon name="users" size="h-5 w-5" />
                </div>
                <div class="device-form-section-copy">
                    <div class="device-form-section-name">Statuss un piesaiste</div>
                    <div class="device-form-section-note">Norādi, kam ierīce piešķirta un kurā telpā tā atrodas ikdienā.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @if ($isCreating)
                    <div class="block">
                        <span class="crud-label">Statuss</span>
                        <input type="hidden" name="status" value="{{ \App\Models\Device::STATUS_ACTIVE }}">
                        <div class="crud-control flex items-center justify-between bg-slate-50 text-slate-700">
                            <span>Aktīva</span>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700">Noklusēts</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">Jauna ierīce vienmēr tiek izveidota ar aktīvu statusu.</div>
                    </div>
                @else
                    <label class="block">
                        <span class="crud-label">Statuss</span>
                        @if ($isWrittenOff)
                            <input type="hidden" name="status" value="{{ $current?->status }}">
                        @endif
                        @if ($isWrittenOff)
                            <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                                <span>{{ $statusLabels[$current?->status] ?? 'Norakstīta' }}</span>
                            </div>
                        @else
                            <x-searchable-select
                                name="status"
                                query-name="status_query"
                                identifier="device-status-form-select"
                                :options="$statusOptions"
                                :selected="(string) $selectedStatus"
                                :query="$selectedStatusLabel"
                                placeholder="Izvēlies statusu"
                                empty-message="Neviens statuss neatbilst meklējumam."
                            />
                        @endif
                    </label>
                @endif
                <label class="block">
                    <span class="crud-label">Atbildīgā persona *</span>
                    @if ($isWrittenOff)
                        <input type="hidden" name="assigned_to_id" value="">
                        <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                            <span>Nav piešķirts</span>
                        </div>
                    @else
                        <x-searchable-select
                            name="assigned_to_id"
                            query-name="assigned_to_query"
                            identifier="device-assigned-user-form-select"
                            :options="$assignedUserOptions"
                            :selected="(string) $selectedAssignedToId"
                            :query="$selectedAssignedToLabel"
                            placeholder="Meklē atbildīgo personu"
                            empty-message="Neviens lietotājs neatbilst meklējumam."
                        />
                    @endif
                </label>
                @if ($showBuildingField)
                    <label class="block">
                        <span class="crud-label">Ēka</span>
                        @if ($isWrittenOff)
                            <input type="hidden" name="building_id" value="">
                            <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                                <span>Nav piešķirta ēkai</span>
                            </div>
                        @else
                            <x-searchable-select
                                name="building_id"
                                query-name="building_query"
                                identifier="device-building-form-select"
                                :options="$buildingOptions"
                                :selected="(string) $selectedBuildingId"
                                :query="$selectedBuildingLabel"
                                placeholder="Meklē ēku"
                                empty-message="Neviena ēka neatbilst meklējumam."
                            />
                        @endif
                    </label>
                @else
                    <input type="hidden" name="building_id" value="{{ $isWrittenOff ? '' : $selectedBuildingId }}">
                @endif
                <label class="block">
                    <span class="crud-label">Telpa *</span>
                    @if ($isWrittenOff)
                        <input type="hidden" name="room_id" value="">
                        <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                            <span>Nav piešķirta telpai</span>
                        </div>
                    @else
                        <x-searchable-select
                            name="room_id"
                            query-name="room_query"
                            identifier="device-room-form-select"
                            :options="$roomOptions"
                            :selected="(string) $selectedRoomId"
                            :query="$selectedRoomLabel"
                            placeholder="Meklē telpu"
                            empty-message="Neviena telpa neatbilst meklējumam."
                        />
                    @endif
                </label>
            </div>
        </section>

        <section class="device-form-card">
            <div class="device-form-section-header">
                <div class="device-form-section-icon bg-violet-50 text-violet-700 ring-violet-200">
                    <x-icon name="calendar" size="h-5 w-5" />
                </div>
                <div class="device-form-section-copy">
                    <div class="device-form-section-name">Iegāde, garantija un piezīmes</div>
                    <div class="device-form-section-note">Papildini datumu, cenu, attēla un piezīmju laukus, ja tie ir zināmi.</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-localized-date-input
                    name="purchase_date"
                    label="Iegades datums"
                    :value="old('purchase_date', $current?->purchase_date?->format('Y-m-d'))"
                />
                <label class="block">
                    <span class="crud-label">Iegades cena</span>
                    <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $current?->purchase_price) }}" class="crud-control">
                </label>
                <x-localized-date-input
                    name="warranty_until"
                    label="Garantija līdz"
                    :value="old('warranty_until', $current?->warranty_until?->format('Y-m-d'))"
                />
                <div class="block">
                    <span class="crud-label">Ierīces attēls</span>
                    <input type="file" name="device_image" class="device-file-input">
                    <div class="mt-2 text-xs text-slate-500">PNG, JPG vai WEBP līdz {{ (int) config('devices.max_upload_kb', 5120) / 1024 }} MB.</div>
                    @if ($current)
                        <label class="mt-3 inline-flex items-center gap-3">
                            <input type="checkbox" name="remove_device_image" value="1" class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-slate-700">Noņemt ierīces attēlu</span>
                        </label>
                    @endif
                </div>
                <label class="block md:col-span-2">
                    <span class="crud-label">Piezīmes</span>
                    <textarea name="notes" rows="5" class="crud-control">{{ old('notes', $current?->notes) }}</textarea>
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="device-form-card">
            <div class="device-form-section-title">Kopsavilkums</div>
            <div class="space-y-4 text-sm text-slate-600">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kas ir obligāti</div>
                    <ul class="mt-3 space-y-2 leading-6">
                        <li>Kods, nosaukums, tips un modelis ir obligāti lauki.</li>
                        <li>Atbildīgā persona un telpa ir obligātas un pēc noklusējuma tiek aizpildītas automātiski.</li>
                        <li>Jauna ierīce vienmēr tiek saglabāta ar aktīvu statusu.</li>
                        <li>Datumi, cena un piezīmes var palikt tukši, ja tie nav zināmi.</li>
                    </ul>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attēla priekšskats</div>
                    <div class="mt-3">
                        @if ($deviceImageUrl)
                            <img src="{{ $deviceImageUrl }}" alt="{{ $current?->name ?: 'Ierīce' }}" class="w-full rounded-[1.25rem] border border-slate-200 object-contain">
                        @else
                            <div class="rounded-[1.25rem] border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">
                                Attēls tiks parādīts pēc pievienošanas.
                            </div>
                        @endif
                    </div>
                </div>

                @if ($current)
                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Esošais ieraksts</div>
                        <div class="mt-3 space-y-2">
                            <div><strong class="text-slate-900">Statuss:</strong> {{ $statusLabels[$current->status] ?? $current->status }}</div>
                            <div><strong class="text-slate-900">Kods:</strong> {{ $current->code ?: '-' }}</div>
                            <div><strong class="text-slate-900">Lietotājs:</strong> {{ $current->assignedTo?->full_name ?: 'Nav piešķirts' }}</div>
                            <div><strong class="text-slate-900">Atrašanās vieta:</strong> {{ $current->building?->building_name ?: 'Bez ēkas' }} / {{ $current->room?->room_number ?: 'Bez telpas' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </aside>
</div>
