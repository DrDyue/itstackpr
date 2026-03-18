<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        @if (! empty($authSetupMessage))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $authSetupMessage }}
            </div>
        @endif

        <div class="form-group">
            <x-input-label for="email">Darbinieka e-pasts</x-input-label>
            <x-text-input
                id="email"
                type="email"
                name="email"
                placeholder="artis.berzins@ludzas.lv"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="form-group">
            <x-input-label for="password">Parole</x-input-label>
            <x-text-input
                id="password"
                type="password"
                name="password"
                placeholder="********"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="checkbox-group">
            <input
                id="remember_me"
                type="checkbox"
                class="form-checkbox"
                name="remember"
            >
            <label for="remember_me" class="checkbox-label">
                Atcereties mani
            </label>
        </div>

        <button type="submit" class="btn-primary">
            <x-icon name="profile" size="h-5 w-5" />
            Pierakstities
        </button>

        @if (Route::has('password.request'))
            <div class="auth-link-row">
                <a href="{{ route('password.request') }}" class="auth-link">
                    Aizmirsi paroli?
                </a>
            </div>
        @endif

        <div class="auth-demo-list">
            <div class="auth-demo-item">
                <span class="auth-demo-icon auth-demo-icon-admin">
                    <x-icon name="users" size="h-4 w-4" />
                </span>
                <div>
                    <div class="font-semibold text-slate-900">Demo admin</div>
                    <div>artis.berzins@ludzas.lv</div>
                    <div>Parole: password</div>
                </div>
            </div>
            <div class="auth-demo-item">
                <span class="auth-demo-icon auth-demo-icon-user">
                    <x-icon name="user" size="h-4 w-4" />
                </span>
                <div>
                    <div class="font-semibold text-slate-900">Demo user</div>
                    <div>ilze.strautina@ludzas.lv</div>
                    <div>Parole: password</div>
                </div>
            </div>
        </div>
    </form>
</x-guest-layout>

