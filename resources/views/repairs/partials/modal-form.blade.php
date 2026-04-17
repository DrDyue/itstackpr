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

            @include('repairs.partials.form-fields', ['repair' => $repair])
        </div>

        <div class="device-type-modal-actions justify-end">
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
