<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediget komplekta poziciju</h1>
            <a href="{{ route('device-set-items.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Atpakal uz sarakstu
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('device-set-items.update', $deviceSetItem) }}" class="crud-form-card">
            @csrf
            @method('PUT')

            <div>
                <label class="crud-label">Komplekts *</label>
                <select name="device_set_id" required class="crud-control">
                    <option value="">Izvelieties komplektu</option>
                    @foreach ($deviceSets as $set)
                        <option value="{{ $set->id }}" @selected(old('device_set_id', $deviceSetItem->device_set_id) == $set->id)>{{ $set->set_name ?? $set->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="crud-label">Ierice *</label>
                <select name="device_id" required class="crud-control">
                    <option value="">Izvelieties ierici</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected(old('device_id', $deviceSetItem->device_id) == $device->id)>{{ $device->name }} ({{ $device->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Daudzums</label>
                    <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity', $deviceSetItem->quantity ?? 1) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Loma komplekta</label>
                    <input type="text" name="role" maxlength="50" value="{{ old('role', $deviceSetItem->role) }}" class="crud-control" placeholder="Piemers: galvenais dators">
                </div>
            </div>

            <div>
                <label class="crud-label">Apraksts</label>
                <textarea name="description" rows="3" class="crud-control">{{ old('description', $deviceSetItem->description) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Atjauninat
                </button>
                <a href="{{ route('device-set-items.index') }}" class="crud-btn-secondary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                    Atcelt
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
