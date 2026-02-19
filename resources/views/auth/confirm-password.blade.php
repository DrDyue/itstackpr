<x-guest-layout>
    <div style="color: #555; font-size: 14px; margin-bottom: 20px;">
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

        <div style="text-align: right; margin-top: 20px;">
            <button type="submit" class="btn-primary" style="width: auto; display: inline-block;">
                Apstipriniet
            </button>
        </div>
    </form>
</x-guest-layout>
