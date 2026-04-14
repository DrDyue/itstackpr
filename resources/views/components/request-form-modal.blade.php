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
        class="divide-y divide-gray-200"
    >
        @csrf

        <!-- Galvene -->
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ $titles[$type] }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ $descriptions[$type] }}</p>
        </div>

        <!-- Satura daļa -->
        <div class="px-6 py-4 space-y-6">
            <!-- Ierīces izvēle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ierīce <span class="text-red-500">*</span>
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
                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <!-- Apraksts / Iemesls / Piezīmes -->
            <div>
                @if($type === 'repair')
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Apraksts <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="description"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Apraksti problēmu, kas jārisina..."
                        required
                    >{{ old('description', '') }}</textarea>
                    @error('description')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                @elseif($type === 'writeoff')
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Iemesls <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Norādi iemeslu norakstīšanai..."
                        required
                    >{{ old('reason', '') }}</textarea>
                    @error('reason')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                @elseif($type === 'transfer')
                    <!-- Nodošana lietotājam -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nodot lietotājam <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="transfered_to_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
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
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Pārsūtīšanas iemesls -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Iemesls <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            name="transfer_reason"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Izskaidro, kāpēc nodot šo ierīci..."
                            required
                        >{{ old('transfer_reason', '') }}</textarea>
                        @error('transfer_reason')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <!-- Darbības poga -->
        <div class="flex justify-end gap-3 px-6 py-4">
            <button
                type="button"
                @click="$dispatch('close-modal', '{{ 'request-form-'.$type }}')"
                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
                Atcelt
            </button>
            
            <button
                type="submit"
                class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700"
            >
                Nosūtīt pieteikumu
            </button>
        </div>
    </form>
</x-modal>
