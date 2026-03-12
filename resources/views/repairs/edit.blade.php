<x-app-layout>
    @php
        $statusLabels = [
            'waiting' => 'Gaida',
            'in-progress' => 'Procesa',
            'completed' => 'Pabeigts',
            'cancelled' => 'Atcelts',
        ];
        $priorityLabels = [
            'low' => 'Zema',
            'medium' => 'Videja',
            'high' => 'Augsta',
            'critical' => 'Kritiska',
        ];
        $typeLabels = [
            'internal' => 'Ieksejais',
            'external' => 'Arejais',
        ];
        $deviceAssignees = $devices->mapWithKeys(fn ($device) => [$device->id => $device->created_by])->all();
    @endphp

    <section class="repair-form-shell"
        x-data="{
            repairType: @js(old('repair_type', $repair->repair_type)),
            status: @js(old('status', $repair->status ?? 'waiting')),
            assignedTo: @js((string) old('assigned_to', $repair->assigned_to)),
            assignedTouched: true,
            deviceAssignees: @js($deviceAssignees),
            onDeviceChange(deviceId) {
                if (!this.assignedTouched) {
                    this.assignedTo = this.deviceAssignees[deviceId] ?? '';
                }
            }
        }">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Rediget remontu #{{ $repair->id }}</h1>
                <p class="device-page-subtitle">Labojiet statusu, terminus un remonta izpildi.</p>
            </div>
            <a href="{{ route('repairs.index') }}" class="type-back-link">Atpakal uz sarakstu</a>
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

        <form method="POST" action="{{ route('repairs.update', $repair) }}" class="repair-form-grid">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">1. Ierice un remonta tips</div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Ierice *</label>
                            <select name="device_id" required class="crud-control" @change="onDeviceChange($event.target.value)">
                                <option value="">Izvelies ierici</option>
                                @foreach ($devices as $device)
                                    <option value="{{ $device->id }}" @selected(old('device_id', $repair->device_id) == $device->id)>{{ $device->code ?? ('Ierice #' . $device->id) }} - {{ $device->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Remonta tips *</label>
                            <select name="repair_type" required class="crud-control" x-model="repairType">
                                @foreach ($repairTypes as $type)
                                    <option value="{{ $type }}" @selected(old('repair_type', $repair->repair_type) === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">2. Gaita un termini</div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label">Apraksts *</label>
                        <textarea name="description" rows="4" required class="crud-control">{{ old('description', $repair->description) }}</textarea>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="crud-label">Statuss</label>
                            <select name="status" class="crud-control" x-model="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', $repair->status ?? 'waiting') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Prioritate</label>
                            <select name="priority" class="crud-control">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', $repair->priority ?? 'medium') === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Sakuma datums</label>
                            <input type="date" value="{{ optional($repair->start_date)->format('Y-m-d') }}" class="crud-control bg-slate-50" disabled>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Planotais beigums</label>
                            <input type="date" name="estimated_completion" value="{{ old('estimated_completion', optional($repair->estimated_completion)->format('Y-m-d')) }}" class="crud-control">
                        </div>
                        <div x-show="status === 'completed'" x-cloak>
                            <label class="crud-label">Faktiskais beigums</label>
                            <input type="date" name="actual_completion" value="{{ old('actual_completion', optional($repair->actual_completion)->format('Y-m-d')) }}" class="crud-control">
                        </div>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">3. Atbildiba</div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Zinoja lietotajs</label>
                            <select name="issue_reported_by" class="crud-control">
                                <option value="">Nav</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('issue_reported_by', $repair->issue_reported_by) == $user->id)>{{ $user->employee?->full_name ?? ('Lietotajs #' . $user->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Pieskirts lietotajam</label>
                            <select name="assigned_to" class="crud-control" x-model="assignedTo" @change="assignedTouched = true">
                                <option value="">Nav</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->employee?->full_name ?? ('Lietotajs #' . $user->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div x-show="repairType === 'external'" x-cloak class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">4. Areja remonta dati</div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Piegadatajs *</label>
                            <input type="text" name="vendor_name" value="{{ old('vendor_name', $repair->vendor_name) }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Piegadataja kontakts *</label>
                            <input type="text" name="vendor_contact" value="{{ old('vendor_contact', $repair->vendor_contact) }}" class="crud-control">
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Rekina numurs</label>
                            <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number', $repair->invoice_number) }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Izmaksas (EUR)</label>
                            <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $repair->cost) }}" class="crud-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="type-form-actions">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Darbibas</div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="crud-btn-primary">Atjaunot</button>
                        <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Atcelt</a>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Pasreizeja informacija</div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="repair-info-row">
                            <span>Izveidots</span>
                            <strong>{{ $repair->created_at?->format('d.m.Y H:i') ?: '-' }}</strong>
                        </div>
                        <div class="repair-info-row">
                            <span>Sakuma datums</span>
                            <strong>{{ $repair->start_date?->format('d.m.Y') ?: '-' }}</strong>
                        </div>
                        <div class="repair-info-row">
                            <span>Pedejais statuss</span>
                            <strong>{{ $statusLabels[$repair->status ?? 'waiting'] ?? ($repair->status ?? 'waiting') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
