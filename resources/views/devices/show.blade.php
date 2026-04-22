{{--
    Lapa: Vienas ierīces detalizētais skats.
    Atbildība: parāda pilnu informāciju par konkrētu ierīci, tās statusu, atrašanās vietu un saistītajām darbībām.
    Datu avots: DeviceController@show.
--}}
<x-app-layout>
    @php
        $deviceMeta = collect([$device->manufacturer, $device->model])->filter(fn ($v) => filled($v))->implode(' · ');
        $activeRepair = $device->activeRepair;
        $usesUserDeviceView = ! $canManageDevices;
        $activeRepairUrl = $activeRepair
            ? route('repairs.index', ['repair_modal' => 'edit', 'modal_repair' => $activeRepair->id])
            : null;
        $repairCreateUrl = route('repairs.index', ['repair_modal' => 'create', 'device_id' => $device->id]);
        $repairRequestCreateUrl = route('repair-requests.index', ['repair_request_modal' => 'create', 'device_id' => $device->id]);
        $writeoffRequestCreateUrl = route('writeoff-requests.index', ['writeoff_request_modal' => 'create', 'device_id' => $device->id]);
        $transferCreateUrl = route('device-transfers.index', ['device_transfer_modal' => 'create', 'device_id' => $device->id]);
        $repairTypeLabels = ['internal' => 'Iekšējais', 'external' => 'Ārējais'];
        $repairPriorityLabels = ['low' => 'Zema', 'medium' => 'Vidēja', 'high' => 'Augsta', 'critical' => 'Kritiska'];
        $hasTransferOrigin = (bool) ($latestTransferToCurrentUser ?? null);
        $deviceRoomLabel = $device->room
            ? ($device->room->room_number . ($device->room->room_name ? ' – ' . $device->room->room_name : ''))
            : 'Nav norādīta';
        $deviceBuildingLabel = $device->building?->building_name ?: 'Nav norādīta';
        $deviceUserLabel = $device->assignedTo?->full_name ?: 'Nav piešķirts';
        $metaLine = collect([$device->type?->type_name, $deviceMeta])->filter()->implode(' · ');
    @endphp

    <section class="app-shell app-shell-wide">

        {{-- ═══ KOMPAKTS HEADER ═══ --}}
        <div class="mb-5 flex flex-wrap items-center gap-3">
            {{-- Attēls --}}
            <div class="shrink-0">
                @if ($deviceImageUrl)
                    <img src="{{ $deviceImageUrl }}" alt="{{ $device->name }}"
                        class="h-14 w-14 rounded-xl border border-slate-200 object-cover shadow-sm">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 text-slate-400">
                        <x-icon name="device" size="h-7 w-7" />
                    </div>
                @endif
            </div>

            {{-- Virsraksts + kods + statuss --}}
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900">{{ $device->name }}</h1>
                    @if ($device->code)
                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $device->code }}</span>
                    @endif
                    <x-status-pill context="device" :value="$device->status" :label="$statusLabels[$device->status] ?? null" />
                    @if ($repairStatusLabel)
                        <span class="device-repair-state-chip">
                            <x-icon name="repair" size="h-3.5 w-3.5" />
                            <span>{{ $repairStatusLabel }}</span>
                        </span>
                    @endif
                </div>
                @if ($metaLine)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $metaLine }}</p>
                @endif
            </div>

            {{-- Darbības --}}
            <div class="flex flex-wrap items-center gap-2">
                @if ($canManageDevices)
                    <a href="{{ route('devices.index', ['device_modal' => 'edit', 'modal_device' => $device->id]) }}" class="btn-edit">
                        <x-icon name="edit" size="h-4 w-4" /><span>Rediģēt</span>
                    </a>
                    @if ($activeRepairUrl)
                        <a href="{{ $activeRepairUrl }}" class="btn-view">
                            <x-icon name="repair" size="h-4 w-4" /><span>Atvērt remontu</span>
                        </a>
                    @elseif ($device->status !== \App\Models\Device::STATUS_WRITEOFF)
                        <a href="{{ $repairCreateUrl }}" class="btn-view">
                            <x-icon name="repair" size="h-4 w-4" /><span>Jauns remonts</span>
                        </a>
                    @endif
                @else
                    @if ($requestAvailability['repair'])
                        <a href="{{ $repairRequestCreateUrl }}" class="btn-edit">
                            <x-icon name="repair" size="h-4 w-4" /><span>Remonts</span>
                        </a>
                    @endif
                    @if ($requestAvailability['writeoff'])
                        <a href="{{ $writeoffRequestCreateUrl }}" class="btn-danger">
                            <x-icon name="writeoff" size="h-4 w-4" /><span>Norakstīt</span>
                        </a>
                    @endif
                    @if ($requestAvailability['transfer'])
                        <a href="{{ $transferCreateUrl }}" class="btn-view">
                            <x-icon name="transfer" size="h-4 w-4" /><span>Nodot</span>
                        </a>
                    @endif
                @endif
                <a href="{{ route('devices.index') }}" class="btn-back">
                    <x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span>
                </a>
            </div>
        </div>

        {{-- Brīdinājums --}}
        @if (session('warning'))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ session('warning') }}
            </div>
        @endif

        {{-- ═══ GALVENĀ INFORMĀCIJA ═══ --}}
        <div class="grid gap-4 xl:grid-cols-12">

            {{-- Identitāte --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-4">
                <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                    <x-icon name="device" size="h-3.5 w-3.5" />
                    <span>Identitāte</span>
                </h3>
                <dl class="mt-3 divide-y divide-slate-100">
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Kods</dt>
                        <dd class="min-w-0 font-mono text-sm font-semibold text-slate-900">{{ $device->code ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Nosaukums</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $device->name }}</dd>
                    </div>
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Tips</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $device->type?->type_name ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Ražotājs</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $device->manufacturer ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Modelis</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $device->model ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Sērija</dt>
                        <dd class="min-w-0 font-mono text-sm text-slate-900">{{ $device->serial_number ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Atrašanās vieta --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-4">
                <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                    <x-icon name="room" size="h-3.5 w-3.5" />
                    <span>Atrašanās vieta</span>
                </h3>
                <dl class="mt-3 divide-y divide-slate-100">
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Ēka</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $deviceBuildingLabel }}</dd>
                    </div>
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Telpa</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $deviceRoomLabel }}</dd>
                    </div>
                    @if ($device->room?->department)
                        <div class="flex items-start gap-3 py-2">
                            <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Nodaļa</dt>
                            <dd class="min-w-0 text-sm text-slate-900">{{ $device->room->department }}</dd>
                        </div>
                    @endif
                    <div class="flex items-start gap-3 py-2">
                        <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Piešķirts</dt>
                        <dd class="min-w-0 text-sm text-slate-900">{{ $deviceUserLabel }}</dd>
                    </div>
                    @if ($device->assignedTo?->job_title)
                        <div class="flex items-start gap-3 py-2">
                            <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Amats</dt>
                            <dd class="min-w-0 text-sm text-slate-500">{{ $device->assignedTo->job_title }}</dd>
                        </div>
                    @endif
                    @if ($canManageDevices)
                        <div class="flex items-start gap-3 py-2">
                            <dt class="w-24 shrink-0 text-xs font-semibold text-slate-500">Izveidoja</dt>
                            <dd class="min-w-0 text-sm text-slate-500">{{ $device->createdBy?->full_name ?: 'Sistēma' }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Lietotāja telpas maiņa --}}
                @if ($usesUserDeviceView)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        @if ($roomUpdateAvailability['allowed'])
                            <button type="button" class="btn-search w-full justify-center" x-data @click="$dispatch('open-modal', 'device-show-room-modal')">
                                <x-icon name="room" size="h-4 w-4" /><span>Mainīt telpu</span>
                            </button>
                        @else
                            <p class="text-xs text-slate-500">{{ $roomUpdateAvailability['reason'] }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Labā kolonna --}}
            <div class="space-y-4 xl:col-span-4">
                @if ($canManageDevices)
                    {{-- Iegāde un garantija --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <x-icon name="tag" size="h-3.5 w-3.5" />
                            <span>Iegāde un garantija</span>
                        </h3>
                        <dl class="mt-3 divide-y divide-slate-100">
                            <div class="flex items-start gap-3 py-2">
                                <dt class="w-28 shrink-0 text-xs font-semibold text-slate-500">Iegādes datums</dt>
                                <dd class="min-w-0 text-sm text-slate-900">{{ $device->purchase_date?->format('d.m.Y') ?: '—' }}</dd>
                            </div>
                            <div class="flex items-start gap-3 py-2">
                                <dt class="w-28 shrink-0 text-xs font-semibold text-slate-500">Iegādes cena</dt>
                                <dd class="min-w-0 text-sm font-semibold text-slate-900">
                                    {{ $device->purchase_price !== null ? number_format((float) $device->purchase_price, 2, '.', ' ') . ' EUR' : '—' }}
                                </dd>
                            </div>
                            <div class="flex items-start gap-3 py-2">
                                <dt class="w-28 shrink-0 text-xs font-semibold text-slate-500">Garantija līdz</dt>
                                <dd class="min-w-0 text-sm text-slate-900
                                    {{ $device->warranty_until && $device->warranty_until->isPast() ? 'text-rose-600' : '' }}">
                                    {{ $device->warranty_until?->format('d.m.Y') ?: '—' }}
                                </dd>
                            </div>
                            <div class="flex items-start gap-3 py-2">
                                <dt class="w-28 shrink-0 text-xs font-semibold text-slate-500">Izveidots</dt>
                                <dd class="min-w-0 text-sm text-slate-500">{{ $device->created_at?->format('d.m.Y H:i') ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Lielāks attēls (admin) --}}
                    @if ($deviceImageUrl)
                        <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                            <img src="{{ $deviceImageUrl }}" alt="{{ $device->name }}"
                                class="w-full object-cover" style="max-height: 220px;">
                        </div>
                    @endif
                @else
                    {{-- Lietotāja pieteikumu ierobežojums --}}
                    @if ($requestAvailability['reason'])
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-amber-600">
                                <x-icon name="exclamation-triangle" size="h-3.5 w-3.5" />
                                <span>Ierobežojums</span>
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-amber-900">{{ $requestAvailability['reason'] }}</p>
                        </div>
                    @endif

                    {{-- Izcelsme (nodošana) --}}
                    @if ($hasTransferOrigin)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                            <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-emerald-600">
                                <x-icon name="transfer" size="h-3.5 w-3.5" />
                                <span>Izcelsme</span>
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-emerald-900">{{ $originLabel }}</p>
                        </div>
                    @endif
                @endif

                {{-- Piezīmes --}}
                @if ($device->notes)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <x-icon name="audit" size="h-3.5 w-3.5" />
                            <span>Piezīmes</span>
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $device->notes }}</p>
                    </div>
                @endif

                {{-- Admin: pieteikumu ierobežojums --}}
                @if ($canManageDevices && $requestAvailability['reason'])
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-amber-600">
                            <x-icon name="exclamation-triangle" size="h-3.5 w-3.5" />
                            <span>Ierobežojums</span>
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-amber-900">{{ $requestAvailability['reason'] }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Telpas maiņas modāls (lietotājs) --}}
        @if ($usesUserDeviceView && $roomUpdateAvailability['allowed'])
            <x-modal name="device-show-room-modal" maxWidth="2xl">
                <div class="device-user-room-modal-shell">
                    <div class="device-user-room-modal-head">
                        <div>
                            <div class="device-user-room-modal-badge">Telpas maiņa</div>
                            <h2 class="device-user-room-modal-title">{{ $device->name }}</h2>
                            <p class="device-user-room-modal-copy">Izvēlies telpu, uz kuru pārvietot ierīci. Ēka tiks atjaunota automātiski.</p>
                        </div>
                        <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'device-show-room-modal')" aria-label="Aizvērt">
                            <x-icon name="x-mark" size="h-5 w-5" />
                        </button>
                    </div>
                    <form method="POST" action="{{ route('devices.user-room.update', $device) }}" class="device-user-room-modal-form">
                        @csrf
                        <input type="hidden" name="modal_form" value="device_show_room">
                        <div class="device-user-room-modal-device">
                            <div>
                                <div class="device-user-room-modal-label">Pašreizējā telpa</div>
                                <div class="device-user-room-modal-value">{{ $deviceRoomLabel }}</div>
                            </div>
                            <div>
                                <div class="device-user-room-modal-label">Ēka</div>
                                <div class="device-user-room-modal-value">{{ $deviceBuildingLabel }}</div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="device-user-room-modal-label" for="device-show-room-input">Jaunā telpa</label>
                            <x-searchable-select name="room_id" queryName="room_query" :options="$roomOptions"
                                :selected="old('modal_form') === 'device_show_room' ? old('room_id', (string) $device->room_id) : (string) $device->room_id"
                                :query="old('modal_form') === 'device_show_room' ? old('room_query', $deviceRoomLabel) : $deviceRoomLabel"
                                identifier="device-show-room" :prioritize-selected="true"
                                selected-group-label="Pašreizējā telpa" placeholder="Izvēlies telpu"
                                emptyMessage="Neviena telpa neatbilst meklējumam."
                                :error="old('modal_form') === 'device_show_room' ? $errors->first('room_id') : null" />
                            @if (old('modal_form') === 'device_show_room' && $errors->has('room_id'))
                                <div class="text-sm text-rose-600">{{ $errors->first('room_id') }}</div>
                            @endif
                        </div>
                        <div class="device-user-room-modal-actions">
                            <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'device-show-room-modal')">Atcelt</button>
                            <button type="submit" class="btn-search"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                        </div>
                    </form>
                </div>
            </x-modal>
            @if (old('modal_form') === 'device_show_room')
                <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'device-show-room-modal' })));</script>
            @endif
        @endif

        {{-- ═══ VĒSTURE ═══ --}}
        <div class="mt-5 grid gap-4 xl:grid-cols-12">

            {{-- Remonta pieteikumi --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-6">
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    <x-icon name="repair-request" size="h-4 w-4" class="shrink-0 text-sky-500" />
                    <h2 class="text-sm font-semibold text-slate-900">Remonta pieteikumi</h2>
                    @if ($visibleRepairRequests->count() > 0)
                        <span class="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $visibleRepairRequests->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 px-5">
                    @forelse ($visibleRepairRequests as $request)
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-sm font-semibold text-slate-900">{{ $request->responsibleUser?->full_name ?: 'Nav norādīts' }}</span>
                                        <span class="text-xs text-slate-400">{{ $request->created_at?->format('d.m.Y H:i') ?: '' }}</span>
                                    </div>
                                    @if ($request->description)
                                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $request->description }}</p>
                                    @endif
                                    @if ($request->reviewedBy)
                                        <p class="mt-0.5 text-xs text-slate-400">Izskatīja: {{ $request->reviewedBy->full_name }}@if ($request->review_notes) · {{ $request->review_notes }}@endif</p>
                                    @endif
                                </div>
                                <x-status-pill context="request" :value="$request->status" />
                            </div>
                        </div>
                    @empty
                        <p class="py-5 text-center text-sm text-slate-400">Nav remonta pieteikumu.</p>
                    @endforelse
                </div>
            </section>

            {{-- Remonta ieraksti --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-6">
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    <x-icon name="repair" size="h-4 w-4" class="shrink-0 text-amber-500" />
                    <h2 class="text-sm font-semibold text-slate-900">Remonta ieraksti</h2>
                    @if ($visibleRepairs->count() > 0)
                        <span class="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $visibleRepairs->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 px-5">
                    @forelse ($visibleRepairs as $repair)
                        @php
                            $repairExecutorLabel = $repair->executor?->full_name ?: 'Nav izpildītāja';
                            $repairDatesLabel = null;
                            if ($repair->start_date) {
                                $repairDatesLabel = $repair->start_date->format('d.m.Y');
                                if ($repair->end_date) {
                                    $repairDatesLabel .= ' – ' . $repair->end_date->format('d.m.Y');
                                }
                            }
                        @endphp
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-sm font-semibold text-slate-900">Remonts #{{ $repair->id }}</span>
                                        <span class="text-xs text-slate-400">{{ $repair->created_at?->format('d.m.Y') ?: '' }}</span>
                                        <span class="text-xs text-slate-400">{{ $repairTypeLabels[$repair->repair_type] ?? '' }}</span>
                                    </div>
                                    @if ($canManageDevices)
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ $repairExecutorLabel }}
                                            @if ($repairDatesLabel)
                                                · {{ $repairDatesLabel }}
                                            @endif
                                        </p>
                                    @endif
                                    @if ($repair->description)
                                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $repair->description }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <x-status-pill context="repair" :value="$repair->status" />
                                    @if ($canManageDevices)
                                        <a href="{{ route('repairs.index', ['repair_modal' => 'edit', 'modal_repair' => $repair->id]) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600 hover:border-slate-300 hover:text-slate-900">
                                            <x-icon name="repair" size="h-3.5 w-3.5" /><span>Atvērt</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-5 text-center text-sm text-slate-400">Nav remonta ierakstu.</p>
                    @endforelse
                </div>
            </section>

            {{-- Norakstīšanas pieteikumi --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-6">
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    <x-icon name="writeoff" size="h-4 w-4" class="shrink-0 text-rose-500" />
                    <h2 class="text-sm font-semibold text-slate-900">Norakstīšanas pieteikumi</h2>
                    @if ($visibleWriteoffRequests->count() > 0)
                        <span class="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $visibleWriteoffRequests->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 px-5">
                    @forelse ($visibleWriteoffRequests as $request)
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-sm font-semibold text-slate-900">{{ $request->responsibleUser?->full_name ?: 'Nav norādīts' }}</span>
                                        <span class="text-xs text-slate-400">{{ $request->created_at?->format('d.m.Y H:i') ?: '' }}</span>
                                    </div>
                                    @if ($request->reason)
                                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $request->reason }}</p>
                                    @endif
                                    @if ($request->reviewedBy)
                                        <p class="mt-0.5 text-xs text-slate-400">Izskatīja: {{ $request->reviewedBy->full_name }}@if ($request->review_notes) · {{ $request->review_notes }}@endif</p>
                                    @endif
                                </div>
                                <x-status-pill context="request" :value="$request->status" />
                            </div>
                        </div>
                    @empty
                        <p class="py-5 text-center text-sm text-slate-400">Nav norakstīšanas pieteikumu.</p>
                    @endforelse
                </div>
            </section>

            {{-- Nodošanas vēsture --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-6">
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    <x-icon name="transfer" size="h-4 w-4" class="shrink-0 text-emerald-500" />
                    <h2 class="text-sm font-semibold text-slate-900">Nodošanas vēsture</h2>
                    @if ($visibleTransfers->count() > 0)
                        <span class="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $visibleTransfers->count() }}</span>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 px-5">
                    @forelse ($visibleTransfers as $transfer)
                        @php
                            $isIncomingPendingTransfer = $usesUserDeviceView
                                && (int) auth()->id() === (int) $transfer->transfered_to_id
                                && $transfer->status === 'submitted';
                            $isOriginTransfer = $hasTransferOrigin
                                && (int) ($latestTransferToCurrentUser?->id ?? 0) === (int) $transfer->id;
                            $transferStatusLabel = $isIncomingPendingTransfer ? 'Ienākošs' : null;
                        @endphp
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-sm font-semibold text-slate-900">{{ $transfer->responsibleUser?->full_name ?: '—' }}</span>
                                        <span class="text-xs text-slate-400">→</span>
                                        <span class="text-sm text-slate-700">{{ $transfer->transferTo?->full_name ?: '—' }}</span>
                                        <span class="text-xs text-slate-400">{{ $transfer->created_at?->format('d.m.Y') ?: '' }}</span>
                                        @if ($isOriginTransfer)
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Nonāca pie tevis</span>
                                        @endif
                                    </div>
                                    @if ($transfer->transfer_reason)
                                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $transfer->transfer_reason }}</p>
                                    @endif
                                    @if ($transfer->reviewedBy)
                                        <p class="mt-0.5 text-xs text-slate-400">Izskatīja: {{ $transfer->reviewedBy->full_name }}</p>
                                    @endif
                                </div>
                                <x-status-pill
                                    context="request"
                                    :value="$transfer->status"
                                    :label="$transferStatusLabel"
                                    :pending-suffix="$usesUserDeviceView && ! $isIncomingPendingTransfer ? null : false"
                                    :pending-action="$usesUserDeviceView && ! $isIncomingPendingTransfer && $transfer->status === 'submitted'"
                                    class="{{ $isIncomingPendingTransfer ? 'status-pill-incoming' : '' }}"
                                />
                            </div>
                        </div>
                    @empty
                        <p class="py-5 text-center text-sm text-slate-400">Nav nodošanas ierakstu.</p>
                    @endforelse
                </div>
            </section>

        </div>
    </section>
</x-app-layout>
