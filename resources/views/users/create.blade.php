<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauns lietotjs</h1>
            <a href="{{ route('users.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpaka uz sarakstu</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Lietotjs tiek veidots uz esoa darbinieka bzes.
            Ja darbinieks vl nav pievienots, vispirms izveidojiet to sada
            <a href="{{ route('employees.create') }}" class="font-semibold underline hover:no-underline">Darbinieki</a>.
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Darbinieks *</label>
                <select name="employee_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Izvlieties darbinieku</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }} ({{ $employee->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Parole *</label>
                    <input type="password" name="password" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Apstiprint paroli *</label>
                    <input type="password" name="password_confirmation" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Loma *</label>
                <select name="role" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Izvlieties lomu</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role') == $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Konts aktvs (var pieslgties)
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Saglabt</button>
                <a href="{{ route('users.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
