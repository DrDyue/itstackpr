<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="users" size="h-4 w-4" /><span>Jauns lietotajs</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-emerald"><x-icon name="user" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Jauns lietotajs</h1>
                            <p class="page-subtitle">Izveido pilnu lietotaja ierakstu viena tabula.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" class="btn-back"><x-icon name="back" size="h-4 w-4" /><span>Atpakal</span></a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('users.store') }}" class="surface-card space-y-6 p-6">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="crud-label">Vards un uzvards</span>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">E-pasts</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Talrunis</span>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Amats</span>
                    <input type="text" name="job_title" value="{{ old('job_title') }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Loma</span>
                    <select name="role" class="crud-control" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Parole</span>
                    <input type="password" name="password" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Apstiprinat paroli</span>
                    <input type="password" name="password_confirmation" class="crud-control" required>
                </label>
            </div>

            <label class="inline-flex items-center gap-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300 text-blue-600">
                <span class="text-sm text-slate-700">Konts aktivs</span>
            </label>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-create"><x-icon name="save" size="h-4 w-4" /><span>Saglabat</span></button>
                <a href="{{ route('users.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

