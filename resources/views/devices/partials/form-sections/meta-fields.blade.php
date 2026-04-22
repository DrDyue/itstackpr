<div>
    <div class="form-group-sep">
        <span class="form-group-sep-label">Finanses</span>
        <div class="form-group-sep-line"></div>
    </div>

    <div class="form-field-group">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <x-localized-date-input
                    name="purchase_date"
                    label="Iegādes datums"
                    :value="$fieldValue('purchase_date', $current?->purchase_date?->format('Y-m-d'))"
                />
            </div>

            <div>
                <x-localized-date-input
                    name="warranty_until"
                    label="Garantija līdz"
                    :value="$fieldValue('warranty_until', $current?->warranty_until?->format('Y-m-d'))"
                />
            </div>

            <label class="block">
                <span class="crud-label">Iegādes cena</span>
                <div class="device-money-field">
                    <input type="number" step="0.01" name="purchase_price" value="{{ $fieldValue('purchase_price', $current?->purchase_price) }}" class="crud-control device-money-input">
                    <span class="device-money-suffix">€</span>
                </div>
            </label>
        </div>
    </div>
</div>

<div>
    <div class="form-group-sep">
        <span class="form-group-sep-label">Piezīmes un attēls</span>
        <div class="form-group-sep-line"></div>
    </div>

    <div class="form-field-group">
        <div class="grid gap-4 md:grid-cols-12">
            <label class="block md:col-span-7">
                <span class="crud-label">Piezīmes</span>
                <textarea name="notes" rows="4" class="crud-control device-notes-input">{{ $fieldValue('notes', $current?->notes) }}</textarea>
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
    </div>
</div>
