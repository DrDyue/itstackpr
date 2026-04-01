{{--
    Lapa: Remonta ieraksta rediģēšana.
    Atbildība: ļauj administratoram vienuviet pārskatīt remonta situāciju,
    labot datus un veikt nākamās statusa darbības bez liekas tukšas vietas.
--}}
<x-app-layout>
    @php
        $statusTone = match ($repair->status) {
            'in-progress' => 'border-sky-200 bg-sky-50 text-sky-900',
            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'cancelled' => 'border-rose-200 bg-rose-50 text-rose-900',
            default => 'border-amber-200 bg-amber-50 text-amber-900',
        };
        $deviceThumbUrl = $repair->device?->deviceImageThumbUrl();
        $statusMessage = match ($repair->status) {
            'in-progress' => 'Remonts šobrīd ir procesā. Kad viss nepieciešamais ir aizpildīts, to var pabeigt.',
            'completed' => 'Remonts ir pabeigts. Ja nepieciešams turpināt darbu, to var atjaunot uz procesa statusu.',
            'cancelled' => 'Remonts ir atcelts un paliek vēsturē. Datus vēl vari pārskatīt vai precizēt.',
            default => 'Remonts šobrīd gaida uzsākšanu. Pirms starta pārliecinies, ka viss nepieciešamais ir aizpildīts.',
        };
        $locationPrimary = trim(collect([
            $repair->device?->room?->room_name,
            $repair->device?->room?->room_number,
        ])->filter()->implode(' '));
        $locationSecondary = $repair->device?->building?->building_name;
        $deviceMeta = collect([$repair->device?->manufacturer, $repair->device?->model])->filter()->implode(' ');
    @endphp

    <section
        class="app-shell max-w-7xl"
        x-data="repairProcess({
            repairId: {{ $repair->id }},
            repairType: @js(old('repair_type', $repair->repair_type ?? 'internal')),
            status: @js($repair->status),
            priority: @js(old('priority', $repair->priority ?? 'medium')),
            description: @js(old('description', $repair->description ?? '')),
            vendorName: @js(old('vendor_name', $repair->vendor_name ?? '')),
            vendorContact: @js(old('vendor_contact', $repair->vendor_contact ?? '')),
            invoiceNumber: @js(old('invoice_number', $repair->invoice_number ?? '')),
            cost: @js(old('cost', $repair->cost ?? '')),
            transitionBaseUrl: @js(url('/repairs')),
            csrfToken: @js(csrf_token()),
        })"
    >
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="page-eyebrow">
                        <x-icon name="edit" size="h-4 w-4" />
                        <span>Remonta kartīte</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-amber">
                            <x-icon name="repair" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Rediģēt remontu</h1>
                            <p class="page-subtitle">Sakārto remonta datus, pārskati gatavību un veic nākamo soli vienā saprotamā skatā.</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('repairs.index') }}" class="btn-back">
                    <x-icon name="back" size="h-4 w-4" />
                    <span>Atpakaļ</span>
                </a>
            </div>
        </div>

        <x-validation-summary />

        <div class="space-y-4">
            {{-- REMONTA KOPSAVILKUMS --}}
            <div class="surface-card space-y-4 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            @if ($deviceThumbUrl)
                                <img src="{{ $deviceThumbUrl }}" alt="{{ $repair->device?->name ?: 'Ierīce' }}" class="device-table-thumb shrink-0">
                            @else
                                <div class="device-table-thumb device-table-thumb-placeholder shrink-0">
                                    <x-icon name="device" size="h-4 w-4" />
                                </div>
                            @endif

                            <div class="space-y-2">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Remonta kopsavilkums</div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ $repair->device?->name ?: 'Ierīce nav atrasta' }}</h2>
                                <div class="flex flex-wrap gap-2 text-sm text-slate-500">
                                    <span>Kods: {{ $repair->device?->code ?: 'bez koda' }}</span>
                                    @if ($deviceMeta)
                                        <span>{{ $deviceMeta }}</span>
                                    @endif
                                    @if ($repair->device?->serial_number)
                                        <span>Sērija: {{ $repair->device->serial_number }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2 text-sm text-slate-600">
                                    @if ($locationPrimary)
                                        <span><strong class="text-slate-900">Atrodas:</strong> {{ $locationPrimary }}</span>
                                    @endif
                                    @if ($locationSecondary)
                                        <span>{{ $locationSecondary }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" />
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        <div class="rounded-2xl border px-4 py-4 text-sm {{ $statusTone }}">
                            <div class="font-semibold">Statusa informācija</div>
                            <div class="mt-2">{{ $statusMessage }}</div>
                        </div>

                        <div class="surface-card-muted space-y-2">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Atbildība un laiks</div>
                            <div class="text-sm text-slate-600"><strong class="text-slate-900">Piešķirta:</strong> {{ $repair->device?->assignedTo?->full_name ?: 'Nav piešķirta' }}</div>
                            <div class="text-sm text-slate-600"><strong class="text-slate-900">Apstiprināja:</strong> {{ $repair->approval_actor?->full_name ?: 'Nav norādīts' }}</div>
                            @if ($repair->executor)
                                <div class="text-sm text-slate-600"><strong class="text-slate-900">Izpildītājs:</strong> {{ $repair->executor->full_name }}</div>
                            @endif
                            <div class="text-sm text-slate-600"><strong class="text-slate-900">Sākums:</strong> {{ $repair->start_date?->format('d.m.Y') ?: '-' }}</div>
                            <div class="text-sm text-slate-600"><strong class="text-slate-900">Beigas:</strong> {{ $repair->end_date?->format('d.m.Y') ?: '-' }}</div>
                        </div>
                    </div>

                    @if ($repair->request)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Saistītais pieteikums</div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        Pieteicējs: {{ $repair->request->responsibleUser?->full_name ?: 'Nav norādīts' }}
                                    </div>
                                </div>
                                <a href="{{ route('repair-requests.index', ['request_id' => $repair->request_id, 'statuses_filter' => 1]) }}" class="btn-view">
                                    <x-icon name="repair-request" size="h-4 w-4" />
                                    <span>Atvērt pieteikumu</span>
                                </a>
                            </div>
                            <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                                <strong class="text-slate-900">Pieteikuma teksts:</strong> {{ $repair->request->description ?: '-' }}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- GALVENĀ DARBĪBA - FORMA --}}
                <form id="repair-edit-form" method="POST" action="{{ route('repairs.update', $repair) }}" class="surface-card space-y-5 p-5">
                    @csrf
                    @method('PUT')

                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Galvenā darbība</div>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">Rediģēt remonta datus</h2>
                        <p class="mt-1 text-sm text-slate-500">Lauki mainās atkarībā no statusa un remonta tipa.</p>
                    </div>

                    @include('repairs.partials.form-fields', ['repair' => $repair])

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                        <div class="text-sm text-slate-500">Vispirms saglabā datus. Pēc tam vari droši veikt statusa darbības labajā pusē.</div>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="btn-edit">
                                <x-icon name="save" size="h-4 w-4" />
                                <span>Saglabāt izmaiņas</span>
                            </button>
                            <a href="{{ route('repairs.index') }}" class="btn-clear">
                                <x-icon name="clear" size="h-4 w-4" />
                                <span>Aizvērt</span>
                            </a>
                        </div>
                    </div>
                </form>

                {{-- GATAVĪBA UN DARBĪBAS --}}
                <div class="grid gap-4 md:grid-cols-2">
                    {{-- NĀKAMĀ SOĻA GATAVĪBA - tikai ja statuss ir in-progress --}}
                    @if ($repair->status === 'in-progress')
                    <div class="surface-card space-y-5 p-5">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nākamā soļa gatavība</div>
                            <div class="mt-1 text-sm text-slate-500" x-text="nextStepLabel()"></div>
                        </div>

                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-sm font-medium text-slate-700">Pašreizējā gatavība</div>
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                :class="nextStepReady() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                x-text="nextStepReady() ? 'Gatavs' : 'Nepilnīgs'"
                            ></span>
                        </div>

                        <template x-if="requirementRows().length > 0">
                            <div class="space-y-2">
                                <template x-for="item in requirementRows()" :key="item.key">
                                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
                                        <span class="font-medium text-slate-700" x-text="item.label"></span>
                                        <span class="inline-flex items-center gap-2 font-semibold" :class="item.done ? 'text-emerald-700' : 'text-rose-700'">
                                            <span class="inline-flex" x-show="item.done">
                                                <x-icon name="check-circle" size="h-4 w-4" />
                                            </span>
                                            <span class="inline-flex" x-show="!item.done">
                                                <x-icon name="x-circle" size="h-4 w-4" />
                                            </span>
                                            <span x-text="item.done ? 'Aizpildīts' : 'Trūkst'"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    @endif

                    {{-- DARBĪBAS --}}
                    <div class="surface-card space-y-4 p-5">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Darbības</div>
                            <div class="mt-1 text-sm text-slate-500">Svarīgākā darbība ir datu rediģēšana un saglabāšana, pēc tam statusa maiņa.</div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button type="submit" form="repair-edit-form" class="btn-edit w-full justify-center">
                                <x-icon name="save" size="h-4 w-4" />
                                <span>Saglabāt izmaiņas</span>
                            </button>

                            @if ($repair->status === 'waiting')
                                <button type="button" class="btn-approve w-full justify-center" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                                    <x-icon name="stats" size="h-4 w-4" />
                                    <span>Sākt remontu</span>
                                </button>
                                <button type="button" class="btn-danger w-full justify-center" @click="submitTransition({{ $repair->id }}, 'cancelled')">
                                    <x-icon name="clear" size="h-4 w-4" />
                                    <span>Atcelt remontu</span>
                                </button>
                            @elseif ($repair->status === 'in-progress')
                                <button type="button" class="btn-approve w-full justify-center" @click="submitCompletion()">
                                    <x-icon name="check-circle" size="h-4 w-4" />
                                    <span>Pabeigt remontu</span>
                                </button>
                                <button type="button" class="btn-clear w-full justify-center" @click="submitTransition({{ $repair->id }}, 'waiting')">
                                    <x-icon name="back" size="h-4 w-4" />
                                    <span>Atpakaļ uz gaida</span>
                                </button>
                                <button type="button" class="btn-danger w-full justify-center" @click="submitTransition({{ $repair->id }}, 'cancelled')">
                                    <x-icon name="clear" size="h-4 w-4" />
                                    <span>Atcelt remontu</span>
                                </button>
                            @elseif ($repair->status === 'completed')
                                <button type="button" class="btn-clear w-full justify-center" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                                    <x-icon name="back" size="h-4 w-4" />
                                    <span>Atjaunot uz procesu</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    function repairProcess(config) {
        return {
            repairType: config.repairType,
            status: config.status,
            priority: config.priority,
            description: config.description,
            vendorName: config.vendorName,
            vendorContact: config.vendorContact,
            invoiceNumber: config.invoiceNumber,
            cost: config.cost,
            forceUpdate: 0,

            init() {
                // Seko līdzi izmaiņām lai atjauninātu gatavību
                this.$watch('repairType', () => this.forceUpdate++);
                this.$watch('status', () => this.forceUpdate++);
                this.$watch('description', () => this.forceUpdate++);
                this.$watch('vendorName', () => this.forceUpdate++);
                this.$watch('vendorContact', () => this.forceUpdate++);
                this.$watch('invoiceNumber', () => this.forceUpdate++);
            },

            nextStepLabel() {
                this.forceUpdate; // reaktivitatei
                if (this.status === 'in-progress') {
                    if (this.repairType === 'external') {
                        return 'Lai pabeigtu ārējo remontu, aizpildi 4 prasības.';
                    }
                    return 'Lai pabeigtu iekšējo remontu, aizpildi 1 prasību.';
                }
                return '';
            },

            nextStepReady() {
                this.forceUpdate; // reaktivitatei
                if (this.status !== 'in-progress') {
                    return true;
                }
                
                const rows = this.requirementRows();
                if (rows.length === 0) {
                    return true;
                }
                return rows.every(r => r.done);
            },

            requirementRows() {
                this.forceUpdate; // reaktivitatei
                // Ja nav in-progress statusā, nav prasību
                if (this.status !== 'in-progress') {
                    return [];
                }

                const rows = [];

                // 1. Apraksts - OBLIGĀTS VISIEM REMONTIEM
                const hasDescription = this.description && this.description.trim().length > 0;
                rows.push({
                    key: 'description',
                    label: 'Remonta apraksts',
                    done: hasDescription
                });

                // 2. Ja ir ĀRĒJAIS remonts - papildus 3 prasības
                if (this.repairType === 'external') {
                    const hasVendorName = this.vendorName && this.vendorName.trim().length > 0;
                    rows.push({
                        key: 'vendor_name',
                        label: 'Pakalpojuma sniedzējs',
                        done: hasVendorName
                    });

                    const hasVendorContact = this.vendorContact && this.vendorContact.trim().length > 0;
                    rows.push({
                        key: 'vendor_contact',
                        label: 'Vendora kontakts',
                        done: hasVendorContact
                    });

                    const hasInvoiceNumber = this.invoiceNumber && this.invoiceNumber.trim().length > 0;
                    rows.push({
                        key: 'invoice_number',
                        label: 'Rēķina numurs',
                        done: hasInvoiceNumber
                    });
                }

                // 3. Iekšējam remontam - TIKAI apraksts (vairāk nekas nav jāpievieno)

                return rows;
            },

            submitTransition(repairId, targetStatus) {
                if (targetStatus === 'in-progress' && !confirm('Vai tiešām sākt šo remontu?')) {
                    return;
                }

                if (targetStatus === 'waiting' && !confirm('Vai tiešām atgriezt remontu gaida statusā?')) {
                    return;
                }

                if (targetStatus === 'cancelled' && !confirm('Vai tiešām atcelt šo remontu?')) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = config.transitionBaseUrl + '/' + repairId + '/transition';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = config.csrfToken;
                form.appendChild(csrfInput);

                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = targetStatus;
                form.appendChild(statusInput);

                document.body.appendChild(form);
                form.submit();
            },

            submitCompletion() {
                const rows = this.requirementRows();
                const allDone = rows.length === 0 || rows.every(r => r.done);
                
                if (!allDone) {
                    const missingCount = rows.filter(r => !r.done).length;
                    alert('Lūdzu vispirms aizpildi ' + missingCount + ' trūkstošās prasības remonta pabeigšanai.');
                    return;
                }

                if (!confirm('Vai tiešām pabeigt šo remontu?')) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = config.transitionBaseUrl + '/' + config.repairId + '/completion';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = config.csrfToken;
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
            }
        };
    }
    </script>
</x-app-layout>
