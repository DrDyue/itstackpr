@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
    $hideWrittenOffDevices = old('hide_written_off_devices', $user?->prefersHiddenWrittenOffDevices() ?? false);
    $defaultStartPage = old('default_start_page', $user?->defaultStartPage() ?? \App\Models\User::START_PAGE_DASHBOARD);
    $defaultViewMode = old('default_view_mode', $user?->defaultViewMode() ?? \App\Models\User::VIEW_MODE_ADMIN);
    $defaultDeviceFilter = old('default_device_filter', $user?->defaultDeviceFilter() ?? \App\Models\User::DEVICE_FILTER_ALL);
    $notificationVisualMode = old('notification_visual_mode', $user?->notificationVisualMode() ?? \App\Models\User::NOTIFICATION_VISUAL_ANIMATED);
    $defaultRequestFilter = old('default_request_filter', $user?->defaultRequestFilter() ?? \App\Models\User::REQUEST_FILTER_SUBMITTED);
@endphp

@if ($user)
    <x-modal name="profile-modal" maxWidth="4xl">
        <div
            x-data="managedModalForm({
                modalName: 'profile-modal',
                closeTitle: 'Aizvērt profilu?',
                closeMessage: 'Tev ir nesaglabātas profila izmaiņas. Vai tiešām aizvērt profila logu?',
            })"
            @modal-opened.window="handleOpened($event)"
            @modal-before-close.window="confirmClose($event)"
            class="device-user-room-modal-shell"
        >
            <div class="device-user-room-modal-head">
                <div>
                    <div class="device-user-room-modal-badge">Profils</div>
                    <h2 class="device-user-room-modal-title">Mans profils</h2>
                    <p class="device-user-room-modal-copy">Atjauno savu kontaktinformāciju un konta datus vienā vietā. Paroles maiņa tiek atvērta atsevišķā drošības logā.</p>
                </div>
                <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'profile-modal')" aria-label="Aizvērt">
                    <x-icon name="x-mark" size="h-5 w-5" />
                </button>
            </div>

            <div class="device-user-room-modal-form space-y-5">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="device-user-room-modal-device md:col-span-2">
                        <div>
                            <div class="device-user-room-modal-label">Lietotājs</div>
                            <div class="device-user-room-modal-value">{{ $user->full_name }}</div>
                        </div>
                        <div>
                            <div class="device-user-room-modal-label">E-pasts</div>
                            <div class="device-user-room-modal-value">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="device-user-room-modal-device">
                        <div>
                            <div class="device-user-room-modal-label">Loma</div>
                            <div class="device-user-room-modal-value">{{ $user->role }}</div>
                        </div>
                        <div>
                            <div class="device-user-room-modal-label">Statuss</div>
                            <div class="device-user-room-modal-value">{{ $user->is_active ? 'Aktīvs' : 'Neaktīvs' }}</div>
                        </div>
                    </div>
                </div>

                <form method="post" action="{{ route('profile.update') }}" class="space-y-6" @input="markDirty()" @change="markDirty()" @submit="handleSubmit()">
                    @csrf
                    @method('patch')

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label for="profile_modal_full_name" value="Vārds un uzvārds" />
                            <x-text-input id="profile_modal_full_name" name="full_name" type="text" class="mt-2 block w-full" :value="old('full_name', $user->full_name)" required autofocus autocomplete="name" x-ref="firstField" />
                            <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
                        </div>

                        <div>
                            <x-input-label for="profile_modal_email" value="E-pasts" />
                            <x-text-input id="profile_modal_email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="profile_modal_phone" value="Tālrunis" />
                            <x-text-input id="profile_modal_phone" name="phone" type="text" class="mt-2 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="profile_modal_job_title" value="Amats" />
                            <x-text-input id="profile_modal_job_title" name="job_title" type="text" class="mt-2 block w-full" :value="old('job_title', $user->job_title)" autocomplete="organization-title" />
                            <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
                        </div>
                    </div>

                    <div class="device-user-room-modal-actions">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('open-modal', 'profile-password-modal')">
                            <x-icon name="key" size="h-4 w-4" />
                            <span>Mainīt paroli</span>
                        </button>
                        <button type="submit" class="btn-search" :disabled="submitting" :class="submitting ? 'opacity-70 cursor-wait' : ''">
                            <x-icon name="save" size="h-4 w-4" />
                            <span x-text="submitting ? 'Saglabā...' : 'Saglabāt'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </x-modal>

    <x-modal name="profile-password-modal" maxWidth="2xl">
        <div
            x-data="managedModalForm({
                modalName: 'profile-password-modal',
                closeTitle: 'Aizvērt paroles maiņu?',
                closeMessage: 'Tev ir nesaglabātas paroles maiņas izmaiņas. Vai tiešām aizvērt šo logu?',
            })"
            @modal-opened.window="handleOpened($event)"
            @modal-before-close.window="confirmClose($event)"
            class="device-user-room-modal-shell"
        >
            <div class="device-user-room-modal-head">
                <div>
                    <div class="device-user-room-modal-badge">Drošība</div>
                    <h2 class="device-user-room-modal-title">Paroles maiņa</h2>
                    <p class="device-user-room-modal-copy">Ievadi pašreizējo paroli un jauno paroli. Šis logs atveras virs profila informācijas.</p>
                </div>
                <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', 'profile-password-modal')" aria-label="Aizvērt">
                    <x-icon name="x-mark" size="h-5 w-5" />
                </button>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="device-user-room-modal-form space-y-5" @input="markDirty()" @change="markDirty()" @submit="handleSubmit()">
                @csrf
                @method('put')

                <div>
                    <x-input-label for="profile_password_current_password" value="Pašreizējā parole" />
                    <x-text-input id="profile_password_current_password" name="current_password" type="password" class="mt-2 block w-full" autocomplete="current-password" x-ref="firstField" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="profile_password_password" value="Jauna parole" />
                    <x-text-input id="profile_password_password" name="password" type="password" class="mt-2 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="profile_password_password_confirmation" value="Atkārtota parole" />
                    <x-text-input id="profile_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="device-user-room-modal-actions">
                    <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'profile-password-modal')">Atcelt</button>
                    <button type="submit" class="btn-search" :disabled="submitting" :class="submitting ? 'opacity-70 cursor-wait' : ''">
                        <x-icon name="save" size="h-4 w-4" />
                        <span x-text="submitting ? 'Saglabā...' : 'Saglabāt paroli'"></span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @if ($isAdmin)
        <x-modal name="profile-settings-modal" maxWidth="4xl">
            <div
                x-data="managedModalForm({
                    modalName: 'profile-settings-modal',
                    closeTitle: 'Aizvērt iestatījumus?',
                    closeMessage: 'Tev ir nesaglabātas iestatījumu izmaiņas. Vai tiešām aizvērt šo logu?',
                })"
                @modal-opened.window="handleOpened($event)"
                @modal-before-close.window="confirmClose($event)"
                class="device-user-room-modal-shell"
            >
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

                <form method="post" action="{{ route('profile.settings.update') }}" class="device-user-room-modal-form space-y-5" @input="markDirty()" @change="markDirty()" @submit="handleSubmit()">
                    @csrf
                    @method('patch')

                    <input type="hidden" name="hide_written_off_devices" value="0">

                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <label for="profile_hide_written_off_devices" class="flex cursor-pointer items-start justify-between gap-4">
                            <div class="pr-2">
                                <div class="text-sm font-semibold text-slate-900">Paslēpt norakstītās ierīces</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    Ja ieslēgts, dashboardā un ierīču tabulās netiks rādītas norakstītās ierīces. Vecajos remonta, pieteikumu un nodošanas ierakstos tās joprojām paliek redzamas.
                                </p>
                            </div>

                            <span class="relative mt-1 inline-flex shrink-0 items-center">
                                <input id="profile_hide_written_off_devices" type="checkbox" name="hide_written_off_devices" value="1" class="peer sr-only" @checked((bool) $hideWrittenOffDevices) x-ref="firstField">
                                <span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-sky-500"></span>
                                <span class="pointer-events-none absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
                            </span>
                        </label>

                        <x-input-error class="mt-3" :messages="$errors->profileSettings->get('hide_written_off_devices')" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="profile_default_start_page" value="Sākuma lapa pēc pieslēgšanās" />
                            <select id="profile_default_start_page" name="default_start_page" class="crud-control mt-2">
                                <option value="{{ \App\Models\User::START_PAGE_DASHBOARD }}" @selected($defaultStartPage === \App\Models\User::START_PAGE_DASHBOARD)>Dashboard</option>
                                <option value="{{ \App\Models\User::START_PAGE_DEVICES }}" @selected($defaultStartPage === \App\Models\User::START_PAGE_DEVICES)>Ierīces</option>
                                <option value="{{ \App\Models\User::START_PAGE_REPAIR_REQUESTS }}" @selected($defaultStartPage === \App\Models\User::START_PAGE_REPAIR_REQUESTS)>Remonta pieteikumi</option>
                                <option value="{{ \App\Models\User::START_PAGE_WRITEOFF_REQUESTS }}" @selected($defaultStartPage === \App\Models\User::START_PAGE_WRITEOFF_REQUESTS)>Norakstīšanas pieteikumi</option>
                                <option value="{{ \App\Models\User::START_PAGE_DEVICE_TRANSFERS }}" @selected($defaultStartPage === \App\Models\User::START_PAGE_DEVICE_TRANSFERS)>Nodošanas pieteikumi</option>
                                <option value="{{ \App\Models\User::START_PAGE_AUDIT_LOG }}" @selected($defaultStartPage === \App\Models\User::START_PAGE_AUDIT_LOG)>Audita žurnāls</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->profileSettings->get('default_start_page')" />
                        </div>

                        <div>
                            <x-input-label for="profile_default_view_mode" value="Noklusētais skata režīms" />
                            <select id="profile_default_view_mode" name="default_view_mode" class="crud-control mt-2">
                                <option value="{{ \App\Models\User::VIEW_MODE_ADMIN }}" @selected($defaultViewMode === \App\Models\User::VIEW_MODE_ADMIN)>Admina skats</option>
                                <option value="{{ \App\Models\User::VIEW_MODE_USER }}" @selected($defaultViewMode === \App\Models\User::VIEW_MODE_USER)>Darbinieka skats</option>
                                <option value="{{ \App\Models\User::DEFAULT_VIEW_MODE_LAST }}" @selected($defaultViewMode === \App\Models\User::DEFAULT_VIEW_MODE_LAST)>Atcerēties pēdējo</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->profileSettings->get('default_view_mode')" />
                        </div>

                        <div>
                            <x-input-label for="profile_default_device_filter" value="Ierīču noklusētais filtrs" />
                            <select id="profile_default_device_filter" name="default_device_filter" class="crud-control mt-2">
                                <option value="{{ \App\Models\User::DEVICE_FILTER_ALL }}" @selected($defaultDeviceFilter === \App\Models\User::DEVICE_FILTER_ALL)>Visas ierīces</option>
                                <option value="{{ \App\Models\User::DEVICE_FILTER_ACTIVE }}" @selected($defaultDeviceFilter === \App\Models\User::DEVICE_FILTER_ACTIVE)>Tikai aktīvās</option>
                                <option value="{{ \App\Models\User::DEVICE_FILTER_REPAIR }}" @selected($defaultDeviceFilter === \App\Models\User::DEVICE_FILTER_REPAIR)>Tikai remontā</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->profileSettings->get('default_device_filter')" />
                        </div>

                        <div>
                            <x-input-label for="profile_default_request_filter" value="Pieteikumu noklusētais filtrs" />
                            <select id="profile_default_request_filter" name="default_request_filter" class="crud-control mt-2">
                                <option value="{{ \App\Models\User::REQUEST_FILTER_SUBMITTED }}" @selected($defaultRequestFilter === \App\Models\User::REQUEST_FILTER_SUBMITTED)>Tikai iesniegtie</option>
                                <option value="{{ \App\Models\User::REQUEST_FILTER_ALL }}" @selected($defaultRequestFilter === \App\Models\User::REQUEST_FILTER_ALL)>Visi pieteikumi</option>
                                <option value="{{ \App\Models\User::REQUEST_FILTER_TODAY }}" @selected($defaultRequestFilter === \App\Models\User::REQUEST_FILTER_TODAY)>Tikai šodienas</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->profileSettings->get('default_request_filter')" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="profile_notification_visual_mode" value="Paziņojumu izcelšana" />
                            <select id="profile_notification_visual_mode" name="notification_visual_mode" class="crud-control mt-2">
                                <option value="{{ \App\Models\User::NOTIFICATION_VISUAL_ANIMATED }}" @selected($notificationVisualMode === \App\Models\User::NOTIFICATION_VISUAL_ANIMATED)>Pilna animācija</option>
                                <option value="{{ \App\Models\User::NOTIFICATION_VISUAL_SUBTLE }}" @selected($notificationVisualMode === \App\Models\User::NOTIFICATION_VISUAL_SUBTLE)>Klusāka izcelšana</option>
                                <option value="{{ \App\Models\User::NOTIFICATION_VISUAL_OFF }}" @selected($notificationVisualMode === \App\Models\User::NOTIFICATION_VISUAL_OFF)>Bez toast paziņojumiem</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->profileSettings->get('notification_visual_mode')" />
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                        <div class="font-semibold">Šie iestatījumi attiecas tikai uz tavu kontu.</div>
                        <p class="mt-1">Tie nosaka, kur nonāc pēc pieslēgšanās, kādi saraksti atveras pēc noklusējuma un cik uzkrītoši tiek rādīti jaunie paziņojumi.</p>
                    </div>

                    <div class="device-user-room-modal-actions">
                        <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', 'profile-settings-modal')">Atcelt</button>
                        <button type="submit" class="btn-search" :disabled="submitting" :class="submitting ? 'opacity-70 cursor-wait' : ''">
                            <x-icon name="save" size="h-4 w-4" />
                            <span x-text="submitting ? 'Saglabā...' : 'Saglabāt iestatījumus'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif

    @if ($errors->isNotEmpty() && ! $errors->hasBag('updatePassword') && ! $errors->hasBag('profileSettings'))
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-modal' })));</script>
    @endif

    @if (request()->query('profile_modal') === 'edit')
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-modal' })));</script>
    @endif

    @if ($errors->updatePassword->isNotEmpty())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-modal' }));
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-password-modal' }));
            });
        </script>
    @endif

    @if ($isAdmin && $errors->profileSettings->isNotEmpty())
        <script>window.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'profile-settings-modal' })));</script>
    @endif

    @if (session('close_profile_modals'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'profile-password-modal' }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'profile-settings-modal' }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'profile-modal' }));
            });
        </script>
    @endif
@endif
