<x-app-layout>
    <section class="app-shell max-w-6xl" x-data="{ requestType: @js(old('request_type', $selectedType)) }">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Jauns pieteikums</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="repair-request" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Izveidot pieteikumu</h1>
                            <p class="page-subtitle">
                                Izvelies vajadzigo pieteikuma tipu, un forma pielagosies izveletajai darbibai.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="{{ route('my-requests.index') }}" class="btn-back">
                        <x-icon name="back" size="h-4 w-4" />
                        <span>Atpakal</span>
                    </a>
                </div>
            </div>
        </div>

        <section class="surface-card p-6">
            <div class="grid gap-3 md:grid-cols-3">
                <button type="button" class="rounded-[1.4rem] border px-4 py-4 text-left transition" :class="requestType === 'repair' ? 'border-sky-300 bg-sky-50 text-sky-900 shadow-[0_16px_36px_-24px_rgba(14,165,233,0.55)]' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" @click="requestType = 'repair'">
                    <div class="text-sm font-semibold">Remonta pieteikums</div>
                    <div class="mt-1 text-xs text-slate-500">Piesaki problemas ar savu ierici.</div>
                </button>
                <button type="button" class="rounded-[1.4rem] border px-4 py-4 text-left transition" :class="requestType === 'writeoff' ? 'border-rose-300 bg-rose-50 text-rose-900 shadow-[0_16px_36px_-24px_rgba(244,63,94,0.45)]' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" @click="requestType = 'writeoff'">
                    <div class="text-sm font-semibold">Norakstisanas pieteikums</div>
                    <div class="mt-1 text-xs text-slate-500">Piesaki ierici norakstisanai.</div>
                </button>
                <button type="button" class="rounded-[1.4rem] border px-4 py-4 text-left transition" :class="requestType === 'transfer' ? 'border-emerald-300 bg-emerald-50 text-emerald-900 shadow-[0_16px_36px_-24px_rgba(16,185,129,0.45)]' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" @click="requestType = 'transfer'">
                    <div class="text-sm font-semibold">Nodosanas pieteikums</div>
                    <div class="mt-1 text-xs text-slate-500">Piedava ierici citam lietotajam.</div>
                </button>
            </div>
        </section>

        <form method="POST" action="{{ route('my-requests.store') }}" class="mt-6 space-y-6">
            @csrf
            <input type="hidden" name="request_type" :value="requestType">

            @if ($deviceOptions->isEmpty())
                <section class="surface-card p-6">
                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                        Tev paslaik nav aktivu iericu, kuram var izveidot jaunu pieteikumu.
                    </div>
                </section>
            @endif

            <section class="surface-card p-6">
                <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <div class="mb-2 text-sm font-medium text-slate-700">Ierice</div>
                        <x-searchable-select
                            name="device_id"
                            queryName="device_query"
                            :options="$deviceOptions"
                            :selected="old('device_id', (string) $selectedDeviceId)"
                            :query="''"
                            identifier="my-request-device"
                            placeholder="Izvelies ierici"
                        />
                        @error('device_id')
                            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <div class="font-semibold text-slate-900">Kas bus talak?</div>
                        <div class="mt-2" x-show="requestType === 'repair'">Pieteikums nonaks administratoram, kurs var to apstiprinat un izveidot remonta ierakstu.</div>
                        <div class="mt-2" x-show="requestType === 'writeoff'">Administrators izvertes norakstisanas pamatojumu un pienems lemumu.</div>
                        <div class="mt-2" x-show="requestType === 'transfer'">Izveletais lietotajs sanems pieprasijumu apstiprinat vai noraidit ierices sanemsanu.</div>
                    </div>
                </div>
            </section>

            <section class="surface-card p-6 space-y-5">
                <div x-show="requestType === 'repair'" x-cloak>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Problemas apraksts</span>
                        <textarea name="description" rows="6" class="crud-control" placeholder="Apraksti, kas nedarbojas, kad problema sakas un kas jau ir pameginats.">{{ old('description') }}</textarea>
                    </label>
                    @error('description')
                        <div class="text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div x-show="requestType === 'writeoff'" x-cloak>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Norakstisanas iemesls</span>
                        <textarea name="reason" rows="6" class="crud-control" placeholder="Apraksti, kapec ierici vairs nav lietderigi izmantot.">{{ old('reason') }}</textarea>
                    </label>
                    @error('reason')
                        <div class="text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div x-show="requestType === 'transfer'" x-cloak class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                    <div>
                        <div class="mb-2 text-sm font-medium text-slate-700">Kam nodot ierici</div>
                        <x-searchable-select
                            name="transfered_to_id"
                            queryName="recipient_query"
                            :options="$recipientOptions"
                            :selected="old('transfered_to_id')"
                            :query="''"
                            identifier="my-request-recipient"
                            placeholder="Izvelies lietotaju"
                        />
                        @error('transfered_to_id')
                            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-slate-700">Nodosanas iemesls</span>
                            <textarea name="transfer_reason" rows="6" class="crud-control" placeholder="Apraksti, kapec ierici vajadzetu nodot citam lietotajam.">{{ old('transfer_reason') }}</textarea>
                        </label>
                        @error('transfer_reason')
                            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-create disabled:cursor-not-allowed disabled:opacity-60" @disabled($deviceOptions->isEmpty())>
                    <x-icon name="plus" size="h-4 w-4" />
                    <span>Saglabat pieteikumu</span>
                </button>
                <a href="{{ route('my-requests.index') }}" class="btn-clear">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
