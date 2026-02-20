<x-guest-layout>
    <div class="auth-intro-text">
        Tas ir draudzgs aplikcijas apgabals. Ldzu apstipriniet savu paroli pirms turpinanas.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Parole -->
        <div class="form-group">
            <x-input-label for="password">Parole</x-input-label>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="form-actions-end">
            <button type="submit" class="btn-primary btn-auto-inline">
                Apstipriniet
            </button>
        </div>
    </form>
</x-guest-layout>


