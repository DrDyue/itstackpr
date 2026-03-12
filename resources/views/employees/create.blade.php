<x-app-layout>
    <section class="employee-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Jauns darbinieks</h1>
                <p class="device-page-subtitle">Pievieno jaunu darbinieku sarakstam.</p>
            </div>
            <a href="{{ route('employees.index') }}" class="type-back-link">Atpakal uz sarakstu</a>
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

        <form method="POST" action="{{ route('employees.store') }}" class="employee-form-grid">
            @csrf

            <div class="space-y-4">
                <div class="employee-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pamata informacija</div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label">Vards, uzvards *</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" class="crud-control" required>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">E-pasts</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Telefons</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="crud-control">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label">Amats</label>
                        <input type="text" name="job_title" value="{{ old('job_title') }}" class="crud-control">
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="employee-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Statuss</div>
                    </div>

                    <label class="employee-checkbox mt-4">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Aktivs</span>
                    </label>
                </div>

                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbibas</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary">Saglabat</button>
                        <a href="{{ route('employees.index') }}" class="crud-btn-secondary">Atcelt</a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
