<div>
    <div class="form-group-sep">
        <span class="form-group-sep-label">Piesaiste un atrašanās vieta</span>
        <div class="form-group-sep-line"></div>
    </div>

    <div class="grid gap-4 md:grid-cols-12">
        <label class="block md:col-span-6">
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

        <label class="block md:col-span-6">
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
</div>
