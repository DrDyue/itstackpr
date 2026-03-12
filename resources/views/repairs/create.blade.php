<x-app-layout>
    @php
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
            repairType: @js(old('repair_type', 'internal')),
            status: 'waiting',
            assignedTo: @js((string) old('assigned_to', '')),
            assignedTouched: {{ old('assigned_to') ? 'true' : 'false' }},
            deviceAssignees: @js($deviceAssignees),
            onDeviceChange(deviceId) {
                if (!this.assignedTouched) {
                    this.assignedTo = this.deviceAssignees[deviceId] ?? '';
                }
            }
        }"
        x-init="onDeviceChange(@js(old('device_id', '')))">
        <div class="device-page-header">
            <div>
                <h1 class="device-page-title">Jauns remonts</h1>
                <p class="device-page-subtitle">Izveido remonta ierakstu secigi, tikai ar vajadzigajiem laukiem.</p>
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

        <form method="POST" action="{{ route('repairs.store') }}" class="repair-form-grid">
            @csrf
            <input type="hidden" name="status" value="waiting">

            <div class="space-y-4">
                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">1. Izvelies ierici un remonta tipu</div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Ierice *</label>
                            <select name="device_id" required class="crud-control" @change="onDeviceChange($event.target.value)">
                                <option value="">Izvelies ierici</option>
                                @foreach ($devices as $device)
                                    <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>{{ $device->code ?? ('Ierice #' . $device->id) }} - {{ $device->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Remonta tips *</label>
                            <select name="repair_type" required class="crud-control" x-model="repairType">
                                @foreach ($repairTypes as $type)
                                    <option value="{{ $type }}" @selected(old('repair_type', 'internal') === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">2. Remonta saturs</div>
                    </div>

                    <div class="mt-4">
                        <label class="crud-label">Apraksts *</label>
                        <textarea name="description" rows="4" required class="crud-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Prioritate</label>
                            <select name="priority" class="crud-control">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crud-label">Planotais beigums</label>
                            <input type="date" name="estimated_completion" value="{{ old('estimated_completion') }}" class="crud-control">
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
                                    <option value="{{ $user->id }}" @selected(old('issue_reported_by', $defaultReporterId) == $user->id)>{{ $user->employee?->full_name ?? ('Lietotajs #' . $user->id) }}</option>
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
                            <input type="text" name="vendor_name" value="{{ old('vendor_name') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Piegadataja kontakts *</label>
                            <input type="text" name="vendor_contact" value="{{ old('vendor_contact') }}" class="crud-control">
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="crud-label">Rekina numurs</label>
                            <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number') }}" class="crud-control">
                        </div>
                        <div>
                            <label class="crud-label">Izmaksas (EUR)</label>
                            <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}" class="crud-control">
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
                        <button type="submit" class="crud-btn-primary">Saglabat</button>
                        <a href="{{ route('repairs.index') }}" class="crud-btn-secondary">Atcelt</a>
                    </div>
                </div>

                <div class="repair-form-card">
                    <div class="type-form-section-head">
                        <div class="device-form-section-name">Kas notiek automatiski</div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="repair-info-row">
                            <span>Sakuma datums</span>
                            <strong>tiks iestatits automatiski</strong>
                        </div>
                        <div class="repair-info-row">
                            <span>Statuss</span>
                            <strong>Gaida</strong>
                        </div>
                        <div class="repair-info-row">
                            <span>Pieskirts</span>
                            <strong>pec noklusejuma ierices izveidotajam</strong>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-app-layout>
