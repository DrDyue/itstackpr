<x-app-layout>
    @php
        $sorting = $sorting ?? ['sort' => 'type_name', 'direction' => 'asc'];
        $sortOptions = $sortOptions ?? [
            'type_name' => ['label' => 'tipa nosaukuma'],
            'devices_count' => ['label' => 'piesaistīto ierīču skaita'],
        ];
        $sortDirectionLabels = ['asc' => 'augošajā secībā', 'desc' => 'dilstošajā secībā'];
        $modalState = $deviceTypeModal ?? ['mode' => '', 'id' => '', 'type' => null];
        $modalMode = (string) ($modalState['mode'] ?? '');
        $modalTypeId = (string) ($modalState['id'] ?? '');
        $selectedModalType = $modalState['type'] ?? null;
        $createTypeName = old('type_name', '');
        $editTypeName = old('type_name', $selectedModalType?->type_name ?? '');
        $createErrors = $modalMode === 'create' && $errors->has('type_name')
            ? ['type_name' => $errors->first('type_name')]
            : [];
        $editErrors = $modalMode === 'edit' && $errors->has('type_name')
            ? ['type_name' => $errors->first('type_name')]
            : [];
        $editModalAction = $selectedModalType
            ? route('device-types.update', $selectedModalType)
            : route('device-types.index');
    @endphp

    <section
        class="app-shell app-shell-wide"
        x-data='{
            tableForm: null,
            createAction: @js(route("device-types.store")),
            updateActionTemplate: @js(route("device-types.update", ["device_type" => "__TYPE_ID__"])),
            editAction: @js(route("device-types.update", ["device_type" => "__TYPE_ID__"])),
            createForm: { type_name: @js($createTypeName) },
            editForm: { id: @js($modalTypeId), type_name: @js($editTypeName) },
            createErrors: @js($createErrors),
            editErrors: @js($editErrors),
            init() {
                this.tableForm = this.$root.querySelector("[data-async-table-form]");

                if (this.editForm.id) {
                    this.editAction = this.updateActionTemplate.replace("__TYPE_ID__", encodeURIComponent(String(this.editForm.id)));
                }
            },
            openCreate() {
                this.clearErrors("create");
                window.dispatchEvent(new CustomEvent("open-modal", { detail: "create-device-type" }));
            },
            openEdit(type) {
                if (!type) {
                    return;
                }

                this.clearErrors("edit");
                this.editForm.id = String(type.id ?? "");
                this.editForm.type_name = String(type.type_name ?? "");
                this.editAction = this.updateActionTemplate.replace("__TYPE_ID__", encodeURIComponent(String(this.editForm.id)));
                window.dispatchEvent(new CustomEvent("open-modal", { detail: "edit-device-type" }));
            },
            closeModal(name) {
                window.dispatchEvent(new CustomEvent("close-modal", { detail: name }));
            },
            clearErrors(mode) {
                if (mode === "edit") {
                    this.editErrors = {};
                    return;
                }

                this.createErrors = {};
            },
            setErrors(mode, errors = {}) {
                const normalized = Object.entries(errors || {}).reduce((carry, [key, value]) => {
                    carry[key] = Array.isArray(value) ? String(value[0] ?? "") : String(value ?? "");
                    return carry;
                }, {});

                if (mode === "edit") {
                    this.editErrors = normalized;
                    return;
                }

                this.createErrors = normalized;
            },
            async refreshTable() {
                if (!this.tableForm || typeof window.submitAsyncTableForm !== "function") {
                    return;
                }

                await window.submitAsyncTableForm(this.tableForm, { resetPage: false });
            },
            async submitDeviceType(mode, event) {
                const form = event.currentTarget;
                const submitButton = form.querySelector("[data-device-type-submit]");
                const action = form.getAttribute("action") || (mode === "edit" ? this.editAction : this.createAction);

                if (submitButton) {
                    submitButton.disabled = true;
                }

                try {
                    const response = await fetch(action, {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: new FormData(form),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        if (response.status === 422 && payload.errors) {
                            this.setErrors(mode, payload.errors);

                            const firstField = Object.keys(payload.errors)[0];
                            if (firstField) {
                                window.requestAnimationFrame(() => {
                                    form.querySelector(`[name="${firstField}"]`)?.focus();
                                });
                            }

                            return;
                        }

                        window.dispatchAppToast({
                            title: "Saglabāšana neizdevās",
                            message: payload.message || "Neizdevās saglabāt ierīces tipu.",
                            tone: "info",
                        });

                        return;
                    }

                    this.clearErrors(mode);
                    window.dispatchAppToast({
                        title: "Veiksmīgi",
                        message: payload.message || (mode === "create" ? "Ierīces tips veiksmīgi pievienots." : "Ierīces tips atjaunināts."),
                        tone: "success",
                    });

                    this.closeModal(mode === "create" ? "create-device-type" : "edit-device-type");
                    await new Promise((resolve) => window.requestAnimationFrame(resolve));
                    await this.refreshTable();
                } catch (error) {
                    window.dispatchAppToast({
                        title: "Savienojuma kļūda",
                        message: "Neizdevās saglabāt ierīces tipu.",
                        tone: "info",
                    });
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                }
            },
        }'
        x-init="init()"
    >
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="page-eyebrow">
                            <x-icon name="type" size="h-4 w-4" />
                            <span>Ierīču tipi</span>
                        </div>
                        <div class="inventory-inline-metrics">
                            <span class="inventory-inline-chip inventory-inline-chip-slate">
                                <x-icon name="type" size="h-3.5 w-3.5" />
                                <span class="inventory-inline-label">Kopā</span>
                                <span class="inventory-inline-value">{{ $types->total() }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-sky">
                            <x-icon name="type" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Ierīču tipi</h1>
                            <p class="page-subtitle">Vienkāršota tipu vārdnīca ar saistīto ierīču skaitu un ātru pārvaldību modernās modālajās formās.</p>
                        </div>
                    </div>
                </div>

                <div class="page-actions">
                    <a href="{{ route('device-types.create') }}" class="btn-create" @click.prevent="openCreate()">
                        <x-icon name="plus" size="h-4 w-4" />
                        <span>Pievienot tipu</span>
                    </a>
                </div>
            </div>
        </div>

        <div id="device-types-index-root" data-async-table-root>
            <form method="GET" action="{{ route('device-types.index') }}" data-async-table-form data-async-root="#device-types-index-root">
                <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" data-sort-hidden="field">
                <input type="hidden" name="direction" value="{{ $sorting['direction'] }}" data-sort-hidden="direction">
            </form>

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
            @endif

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="app-table-shell">
                <div class="app-table-scroll rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <table class="app-table-content app-table-content-compact min-w-full text-sm">
                        <thead class="app-table-head bg-slate-50 text-left text-slate-500">
                            <tr>
                                @foreach ([
                                    'type_name' => 'Ierīces tips',
                                    'devices_count' => 'Piesaistītās ierīces',
                                ] as $column => $label)
                                    @php
                                        $isCurrentSort = $sorting['sort'] === $column;
                                        $defaultDirection = $column === 'devices_count' ? 'desc' : 'asc';
                                        $nextDirection = $isCurrentSort && $sorting['direction'] === 'asc'
                                            ? 'desc'
                                            : ($isCurrentSort && $sorting['direction'] === 'desc' ? 'asc' : $defaultDirection);
                                        $sortMessage = 'Tabula "Ierīču tipi" kārtota pēc ' . ($sortOptions[$column]['label'] ?? mb_strtolower($label)) . ' ' . ($sortDirectionLabels[$nextDirection] ?? '');
                                    @endphp
                                    <th class="{{ match ($column) {
                                        'type_name' => 'table-col-name',
                                        'devices_count' => 'table-col-code',
                                        default => '',
                                    } }} px-4 py-3">
                                        <button
                                            type="button"
                                            class="device-sort-trigger {{ $isCurrentSort ? 'device-sort-trigger-active' : '' }}"
                                            data-sort-trigger="true"
                                            data-sort-field="{{ $column }}"
                                            data-sort-direction="{{ $nextDirection }}"
                                            data-sort-toast="{{ $sortMessage }}"
                                        >
                                            <span>{{ $label }}</span>
                                            <span class="device-sort-icon" aria-hidden="true">
                                                <svg class="h-[1.05em] w-[1.05em]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 9 3.75-3.75L15.75 9" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15-3.75 3.75L8.25 15" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>
                                @endforeach
                                <th class="table-col-actions px-4 py-3 text-right">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($types as $type)
                                @php
                                    $canDelete = (int) $type->devices_count === 0;
                                    $deleteTooltip = 'Šo ierīces tipu nevar dzēst, kamēr nav atbrīvoti visi ieraksti, kas ir saistīti ar šo ierīces tipu.';
                                @endphp
                                <tr class="app-table-row border-t border-slate-100 align-middle" data-table-row-id="device-type-{{ $type->id }}" data-table-search-value="{{ \Illuminate\Support\Str::lower($type->type_name) }}">
                                    <td class="px-4 py-4">
                                        <div class="app-table-cell-strong">{{ $type->type_name }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($type->devices_count > 0)
                                            <a
                                                href="{{ route('devices.index', ['type' => $type->id, 'type_query' => $type->type_name]) }}"
                                                class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 transition hover:bg-sky-100"
                                            >
                                                {{ $type->devices_count }} ierīces
                                            </a>
                                        @else
                                            <span class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500">
                                                0 ierīces
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="device-type-actions">
                                            <a
                                                href="{{ route('device-types.edit', $type) }}"
                                                class="btn-edit"
                                                @click.prevent="openEdit({ id: {{ $type->id }}, type_name: @js($type->type_name) })"
                                            >
                                                <x-icon name="edit" size="h-4 w-4" />
                                                <span>Rediģēt</span>
                                            </a>

                                            @if ($canDelete)
                                                <form
                                                    method="POST"
                                                    action="{{ route('device-types.destroy', $type) }}"
                                                    data-app-confirm-title="Dzēst ierīces tipu?"
                                                    data-app-confirm-message="Vai tiešām dzēst šo ierīces tipu?"
                                                    data-app-confirm-accept="Jā, dzēst"
                                                    data-app-confirm-cancel="Nē"
                                                    data-app-confirm-tone="danger"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">
                                                        <x-icon name="trash" size="h-4 w-4" />
                                                        <span>Dzēst</span>
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn-disabled"
                                                    data-app-toast-title="Dzēšana nav pieejama"
                                                    data-app-toast-message="{{ $deleteTooltip }}"
                                                    data-app-toast-tone="info"
                                                >
                                                    <x-icon name="trash" size="h-4 w-4" />
                                                    <span>Dzēst</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6">
                                        <x-empty-state
                                            compact
                                            icon="tag"
                                            title="Ierīču tipu saraksts pašlaik ir tukšs"
                                            description="Pievieno pirmo ierīces tipu, lai varētu to izmantot ierīču formās un filtros."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($types->hasPages())
                <div class="mt-5">{{ $types->links() }}</div>
            @endif

            <x-modal name="create-device-type" :show="$modalMode === 'create'" maxWidth="xl" focusable>
                <div class="p-0">
                    @include('device_types.partials.modal-form', [
                        'mode' => 'create',
                        'action' => route('device-types.store'),
                        'modalName' => 'create-device-type',
                        'title' => 'Jauns ierīces tips',
                        'subtitle' => 'Pievieno jaunu klasifikatora ierakstu bez lapas maiņas.',
                        'submitLabel' => 'Saglabāt tipu',
                        'submitClass' => 'btn-create',
                    ])
                </div>
            </x-modal>

            <x-modal name="edit-device-type" :show="$modalMode === 'edit'" maxWidth="xl" focusable>
                <div class="p-0">
                    @include('device_types.partials.modal-form', [
                        'mode' => 'edit',
                        'action' => $editModalAction,
                        'modalName' => 'edit-device-type',
                        'type' => $selectedModalType,
                        'title' => 'Rediģēt ierīces tipu',
                        'subtitle' => 'Atjauno tipa nosaukumu un uzreiz saglabā to pašā saraksta lapā.',
                        'submitLabel' => 'Saglabāt izmaiņas',
                        'submitClass' => 'btn-edit',
                    ])
                </div>
            </x-modal>
        </div>
    </section>
</x-app-layout>
