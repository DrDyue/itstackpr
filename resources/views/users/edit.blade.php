<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediģēt lietotāju</h1>
            <a href="{{ route('users.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
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

        <form method="POST" action="{{ route('users.update', $user) }}" class="crud-form-card">
            @csrf
            @method('PUT')

            <div>
                <label class="crud-label">Darbinieks *</label>
                <select name="employee_id" class="crud-control" required>
                    <option value="">Izvēlieties darbinieku</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id', $user->employee_id) == $employee->id)>{{ $employee->full_name }} ({{ $employee->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Jaunā parole</label>
                    <input type="password" name="password" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Apstiprināt paroli</label>
                    <input type="password" name="password_confirmation" class="crud-control">
                </div>
            </div>

            <div>
                <label class="crud-label">Loma *</label>
                <select name="role" class="crud-control" required>
                    <option value="">Izvēlieties lomu</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) == $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (bool) $user->is_active)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Konts aktīvs (var pieslēgties)
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Atjaunināt</button>
                <a href="{{ route('users.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


