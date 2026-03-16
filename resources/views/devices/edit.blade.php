<x-app-layout>
    @php
        $statusLabels = [
            'active' => 'Aktiva',
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'retired' => 'Norakstita',
            'kitting' => 'Komplektacija',
        ];
    @endphp

    <section class="device-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Rediget ierici</h1>
                <p class="device-page-subtitle">Parkartota struktura ar akcentiem un ātrām darbībām augšpusē.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('devices.show', $device) }}" class="text-sm font-medium text-slate-600 hover:text-slate-800">Skatit detalas</a>
                <a href="{{ route('devices.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakal uz sarakstu</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('devices.update', $device) }}"
            enctype="multipart/form-data"
            class="device-form-grid"
            x-data="{
                devicePreview: @js($device->deviceImageUrl() ?? ''),
                warrantyPreview: @js($device->warrantyImageUrl() ?? ''),
                deviceImageName: 'Nav atlasits fails',
                warrantyImageName: 'Nav atlasits fails',
                removeDeviceImage: @js(old('remove_device_image') === '1'),
                clearDeviceImage() {
                    this.devicePreview = null;
                    this.deviceImageName = 'Nav atlasits fails';
                    this.removeDeviceImage = true;
                    this.$refs.deviceImageInput.value = '';
                }
            }"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="remove_device_image" :value="removeDeviceImage ? '1' : '0'">

            <div class="space-y-6">
                <div class="device-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-sky-100 text-sky-700 ring-sky-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75h4.5m-7.5 3h10.5m-12 4.5h13.5m-15 4.5h16.5M6 20.25h12"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">Pamata dati</div>
                            <div class="device-form-section-note">Nosaukums, kods, tips, modelis un statuss.</div>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Kods</label>
                            <input type="text" name="code" value="{{ old('code', $device->code) }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Nosaukums *</label>
                            <input type="text" name="name" value="{{ old('name', $device->name) }}" required class="crud-control" x-ref="name">
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-4">
                        <div>
                            <label class="crud-label">Tips *</label>
                            <select name="device_type_id" required class="crud-control" x-ref="deviceType">
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('device_type_id', $device->device_type_id) == $type->id)>{{ $type->type_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Modelis *</label>
                            <input type="text" name="model" value="{{ old('model', $device->model) }}" required class="crud-control" x-ref="model">
                        </div>
                        <div>
                            <label class="crud-label">Razotajs</label>
                            <input type="text" name="manufacturer" value="{{ old('manufacturer', $device->manufacturer) }}" class="crud-control" x-ref="manufacturer">
                        </div>
                        <div>
                            <label class="crud-label">Statuss *</label>
                            <select name="status" required class="crud-control">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', $device->status) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="device-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-amber-100 text-amber-700 ring-amber-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5l7.5-3 7.5 3V21M9 9.75h.008v.008H9V9.75Zm0 3.75h.008v.008H9V13.5Zm0 3.75h.008v.008H9v-.008Zm6-7.5h.008v.008H15V9.75Zm0 3.75h.008v.008H15V13.5Zm0 3.75h.008v.008H15v-.008Z"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">Atrasanas vieta</div>
                            <div class="device-form-section-note">Eka, telpa un kam ierice ir pieskirta.</div>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Eka</label>
                            <select name="building_id" class="crud-control">
                                <option value="">Nav</option>
                                @foreach ($buildings as $building)
                                    <option value="{{ $building->id }}" @selected(old('building_id', $device->building_id) == $building->id)>{{ $building->building_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Telpa</label>
                            <select name="room_id" class="crud-control">
                                <option value="">Nav</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}" @selected(old('room_id', $device->room_id) == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Pieskirta personai</label>
                        <input type="text" name="assigned_to" value="{{ old('assigned_to', $device->assigned_to) }}" class="crud-control">
                    </div>
                </div>

                <div class="device-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-emerald-100 text-emerald-700 ring-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12m-12 5.25h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">Iegade un apraksts</div>
                            <div class="device-form-section-note">Serijas numurs, iegades dati un piezimes.</div>
                        </div>
                    </div>
                    <div>
                        <label class="crud-label">Serijas numurs</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}" class="crud-control">
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <x-localized-date-picker
                            name="purchase_date"
                            :value="old('purchase_date', optional($device->purchase_date)->format('Y-m-d'))"
                            label="Pirkuma datums *"
                            label-class="crud-label"
                            required
                        />
                        <div>
                            <label class="crud-label">Cena</label>
                            <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $device->purchase_price) }}" class="crud-control">
                        </div>
                        <x-localized-date-picker
                            name="warranty_until"
                            :value="old('warranty_until', optional($device->warranty_until)->format('Y-m-d'))"
                            label="Garantija lidz"
                            label-class="crud-label"
                        />
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Piezimes</label>
                        <textarea name="notes" rows="4" class="crud-control">{{ old('notes', $device->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="device-action-bar">
                    <div class="device-action-title">Darbibas</div>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary">Atjaunot</button>
                        <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Atcelt</a>
                    </div>
                </div>

                <div class="device-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-violet-100 text-violet-700 ring-violet-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5m-10.5 4.5h10.5m-10.5 4.5h7.5M4.5 5.25h15A1.5 1.5 0 0 1 21 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.25V6.75a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">Faili un atteli</div>
                            <div class="device-form-section-note">Esošie un jaunie atteli ar preview vienuviet.</div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="device-upload-box">
                            <label class="crud-label">Ierices foto</label>
                            <input
                                type="file"
                                name="device_image"
                                accept="image/*"
                                class="sr-only"
                                x-ref="deviceImageInput"
                                @change="devicePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : '{{ $device->deviceImageUrl() ?? '' }}'; deviceImageName = $event.target.files[0] ? $event.target.files[0].name : 'Nav atlasits fails'; removeDeviceImage = false"
                            >
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <button type="button" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100" @click="$refs.deviceImageInput.click()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15"/>
                                    </svg>
                                    Izveleties failu
                                </button>
                                <span class="text-sm text-slate-500" x-text="deviceImageName">Nav atlasits fails</span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Atstaj tuksu, ja negribi nomainit attelu.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="crud-btn-secondary" @click="clearDeviceImage()" x-show="devicePreview" x-cloak>
                                    Nonemt attelu
                                </button>
                            </div>
                            <div class="device-upload-preview">
                                <template x-if="devicePreview">
                                    <img :src="devicePreview" alt="Ierices foto preview" loading="lazy" x-on:error="clearDeviceImage()">
                                </template>
                                <template x-if="!devicePreview">
                                    <div class="device-upload-preview-empty">Foto nav pievienots</div>
                                </template>
                            </div>
                        </div>
                        <div class="device-upload-box">
                            <label class="crud-label">Garantijas attels</label>
                            <input
                                type="file"
                                name="warranty_image"
                                accept="image/*"
                                class="sr-only"
                                x-ref="warrantyImageInput"
                                @change="warrantyPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : '{{ $device->warrantyImageUrl() ?? '' }}'; warrantyImageName = $event.target.files[0] ? $event.target.files[0].name : 'Nav atlasits fails'"
                            >
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <button type="button" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100" @click="$refs.warrantyImageInput.click()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15"/>
                                    </svg>
                                    Izveleties failu
                                </button>
                                <span class="text-sm text-slate-500" x-text="warrantyImageName">Nav atlasits fails</span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Atstaj tuksu, ja negribi nomainit garantijas attelu.</p>
                            <div class="device-upload-preview">
                                <template x-if="warrantyPreview">
                                    <img :src="warrantyPreview" alt="Garantijas preview">
                                </template>
                                <template x-if="!warrantyPreview">
                                    <div class="device-upload-preview-empty">Garantijas attels nav pievienots</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
