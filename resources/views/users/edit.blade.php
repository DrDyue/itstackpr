{--
    Lapa: Lietotāja rediģēšana.
    Atbildība: ļauj administratoram atjaunot lietotāja kontaktus, lomu un konta statusu.
    Datu avots: UserController@edit, saglabāšana caur UserController@update.
    Galvenās daļas:
    1. Hero ar rediģēšanas kontekstu.
    2. Kļūdu kopsavilkums.
    3. Lietotāja rediģēšanas forma.
--}}
<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labošana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="user" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediģēt lietotāju</h1>
                            <p class="page-subtitle">Atjauno lietotāja datus, lomu un piekļuvi.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakaļ</span></a>
            </div>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="form-page-grid">
                <div class="form-page-main">
                    <div class="surface-card space-y-6 p-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="crud-label">Vārds un uzvārds</span>
                                <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">E-pasts</span>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="crud-control" required>
                            </label>
                            <label class="block">
                                <span class="crud-label">Tālrunis</span>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="crud-control">
                            </label>
                            <label class="block">
                                <span class="crud-label">Amats</span>
                                <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="crud-control">
                            </label>
                            <div class="block md:col-span-2">
                                <span class="crud-label">Loma</span>
                                <div class="mt-2" x-data="{ role: @js(old('role', $user->role)) }">
                                    <input type="hidden" name="role" :value="role">
                                    <div class="role-toggle">
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
                                <span class="crud-label">Jauna parole</span>
                                <input type="password" name="password" class="crud-control">
                            </label>
                            <label class="block">
                                <span class="crud-label">Apstiprināt paroli</span>
                                <input type="password" name="password_confirmation" class="crud-control">
                            </label>
                        </div>

                        <label class="inline-flex items-center gap-3">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-slate-700">Konts aktīvs</span>
                        </label>
                    </div>
                </div>

                <aside class="form-page-aside">
                    <div class="form-page-note">
                        <div class="form-page-note-title">Pirms saglabāšanas</div>
                        <div class="form-page-note-copy">Pārbaudi, vai e-pasts nav kļūdains, loma ir pareiza un paroles lauki aizpildīti tikai tad, ja tiešām jāmaina piekļuve.</div>
                    </div>
                </aside>
            </div>

            <div class="form-page-actions">
                <div class="form-page-actions-copy">
                    <div class="form-page-actions-title">Saglabā lietotāja izmaiņas</div>
                    <div class="form-page-actions-text">Lietotāja profils un piesaistes vēsture paliks pieejama arī pēc šo datu atjaunošanas.</div>
                </div>
                <div class="form-page-actions-buttons">
                    <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Saglabāt</span></button>
                    <a href="{{ route('users.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
