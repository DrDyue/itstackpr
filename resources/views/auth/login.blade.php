<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Epasta adrese -->
        <div class="form-group">
            <x-input-label for="email">E-pasta adrese</x-input-label>
            <x-text-input 
                id="email" 
                type="email" 
                name="email" 
                value="admin@example.com"
                placeholder="admin@example.com"
                required 
                autofocus 
                autocomplete="username" 
            />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Parole -->
        <div class="form-group">
            <x-input-label for="password">Parole</x-input-label>
            <x-text-input 
                id="password" 
                type="password"
                name="password"
                value="password"
                placeholder="••••••••"
                required 
                autocomplete="current-password" 
            />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Atcerēties -->
        <div class="checkbox-group">
            <input 
                id="remember_me" 
                type="checkbox" 
                class="form-checkbox" 
                name="remember"
            >
            <label for="remember_me" class="checkbox-label">
                Atcerēties mani
            </label>
        </div>

        <!-- Pogu -->
        <button type="submit" class="btn-primary">
            Pierakstīties
        </button>

        <!-- Aizmirsa paroli -->
        @if (Route::has('password.request'))
            <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #d5d5d7;">
                <a href="{{ route('password.request') }}" class="auth-link">
                    Aizmirsi paroli?
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
