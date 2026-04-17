<section class="device-form-card">
    <div class="device-form-section-header">
        <div class="device-form-section-icon bg-emerald-50 text-emerald-700 ring-emerald-200">
            <x-icon name="users" size="h-5 w-5" />
        </div>
        <div class="device-form-section-copy">
            <div class="device-form-section-name">Statuss un piesaiste</div>
            <div class="device-form-section-note">Norādi statusu, atbildīgo personu un telpu, kur ierīce atrodas.</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @if ($isCreating)
            <div class="block">
                <span class="crud-label">Statuss</span>
                <input type="hidden" name="status" value="{{ \App\Models\Device::STATUS_ACTIVE }}">
                <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                    <span>Aktīva</span>
                </div>
            </div>
        @else
            <label class="block">
                <span class="crud-label">Statuss</span>
                @if ($isWrittenOff)
                    <input type="hidden" name="status" value="{{ $current?->status }}">
                @endif
                @if ($isWrittenOff)
                    <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                        <span>{{ $statusLabels[$current?->status] ?? 'Norakstīta' }}</span>
                    </div>
                @else
                    <x-searchable-select
                        name="status"
                        query-name="status_query"
                        identifier="device-status-form-select-{{ $formKey }}"
                        :options="$statusOptions"
                        :selected="(string) $selectedStatus"
                        :query="$selectedStatusLabel"
                        placeholder="Izvēlies statusu"
                        empty-message="Neviens statuss neatbilst meklējumam."
                    />
                @endif
            </label>
        @endif

        <label class="block">
            <span class="crud-label">Atbildīgā persona *</span>
            @if ($isWrittenOff)
                <input type="hidden" name="assigned_to_id" value="">
                <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                    <span>Nav piešķirts</span>
                </div>
            @else
                <x-searchable-select
                    name="assigned_to_id"
                    query-name="assigned_to_query"
                    identifier="device-assigned-user-form-select-{{ $formKey }}"
                    :options="$assignedUserOptions"
                    :selected="(string) $selectedAssignedToId"
                    :query="$selectedAssignedToLabel"
                    placeholder="Meklē atbildīgo personu"
                    empty-message="Neviens lietotājs neatbilst meklējumam."
                />
            @endif
        </label>

        <label class="block">
            <span class="crud-label">Telpa *</span>
            @if ($isWrittenOff)
                <input type="hidden" name="room_id" value="">
                <div class="crud-control flex items-center bg-slate-50 text-slate-700">
                    <span>Nav piešķirta telpai</span>
                </div>
            @else
                <x-searchable-select
                    name="room_id"
                    query-name="room_query"
                    identifier="device-room-form-select-{{ $formKey }}"
                    :options="$roomOptions"
                    :selected="(string) $selectedRoomId"
                    :query="$selectedRoomLabel"
                    placeholder="Meklē telpu"
                    empty-message="Neviena telpa neatbilst meklējumam."
                />
            @endif
        </label>

        <input type="hidden" name="building_id" value="{{ $isWrittenOff ? '' : $selectedBuildingId }}">
    </div>
</section>
