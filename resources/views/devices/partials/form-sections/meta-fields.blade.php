<section class="device-form-card">
    <div class="device-form-section-header">
        <div class="device-form-section-icon bg-violet-50 text-violet-700 ring-violet-200">
            <x-icon name="calendar" size="h-5 w-5" />
        </div>
        <div class="device-form-section-copy">
            <div class="device-form-section-name">Iegāde, piezīmes un attēls</div>
            <div class="device-form-section-note">Sakārto iegādes informāciju loģiskā secībā un pievieno attēlu, ja tas palīdz ierīci ātrāk atpazīt.</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-12">
        <div class="md:col-span-4">
            <x-localized-date-input
                name="purchase_date"
                label="Iegādes datums"
                :value="$fieldValue('purchase_date', $current?->purchase_date?->format('Y-m-d'))"
            />
        </div>

        <div class="md:col-span-4">
            <x-localized-date-input
                name="warranty_until"
                label="Garantija līdz"
                :value="$fieldValue('warranty_until', $current?->warranty_until?->format('Y-m-d'))"
            />
        </div>

        <label class="block md:col-span-4">
            <span class="crud-label">Iegādes cena</span>
            <div class="device-money-field">
                <input type="number" step="0.01" name="purchase_price" value="{{ $fieldValue('purchase_price', $current?->purchase_price) }}" class="crud-control device-money-input">
                <span class="device-money-suffix">€</span>
            </div>
        </label>

        <label class="block md:col-span-7">
            <span class="crud-label">Piezīmes</span>
            <textarea name="notes" rows="8" class="crud-control device-notes-input">{{ $fieldValue('notes', $current?->notes) }}</textarea>
        </label>

        <div class="device-image-field md:col-span-5">
            <div class="crud-label">Ierīces attēls</div>
            <div class="device-image-upload-card">
                <input type="file" name="device_image" class="device-file-input">
                <div class="device-image-upload-copy">PNG, JPG vai WEBP līdz {{ (int) config('devices.max_upload_kb', 5120) / 1024 }} MB.</div>

                @if ($current)
                    <label class="device-image-remove-toggle">
                        <input type="checkbox" name="remove_device_image" value="1" class="rounded border-gray-300 text-blue-600">
                        <span>Noņemt attēlu</span>
                    </label>
                @endif

                @if ($deviceImageUrl)
                    <div class="device-image-preview-card">
                        <img src="{{ $deviceImageUrl }}" alt="{{ $current?->name ?: 'Ierīce' }}" class="device-image-preview-frame">
                    </div>
                @else
                    <div class="device-image-preview-empty">
                        <x-icon name="device" size="h-7 w-7" />
                        <span>Priekšskatījums parādīsies pēc attēla pievienošanas.</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
