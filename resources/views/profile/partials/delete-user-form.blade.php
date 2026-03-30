{{--
    Profila daļa: Konta dzēšana.
    Atbildība: ļauj lietotājam neatgriezeniski dzēst savu kontu pēc paroles apstiprinājuma.
    Kāpēc tas ir svarīgi:
    1. Tā ir riskanta darbība, tāpēc vizuāli un loģiski atdalīta no pārējām formām.
    2. Papildu paroles ievade samazina nejaušas dzēšanas risku.
    3. Komisijai šī forma labi parāda, kā projektā tiek risinātas kritiskās darbības.
--}}
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-4">
        <h2 class="text-xl font-semibold text-slate-900">
            Dzēst kontu
        </h2>

        <p class="mt-2 text-sm text-slate-600">
            Ja konts vairs nav vajadzīgs, to var neatgriezeniski dzēst. Pirms tam pārliecinies, ka tiešām gribi liegt piekļuvi šī lietotāja kontam.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        <x-icon name="trash" size="h-4 w-4" />
        <span>Dzēst kontu</span>
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Vai tiešām dzēst kontu?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Konta dzēšana ir neatgriezeniska. Ievadi savu paroli, lai apstiprinātu darbību.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Parole" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Parole"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    <x-icon name="clear" size="h-4 w-4" />
                    Atcelt
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    <x-icon name="trash" size="h-4 w-4" />
                    Dzēst kontu
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>



