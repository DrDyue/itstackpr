<x-app-layout>
    @php
        $statusLabels = [
            'active' => 'Aktiva',
            'reserve' => 'Rezerve',
            'broken' => 'Bojata',
            'repair' => 'Remonta',
            'retired' => 'Norakstita',
            'kitting' => 'Komplektacija',
        ];
    @endphp

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Jauna ierice</h1>
                <p class="text-sm text-gray-500">Pievieno ierici ar attelu un garantijas dokumenta foto.</p>
            </div>
            <a href="{{ route('devices.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Atpakal uz sarakstu</a>
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

        <form method="POST" action="{{ route('devices.store') }}" enctype="multipart/form-data" class="crud-form-card">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Kods</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Nosaukums *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="crud-label">Tips *</label>
                    <select name="device_type_id" required class="crud-control">
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('device_type_id') == $type->id)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Modelis *</label>
                    <input type="text" name="model" value="{{ old('model') }}" required class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Statuss *</label>
                    <select name="status" required class="crud-control">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Eka</label>
                    <select name="building_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}" @selected(old('building_id') == $building->id)>{{ $building->building_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="crud-label">Telpa</label>
                    <select name="room_id" class="crud-control">
                        <option value="">Nav</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->building?->building_name }} / {{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Pieskirta personai</label>
                    <input type="text" name="assigned_to" value="{{ old('assigned_to') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Razotajs</label>
                    <input type="text" name="manufacturer" value="{{ old('manufacturer') }}" class="crud-control">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="crud-label">Pirkuma datums *</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" required class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Cena</label>
                    <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price') }}" class="crud-control">
                </div>
                <div>
                    <label class="crud-label">Garantija lidz</label>
                    <input type="date" name="warranty_until" value="{{ old('warranty_until') }}" class="crud-control">
                </div>
            </div>

            <div>
                <label class="crud-label">Serijas numurs</label>
                <input type="text" name="serial_number" value="{{ old('serial_number') }}" class="crud-control">
            </div>

            <div>
                <label class="crud-label">Piezimes</label>
                <textarea name="notes" rows="3" class="crud-control">{{ old('notes') }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="crud-label">Ierices foto</label>
                    <input type="file" name="device_image" accept="image/*" class="crud-control">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG vai WEBP. Augsuplade tiks optimizeta glabasanai.</p>
                </div>
                <div>
                    <label class="crud-label">Garantijas attels</label>
                    <input type="file" name="warranty_image" accept="image/*" class="crud-control">
                    <p class="mt-2 text-xs text-gray-500">Pievieno garantijas foto vai skenu, lai to var redzet detalu skatā.</p>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="crud-btn-primary">Saglabat</button>
                <a href="{{ route('devices.index') }}" class="crud-btn-secondary">Atcelt</a>
            </div>
        </form>
    </section>
</x-app-layout>
