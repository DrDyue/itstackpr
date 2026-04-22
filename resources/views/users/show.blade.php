{{-- Lietotāja detalizētais profils administratoram. --}}
<x-app-layout>
    @php
        $assignedDevicesUrl = route('devices.index', ['assigned_to_id' => $managedUser->id, 'assigned_to_query' => $managedUser->full_name]);
        $auditUrl = route('audit-log.index', ['user_id' => $managedUser->id, 'user_query' => $managedUser->full_name]);
        $repairRequestsUrl = route('repair-requests.index', ['requester_id' => $managedUser->id, 'requester_query' => $managedUser->full_name, 'statuses_filter' => 1]);
        $writeoffRequestsUrl = route('writeoff-requests.index', ['requester_id' => $managedUser->id, 'requester_query' => $managedUser->full_name, 'statuses_filter' => 1]);
        $outgoingTransfersUrl = route('device-transfers.index', ['requester_id' => $managedUser->id, 'requester_query' => $managedUser->full_name, 'statuses_filter' => 1]);
        $incomingTransfersUrl = route('device-transfers.index', ['recipient_id' => $managedUser->id, 'recipient_query' => $managedUser->full_name, 'statuses_filter' => 1]);
    @endphp

    <section class="app-shell app-shell-wide">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-4xl">
                    <div class="page-eyebrow">
                        <x-icon name="users" size="h-4 w-4" />
                        <span>Lietotāja profils</span>
                    </div>

                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet">
                            <x-icon name="profile" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">{{ $managedUser->full_name }}</h1>
                            <p class="page-subtitle">
                                {{ $managedUser->email }}
                                @if ($managedUser->job_title)
                                    | {{ $managedUser->job_title }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="inventory-inline-metrics mt-4">
                        <span class="inventory-inline-chip inventory-inline-chip-violet">
                            <x-icon name="users" size="h-3.5 w-3.5" />
                            <span class="inventory-inline-label">Loma</span>
                            <span class="inventory-inline-value">{{ $roleLabels[$managedUser->role] ?? $managedUser->role }}</span>
                        </span>
                        <span class="inventory-inline-chip inventory-inline-chip-sky">
                            <x-icon name="device" size="h-3.5 w-3.5" />
                            <span class="inventory-inline-label">Ierīces</span>
                            <span class="inventory-inline-value">{{ $managedUser->assigned_devices_count ?? 0 }}</span>
                        </span>
                        <span class="inventory-inline-chip inventory-inline-chip-amber">
                            <x-icon name="repair-request" size="h-3.5 w-3.5" />
                            <span class="inventory-inline-label">Atvērtie pieprasījumi</span>
                            <span class="inventory-inline-value">{{ $managedUser->active_requests_total ?? 0 }}</span>
                        </span>
                        <span class="inventory-inline-chip inventory-inline-chip-slate">
                            <x-icon name="audit" size="h-3.5 w-3.5" />
                            <span class="inventory-inline-label">Audita ieraksti</span>
                            <span class="inventory-inline-value">{{ $managedUser->audit_logs_total_count ?? 0 }}</span>
                        </span>
                    </div>
                </div>

                <div class="page-actions">
                    <a href="{{ route('users.index', ['user_modal' => 'edit', 'modal_user' => $managedUser->id]) }}" class="btn-edit">
                        <x-icon name="edit" size="h-4 w-4" />
                        <span>Rediģēt</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="btn-back">
                        <x-icon name="back" size="h-4 w-4" />
                        <span>Atpakaļ</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <section class="surface-card p-6">
                <div class="user-profile-section-head">
                    <div>
                        <h2 class="user-profile-section-title">Pamata informācija</h2>
                        <p class="user-profile-section-subtitle">Konta statuss, saziņas dati un pēdējā aktivitāte vienā vietā.</p>
                    </div>
                    <x-status-pill context="user-active" :value="$managedUser->is_active" />
                </div>

                <div class="user-profile-grid mt-5">
                    <div class="user-profile-card">
                        <div class="user-profile-card-label">E-pasts</div>
                        <div class="user-profile-card-value">{{ $managedUser->email }}</div>
                    </div>
                    <div class="user-profile-card">
                        <div class="user-profile-card-label">Tālrunis</div>
                        <div class="user-profile-card-value">{{ $managedUser->phone ?: 'Nav norādīts' }}</div>
                    </div>
                    <div class="user-profile-card">
                        <div class="user-profile-card-label">Pēdējā pieslēgšanās</div>
                        <div class="user-profile-card-value">{{ $managedUser->last_login?->format('d.m.Y H:i') ?: 'Nav pieslēdzies' }}</div>
                        <div class="user-profile-card-meta">{{ $managedUser->last_login ? $managedUser->last_login->diffForHumans() : 'Pirmā pieslēgšanās vēl nav notikusi' }}</div>
                    </div>
                    <div class="user-profile-card">
                        <div class="user-profile-card-label">Izveidots</div>
                        <div class="user-profile-card-value">{{ $managedUser->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                        <div class="user-profile-card-meta">Konts sistēmā</div>
                    </div>
                </div>
            </section>

            <aside class="surface-card p-6">
                <div class="user-profile-section-head">
                    <div>
                        <h2 class="user-profile-section-title">Ātrās saites</h2>
                        <p class="user-profile-section-subtitle">Pāreja uz saistītajiem sarakstiem un filtrētajiem skatiem.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <a href="{{ $assignedDevicesUrl }}" class="user-profile-link-card">
                        <x-icon name="device" size="h-4 w-4" />
                        <span>Piesaistītās ierīces</span>
                        <strong>{{ $managedUser->assigned_devices_count ?? 0 }}</strong>
                    </a>
                    <a href="{{ $repairRequestsUrl }}" class="user-profile-link-card">
                        <x-icon name="repair-request" size="h-4 w-4" />
                        <span>Remonta pieteikumi</span>
                        <strong>{{ $managedUser->active_repair_requests_count ?? 0 }}</strong>
                    </a>
                    <a href="{{ $writeoffRequestsUrl }}" class="user-profile-link-card">
                        <x-icon name="writeoff" size="h-4 w-4" />
                        <span>Norakstīšanas pieteikumi</span>
                        <strong>{{ $managedUser->active_writeoff_requests_count ?? 0 }}</strong>
                    </a>
                    <a href="{{ $outgoingTransfersUrl }}" class="user-profile-link-card">
                        <x-icon name="transfer" size="h-4 w-4" />
                        <span>Nosūtītās nodošanas</span>
                        <strong>{{ $managedUser->active_transfer_requests_count ?? 0 }}</strong>
                    </a>
                    <a href="{{ $incomingTransfersUrl }}" class="user-profile-link-card">
                        <x-icon name="transfer" size="h-4 w-4" />
                        <span>Ienākošās nodošanas</span>
                        <strong>{{ $managedUser->incoming_transfer_requests_count ?? 0 }}</strong>
                    </a>
                    <a href="{{ $auditUrl }}" class="user-profile-link-card">
                        <x-icon name="audit" size="h-4 w-4" />
                        <span>Pilna aktivitātes vēsture</span>
                        <strong>{{ $managedUser->audit_logs_total_count ?? 0 }}</strong>
                    </a>
                </div>
            </aside>
        </div>

        <section class="surface-card p-6">
            <div class="user-profile-section-head">
                <div>
                    <h2 class="user-profile-section-title">Piesaistītās ierīces</h2>
                    <p class="user-profile-section-subtitle">Pēdējās ierīces, kas šobrīd piesaistītas šim lietotājam.</p>
                </div>
                <a href="{{ $assignedDevicesUrl }}" class="btn-view">
                    <x-icon name="view" size="h-4 w-4" />
                    <span>Atvērt pilnu sarakstu</span>
                </a>
            </div>

            @if ($assignedDevices->isEmpty())
                <x-empty-state
                    class="mt-5"
                    compact
                    icon="device"
                    title="Šim lietotājam nav piesaistītu ierīču"
                    description="Kad lietotājam piešķirs ierīci, tā parādīsies šajā sadaļā."
                />
            @else
                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($assignedDevices as $device)
                        <a href="{{ route('devices.show', $device) }}" class="user-profile-device-card">
                            <div class="user-profile-device-title">{{ $device->name }}</div>
                            <div class="user-profile-device-meta">{{ $device->code ?: 'Bez koda' }} | {{ $device->type?->type_name ?: 'Bez tipa' }}</div>
                            <div class="user-profile-device-sub">
                                {{ $device->room?->room_number ?: 'Bez telpas' }}
                                @if ($device->room?->room_name)
                                    | {{ $device->room->room_name }}
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="surface-card p-6">
                <div class="user-profile-section-head">
                    <div>
                        <h2 class="user-profile-section-title">Atvērtie pieprasījumi</h2>
                        <p class="user-profile-section-subtitle">Pārskats par vēl neizskatītajiem pieprasījumiem un nodošanām.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-5">
                    <div class="user-profile-request-group">
                        <div class="user-profile-request-head">
                            <span>Remonta pieteikumi</span>
                            <a href="{{ $repairRequestsUrl }}">Skatīt visus</a>
                        </div>
                        @forelse ($openRepairRequests as $request)
                            <div class="user-profile-request-item">
                                <div class="user-profile-request-title">
                                    <x-icon name="repair-request" size="h-4 w-4" />
                                    <span>{{ $request->device?->name ?: 'Ierīce nav atrasta' }}</span>
                                </div>
                                <div class="user-profile-request-meta">
                                    {{ $request->device?->code ?: '-' }}
                                    @if ($request->device?->type?->type_name)
                                        | {{ $request->device->type->type_name }}
                                    @endif
                                    | {{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}
                                </div>
                                <div class="user-profile-request-copy">{{ $request->description }}</div>
                            </div>
                        @empty
                            <x-empty-state compact icon="repair-request" title="Nav atvērtu remonta pieteikumu" />
                        @endforelse
                    </div>

                    <div class="user-profile-request-group">
                        <div class="user-profile-request-head">
                            <span>Norakstīšanas pieteikumi</span>
                            <a href="{{ $writeoffRequestsUrl }}">Skatīt visus</a>
                        </div>
                        @forelse ($openWriteoffRequests as $request)
                            <div class="user-profile-request-item">
                                <div class="user-profile-request-title">
                                    <x-icon name="writeoff" size="h-4 w-4" />
                                    <span>{{ $request->device?->name ?: 'Ierīce nav atrasta' }}</span>
                                </div>
                                <div class="user-profile-request-meta">
                                    {{ $request->device?->code ?: '-' }}
                                    @if ($request->device?->type?->type_name)
                                        | {{ $request->device->type->type_name }}
                                    @endif
                                    | {{ $request->created_at?->format('d.m.Y H:i') ?: '-' }}
                                </div>
                                <div class="user-profile-request-copy">{{ $request->reason }}</div>
                            </div>
                        @empty
                            <x-empty-state compact icon="writeoff" title="Nav atvērtu norakstīšanas pieteikumu" />
                        @endforelse
                    </div>

                    <div class="user-profile-request-group">
                        <div class="user-profile-request-head">
                            <span>Nodošanas</span>
                            <a href="{{ $outgoingTransfersUrl }}">Nosūtītās</a>
                        </div>
                        @forelse ($outgoingTransfers as $transfer)
                            <div class="user-profile-request-item">
                                <div class="user-profile-request-title">
                                    <x-icon name="transfer" size="h-4 w-4" />
                                    <span>{{ $transfer->device?->name ?: 'Ierīce nav atrasta' }}</span>
                                </div>
                                <div class="user-profile-request-meta">
                                    Saņēmējs: {{ $transfer->transferTo?->full_name ?: 'Nav norādīts' }}
                                    | {{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}
                                </div>
                                <div class="user-profile-request-copy">{{ $transfer->transfer_reason }}</div>
                            </div>
                        @empty
                            <x-empty-state compact icon="transfer" title="Nav atvērtu nosūtīto nodošanu" />
                        @endforelse

                        @forelse ($incomingTransfers as $transfer)
                            <div class="user-profile-request-item">
                                <div class="user-profile-request-title">
                                    <x-icon name="transfer" size="h-4 w-4" />
                                    <span>{{ $transfer->device?->name ?: 'Ierīce nav atrasta' }}</span>
                                </div>
                                <div class="user-profile-request-meta">
                                    No: {{ $transfer->responsibleUser?->full_name ?: 'Nav norādīts' }}
                                    | {{ $transfer->created_at?->format('d.m.Y H:i') ?: '-' }}
                                </div>
                                <div class="user-profile-request-copy">{{ $transfer->transfer_reason }}</div>
                            </div>
                        @empty
                            <x-empty-state compact icon="transfer" title="Nav atvērtu ienākošo nodošanu" />
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="surface-card p-6">
                <div class="user-profile-section-head">
                    <div>
                        <h2 class="user-profile-section-title">Pēdējās darbības</h2>
                        <p class="user-profile-section-subtitle">Jaunākā auditētā aktivitāte šim lietotājam.</p>
                    </div>
                    <a href="{{ $auditUrl }}" class="btn-view">
                        <x-icon name="audit" size="h-4 w-4" />
                        <span>Atvērt auditā</span>
                    </a>
                </div>

                @if ($recentAuditLogs->isEmpty())
                    <x-empty-state
                        class="mt-5"
                        compact
                        icon="audit"
                        title="Auditētās darbības vēl nav fiksētas"
                        description="Kad lietotājs veiks darbības sistēmā, tās parādīsies šeit."
                    />
                @else
                    <div class="mt-5 space-y-3">
                        @foreach ($recentAuditLogs as $log)
                            <div class="user-profile-audit-item">
                                <div class="user-profile-audit-title">{{ $log->localized_action }}</div>
                                <div class="user-profile-audit-copy">{{ $log->localized_description }}</div>
                                <div class="user-profile-audit-meta">{{ $log->timestamp?->format('d.m.Y H:i:s') ?: '-' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <section class="surface-card p-6">
            <div class="user-profile-section-head">
                <div>
                    <h2 class="user-profile-section-title">Pilna aktivitātes vēsture</h2>
                    <p class="user-profile-section-subtitle">Pilns auditēto darbību laika ceļš ar ieraksta tipu, aprakstu un laiku.</p>
                </div>
                <a href="{{ $auditUrl }}" class="btn-view">
                    <x-icon name="audit" size="h-4 w-4" />
                    <span>Atvērt auditā</span>
                </a>
            </div>

            @if ($activityHistory->isEmpty())
                <x-empty-state
                    class="mt-5"
                    compact
                    icon="audit"
                    title="Pilna aktivitātes vēsture vēl nav pieejama"
                    description="Kad lietotājs veiks darbības sistēmā, tās parādīsies arī šajā detalizētajā vēsturē."
                />
            @else
                <div class="mt-5 space-y-3">
                    @foreach ($activityHistory as $log)
                        <div class="user-profile-history-item">
                            <div class="user-profile-history-head">
                                <div class="user-profile-history-title">{{ $log->localized_action }}</div>
                                <x-status-pill context="audit-severity" :value="$log->severity" />
                            </div>
                            <div class="user-profile-history-meta">
                                <span>{{ $log->localized_entity_type }}</span>
                                <span>•</span>
                                <span>{{ $log->timestamp?->format('d.m.Y H:i:s') ?: '-' }}</span>
                            </div>
                            <div class="user-profile-history-copy">{{ $log->localized_description }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    {{ $activityHistory->links() }}
                </div>
            @endif
        </section>
    </section>
</x-app-layout>
