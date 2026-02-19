@php
    // Fetch real data from database - with error handling
    try {
        $totalDevices = \App\Models\Device::count();
        $activeDevices = \App\Models\Device::where('status', 'active')->count();
        $brokenDevices = \App\Models\Device::where('status', 'broken')->count();
        $inRepairDevices = \App\Models\Device::where('status', 'in-repair')->count();
        
        $buildings = \App\Models\Building::all();
        $criticalDevices = \App\Models\Device::where('status', 'broken')
            ->orWhere('status', 'in-repair')
            ->limit(3)
            ->get();
        
        $recentDevices = \App\Models\Device::with('room', 'device_type')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();
    } catch (\Exception $e) {
        // If database queries fail, use empty defaults
        $totalDevices = 0;
        $activeDevices = 0;
        $brokenDevices = 0;
        $inRepairDevices = 0;
        $buildings = collect();
        $criticalDevices = collect();
        $recentDevices = collect();
    }
@endphp

<x-app-layout>
    <div style="padding: 0; margin: 0; background-color: white;">
        <!-- Main Container with Sidebar -->
        <div style="display: flex; min-height: calc(100vh - 80px); background-color: #f5f5f7;">
            <!-- Sidebar Navigation -->
            <nav style="width: 240px; background-color: white; border-right: 1px solid #e5e5e7; padding: 20px 0; position: fixed; height: calc(100vh - 80px); overflow-y: auto;">
                <div style="padding: 16px; border-bottom: 1px solid #e5e5e7; margin-bottom: 8px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: #1d1d1f; margin: 0;">NAVIGĀCIJA</h3>
                </div>

                <a href="{{ route('dashboard') }}" style="display: block; padding: 12px 16px; color: #0071e3; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid #0071e3; background-color: #eff6ff; font-weight: 600;">
                    📊 Darbvirsma
                </a>
                <a href="{{ route('devices.index') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                    💻 Inventārs
                </a>
                <a href="{{ route('buildings.index') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                    🏢 Ēkas & Kabineti
                </a>
                <a href="{{ route('repairs.index') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                    🔧 Remonti
                </a>
                <a href="#" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                    💾 Rezerves Kopijas
                </a>
                <a href="#" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                    📋 Audita Žurnāls
                </a>
                <a href="{{ route('device-sets.index') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                    📦 Komplektācijas
                </a>

                <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e5e7;">
                    <a href="{{ route('profile.edit') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer;">
                        ⚙️ Profils
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                        @csrf
                        <button type="submit" style="width: 100%; display: block; padding: 12px 16px; color: #d70015; text-decoration: none; font-size: 14px; cursor: pointer; background: none; border: none; text-align: left; font-family: inherit;">
                            🚪 Izloģēties
                        </button>
                    </form>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div style="margin-left: 240px; flex: 1; padding: 20px;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; background-color: white; padding: 16px 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                    <div>
                        <h1 style="font-size: 24px; font-weight: 700; color: #1d1d1f; margin: 0;">Darbvirsma</h1>
                        <p style="font-size: 12px; color: #86868b; margin: 4px 0 0 0;">{{ now()->format('d.m.Y') }} | {{ now()->format('H:i') }}</p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <a href="{{ route('devices.create') }}" style="padding: 10px 16px; background-color: #0071e3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none;">+ Pievienot Ierīci</a>
                    </div>
                </div>

                <!-- Dashboard Grid - 3 Columns -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
                    <!-- Left Column: Buildings -->
                    <div style="background-color: white; border: 1px solid #e5e5e7; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f5f5f7;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #1d1d1f; margin: 0;">🏢 Ēkas</h3>
                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">{{ $buildings->count() }} ēkas</span>
                        </div>
                        <div>
                            @forelse($buildings as $building)
                                <div style="margin-bottom: 12px;">
                                    <a href="{{ route('buildings.edit', $building) }}" style="display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 8px; background-color: #f9f9fa; cursor: pointer; text-decoration: none; transition: all 0.2s ease;">
                                        <span style="width: 8px; height: 8px; background-color: #22c55e; border-radius: 50%; flex-shrink: 0;"></span>
                                        <div>
                                            <div style="font-weight: 600; font-size: 14px; color: #1d1d1f;">{{ $building->building_name }}</div>
                                            <div style="font-size: 12px; color: #86868b;">{{ $building->total_floors ?? 1 }} stāvi</div>
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <p style="font-size: 12px; color: #86868b; padding: 10px;">Nav ēku sistēmā. <a href="{{ route('buildings.create') }}" style="color: #0071e3;">Pievienot</a></p>
                            @endforelse
                        </div>
                        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f5f5f7; font-size: 12px; color: #86868b;">
                            Kopā: {{ $buildings->count() }} ēkas
                        </div>
                    </div>

                    <!-- Center Column: Critical Issues -->
                    <div style="background-color: white; border: 1px solid #e5e5e7; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f5f5f7;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #1d1d1f; margin: 0;">⚠️ Kritiskās Problēmas</h3>
                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">{{ $brokenDevices + $inRepairDevices }}</span>
                        </div>
                        
                        <div style="color: #333;">
                            @forelse($criticalDevices as $device)
                                <div style="display: flex; gap: 12px; padding: 12px; border-radius: 8px; background-color: #f9f9fa; margin-bottom: 8px; cursor: pointer;">
                                    <div style="width: 40px; height: 40px; background-color: #ef4444; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; flex-shrink: 0;">
                                        @if($device->status === 'broken') 🔴
                                        @elseif($device->status === 'in-repair') 🟡
                                        @else 🔵 @endif
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #1d1d1f; font-size: 14px; margin-bottom: 2px;">{{ $device->device_type->type_name ?? 'Neznāms' }}</div>
                                        <div style="font-size: 12px; color: #86868b;">{{ $device->code }} • {{ optional($device->room)->number ?? 'Nav norādītas' }}</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        @if($device->status === 'broken')
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">Bojāta</span>
                                        @elseif($device->status === 'in-repair')
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #fff7ed; color: #b45309; border: 1px solid #fed7aa;">Remontā</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p style="font-size: 12px; color: #86868b; padding: 10px;">Nav problēmatisko ierīču 🎉</p>
                            @endforelse
                        </div>

                        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f5f5f7; font-size: 12px;">
                            <a href="{{ route('devices.index') }}" style="color: #0071e3; text-decoration: none; font-weight: 500;">Skatīt Visas →</a>
                        </div>
                    </div>

                    <!-- Right Column: Statistics -->
                    <div style="background-color: white; border: 1px solid #e5e5e7; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); grid-row: span 2;">
                        <div style="font-size: 12px; color: #86868b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Kopā Ierīču</div>
                        <p style="font-size: 32px; font-weight: 700; color: #2563eb; margin: 0;">{{ $totalDevices }}</p>
                        <div style="font-size: 12px; color: #15803d; margin-top: 4px;">+{{ \App\Models\Device::where('created_at', '>=', now()->subMonth())->count() }} šomēnes</div>

                        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e5e7;">
                            <div style="font-size: 12px; color: #86868b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Aktīvās</div>
                            <p style="font-size: 24px; font-weight: 700; color: #22c55e; margin: 0;">{{ $activeDevices }}</p>
                        </div>

                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e7;">
                            <div style="font-size: 12px; color: #86868b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Remontā/Bojātas</div>
                            <p style="font-size: 24px; font-weight: 700; color: #ef4444; margin: 0;">{{ $brokenDevices + $inRepairDevices }}</p>
                        </div>

                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e7;">
                            <div style="font-size: 12px; color: #86868b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Pēdējā Aktivitāte</div>
                            <div style="font-size: 12px; color: #555; margin-top: 8px;">
                                <div style="margin-bottom: 6px;">• Ierīces pievienotas</div>
                                <div style="margin-bottom: 6px;">• Statusi mainīti</div>
                                <div>• Remonti veikti</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Recent Devices -->
                <div style="margin-top: 24px;">
                    <div style="background-color: white; border: 1px solid #e5e5e7; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                        <div style="padding: 20px;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #1d1d1f; margin: 0;">📋 Pēdējās Ierīces</h3>
                        </div>

                        @if($recentDevices->count() > 0)
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background-color: #f5f5f7; border-bottom: 2px solid #e5e5e7;">
                                    <tr>
                                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Kods</th>
                                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Tips</th>
                                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Statuss</th>
                                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Atrašanās vieta</th>
                                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Darbības</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDevices as $device)
                                        <tr style="border-bottom: 1px solid #f5f5f7;">
                                            <td style="padding: 12px 16px; font-size: 14px; color: #333;"><strong>{{ $device->code }}</strong></td>
                                            <td style="padding: 12px 16px; font-size: 14px; color: #333;">{{ $device->device_type->type_name ?? 'Neznāms' }}</td>
                                            <td style="padding: 12px 16px; font-size: 14px; color: #333;">
                                                @if($device->status === 'active')
                                                    <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">Aktīvs</span>
                                                @elseif($device->status === 'broken')
                                                    <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">Bojāta</span>
                                                @elseif($device->status === 'in-repair')
                                                    <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #fff7ed; color: #b45309; border: 1px solid #fed7aa;">Remontā</span>
                                                @else
                                                    <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">{{ $device->status }}</span>
                                                @endif
                                            </td>
                                            <td style="padding: 12px 16px; font-size: 14px; color: #333;">{{ optional($device->room)->number ?? 'Nav norādītas' }}</td>
                                            <td style="padding: 12px 16px; font-size: 14px; color: #333; text-align: center;">
                                                <a href="{{ route('devices.edit', $device) }}" style="color: #0071e3; text-decoration: none; margin: 0 4px;">✏️</a>
                                                <form method="POST" action="{{ route('devices.destroy', $device) }}" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="background: none; border: none; color: #d70015; cursor: pointer; margin: 0 4px; font-size: 14px;" onclick="return confirm('Dzēst šo ierīci?')">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div style="padding: 20px; text-align: center; color: #86868b;">
                                <p>Nav ierīču sistēmā. <a href="{{ route('devices.create') }}" style="color: #0071e3; text-decoration: none;">Pievienot pirmo ierīci</a></p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
