@props([
    'mode' => 'create',
    'modalName',
    'user' => null,
    'roles' => [],
    'roleLabels' => [],
])

@php
    $isEdit = $mode === 'edit' && $user;
    $modalForm = $isEdit ? 'user_edit_' . $user->id : 'user_create';
    $shouldUseOldInput = old('modal_form') === $modalForm;
    $action = $isEdit ? route('users.update', $user) : route('users.store');
    $title = $isEdit ? 'Rediģēt lietotāju' : 'Jauns lietotājs';
    $subtitle = $isEdit
        ? 'Atjauno kontaktus, lomu un konta statusu vienā modālī.'
        : 'Izveido pilnu sistēmas lietotāja kontu bez lapas maiņas.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Izveidot lietotāju';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
    $badgeLabel = $isEdit ? 'Rediģēšana' : 'Jauns ieraksts';
    $fieldValue = fn (string $field, mixed $default = null) => $shouldUseOldInput ? old($field, $default) : $default;
    $currentRole = $fieldValue('role', $user?->role ?? 'admin');
@endphp

<x-modal :name="$modalName" maxWidth="4xl">
    <form method="POST" action="{{ $action }}" class="flex max-h-[calc(100vh-2.5rem)] flex-col overflow-hidden">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="modal_form" value="{{ $modalForm }}">

        <div class="device-type-modal-head">
            <div class="device-type-modal-head-copy">
                <div class="device-type-modal-badge">
                    <x-icon :name="$isEdit ? 'edit' : 'plus'" size="h-4 w-4" />
                    <span>{{ $badgeLabel }}</span>
                </div>
                <div class="device-type-modal-title-row">
                    <div class="device-type-modal-icon">
                        <x-icon name="user" size="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="device-type-modal-title">{{ $title }}</h2>
                        <p class="device-type-modal-subtitle">{{ $subtitle }}</p>
                    </div>
                </div>
            </div>

            <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', '{{ $modalName }}')" aria-label="Aizvērt">
                <x-icon name="x-mark" size="h-4 w-4" />
            </button>
        </div>

        <div class="device-type-modal-body overflow-y-auto">
            @if ($shouldUseOldInput && $errors->any())
                <x-validation-summary
                    class="mb-5"
                    :title="$isEdit ? 'Neizdevās saglabāt lietotāja izmaiņas' : 'Neizdevās izveidot lietotāju'"
                    :field-labels="[
                        'full_name' => 'Vārds un uzvārds',
                        'email' => 'E-pasts',
                        'phone' => 'Tālrunis',
                        'job_title' => 'Amats',
                        'password' => 'Parole',
                        'password_confirmation' => 'Paroles apstiprinājums',
                        'role' => 'Loma',
                        'is_active' => 'Konta statuss',
                    ]"
                />
            @endif

            <div class="space-y-5">
                <section class="device-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-sky-50 text-sky-700 ring-sky-200">
                            <x-icon name="profile" size="h-5 w-5" />
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">Pamata dati</div>
                            <div class="device-form-section-note">Kontaktinformācija un amata dati lietotāja ierakstam.</div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.form-field class="md:col-span-2" label="Vārds un uzvārds" name="full_name" :required="true">
                            <input type="text" name="full_name" value="{{ $fieldValue('full_name', $user?->full_name) }}" class="crud-control" required>
                        </x-ui.form-field>
                        <x-ui.form-field label="E-pasts" name="email" :required="true">
                            <input type="email" name="email" value="{{ $fieldValue('email', $user?->email) }}" class="crud-control" required>
                        </x-ui.form-field>
                        <x-ui.form-field label="Tālrunis" name="phone">
                            <input type="text" name="phone" value="{{ $fieldValue('phone', $user?->phone) }}" class="crud-control">
                        </x-ui.form-field>
                        <x-ui.form-field class="md:col-span-2" label="Amats" name="job_title">
                            <input type="text" name="job_title" value="{{ $fieldValue('job_title', $user?->job_title) }}" class="crud-control">
                        </x-ui.form-field>
                    </div>
                </section>

                <section class="device-form-card">
                    <div class="device-form-section-header">
                        <div class="device-form-section-icon bg-violet-50 text-violet-700 ring-violet-200">
                            <x-icon name="users" size="h-5 w-5" />
                        </div>
                        <div class="device-form-section-copy">
                            <div class="device-form-section-name">Piekļuve un drošība</div>
                            <div class="device-form-section-note">Loma, konta statuss un paroles maiņa.</div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="block">
                            <span class="crud-label">Loma</span>
                            <div class="mt-2" x-data="{ role: @js($currentRole) }">
                                <input type="hidden" name="role" :value="role">
                                <div class="role-toggle role-toggle-compact">
                                    <button type="button" class="role-toggle-btn" :class="role === 'admin' ? 'role-toggle-active' : ''" @click="role = 'admin'">
                                        <x-icon name="users" size="h-4 w-4" />
                                        <span>{{ $roleLabels['admin'] ?? 'Admins' }}</span>
                                    </button>
                                    <button type="button" class="role-toggle-btn" :class="role === 'user' ? 'role-toggle-active' : ''" @click="role = 'user'">
                                        <x-icon name="profile" size="h-4 w-4" />
                                        <span>{{ $roleLabels['user'] ?? 'Darbinieks' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-3 self-end">
                            <input type="checkbox" name="is_active" value="1" @checked($fieldValue('is_active', $user?->is_active ?? true)) class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-slate-700">Konts aktīvs</span>
                        </label>
                        <x-ui.form-field label="{{ $isEdit ? 'Jauna parole' : 'Parole' }}" name="password" :required="! $isEdit">
                            <input type="password" name="password" class="crud-control" @required(! $isEdit)>
                        </x-ui.form-field>
                        <x-ui.form-field label="Apstiprināt paroli" name="password_confirmation" :required="! $isEdit">
                            <input type="password" name="password_confirmation" class="crud-control" @required(! $isEdit)>
                        </x-ui.form-field>
                    </div>
                </section>
            </div>
        </div>

        <div class="device-type-modal-actions justify-end">
            <div class="device-type-modal-actions-buttons">
                <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', '{{ $modalName }}')">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Atcelt</span>
                </button>
                <button type="submit" class="{{ $submitClass }}">
                    <x-icon name="save" size="h-4 w-4" />
                    <span>{{ $submitLabel }}</span>
                </button>
            </div>
        </div>
    </form>
</x-modal>
