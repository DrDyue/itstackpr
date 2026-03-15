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
                <h1 class="device-page-title">Jauna ierice</h1>
                <p class="device-page-subtitle">Sakartota ievade ar skaidru sadalijumu pa blokiem.</p>
            </div>
            <a href="{{ route('devices.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakal uz sarakstu</a>
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
            action="{{ route('devices.store') }}"
            enctype="multipart/form-data"
            class="device-form-grid"
            x-data="{
                devicePreview: @js(old('auto_device_image_url', '')),
                deviceCandidates: @js(old('auto_device_image_url') ? [[
                    'preview_url' => old('auto_device_image_url'),
                    'image_url' => old('auto_device_image_url'),
                    'source' => 'saved',
                    'label' => 'Saglabata izvele',
                ]] : []),
                warrantyPreview: null,
                autoDeviceImageUrl: @js(old('auto_device_image_url', '')),
                removeDeviceImage: false,
                isFindingDeviceImage: false,
                deviceImageError: '',
                deviceImageBatch: 1,
                remotePreviewBase: @js(route('device-assets.remote-preview')),
                get canSearchDeviceImage() {
                    return !!(
                        this.$refs.model?.value.trim()
                        && this.$refs.manufacturer?.value.trim()
                    );
                },
                async findDeviceImage(batch = 1) {
                    if (! this.canSearchDeviceImage) {
                        this.deviceImageError = 'Lai mekletu attelu, aizpildi modeli un razotaju.';
                        return;
                    }

                    this.isFindingDeviceImage = true;
                    this.deviceImageError = '';
                    this.deviceImageBatch = batch;

                    try {
                        const images = await this.searchDeviceCandidates(batch);

                        if (! images.length) {
                            this.deviceCandidates = [];
                            this.deviceImageError = 'Attelus interneta neizdevas atrast. Precize modeli vai razotaju un meginiet velreiz.';
                            return;
                        }

                        this.deviceCandidates = images;
                        this.selectDeviceImage(images[0]);
                    } catch (error) {
                        this.deviceCandidates = [];
                        this.deviceImageError = 'Neizdevas atrast attelus interneta.';
                    } finally {
                        this.isFindingDeviceImage = false;
                    }
                },
                async searchDeviceCandidates(batch) {
                    const response = await fetch(@js(route('devices.preview-auto-image')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({
                            model: this.$refs.model.value,
                            manufacturer: this.$refs.manufacturer.value,
                            batch: batch,
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    const images = Array.isArray(payload.images) ? payload.images : [];

                    return this.uniqueCandidates(images).map(candidate => ({
                        ...candidate,
                        label: this.truncateLabel(candidate?.label || 'Izveleties so attelu'),
                    }));
                },
                truncateLabel(label) {
                    const text = String(label || '').replace(/\s+/g, ' ').trim();
                    return text.length > 72 ? `${text.slice(0, 69)}...` : text;
                },
                uniqueCandidates(candidates) {
                    const seen = new Set();

                    return candidates.filter(candidate => {
                        const key = candidate?.image_url || candidate?.preview_url;
                        if (!key || seen.has(key)) {
                            return false;
                        }

                        seen.add(key);
                        return this.isAllowedImageUrl(candidate.preview_url || candidate.image_url);
                    });
                },
                proxyImageUrl(url) {
                    const normalized = String(url || '').trim();

                    if (!normalized) {
                        return '';
                    }

                    if (
                        normalized.startsWith('blob:')
                        || normalized.startsWith('data:')
                        || normalized.startsWith('/')
                    ) {
                        return normalized;
                    }

                    if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
                        return `${this.remotePreviewBase}?url=${encodeURIComponent(normalized)}`;
                    }

                    return normalized;
                },
                isAllowedImageUrl(url) {
                    const normalized = String(url || '').toLowerCase();
                    if (!normalized.startsWith('http://') && !normalized.startsWith('https://')) {
                        return false;
                    }

                    return !['.svg', '.tif', '.tiff', '.pdf', '.djvu'].some(ext => normalized.includes(ext));
                },
                looksLikeNonPhoto(text) {
                    const normalized = String(text || '').toLowerCase();
                    return ['logo', 'icon', 'vector', 'wordmark', 'symbol', 'coat of arms'].some(token => normalized.includes(token));
                },
                removeBrokenCandidate(imageUrl) {
                    this.deviceCandidates = this.deviceCandidates.filter(candidate => candidate.image_url !== imageUrl && candidate.preview_url !== imageUrl);

                    if (this.autoDeviceImageUrl === imageUrl || this.devicePreview === imageUrl) {
                        const replacement = this.deviceCandidates[0] || null;
                        this.devicePreview = replacement?.preview_url || replacement?.image_url || '';
                        this.autoDeviceImageUrl = replacement?.image_url || '';
                    }

                    if (!this.deviceCandidates.length && !this.devicePreview) {
                        this.deviceImageError = 'Atrastie atteli neieladejas. Megini velreiz ar citu partiju.';
                    }
                },
                selectDeviceImage(candidate) {
                    this.devicePreview = candidate?.preview_url || candidate?.image_url || '';
                    this.autoDeviceImageUrl = candidate?.image_url || '';
                    this.removeDeviceImage = false;
                    this.$refs.deviceImageInput.value = '';
                },
                clearDeviceImage() {
                    this.devicePreview = null;
                    this.deviceCandidates = [];
                    this.autoDeviceImageUrl = '';
                    this.removeDeviceImage = false;
                    this.deviceImageError = '';
                    this.$refs.deviceImageInput.value = '';
                }
            }"
        >
            @csrf
            <input type="hidden" name="auto_device_image_url" x-model="autoDeviceImageUrl">
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
                            <input type="text" name="code" value="{{ old('code') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Nosaukums *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="crud-control" x-ref="name">
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-4">
                        <div>
                            <label class="crud-label">Tips *</label>
                            <select name="device_type_id" required class="crud-control" x-ref="deviceType">
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('device_type_id') == $type->id)>{{ $type->type_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Modelis *</label>
                            <input type="text" name="model" value="{{ old('model') }}" required class="crud-control" x-ref="model">
                        </div>
                        <div>
                            <label class="crud-label">Razotajs</label>
                            <input type="text" name="manufacturer" value="{{ old('manufacturer') }}" class="crud-control" x-ref="manufacturer">
                        </div>
                        <div>
                            <label class="crud-label">Statuss *</label>
                            <select name="status" required class="crud-control">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
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
                                    <option value="{{ $building->id }}" @selected(old('building_id') == $building->id)>{{ $building->building_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Telpa</label>
                            <select name="room_id" class="crud-control">
                                <option value="">Nav</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Pieskirta personai</label>
                        <input type="text" name="assigned_to" value="{{ old('assigned_to') }}" class="crud-control">
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
                        <input type="text" name="serial_number" value="{{ old('serial_number') }}" class="crud-control">
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="crud-label">Pirkuma datums *</label>
                            <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" required class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Cena</label>
                            <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Garantija lidz</label>
                            <input type="date" name="warranty_until" value="{{ old('warranty_until') }}" class="crud-control">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="crud-label">Piezimes</label>
                        <textarea name="notes" rows="4" class="crud-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="device-action-bar">
                    <div class="device-action-title">Darbibas</div>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary">Saglabat</button>
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
                            <div class="device-form-section-note">Atteli glabajas uz servera diska un tiek optimizeti.</div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="device-upload-box">
                            <label class="crud-label">Ierices foto</label>
                            <input type="file" name="device_image" accept="image/*" class="crud-control" x-ref="deviceImageInput" @change="devicePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; autoDeviceImageUrl = ''; removeDeviceImage = false; deviceImageError = ''">
                            <p class="mt-2 text-xs text-gray-500">JPG, PNG vai WEBP. Augsuplade tiks optimizeta un glabata uz servera diska.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <div class="relative" x-data="{ open: false }">
                                    <span @mouseenter="if (!canSearchDeviceImage) open = true" @mouseleave="open = false" @focusin="if (!canSearchDeviceImage) open = true" @focusout="open = false" class="inline-flex">
                                        <button
                                            type="button"
                                            class="crud-btn-neutral"
                                            @click="findDeviceImage(1)"
                                            :disabled="isFindingDeviceImage || !canSearchDeviceImage"
                                        >
                                    <span x-text="isFindingDeviceImage ? 'Mekle...' : 'Atrast attelu interneta'"></span>
                                        </button>
                                    </span>
                                    <div
                                        x-cloak
                                        x-show="open"
                                        x-transition.opacity
                                        class="absolute left-0 top-full z-20 mt-2 w-72 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 shadow-lg"
                                    >
                                        Si poga nav pieejama, kamer nav aizpilditi lauki: modelis un razotajs.
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="crud-btn-neutral"
                                    @click="findDeviceImage(deviceImageBatch + 1)"
                                    x-show="deviceCandidates.length"
                                    x-cloak
                                    :disabled="isFindingDeviceImage || !canSearchDeviceImage"
                                >
                                    Mainit attelus
                                </button>
                                <button type="button" class="crud-btn-secondary" @click="clearDeviceImage()" x-show="devicePreview" x-cloak>
                                    Notirit attelu
                                </button>
                            </div>
                            <template x-if="deviceImageError">
                                <p class="mt-2 text-xs text-rose-600" x-text="deviceImageError"></p>
                            </template>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3" x-show="deviceCandidates.length" x-cloak>
                                <template x-for="candidate in deviceCandidates" :key="candidate.image_url">
                                    <button
                                        type="button"
                                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-left transition hover:border-sky-300 hover:shadow-sm"
                                        @click="selectDeviceImage(candidate)"
                                        :class="autoDeviceImageUrl === candidate.image_url ? 'ring-2 ring-sky-500 border-sky-400' : ''"
                                    >
                                        <img :src="proxyImageUrl(candidate.preview_url || candidate.image_url)" alt="Atrasts attels" class="h-32 w-full object-cover" loading="lazy" x-on:error="removeBrokenCandidate(candidate.image_url)">
                                        <div class="px-3 py-2 text-xs font-medium text-slate-600">
                                            <span class="device-image-candidate-label" x-text="candidate.label || 'Izveleties so attelu'"></span>
                                            <span class="ml-1 text-slate-400" x-show="candidate.source" x-text="`(${candidate.source})`"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div class="device-upload-preview">
                                <template x-if="devicePreview">
                                    <img :src="proxyImageUrl(devicePreview)" alt="Ierices foto preview" loading="lazy" x-on:error="clearDeviceImage()">
                                </template>
                                <template x-if="!devicePreview">
                                    <div class="device-upload-preview-empty">Preview paradisies seit</div>
                                </template>
                            </div>
                        </div>
                        <div class="device-upload-box">
                            <label class="crud-label">Garantijas attels</label>
                            <input type="file" name="warranty_image" accept="image/*" class="crud-control" @change="warrantyPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                            <p class="mt-2 text-xs text-gray-500">Pievieno garantijas foto vai skenu, lai to var redzet detalas skatā.</p>
                            <div class="device-upload-preview">
                                <template x-if="warrantyPreview">
                                    <img :src="warrantyPreview" alt="Garantijas preview">
                                </template>
                                <template x-if="!warrantyPreview">
                                    <div class="device-upload-preview-empty">Preview paradisies seit</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
