{{--
    Modāļa forma pieprasījumiem (jaunu pieteikumu izveidošanai).
    
    Īpašības:
    - type: pieprasījuma tips ('repair', 'writeoff', 'transfer')
    - show: vai parādīt modāli (default: false)
    - deviceOptions: ierīču variantus filtram
    - userOptions: lietotāju variantus (tikai transfer tipam)
--}}
@props([
    'type' => 'repair',
    'show' => false,
    'deviceOptions' => [],
    'userOptions' => [],
])

@php
    // Route noskaidroša pēc tipa
    $routes = [
        'repair' => 'repair-requests.store',
        'writeoff' => 'writeoff-requests.store',
        'transfer' => 'device-transfers.store',
    ];
    
    $submitRoute = route($routes[$type]);
    
    // Tituli un kopijas
    $titles = [
        'repair' => 'Jauns remonta pieteikums',
        'writeoff' => 'Jauns norakstīšanas pieteikums',
        'transfer' => 'Jauns ierīces nodošanas pieteikums',
    ];
    
    $descriptions = [
        'repair' => 'Apraksti problēmu, kas jārisina.',
        'writeoff' => 'Norādi iemeslu, kāpēc ierīci vajadzētu norakstīt.',
        'transfer' => 'Norādi, kam nodot ierīci, un izskaidro iemeslu.',
    ];
@endphp

<x-modal :name="'request-form-'.$type" :show="$show" maxWidth="lg">
    <form
        method="POST"
        action="{{ $submitRoute }}"
        class="surface-card space-y-6 p-6"
    >
        @csrf
        <input type="hidden" name="request_form_type" value="{{ $type }}">

        <!-- Galvene -->
        <div>
            <h2 class="page-title">{{ $titles[$type] }}</h2>
            <p class="page-subtitle">{{ $descriptions[$type] }}</p>
        </div>

        <!-- Satura daļa -->
        <div class="space-y-6">
            <!-- Ierīces izvēle -->
            <div>
                <label class="crud-label">
                    Ierīce <span class="text-rose-500">*</span>
                </label>
                <x-searchable-select
                    name="device_id"
                    query-name="device_query"
                    :identifier="'request-form-device-' . $type"
                    :options="$deviceOptions"
                    :selected="old('device_id', '')"
                    :query="old('device_query', '')"
                    placeholder="Meklē pēc nosaukuma, koda vai telpas"
                    empty-message="Neviena ierīce neatbilst meklējumam."
                />
                @error('device_id')
                    <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <!-- Apraksts / Iemesls / Piezīmes -->
            <div>
                @if($type === 'repair')
                    <label class="crud-label">
                        Apraksts <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="description"
                        rows="5"
                        class="crud-control"
                        placeholder="Apraksti problēmu, kas jārisina..."
                        required
                    >{{ old('description', '') }}</textarea>
                    @error('description')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                @elseif($type === 'writeoff')
                    <label class="crud-label">
                        Iemesls <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        rows="5"
                        class="crud-control"
                        placeholder="Norādi iemeslu norakstīšanai..."
                        required
                    >{{ old('reason', '') }}</textarea>
                    @error('reason')
                        <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                    @enderror
                @elseif($type === 'transfer')
                    <!-- Nodošana lietotājam -->
                    <div>
                        <label class="crud-label">
                            Nodot lietotājam <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="transfered_to_id"
                            class="crud-control"
                            required
                        >
                            <option value="">-- Izvēlies lietotāju --</option>
                            @foreach($userOptions as $option)
                                <option value="{{ $option['value'] }}" @selected(old('transfered_to_id') == $option['value'])>
                                    {{ $option['label'] }}
                                    @if(!empty($option['description']))
                                        ({{ $option['description'] }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('transfered_to_id')
                            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Pārsūtīšanas iemesls -->
                    <div>
                        <label class="crud-label">
                            Iemesls <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            name="transfer_reason"
                            rows="5"
                            class="crud-control"
                            placeholder="Izskaidro, kāpēc nodot šo ierīci..."
                            required
                        >{{ old('transfer_reason', '') }}</textarea>
                        @error('transfer_reason')
                            <div class="mt-2 text-sm text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <!-- Darbības poga -->
        <div class="flex justify-end gap-3">
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: '{{ 'request-form-'.$type }}' }))"
                class="btn-clear"
            >
                <x-icon name="clear" size="h-4 w-4" />
                <span>Atcelt</span>
            </button>
            
            <button
                type="submit"
                class="btn-create"
            >
                <x-icon name="send" size="h-4 w-4" />
                <span>Nosūtīt</span>
            </button>
        </div>
    </form>
</x-modal>
