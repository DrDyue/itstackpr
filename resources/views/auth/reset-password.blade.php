<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Paroles atiestatanas tokens -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- E-pasta adrese -->
        <div class="form-group">
            <x-input-label for="email">E-pasta adrese</x-input-label>
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Parole -->
        <div class="form-group">
            <x-input-label for="password">Parole</x-input-label>
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Apstiprini paroli -->
        <div class="form-group">
            <x-input-label for="password_confirmation">Apstiprini paroli</x-input-label>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="form-actions-end">
            <button type="submit" class="btn-primary btn-auto-inline">
                Atiestatt paroli
            </button>
        </div>
    </form>
</x-guest-layout>


