<x-app-layout>
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Rediģēt komplekta pozīciju</h1>
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

        <form method="POST" action="{{ route('device-set-items.update', $deviceSetItem) }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Komplekts *</label>
                <select name="device_set_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Izvēlieties komplektu</option>
                    @foreach ($deviceSets as $set)
                        <option value="{{ $set->id }}" @selected(old('device_set_id', $deviceSetItem->device_set_id) == $set->id)>{{ $set->set_name ?? $set->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Ierīce *</label>
                <select name="device_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Izvēlieties ierīci</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected(old('device_id', $deviceSetItem->device_id) == $device->id)>{{ $device->name }} ({{ $device->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Daudzums</label>
                    <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity', $deviceSetItem->quantity ?? 1) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Loma komplektā</label>
                    <input type="text" name="role" maxlength="50" value="{{ old('role', $deviceSetItem->role) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Piemērs: galvenais dators">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Apraksts</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $deviceSetItem->description) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Atjaunināt</button>
                <a href="{{ route('device-set-items.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
