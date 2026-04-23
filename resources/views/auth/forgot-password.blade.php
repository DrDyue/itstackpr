{{--
    Lapa: Aizmirsta parole.
    Atbildība: ļauj lietotājam pieprasīt paroles maiņu pie sistēmas administratora.
    Datu avots: PasswordResetLinkController@create, iesniegšana uz PasswordResetLinkController@store.
--}}
<x-guest-layout>
    <div class="auth-intro-text">
        Ievadiet savu sistēmā reģistrēto e-pasta adresi. Paroles maiņas pieprasījums tiks nodots sistēmas administratoram izskatīšanai.
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <x-validation-summary />

        <div class="form-group">
            <x-input-label for="email">E-pasta adrese</x-input-label>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="form-actions-end">
            <button type="submit" class="btn-primary btn-auto-inline">
                Nosūtīt pieprasījumu
            </button>
        </div>
    </form>
</x-guest-layout>

