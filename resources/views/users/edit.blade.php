<x-app-layout>
    <section class="app-shell max-w-4xl">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow"><x-icon name="edit" size="h-4 w-4" /><span>Labosana</span></div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber"><x-icon name="user" size="h-7 w-7" /></div>
                        <div>
                            <h1 class="page-title">Rediget lietotaju</h1>
                            <p class="page-subtitle">Atjauno lietotaja datus, lomu un piekluvi.</p>
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

        <form method="POST" action="{{ route('users.update', $user) }}" class="surface-card space-y-6 p-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="crud-label">Vards un uzvards</span>
                    <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">E-pasts</span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="crud-control" required>
                </label>
                <label class="block">
                    <span class="crud-label">Talrunis</span>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Amats</span>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Loma</span>
                    <select name="role" class="crud-control" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="crud-label">Jauna parole</span>
                    <input type="password" name="password" class="crud-control">
                </label>
                <label class="block">
                    <span class="crud-label">Apstiprinat paroli</span>
                    <input type="password" name="password_confirmation" class="crud-control">
                </label>
            </div>

            <label class="inline-flex items-center gap-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-gray-300 text-blue-600">
                <span class="text-sm text-slate-700">Konts aktivs</span>
            </label>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-edit"><x-icon name="save" size="h-4 w-4" /><span>Saglabat</span></button>
                <a href="{{ route('users.index') }}" class="btn-clear"><x-icon name="clear" size="h-4 w-4" /><span>Atcelt</span></a>
            </div>
        </form>
    </section>
</x-app-layout>

