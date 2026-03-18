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
            Pierakstities
        </button>

        @if (Route::has('password.request'))
            <div class="auth-link-row">
                <a href="{{ route('password.request') }}" class="auth-link">
                    Aizmirsi paroli?
                </a>
            </div>
        @endif

        <div class="mt-6 space-y-2 text-sm text-slate-500">
            <p>Demo admin: artis.berzins@ludzas.lv | Parole: password</p>
            <p>Demo user: ilze.strautina@ludzas.lv | Parole: password</p>
        </div>
    </form>
</x-guest-layout>

