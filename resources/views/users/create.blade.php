{{--
    Lapa: Jauna lietotāja izveide.
    Atbildība: ļauj administratoram izveidot pilnu sistēmas lietotāja kontu.
    Datu avots: UserController@create, saglabāšana caur UserController@store.
    Galvenās daļas:
    1. Hero zona.
    2. Validācijas kopsavilkums.
    3. Lietotāja datu, lomas un paroles forma.
--}}
<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="users" size="h-4 w-4" /><span>Jauns lietotājs</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="user" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauns lietotājs</h1>
                            <p class="page-subtitle">Izveido pilnu lietotāja ierakstu vienā skatā.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        <x-validation-summary />

        {{-- Lietotāja forma apvieno kontaktus, lomu, paroli un konta statusu. --}}
        <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
            @csrf

            <div class="form-page-grid">
                <div class="form-page-main">
                    <div class="surface-card space-y-6 p-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="crud-label">Vārds un uzvārds</span>
                                <input type="text" name="full_name" value="{{ old('full_name') }}" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">E-pasts</span>
                                <input type="email" name="email" value="{{ old('email') }}" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">Tālrunis</span>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="crud-control">
                            </label>
                            <label class="block">
                                <span class="crud-label">Amats</span>
                                <input type="text" name="job_title" value="{{ old('job_title') }}" class="crud-control">
                            </label>
                            <div class="block md:col-span-2">
                                <span class="crud-label">Loma</span>
                                <div class="mt-2" x-data="{ role: @js(old('role', 'admin')) }">
                                    <input type="hidden" name="role" :value="role">
                                    <div class="role-toggle role-toggle-compact">
                                        <button type="button" class="role-toggle-btn" :class="role === 'admin' ? 'role-toggle-active' : ''" @click="role = 'admin'">
                                            <x-icon name="users" size="h-4 w-4" />
                                            <span>Admins</span>
                                        </button>
                                        <button type="button" class="role-toggle-btn" :class="role === 'user' ? 'role-toggle-active' : ''" @click="role = 'user'">
                                            <x-icon name="profile" size="h-4 w-4" />
                                            <span>Darbinieks</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <label class="block">
                                <span class="crud-label">Parole</span>
                                <input type="password" name="password" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">Apstiprināt paroli</span>
                                <input type="password" name="password_confirmation" class="crud-control" required>
                            </label>
                        </div>

                        <label class="inline-flex items-center gap-3">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-slate-700">Konts aktīvs</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Saglabā jauno lietotāju</div>
                    <div class="form-page-actions-text">Pēc saglabāšanas lietotāju varēsi uzreiz rediģēt vai atvērt viņa pilno profilu.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-create"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                    <a href="{{ route('users.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
