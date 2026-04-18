@props([
    'mode' => 'create',
    'modalName',
    'repair' => null,
    'deviceOptions' => [],
    'statusLabels' => [],
    'priorityLabels' => [],
    'typeLabels' => [],
    'priorities' => [],
    'preselectedDeviceId' => null,
    'featureMessage' => null,
])

@php
    $isEdit = $mode === 'edit' && $repair;
    $modalForm = $isEdit ? 'repair_edit_' . $repair->id : 'repair_create';
    $shouldUseOldInput = old('modal_form') === $modalForm;
    $action = $isEdit ? route('repairs.update', $repair) : route('repairs.store');
    $title = $isEdit ? 'Rediģēt remontu' : 'Jauns remonts';
    $subtitle = $isEdit
        ? 'Pārvaldi remonta ierakstu un statusa darbības vienā modālī.'
        : 'Izveido faktisko remonta darbu bez lapas maiņas.';
    $submitLabel = $isEdit ? 'Saglabāt izmaiņas' : 'Saglabāt remontu';
    $submitClass = $isEdit ? 'btn-edit' : 'btn-create';
    $linkedRequestUrl = $isEdit && $repair?->request_id
        ? route('repair-requests.index', ['request_id' => $repair->request_id, 'statuses_filter' => 1])
        : null;
    $deviceShowUrl = $isEdit && $repair?->device ? route('devices.show', $repair->device) : null;
@endphp

<x-modal :name="$modalName" maxWidth="6xl">
    <form
        method="POST"
        action="{{ $action }}"
        class="flex max-h-[calc(100vh-2.5rem)] flex-col overflow-hidden"
        x-data="repairProcess({
            repairId: {{ $repair?->id ? (int) $repair->id : 'null' }},
            repairType: @js($shouldUseOldInput ? old('repair_type', $repair?->repair_type ?? 'internal') : ($repair?->repair_type ?? 'internal')),
            status: @js($repair?->status ?? 'waiting'),
            priority: @js($shouldUseOldInput ? old('priority', $repair?->priority ?? 'medium') : ($repair?->priority ?? 'medium')),
            description: @js($shouldUseOldInput ? old('description', $repair?->description ?? '') : ($repair?->description ?? '')),
            vendorName: @js($shouldUseOldInput ? old('vendor_name', $repair?->vendor_name ?? '') : ($repair?->vendor_name ?? '')),
            vendorContact: @js($shouldUseOldInput ? old('vendor_contact', $repair?->vendor_contact ?? '') : ($repair?->vendor_contact ?? '')),
            invoiceNumber: @js($shouldUseOldInput ? old('invoice_number', $repair?->invoice_number ?? '') : ($repair?->invoice_number ?? '')),
            cost: @js($shouldUseOldInput ? old('cost', $repair?->cost ?? '') : ($repair?->cost ?? '')),
            transitionBaseUrl: @js(url('/repairs')),
            csrfToken: @js(csrf_token()),
        })"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="modal_form" value="{{ $modalForm }}">

        <div class="device-type-modal-head">
            <div class="device-type-modal-head-copy">
                <div class="device-type-modal-badge">
                    <x-icon :name="$isEdit ? 'edit' : 'plus'" size="h-4 w-4" />
                    <span>{{ $isEdit ? 'Rediģēšana' : 'Jauns ieraksts' }}</span>
                </div>
                <div class="device-type-modal-title-row">
                    <div class="device-type-modal-icon">
                        <x-icon name="repair" size="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="device-type-modal-title">{{ $title }}</h2>
                        <p class="device-type-modal-subtitle">{{ $subtitle }}</p>
                    </div>
                </div>
            </div>

            <button type="button" class="device-type-modal-close" x-data @click="$dispatch('close-modal', '{{ $modalName }}')" aria-label="Aizvērt">
                <x-icon name="x-mark" size="h-4 w-4" />
            </button>
        </div>

        <div class="device-type-modal-body overflow-y-auto">
            @if ($shouldUseOldInput && $errors->any())
                <x-validation-summary
                    class="mb-5"
                    :title="$isEdit ? 'Neizdevās saglabāt remonta izmaiņas' : 'Neizdevās izveidot remontu'"
                    :field-labels="[
                        'device_id' => 'Ierīce',
                        'description' => 'Apraksts',
                        'repair_type' => 'Remonta tips',
                        'priority' => 'Prioritāte',
                        'cost' => 'Izmaksas',
                        'vendor_name' => 'Pakalpojuma sniedzējs',
                        'vendor_contact' => 'Vendora kontakts',
                        'invoice_number' => 'Rēķina numurs',
                    ]"
                />
            @endif

            @if (! empty($featureMessage))
                <x-empty-state compact icon="information-circle" title="Funkcija īslaicīgi nav pieejama" :description="$featureMessage" />
            @endif

            @if ($isEdit)
                <div class="mb-5 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-pill context="repair" :value="$repair->status" :label="$statusLabels[$repair->status] ?? null" />
                        <x-status-pill context="priority" :value="$repair->priority" :label="$priorityLabels[$repair->priority] ?? null" />
                        <x-status-pill context="repair-type" :value="$repair->repair_type" :label="$typeLabels[$repair->repair_type] ?? null" />
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                        @if ($deviceShowUrl)
                            <a href="{{ $deviceShowUrl }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:border-slate-300 hover:text-slate-900">
                                <x-icon name="device" size="h-4 w-4" />
                                <span>Skatīt ierīci</span>
                            </a>
                        @endif
                        @if ($linkedRequestUrl)
                            <a href="{{ $linkedRequestUrl }}" class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 font-semibold text-violet-700 hover:bg-violet-100">
                                <x-icon name="repair-request" size="h-4 w-4" />
                                <span>Saistītais pieprasījums</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @include('repairs.partials.form-fields', ['repair' => $repair])
        </div>

        <div class="device-type-modal-actions justify-between gap-4">
            @if ($isEdit)
                <div class="flex flex-wrap items-center gap-2">
                    @if ($repair->status === 'waiting')
                        <button type="button" class="btn-search" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                            <x-icon name="stats" size="h-4 w-4" />
                            <span>Pārvietot uz procesu</span>
                        </button>
                    @elseif ($repair->status === 'in-progress')
                        <button type="button" class="btn-search" @click="submitTransition({{ $repair->id }}, 'waiting')">
                            <x-icon name="clock" size="h-4 w-4" />
                            <span>Atgriezt gaidīšanā</span>
                        </button>
                        <button type="button" class="btn-create" @click="submitTransition({{ $repair->id }}, 'completed')">
                            <x-icon name="check-circle" size="h-4 w-4" />
                            <span>Pabeigt remontu</span>
                        </button>
                    @elseif ($repair->status === 'completed')
                        <button type="button" class="btn-search" @click="submitTransition({{ $repair->id }}, 'in-progress')">
                            <x-icon name="repair" size="h-4 w-4" />
                            <span>Atgriezt procesā</span>
                        </button>
                    @endif

                    @if (in_array($repair->status, ['waiting', 'in-progress'], true))
                        <button type="button" class="btn-danger-solid" @click="submitTransition({{ $repair->id }}, 'cancelled')">
                            <x-icon name="clear" size="h-4 w-4" />
                            <span>Atcelt remontu</span>
                        </button>
                    @endif
                </div>
            @else
                <div class="text-sm text-slate-500">
                    Remonta ieraksts tiks izveidots ar statusu "Gaida".
                </div>
            @endif

            <div class="device-type-modal-actions-buttons">
                <button type="button" class="btn-clear" x-data @click="$dispatch('close-modal', '{{ $modalName }}')">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Atcelt</span>
                </button>
                <button type="submit" class="{{ $submitClass }}">
                    <x-icon name="save" size="h-4 w-4" />
                    <span>{{ $submitLabel }}</span>
                </button>
            </div>
        </div>
    </form>
</x-modal>
