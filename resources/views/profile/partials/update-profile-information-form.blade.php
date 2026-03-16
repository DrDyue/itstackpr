<section>
    <header class="border-b border-slate-200 pb-4">
        <h2 class="text-xl font-semibold text-slate-900">
            Profila informacija
        </h2>

        <p class="mt-2 text-sm text-slate-600">
            Maini darbinieka pamatdatus, kuri tiek raditi sistema un piesaistiti tavam lietotajam.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="full_name" value="Vards un uzvards" />
                <x-text-input id="full_name" name="full_name" type="text" class="mt-2 block w-full" :value="old('full_name', $employee?->full_name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
            </div>

            <div>
                <x-input-label for="email" value="E-pasts" />
                <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $employee?->email)" required autocomplete="email" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="phone" value="Talrunis" />
                <x-text-input id="phone" name="phone" type="text" class="mt-2 block w-full" :value="old('phone', $employee?->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="job_title" value="Amats" />
                <x-text-input id="job_title" name="job_title" type="text" class="mt-2 block w-full" :value="old('job_title', $employee?->job_title)" autocomplete="organization-title" />
                <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Konts ir piesaistits darbiniekam ar ID #{{ $employee?->id ?? '-' }}.
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Saglabat</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600"
                >Saglabats.</p>
            @endif
        </div>
    </form>
</section>


