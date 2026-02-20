<x-guest-layout>
    <div class="auth-intro-text">
        Aizmirsi paroli? Bez problmm. Vienkri ievadi savu e-pasta adresi un ms nostsim paroles atiestatanas saiti.
    </div>

    @if (session('status'))
        <div class="success-message">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- E-pasta adrese -->
        <div class="form-group">
            <x-input-label for="email">E-pasta adrese</x-input-label>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="form-actions-end">
            <button type="submit" class="btn-primary btn-auto-inline">
                Stt paroles atiestatanas saiti
            </button>
        </div>
    </form>
</x-guest-layout>


