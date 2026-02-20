<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Jauna komplekta pozīcija</h1>
            <a href="{{ route('device-set-items.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakaļ uz sarakstu</a>
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

        <form method="POST" action="{{ route('device-set-items.store') }}" class="crud-form-card">
            @csrf

            <div>
                <label class="crud-label">Komplekts *</label>
                <select name="device_set_id" required class="crud-control">
                    <option value="">Izvēlieties komplektu</option>
                    @foreach ($deviceSets as $set)
                        <option value="{{ $set->id }}" @selected(old('device_set_id', $selectedDeviceSetId) == $set->id)>{{ $set->set_name ?? $set->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="crud-label">Ierīce *</label>
                <select name="device_id" required class="crud-control">
                    <option value="">Izvēlieties ierīci</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>{{ $device->name }} ({{ $device->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Daudzums</label>
                    <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity', 1) }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Loma komplektā</label>
                    <input type="text" name="role" maxlength="50" value="{{ old('role') }}" class="crud-control" placeholder="Piemērs: galvenais dators">
                </div>
            </div>

            <div>
                <label class="crud-label">Apraksts</label>
                <textarea name="description" rows="3" class="crud-control">{{ old('description') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabāt</button>
                <a href="{{ route('device-set-items.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>


