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
                    const manufacturer = this.normalizeQueryPart(this.$refs.manufacturer.value);
                    const model = this.normalizeQueryPart(this.$refs.model.value);
                    const query = `${manufacturer} ${model}`.trim();
                    const queries = [...new Set([
                        query,
                        `${query} photo`.trim(),
                        `${query} product`.trim(),
                        `${query} device`.trim(),
                    ].filter(Boolean))];
                    const perBatch = 3;
                    const needed = batch * perBatch;
                    const pageSize = Math.max(8, needed * 2);
                    const tasks = [];

                    for (const currentQuery of queries) {
                        tasks.push(this.fetchOpenverseImages(currentQuery, batch, pageSize));
                        tasks.push(this.fetchCommonsImages(currentQuery, batch, pageSize));
                        tasks.push(this.fetchWikipediaImages(currentQuery, batch, pageSize));
                    }

                    const settled = await Promise.allSettled(tasks);
                    const candidates = settled
                        .filter(result => result.status === 'fulfilled')
                        .flatMap(result => Array.isArray(result.value) ? result.value : [])
                        .map(candidate => ({
                            ...candidate,
                            score: this.scoreCandidate(candidate, manufacturer, model),
                        }))
                        .filter(candidate => candidate.score > 0);

                    const ranked = this.uniqueCandidates(
                        candidates.sort((a, b) => b.score - a.score)
                    );
                    const combined = this.uniqueCandidates([
                        ...ranked,
                        ...this.fallbackImageCandidates(query, needed + 3),
                    ]);

                    return combined.slice((batch - 1) * perBatch, batch * perBatch);
                },
                async fetchOpenverseImages(query, batch, pageSize) {
                    const url = new URL('https://api.openverse.org/v1/images/');
                    url.search = new URLSearchParams({
                        q: query,
                        page: String(batch),
                        page_size: String(pageSize),
                        mature: 'false',
                    }).toString();

                    const data = await this.fetchJson(url.toString());
                    const results = Array.isArray(data?.results) ? data.results : [];

                    return results
                        .map(item => ({
                            preview_url: item?.thumbnail || item?.url || '',
                            image_url: item?.url || item?.thumbnail || '',
                            source: 'openverse',
                            label: item?.title || 'Openverse',
                        }))
                        .filter(candidate => this.isAllowedImageUrl(candidate.preview_url) && !this.looksLikeNonPhoto(candidate.label || ''));
                },
                async fetchCommonsImages(query, batch, pageSize) {
                    const url = new URL('https://commons.wikimedia.org/w/api.php');
                    url.search = new URLSearchParams({
                        action: 'query',
                        format: 'json',
                        origin: '*',
                        generator: 'search',
                        gsrsearch: query,
                        gsrnamespace: '6',
                        gsrlimit: String(pageSize),
                        gsroffset: String((batch - 1) * pageSize),
                        prop: 'imageinfo',
                        iiprop: 'url',
                        iiurlwidth: '1200',
                    }).toString();

                    const data = await this.fetchJson(url.toString());
                    const pages = Object.values(data?.query?.pages || {});

                    return pages
                        .map(page => ({
                            preview_url: page?.imageinfo?.[0]?.thumburl || page?.imageinfo?.[0]?.url || '',
                            image_url: page?.imageinfo?.[0]?.url || page?.imageinfo?.[0]?.thumburl || '',
                            source: 'commons',
                            label: page?.title || 'Wikimedia Commons',
                        }))
                        .filter(candidate => this.isAllowedImageUrl(candidate.preview_url) && !this.looksLikeNonPhoto(candidate.label || ''));
                },
                async fetchWikipediaImages(query, batch, pageSize) {
                    const url = new URL('https://en.wikipedia.org/w/api.php');
                    url.search = new URLSearchParams({
                        action: 'query',
                        format: 'json',
                        origin: '*',
                        generator: 'search',
                        gsrsearch: query,
                        gsrlimit: String(pageSize),
                        gsroffset: String((batch - 1) * pageSize),
                        prop: 'pageimages|extracts',
                        piprop: 'original|thumbnail',
                        pithumbsize: '1200',
                        exintro: '1',
                        explaintext: '1',
                    }).toString();

                    const data = await this.fetchJson(url.toString());
                    const pages = Object.values(data?.query?.pages || {});

                    return pages
                        .map(page => ({
                            preview_url: page?.thumbnail?.source || page?.original?.source || '',
                            image_url: page?.original?.source || page?.thumbnail?.source || '',
                            source: 'wikipedia',
                            label: `${page?.title || ''} ${page?.extract || ''}`.trim() || 'Wikipedia',
                        }))
                        .filter(candidate => this.isAllowedImageUrl(candidate.preview_url) && !this.looksLikeNonPhoto(candidate.label || ''));
                },
                fallbackImageCandidates(query, count) {
                    return Array.from({ length: count }, (_, index) => {
                        const url = `https://source.unsplash.com/1600x900/?${encodeURIComponent(`${query} device`)}&sig=${index + 1}`;

                        return {
                            preview_url: url,
                            image_url: url,
                            source: 'fallback',
                            label: 'Fallback',
                            score: -100,
                        };
                    });
                },
                async fetchJson(url) {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 6000);

                    try {
                        const response = await fetch(url, {
                            headers: {
                                Accept: 'application/json',
                            },
                            signal: controller.signal,
                        });

                        if (!response.ok) {
                            return null;
                        }

                        return await response.json();
                    } catch (error) {
                        return null;
                    } finally {
                        clearTimeout(timeoutId);
                    }
                },
                normalizeQueryPart(value) {
                    return String(value || '').replace(/\s+/g, ' ').trim();
                },
                tokenize(value) {
                    return [...new Set(
                        String(value || '')
                            .toLowerCase()
                            .split(/[^a-z0-9]+/i)
                            .filter(Boolean)
                    )];
                },
                scoreCandidate(candidate, manufacturer, model) {
                    const haystack = `${candidate?.label || ''} ${candidate?.image_url || ''} ${candidate?.preview_url || ''}`.toLowerCase();
                    if (!haystack) {
                        return 0;
                    }

                    const bonuses = {
                        openverse: 30,
                        commons: 18,
                        wikipedia: 10,
                        fallback: -120,
                    };

                    let score = bonuses[candidate?.source] ?? 0;
                    const normalizedManufacturer = manufacturer.toLowerCase();
                    const normalizedModel = model.toLowerCase();

                    if (normalizedManufacturer && haystack.includes(normalizedManufacturer)) {
                        score += 80;
                    }

                    if (normalizedModel && haystack.includes(normalizedModel)) {
                        score += 140;
                    }

                    for (const token of this.tokenize(manufacturer)) {
                        if (token.length >= 2 && haystack.includes(token)) {
                            score += 20;
                        }
                    }

                    for (const token of this.tokenize(model)) {
                        if (token.length >= 2 && haystack.includes(token)) {
                            score += /^\d+$/.test(token) ? 18 : 30;
                        }
                    }

                    if (normalizedManufacturer && normalizedModel && haystack.includes(normalizedManufacturer) && haystack.includes(normalizedModel)) {
                        score += 120;
                    }

                    if (this.looksLikeNonPhoto(haystack)) {
                        score -= 160;
                    }

                    return score;
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
                                            class="crud-btn-secondary"
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
                                    class="crud-btn-secondary"
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
                                        <img :src="candidate.preview_url || candidate.image_url" alt="Atrasts attels" class="h-32 w-full object-cover" loading="lazy" referrerpolicy="no-referrer" x-on:error="removeBrokenCandidate(candidate.image_url)">
                                        <div class="px-3 py-2 text-xs font-medium text-slate-600">
                                            <span x-text="candidate.label || 'Izveleties so attelu'"></span>
                                            <span class="ml-1 text-slate-400" x-show="candidate.source" x-text="`(${candidate.source})`"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div class="device-upload-preview">
                                <template x-if="devicePreview">
                                    <img :src="devicePreview" alt="Ierices foto preview" loading="lazy" referrerpolicy="no-referrer" x-on:error="clearDeviceImage()">
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
