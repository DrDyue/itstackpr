@php
    $hideWrittenOffDevices = old('hide_written_off_devices', $user->prefersHiddenWrittenOffDevices());
@endphp

<section>
    <header class="border-b border-slate-200 pb-4">
        <h2 class="text-xl font-semibold text-slate-900">Iestatījumi</h2>
        <p class="mt-2 text-sm text-slate-600">Personīgi admina darba vides iestatījumi, kas saglabājas arī nākamajās pieslēgšanās reizēs.</p>
    </header>

    <div class="mt-6 rounded-[1.8rem] border border-slate-200 bg-[linear-gradient(135deg,rgba(240,249,255,0.95),rgba(255,255,255,0.98))] p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-700 ring-1 ring-sky-200">
                    <x-icon name="settings" size="h-4 w-4" />
                    <span>Admina skats</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Darba vides iestatījumi</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Atver iestatījumu izvēlni, lai pielāgotu, kuras ierīces rādīt dashboardā un ierīču tabulās.
                </p>
            </div>

            <button type="button" class="btn-edit" x-data @click="$dispatch('open-modal', 'profile-settings-modal')">
                <x-icon name="settings" size="h-4 w-4" />
                <span>Iestatījumi</span>
            </button>
        </div>

        <div class="mt-5 rounded-[1.4rem] border border-slate-200 bg-white/90 px-4 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Paslēpt norakstītās ierīces</div>
                    <div class="mt-1 text-xs text-slate-500">
                        Statuss: {{ $user->prefersHiddenWrittenOffDevices() ? 'ieslēgts' : 'izslēgts' }}
                    </div>
                </div>

                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $user->prefersHiddenWrittenOffDevices() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' }}">
                    {{ $user->prefersHiddenWrittenOffDevices() ? 'Aktīvs filtrs' : 'Redz visas ierīces' }}
                </span>
            </div>
        </div>
    </div>
</section>

<x-modal name="profile-settings-modal" maxWidth="2xl">
    <div class="device-user-room-modal-shell">
        <div class="device-user-room-modal-head">
            <div>
                <div class="device-user-room-modal-badge">Iestatījumi</div>
                <h2 class="device-user-room-modal-title">Admina darba vides iestatījumi</h2>
                <p class="device-user-room-modal-copy">Šie iestatījumi saglabājas tieši tavam kontam un tiek izmantoti arī pēc nākamās pieslēgšanās.</p>
            </div>

            <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'profile-settings-modal')" aria-label="Aizvērt">
                <x-icon name="x-mark" size="h-5 w-5" />
            </button>
        </div>

        <form method="post" action="{{ route('profile.settings.update') }}" class="device-user-room-modal-form">
            @csrf
            @method('patch')

            <input type="hidden" name="hide_written_off_devices" value="0">

            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 sm:p-5">
                <label for="profile-hide-written-off-devices" class="flex cursor-pointer items-start justify-between gap-4">
                    <div class="pr-2">
                        <div class="text-sm font-semibold text-slate-900">Paslēpt norakstītās ierīces</div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Ja ieslēgts, dashboardā un ierīču tabulās netiks rādītas norakstītās ierīces. Vecajos remonta, pieteikumu un nodošanas ierakstos tās joprojām paliek redzamas.
                        </p>
                    </div>

                    <span class="relative mt-1 inline-flex shrink-0 items-center">
                        <input
                            id="profile-hide-written-off-devices"
                            type="checkbox"
                            name="hide_written_off_devices"
                            value="1"
                            class="peer sr-only"
                            @checked((bool) $hideWrittenOffDevices)
                        >
                        <span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-sky-500"></span>
                        <span class="pointer-events-none absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
                    </span>
                </label>

                <x-input-error class="mt-3" :messages="$errors->profileSettings->get('hide_written_off_devices')" />
            </div>

            <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                <div class="font-semibold">Pēc noklusējuma šis iestatījums ir izslēgts.</div>
                <p class="mt-1">
                    Tas ļauj adminam redzēt pilnu inventāru. Iestatījumu vari ieslēgt, ja ikdienas darbā gribi paslēpt jau norakstītās ierīces no darba virsmas un ierīču saraksta.
                </p>
            </div>

            <div class="device-user-room-modal-actions">
                <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'profile-settings-modal')">Atcelt</button>
                <button type="submit" class="btn-search">
                    <x-icon name="save" size="h-4 w-4" />
                    <span>Saglabāt iestatījumus</span>
                </button>
            </div>
        </form>
    </div>
</x-modal>

@if ($errors->profileSettings->isNotEmpty())
    <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-settings-modal' })));</script>
@endif
