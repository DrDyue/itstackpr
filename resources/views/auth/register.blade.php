{{--
    Lapa: Reģistrācija.
    Atbildība: ļauj administratoram izveidot jaunu sistēmas lietotāju no autentifikācijas sadaļas.
    Datu avots: RegisteredUserController@create, saglabāšana caur RegisteredUserController@store.
    Galvenās daļas:
    1. Lietotāja identitātes lauki.
    2. Lomas un konta piekļuves dati.
    3. Reģistrācijas apstiprināšanas poga.
--}}
<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="full_name" value="Vards un uzvards" />
            <x-text-input id="full_name" class="mt-1 block w-full" type="text" name="full_name" :value="old('full_name')" required autofocus />
            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="E-pasts" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="phone" value="Talrunis" />
                <x-text-input id="phone" class="mt-1 block w-full" type="text" name="phone" :value="old('phone')" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="job_title" value="Amats" />
                <x-text-input id="job_title" class="mt-1 block w-full" type="text" name="job_title" :value="old('job_title')" />
                <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="role" value="Loma" />
            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Parole" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Apstiprināt paroli" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('users.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Atpakaļ</a>
            <x-primary-button>Izveidot lietotāju</x-primary-button>
        </div>
    </form>
</x-guest-layout>
