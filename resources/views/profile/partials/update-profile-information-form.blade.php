{{--
    Profila daļa: Pamatinformācijas atjaunošana.
    Atbildība: ļauj lietotājam labot savu personisko informāciju, kuru sistēma izmanto ierīču piešķiršanā un pieteikumos.
    Kāpēc tas ir svarīgi:
    1. Vārds un amats tiek rādīti ierīču tabulās un pieteikumu kartītēs.
    2. E-pasts un telefons palīdz identificēt lietotāju un sazināties, ja tas nepieciešams.
    3. Šī forma ir atdalīta no paroles maiņas, lai drošības dati nešajāuktos ar profila datiem.
--}}
<section>
    <header class="border-b border-slate-200 pb-4">
        <h2 class="text-xl font-semibold text-slate-900">Profila informācija</h2>
        <p class="mt-2 text-sm text-slate-600">Atjauno savu vārdu, kontaktinformāciju un amatu.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="full_name" value="Vārds un uzvārds" />
                <x-text-input id="full_name" name="full_name" type="text" class="mt-2 block w-full" :value="old('full_name', $user?->full_name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
            </div>

            <div>
                <x-input-label for="email" value="E-pasts" />
                <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user?->email)" required autocomplete="email" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="phone" value="Talrunis" />
                <x-text-input id="phone" name="phone" type="text" class="mt-2 block w-full" :value="old('phone', $user?->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="job_title" value="Amats" />
                <x-text-input id="job_title" name="job_title" type="text" class="mt-2 block w-full" :value="old('job_title', $user?->job_title)" autocomplete="organization-title" />
                <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>
                <x-icon name="save" size="h-4 w-4" />
                <span>Saglabāt</span>
            </x-primary-button>

        </div>
    </form>
</section>

