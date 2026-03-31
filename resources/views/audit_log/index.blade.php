{{--
    Lapa: Audita žurnāls.
    Atbildība: parāda sistēmas darbību vēsturi, kas noder kontrolei un demonstrācijai komisijai.
    Datu avots: AuditLogController@index.
    Galvenās daļas:
    1. Hero un audita kopsavilkumi.
    2. Filtri pēc darbības, smaguma, objekta, lietotāja un datuma.
    3. Audita ierakstu tabula.
--}}
<x-app-layout>
    <section class="app-shell">
        <div class="page-hero">
            <div class="page-hero-grid">
                <div class="max-w-3xl">
                    <div class="page-eyebrow">
                        <x-icon name="audit" size="h-4 w-4" />
                        <span>Administratora zurnals</span>
                    </div>
                    <div class="page-title-group mt-4">
                        <div class="page-title-icon page-title-icon-violet">
                            <x-icon name="audit" size="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="page-title">Audita zurnals</h1>
                            <p class="page-subtitle">Sistēmas darbību vēsture ar lokalizētiem filtriem un skaidru aktivitāšu tabulu.</p>
                        </div>
                    </div>
                </div>

                <div class="inventory-inline-metrics">
                    <div class="inventory-inline-chip inventory-inline-chip-slate">
                        <span class="inventory-inline-label">Kopa</span>
                        <span class="inventory-inline-value">{{ $summary['total'] }}</span>
                    </div>
                    <div class="inventory-inline-chip inventory-inline-chip-amber">
                        <span class="inventory-inline-label">Šodien</span>
                        <span class="inventory-inline-value">{{ $summary['today'] }}</span>
                    </div>
                    <div class="inventory-inline-chip inventory-inline-chip-rose">
                        <span class="inventory-inline-label">Kritiski</span>
                        <span class="inventory-inline-value">{{ $summary['critical'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if (! empty($featureMessage ?? null))
            <div class="surface-empty">{{ $featureMessage }}</div>
        @endif

        <div id="audit-log-index-root" data-async-table-root>
        <form method="GET" action="{{ route('audit-log.index') }}" class="surface-toolbar grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr]" data-async-table-form data-async-root="#audit-log-index-root">
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">Meklēt</span>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="crud-control" placeholder="Meklē pēc apraksta vai ID">
            </label>

            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Darbība</span>
                <x-searchable-select
                    name="action"
                    queryName="action_query"
                    :options="$actionOptions"
                    :selected="$filters['action']"
                    :query="collect($actionOptions)->firstWhere('value', $filters['action'])['label'] ?? ''"
                    identifier="audit-action"
                    placeholder="Visas darbības"
                />
            </div>

            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Svarīgums</span>
                <x-searchable-select
                    name="severity"
                    queryName="severity_query"
                    :options="$severityOptions"
                    :selected="$filters['severity']"
                    :query="collect($severityOptions)->firstWhere('value', $filters['severity'])['label'] ?? ''"
                    identifier="audit-severity"
                    placeholder="Visi limeni"
                />
            </div>

            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Lietotājs</span>
                <x-searchable-select
                    name="user_id"
                    queryName="user_query"
                    :options="$actorOptions"
                    :selected="$filters['user_id']"
                    :query="collect($actorOptions)->firstWhere('value', $filters['user_id'])['label'] ?? ''"
                    identifier="audit-user"
                    placeholder="Visi lietotāji"
                />
            </div>

            <x-localized-date-input
                name="date_from"
                label="No datuma"
                :value="$filters['date_from']"
            />

            <x-localized-date-input
                name="date_to"
                label="Līdz datumam"
                :value="$filters['date_to']"
            />

            <div class="toolbar-actions md:col-span-2 xl:col-span-4">
                <a href="{{ route('audit-log.index') }}" class="btn-clear" data-async-link="true">
                    <x-icon name="clear" size="h-4 w-4" />
                    <span>Notīrīt</span>
                </a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Laiks</th>
                        <th class="px-4 py-3">Lietotājs</th>
                        <th class="px-4 py-3">Darbība</th>
                        <th class="px-4 py-3">Objekts</th>
                        <th class="px-4 py-3">Svarīgums</th>
                        <th class="px-4 py-3">Apraksts</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $actionStyles = match ($log->action) {
                                'CREATE' => ['icon' => 'plus', 'class' => 'audit-badge audit-badge-emerald'],
                                'UPDATE' => ['icon' => 'edit', 'class' => 'audit-badge audit-badge-sky'],
                                'DELETE' => ['icon' => 'trash', 'class' => 'audit-badge audit-badge-rose'],
                                'LOGIN' => ['icon' => 'user', 'class' => 'audit-badge audit-badge-violet'],
                                'LOGOUT' => ['icon' => 'logout', 'class' => 'audit-badge audit-badge-slate'],
                                'EXPORT' => ['icon' => 'send', 'class' => 'audit-badge audit-badge-amber'],
                                'BACKUP' => ['icon' => 'save', 'class' => 'audit-badge audit-badge-amber'],
                                'RESTORE' => ['icon' => 'check-circle', 'class' => 'audit-badge audit-badge-emerald'],
                                default => ['icon' => 'view', 'class' => 'audit-badge audit-badge-slate'],
                            };

                            $entityStyles = match ($log->entity_type) {
                                'Device' => ['icon' => 'device', 'class' => 'audit-badge audit-badge-sky'],
                                'Repair' => ['icon' => 'repair', 'class' => 'audit-badge audit-badge-amber'],
                                'RepairRequest' => ['icon' => 'repair-request', 'class' => 'audit-badge audit-badge-sky'],
                                'WriteoffRequest' => ['icon' => 'writeoff', 'class' => 'audit-badge audit-badge-rose'],
                                'DeviceTransfer' => ['icon' => 'transfer', 'class' => 'audit-badge audit-badge-emerald'],
                                'Room' => ['icon' => 'room', 'class' => 'audit-badge audit-badge-emerald'],
                                'Building' => ['icon' => 'building', 'class' => 'audit-badge audit-badge-slate'],
                                'DeviceType' => ['icon' => 'tag', 'class' => 'audit-badge audit-badge-violet'],
                                'User' => ['icon' => 'users', 'class' => 'audit-badge audit-badge-violet'],
                                default => ['icon' => 'audit', 'class' => 'audit-badge audit-badge-slate'],
                            };
                        @endphp
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $log->timestamp?->format('d.m.Y') ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->timestamp?->format('H:i:s') ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $log->user?->full_name ?: 'Sistēma' }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $actionStyles['class'] }}">
                                    <x-icon :name="$actionStyles['icon']" size="h-3.5 w-3.5" />
                                    <span>{{ $log->localized_action }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ $entityStyles['class'] }}">
                                    <x-icon :name="$entityStyles['icon']" size="h-3.5 w-3.5" />
                                    <span>{{ $log->localized_entity_type }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <x-status-pill context="severity" :value="$log->severity" :label="$log->localized_severity" />
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->localized_description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Audita ierakstu nav.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
        </div>
    </section>
</x-app-layout>
