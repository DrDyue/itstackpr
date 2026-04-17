<section class="device-form-card">
    <div class="device-form-section-header">
        <div class="device-form-section-icon bg-violet-50 text-violet-700 ring-violet-200">
            <x-icon name="calendar" size="h-5 w-5" />
        </div>
        <div class="device-form-section-copy">
            <div class="device-form-section-name">Papildu informācija</div>
            <div class="device-form-section-note">Aizpildi tikai tos laukus, kas ir zināmi vai svarīgi uzskaitei.</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <x-localized-date-input
            name="purchase_date"
            label="Iegādes datums"
            :value="$fieldValue('purchase_date', $current?->purchase_date?->format('Y-m-d'))"
        />
        <label class="block">
            <span class="crud-label">Iegādes cena</span>
            <input type="number" step="0.01" name="purchase_price" value="{{ $fieldValue('purchase_price', $current?->purchase_price) }}" class="crud-control">
        </label>
        <x-localized-date-input
            name="warranty_until"
            label="Garantija līdz"
            :value="$fieldValue('warranty_until', $current?->warranty_until?->format('Y-m-d'))"
        />
        <div class="block">
            <span class="crud-label">Ierīces attēls</span>
            <input type="file" name="device_image" class="device-file-input">
            <div class="mt-2 text-xs text-slate-500">PNG, JPG vai WEBP līdz {{ (int) config('devices.max_upload_kb', 5120) / 1024 }} MB.</div>
            @if ($current)
                <label class="mt-3 inline-flex items-center gap-3">
                    <input type="checkbox" name="remove_device_image" value="1" class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-slate-700">Noņemt attēlu</span>
                </label>
            @endif

            @if ($deviceImageUrl)
                <div class="mt-3 overflow-hidden rounded-[1.25rem] border border-slate-200 bg-slate-50">
                    <img src="{{ $deviceImageUrl }}" alt="{{ $current?->name ?: 'Ierīce' }}" class="h-40 w-full object-contain">
                </div>
            @endif
        </div>
        <label class="block md:col-span-2">
            <span class="crud-label">Piezīmes</span>
            <textarea name="notes" rows="5" class="crud-control">{{ $fieldValue('notes', $current?->notes) }}</textarea>
        </label>
    </div>
</section>
