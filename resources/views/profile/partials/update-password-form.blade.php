{{--
    Profila daļa: Paroles maiņa.
    Atbildība: ļauj autorizētam lietotājam nomainīt savu piekļuves paroli.
    Kāpēc tas ir svarīgi:
    1. Drošības darbība ir atdalīta no pārējiem profila datiem.
    2. Forma pārbauda pašreizējo paroli, lai parole netiktu nomainīta nepamatoti.
    3. Šis ir viens no galvenajiem konta drošības uzturēšanas punktiem.
--}}
<section>
    <header class="border-b border-slate-200 pb-4">
        <h2 class="text-xl font-semibold text-slate-900">
            Paroles maina
        </h2>

        <p class="mt-2 text-sm text-slate-600">
            Nomaini savu piekļuves paroli. Ja ievadi jaunu paroli, izmanto stipru kombināciju.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Pašreizējā parole" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-2 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Jauna parole" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Atkārtota parole" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>
                <x-icon name="save" size="h-4 w-4" />
                <span>Saglabāt</span>
            </x-primary-button>

        </div>
    </form>
</section>



