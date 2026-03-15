<x-app-layout>
    <section class="employee-form-shell">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Rediget darbinieku</h1>
                <p class="device-page-subtitle">Atjauno darbinieka informaciju.</p>
            </div>
            <a href="{{ route('employees.index') }}" class="type-back-link inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakal uz sarakstu
            </a>
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

        <form method="POST" action="{{ route('employees.update', $employee) }}" class="employee-form-grid">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div class="employee-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pamata informacija</div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label">Vards, uzvards *</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $employee->full_name) }}" class="crud-control" required>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">E-pasts</label>
                            <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Telefons</label>
                            <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="crud-control">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label">Amats</label>
                        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title) }}" class="crud-control">
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="employee-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Statuss</div>
                    </div>

                    <label class="employee-checkbox mt-4">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (bool) $employee->is_active)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Aktivs</span>
                    </label>
                </div>

                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbibas</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            Atjaunot
                        </button>
                        <a href="{{ route('employees.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                            Atcelt
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
