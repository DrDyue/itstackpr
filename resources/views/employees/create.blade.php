<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauns darbinieks</h1>
            <a href="{{ route('employees.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('employees.store') }}" class="crud-form-card">
            @csrf

            <div>
                <label class="crud-label">Vārds, uzvārds *</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" class="crud-control" required>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">E-pasts</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Telefons</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="crud-control">
                </div>
            </div>
            <div>
                <label class="crud-label">Amats</label>
                <input type="text" name="job_title" value="{{ old('job_title') }}" class="crud-control">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Aktīvs
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabāt</button>
                <a href="{{ route('employees.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


