<x-app-layout>
<div style="background-color: #f5f5f7; padding: 20px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <!-- Header -->
        <div style="background-color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1 style="font-size: 24px; font-weight: 700; color: #1d1d1f; margin: 0;">🔧 Jauns Remonts</h1>
                <a href="{{ route('repairs.index') }}" style="padding: 10px 16px; background-color: #f5f5f7; color: #1d1d1f; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none;">Atpakaļ</a>
            </div>
        </div>

        <!-- Form Section -->
        <div style="background-color: white; border: 1px solid #e5e5e7; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
            @if ($errors->any())
                <div style="padding: 16px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; margin-bottom: 20px; color: #991b1b;">
                    <p style="font-weight: 600; margin: 0 0 8px 0;">Validācijas Kļūdas:</p>
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('repairs.store') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <!-- Device -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Ierīce *</label>
                    <select name="device_id" required style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;">
                        <option value="">-- Izvēlieties ierīci --</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" @selected(old('device_id') == $device->id)>
                                {{ $device->code ?? ('Device #' . $device->id) }} - {{ $device->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Apraksts *</label>
                    <textarea name="description" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px; font-family: inherit;">{{ old('description') }}</textarea>
                </div>

                <!-- Grid Row 1 -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Statuss</label>
                        <select name="status" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;">
                            <option value="">(noklusējums: waiting)</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" @selected(old('status') == $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Remonta tips *</label>
                        <select name="repair_type" required style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Izvēlieties --</option>
                            @foreach($repairTypes as $t)
                                <option value="{{ $t }}" @selected(old('repair_type') == $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Prioritāte</label>
                        <select name="priority" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;">
                            <option value="">(noklusējums: medium)</option>
                            @foreach($priorities as $p)
                                <option value="{{ $p }}" @selected(old('priority') == $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Dates -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Sākuma datums *</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Paredzamais beigums</label>
                        <input type="date" name="estimated_completion" value="{{ old('estimated_completion') }}" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Faktiskais beigums</label>
                        <input type="date" name="actual_completion" value="{{ old('actual_completion') }}" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                </div>

                <!-- Cost & Invoice -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Izmaksas (EUR)</label>
                        <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Rēķina numurs</label>
                        <input type="text" name="invoice_number" maxlength="50" value="{{ old('invoice_number') }}" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                </div>

                <!-- Vendor -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Piegādātāja nosaukums</label>
                        <input type="text" name="vendor_name" maxlength="100" value="{{ old('vendor_name') }}" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Piegādātāja kontakts</label>
                        <input type="text" name="vendor_contact" maxlength="100" value="{{ old('vendor_contact') }}" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                </div>

                <!-- Users -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Problēmu ziņojusi (lietotājs)</label>
                        <select name="issue_reported_by" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Nav --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(old('issue_reported_by') == $u->id)>{{ $u->name ?? ('User #' . $u->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #1d1d1f; margin-bottom: 6px;">Piešķirts (lietotājs)</label>
                        <select name="assigned_to" style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Nav --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(old('assigned_to') == $u->id)>{{ $u->name ?? ('User #' . $u->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 12px; margin-top: 20px;">
                    <button type="submit" style="padding: 10px 16px; background-color: #0071e3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">Saglabāt</button>
                    <a href="{{ route('repairs.index') }}" style="padding: 10px 16px; background-color: #f5f5f7; color: #1d1d1f; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none;">Atcelt</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
