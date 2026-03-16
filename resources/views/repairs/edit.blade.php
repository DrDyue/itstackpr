<x-app-layout>
    @php
        $currentBuilding = $repair->device?->building;
        $currentRoom = $repair->device?->room;
        $selectedReporterId = old('issue_reported_by', $repair->reported_employee_id ?? $repair->legacyReporter?->employee_id);
        $severityClasses = [
            'info' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'warning' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'error' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'critical' => 'bg-rose-100 text-rose-800 ring-rose-200',
        ];
        $actionLabels = [
            'CREATE' => 'Izveidots',
            'UPDATE' => 'Atjauninats',
            'DELETE' => 'Dzests',
            'VIEW' => 'Skatits',
        ];
        $statusDescriptions = [
            'waiting' => 'Vel nav uzsakta izpilde',
            'in-progress' => 'Darbs notiek sobrid',
            'completed' => 'Remonts ir pabeigts',
            'cancelled' => 'Darbs tika atcelts',
        ];
        $priorityDescriptions = [
            'low' => 'Var planot bez steigas',
            'medium' => 'Standarta izpildes seciba',
            'high' => 'Jareage iespejami driz',
            'critical' => 'Japrioritize uzreiz',
        ];
    @endphp

    <section
        class="repair-form-shell space-y-6"
        x-data="repairProcess({
            transitionBaseUrl: @js(url('/repairs')),
            csrfToken: @js(csrf_token()),
            repairId: @js($repair->id),
            repairType: @js(old('repair_type', $repair->repair_type)),
            status: @js(old('status', $repair->status ?? 'waiting')),
            priority: @js(old('priority', $repair->priority ?? 'medium')),
            cost: @js($repair->cost),
        })"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Remonts #{{ $repair->id }}</span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$repair->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                        @include('repairs.partials.icon', ['name' => $statusIcons[$repair->status] ?? 'clock', 'class' => 'h-3.5 w-3.5'])
                        {{ $statusLabels[$repair->status] ?? $repair->status }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $typeClasses[$repair->repair_type] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                        @include('repairs.partials.icon', ['name' => $typeIcons[$repair->repair_type] ?? 'wrench', 'class' => 'h-3.5 w-3.5'])
                        {{ $typeLabels[$repair->repair_type] ?? $repair->repair_type }}
                    </span>
                </div>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $repair->device?->name ?: 'Nezinama ierice' }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Procesa skats remonta izpildei, terminu kontrolei un gala noslegsanas darbam.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('repairs.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                    Atpakal uz paneli
                </a>
                <a href="{{ route('devices.show', $repair->device) }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z"/>
                    </svg>
                    Skatit ierici
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700 shadow-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                    @include('repairs.partials.icon', ['name' => 'device', 'class' => 'h-4 w-4'])
                    Ierices kods
                </p>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $repair->device?->code ?: '-' }}</div>
                <p class="mt-2 text-sm text-slate-600">{{ $repair->device?->type?->type_name ?: 'Tips nav noradits' }}</p>
            </div>
            <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">
                    @include('repairs.partials.icon', ['name' => 'building', 'class' => 'h-4 w-4'])
                    Atrasanas vieta
                </p>
                <div class="mt-2 text-lg font-semibold text-slate-900">{{ $currentBuilding?->building_name ?: 'Eka nav noradita' }}</div>
                <p class="mt-2 text-sm text-slate-600">
                    @if ($currentRoom)
                        {{ $currentRoom->floor_number }}. stavs | telpa {{ $currentRoom->room_number }}
                        @if ($currentRoom->room_name)
                            | {{ $currentRoom->room_name }}
                        @endif
                    @else
                        Telpa nav noradita
                    @endif
                </p>
            </div>
            <div class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-violet-700">
                    @include('repairs.partials.icon', ['name' => 'users', 'class' => 'h-4 w-4'])
                    Atbildigais
                </p>
                <div class="mt-2 text-lg font-semibold text-slate-900">{{ $repair->assignee?->employee?->full_name ?? 'Nav pieskirta' }}</div>
                <p class="mt-2 text-sm text-slate-600">Pieteica: {{ $repair->reporter?->full_name ?? $repair->legacyReporter?->employee?->full_name ?? 'Nav zinotaja' }}</p>
            </div>
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">
                    @include('repairs.partials.icon', ['name' => 'calendar', 'class' => 'h-4 w-4'])
                    Termini un izmaksas
                </p>
                <div class="mt-2 text-lg font-semibold text-slate-900">{{ $repair->cost !== null ? number_format((float) $repair->cost, 2) . ' EUR' : '-' }}</div>
                <p class="mt-2 text-sm text-slate-600">
                    Sakts {{ $repair->start_date?->format('d.m.Y') ?? '-' }}
                    @if ($repair->estimated_completion)
                        | planots {{ $repair->estimated_completion->format('d.m.Y') }}
                    @endif
                    @if ($repair->actual_completion)
                        | pabeigts {{ $repair->actual_completion->format('d.m.Y') }}
                    @endif
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                @include('repairs.partials.icon', ['name' => 'wrench', 'class' => 'h-5 w-5'])
                                Atras darbibas
                            </h2>
                            <p class="mt-1 text-sm text-slate-600">Parvieto remontu starp gaida, procesa un pabeigts statusiem.</p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $priorityClasses[$repair->priority] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                            @include('repairs.partials.icon', ['name' => $priorityIcons[$repair->priority] ?? 'bars', 'class' => 'h-3.5 w-3.5'])
                            {{ $priorityLabels[$repair->priority] ?? 'Videja' }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($repair->status === 'waiting')
                            <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                @csrf
                                <input type="hidden" name="target_status" value="in-progress">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-7.5-7.5L20.25 12l-7.5 7.5"/>
                                    </svg>
                                    Panemt procesa
                                </button>
                            </form>
                        @endif

                        @if ($repair->status === 'in-progress')
                            <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                @csrf
                                <input type="hidden" name="target_status" value="waiting">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                                    </svg>
                                    Atpakal uz gaida
                                </button>
                            </form>

                            <button type="button" @click="openCompletionModal()" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                    Pabeigt remontu
                            </button>
                        @endif

                        @if (in_array($repair->status, ['completed', 'cancelled'], true))
                            <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                @csrf
                                <input type="hidden" name="target_status" value="in-progress">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5 19.5 4.5M9 4.5h10.5V15"/>
                                    </svg>
                                    Atvert no jauna
                                </button>
                            </form>
                        @endif

                        @if ($repair->status !== 'cancelled')
                            <form method="POST" action="{{ route('repairs.transition', $repair) }}">
                                @csrf
                                <input type="hidden" name="target_status" value="cancelled">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                    Atcelt remontu
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('repairs.update', $repair) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="device_id" value="{{ old('device_id', $repair->device_id) }}">
                    <input type="hidden" name="start_date" value="{{ old('start_date', optional($repair->start_date)->format('Y-m-d')) }}">

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    @include('repairs.partials.icon', ['name' => 'document', 'class' => 'h-5 w-5'])
                                    Pamatinformacija
                                </h2>
                                <p class="mt-1 text-sm text-slate-600">Ierice un sakuma datums tiek tureti nemainigi, lai saglabatu remonta vesturi korektu.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'device', 'class' => 'h-4 w-4'])
                                    Ierice
                                </label>
                                <input type="text" value="{{ $repair->device?->code ?: 'Ierice' }} - {{ $repair->device?->name ?: 'Nezinama ierice' }}" class="crud-control bg-slate-50" disabled>
                            </div>
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'calendar', 'class' => 'h-4 w-4'])
                                    Sakuma datums
                                </label>
                                <input type="text" value="{{ $repair->start_date?->format('d.m.Y') ?? '-' }}" class="crud-control bg-slate-50" disabled>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="crud-label flex items-center gap-2">
                                @include('repairs.partials.icon', ['name' => 'note', 'class' => 'h-4 w-4'])
                                Apraksts *
                            </label>
                            <textarea name="description" rows="5" required class="crud-control">{{ old('description', $repair->description) }}</textarea>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    @include('repairs.partials.icon', ['name' => 'calendar', 'class' => 'h-5 w-5'])
                                    Planosana un izpilde
                                </h2>
                                <p class="mt-1 text-sm text-slate-600">Tips, prioritate, statuss, termini un izmaksas.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'wrench', 'class' => 'h-4 w-4'])
                                    Remonta tips *
                                </label>
                                @include('repairs.partials.custom-select', [
                                    'name' => 'repair_type',
                                    'selected' => old('repair_type', $repair->repair_type),
                                    'options' => $repairTypes,
                                    'labels' => $typeLabels,
                                    'icons' => $typeIcons,
                                    'classes' => $typeClasses,
                                    'descriptions' => [
                                        'internal' => 'Darbs tiek veikts uz vietas',
                                        'external' => 'Darbs tiek nodots arejam servisam',
                                    ],
                                    'syncModel' => 'repairType',
                                    'placeholder' => 'Izvelies remonta tipu',
                                ])
                            </div>
                            <div class="lg:col-span-2">
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'clock', 'class' => 'h-4 w-4'])
                                    Statuss
                                </label>
                                @include('repairs.partials.custom-select', [
                                    'name' => 'status',
                                    'selected' => old('status', $repair->status),
                                    'options' => $statuses,
                                    'labels' => $statusLabels,
                                    'icons' => $statusIcons,
                                    'classes' => $statusClasses,
                                    'descriptions' => $statusDescriptions,
                                    'syncModel' => 'status',
                                    'placeholder' => 'Izvelies statusu',
                                ])
                            </div>
                            <div class="lg:col-span-2 xl:col-span-4">
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'flame', 'class' => 'h-4 w-4'])
                                    Prioritate
                                </label>
                                @include('repairs.partials.custom-select', [
                                    'name' => 'priority',
                                    'selected' => old('priority', $repair->priority ?? 'medium'),
                                    'options' => $priorities,
                                    'labels' => $priorityLabels,
                                    'icons' => $priorityIcons,
                                    'classes' => $priorityClasses,
                                    'descriptions' => $priorityDescriptions,
                                    'syncModel' => 'priority',
                                    'placeholder' => 'Izvelies prioritati',
                                ])
                            </div>
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'money', 'class' => 'h-4 w-4'])
                                    Izmaksas (EUR)
                                </label>
                                <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $repair->cost) }}" class="crud-control">
                            </div>
                            <x-localized-date-picker
                                name="estimated_completion"
                                :value="old('estimated_completion', optional($repair->estimated_completion)->format('Y-m-d'))"
                                label="Planotais beigums"
                                label-class="crud-label flex items-center gap-2"
                                x-show="status === 'in-progress' || status === 'completed'"
                                x-cloak
                            />
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    @include('repairs.partials.icon', ['name' => 'users', 'class' => 'h-5 w-5'])
                                    Atbildiba
                                </h2>
                                <p class="mt-1 text-sm text-slate-600">Kas pieteica un kuram pieskirta remonta izpilde.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'user', 'class' => 'h-4 w-4'])
                                    Zinoja darbinieks
                                </label>
                                <select name="issue_reported_by" class="crud-control">
                                    <option value="">Nav</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected($selectedReporterId == $employee->id)>{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'users', 'class' => 'h-4 w-4'])
                                    Pieskirts lietotajam
                                </label>
                                <select name="assigned_to" class="crud-control">
                                    <option value="">Nav</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(old('assigned_to', $repair->assigned_to) == $user->id)>{{ $user->employee?->full_name ?? ('Lietotajs #' . $user->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div x-show="repairType === 'external'" x-cloak class="rounded-[2rem] border border-rose-200 bg-rose-50 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                    @include('repairs.partials.icon', ['name' => 'truck', 'class' => 'h-5 w-5'])
                                    Areja remonta dati
                                </h2>
                                <p class="mt-1 text-sm text-slate-600">Piegadataja un rekina informacija arejiem servisa darbiem.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'truck', 'class' => 'h-4 w-4'])
                                    Piegadatajs *
                                </label>
                                <input type="text" name="vendor_name" value="{{ old('vendor_name', $repair->vendor_name) }}" class="crud-control">
                            </div>
                            <div>
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'users', 'class' => 'h-4 w-4'])
                                    Piegadataja kontakts *
                                </label>
                                <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $repair->vendor_contact) }}" class="crud-control">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="crud-label flex items-center gap-2">
                                    @include('repairs.partials.icon', ['name' => 'document', 'class' => 'h-4 w-4'])
                                    Rekina numurs
                                </label>
                                <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number', $repair->invoice_number) }}" class="crud-control">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-slate-500">Pedejais ieraksts izveidots {{ $repair->created_at?->format('d.m.Y H:i') ?? '-' }}</div>
                        <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            Saglabat izmainas
                        </button>
                    </div>
                </form>

            </div>

            <aside class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                        @include('repairs.partials.icon', ['name' => 'document', 'class' => 'h-5 w-5'])
                        Remonta vesture
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">Automatiski ieraksti par visam galvenajam izmainam saistiba ar so remontu.</p>

                    <div class="mt-5 space-y-4">
                        @forelse ($timeline as $entry)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 ring-1 ring-slate-200">{{ $actionLabels[$entry->action] ?? $entry->action }}</span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $severityClasses[$entry->severity] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ \App\Support\AuditTrail::severityLabel($entry->severity) }}</span>
                                </div>
                                <p class="mt-3 text-sm font-medium text-slate-900">{{ $entry->localized_description }}</p>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $entry->timestamp?->format('d.m.Y H:i') ?? '-' }} | {{ $entry->user?->employee?->full_name ?? 'Sistema' }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                Vestures ierakstu vel nav.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                        @include('repairs.partials.icon', ['name' => 'building', 'class' => 'h-5 w-5'])
                        Atrasanas vieta
                    </h2>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                @include('repairs.partials.icon', ['name' => 'building', 'class' => 'h-4 w-4'])
                                Eka
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $currentBuilding?->building_name ?: 'Nav' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                @include('repairs.partials.icon', ['name' => 'room', 'class' => 'h-4 w-4'])
                                Telpa
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                @if ($currentRoom)
                                    {{ $currentRoom->room_number }}
                                    @if ($currentRoom->room_name)
                                        | {{ $currentRoom->room_name }}
                                    @endif
                                @else
                                    Nav noradita
                                @endif
                            </p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                @include('repairs.partials.icon', ['name' => 'building', 'class' => 'h-4 w-4'])
                                Stavs
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $currentRoom?->floor_number !== null ? $currentRoom->floor_number . '. stavs' : 'Nav' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                        @include('repairs.partials.icon', ['name' => 'x-mark', 'class' => 'h-5 w-5'])
                        Bistamas darbibas
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">Dzeshot aktivu remontu, saistita ierice tiks iznemta no remonta statusa un atgriezta ieprieksejaja statusa.</p>

                    <form method="POST" action="{{ route('repairs.destroy', $repair) }}" class="mt-4" onsubmit="return confirm('Dzest so remonta ierakstu?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.245-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3a2.25 2.25 0 0 0-2.25 2.25V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                            Dzest remontu
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        <div
            x-cloak
            x-show="completeModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 px-4 py-6"
        >
            <div
                class="w-full max-w-lg rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl"
                @click.outside="closeCompletionModal()"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Pabeigt remontu</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $repair->device?->name ?: 'Nezinama ierice' }}</h2>
                        <p class="mt-2 text-sm text-slate-600">Noradi faktisko beigu datumu un gala izmaksas, lai remonts butu korekti noslegts.</p>
                    </div>
                    <button type="button" @click="closeCompletionModal()" class="rounded-2xl bg-slate-100 p-2 text-slate-500 transition hover:bg-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        Faktiskais beigu datums tiks aizpildits automatiski ar sodienas datumu.
                    </div>
                    <label class="block">
                        <span class="repair-filter-label">Gala izmaksas (EUR)</span>
                        <input type="number" step="0.01" min="0" x-model="completionForm.cost" class="crud-control" placeholder="0.00" required>
                    </label>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" @click="closeCompletionModal()" class="crud-btn-secondary inline-flex items-center gap-2">
                        Atcelt
                    </button>
                    <button type="button" @click="submitCompletion()" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        Apstiprinat pabeigsanu
                    </button>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
