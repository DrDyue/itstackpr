<x-app-layout>
<div style="padding: 0; margin: 0; background-color: white;">
    <!-- Main Container with Sidebar -->
    <div style="display: flex; min-height: calc(100vh - 80px); background-color: #f5f5f7;">
        <!-- Sidebar Navigation -->
        <nav style="width: 240px; background-color: white; border-right: 1px solid #e5e5e7; padding: 20px 0; position: fixed; height: calc(100vh - 80px); overflow-y: auto;">
            <div style="padding: 16px; border-bottom: 1px solid #e5e5e7; margin-bottom: 8px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #1d1d1f; margin: 0;">NAVIGĀCIJA</h3>
            </div>

            <a href="{{ route('dashboard') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                📊 Darbvirsma
            </a>
            <a href="{{ route('devices.index') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                💻 Inventārs
            </a>
            <a href="{{ route('buildings.index') }}" style="display: block; padding: 12px 16px; color: #555; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid transparent; transition: all 0.2s ease;">
                🏢 Ēkas & Kabineti
            </a>
            <a href="{{ route('repairs.index') }}" style="display: block; padding: 12px 16px; color: #0071e3; text-decoration: none; font-size: 14px; cursor: pointer; border-left: 3px solid #0071e3; background-color: #eff6ff; font-weight: 600;">
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
                    <h1 style="font-size: 24px; font-weight: 700; color: #1d1d1f; margin: 0;">🔧 Remonti</h1>
                    <p style="font-size: 12px; color: #86868b; margin: 4px 0 0 0;">Visu ierīču remontu vadība</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="{{ route('repairs.create') }}" style="padding: 10px 16px; background-color: #0071e3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none;">+ Jauns Remonts</a>
                </div>
            </div>

            <!-- Filters -->
            <div style="background-color: white; padding: 16px 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                <form method="GET" action="{{ route('repairs.index') }}" style="display: flex; gap: 12px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 12px; color: #86868b; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Meklēt</label>
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Meklēt pēc apraksta, piegādātāja, rēķina..." style="width: 100%; padding: 10px; border: 1px solid #e5e5e7; border-radius: 8px; font-size: 14px;"/>
                    </div>
                    <button style="padding: 10px 16px; background-color: #0071e3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">Meklēt</button>
                    <a href="{{ route('repairs.index') }}" style="padding: 10px 16px; background-color: #f5f5f7; color: #1d1d1f; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none;">Atiestatīt</a>
                </form>
            </div>

            <!-- Table Section -->
            <div style="background-color: white; border: 1px solid #e5e5e7; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                @if(session('success'))
                    <div style="padding: 16px 24px; background-color: #f0fdf4; border-bottom: 1px solid #e5e5e7; color: #15803d;">
                        {{ session('success') }}
                    </div>
                @endif

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background-color: #f5f5f7; border-bottom: 2px solid #e5e5e7;">
                            <tr>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">ID</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Ierīce</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Statuss</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Tips</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Prioritāte</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Sākums</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Paredzams Beigums</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Faktiskais Beigums</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Izmaksas</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Piegādātājs</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($repairs as $repair)
                                <tr style="border-bottom: 1px solid #f5f5f7;">
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;"><strong>#{{ $repair->id }}</strong></td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">
                                        @if($repair->device)
                                            <div style="font-weight: 600;">{{ $repair->device->code ?? ('Device #' . $repair->device->id) }}</div>
                                            <div style="font-size: 12px; color: #86868b;">{{ $repair->device->name ?? '' }}</div>
                                        @else
                                            <span style="color: #86868b;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">
                                        @if($repair->status === 'completed')
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">Pabeigts</span>
                                        @elseif($repair->status === 'in-progress')
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">Notiek</span>
                                        @elseif($repair->status === 'pending')
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #fff7ed; color: #b45309; border: 1px solid #fed7aa;">Gaida</span>
                                        @else
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; background-color: #f5f5f7; color: #555;">{{ $repair->status }}</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">{{ $repair->repair_type ?? '—' }}</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">
                                        @if($repair->priority === 'high')
                                            <span style="color: #d70015;">🔴 Augsta</span>
                                        @elseif($repair->priority === 'medium')
                                            <span style="color: #f59e0b;">🟡 Normāla</span>
                                        @else
                                            <span style="color: #10b981;">🟢 Zema</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">{{ $repair->start_date ? \Carbon\Carbon::parse($repair->start_date)->format('d.m.Y') : '—' }}</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">{{ $repair->estimated_completion ? \Carbon\Carbon::parse($repair->estimated_completion)->format('d.m.Y') : '—' }}</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">{{ $repair->actual_completion ? \Carbon\Carbon::parse($repair->actual_completion)->format('d.m.Y') : '—' }}</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">
                                        {{ $repair->cost !== null ? number_format((float)$repair->cost, 2) . ' €' : '—' }}
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #333;">
                                        <div style="font-weight: 600;">{{ $repair->vendor_name ?? '—' }}</div>
                                        <div style="font-size: 12px; color: #86868b;">{{ $repair->vendor_contact ?? '' }}</div>
                                        <div style="font-size: 12px; color: #86868b;">{{ $repair->invoice_number ?? '' }}</div>
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 14px; text-align: center;">
                                        <a href="{{ route('repairs.edit', $repair) }}" style="color: #0071e3; text-decoration: none; margin: 0 4px;">✏️</a>
                                        <form method="POST" action="{{ route('repairs.destroy', $repair) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background: none; border: none; color: #d70015; cursor: pointer; margin: 0 4px; font-size: 14px;" onclick="return confirm('Dzēst šo remontu?')">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td style="padding: 32px 16px; text-align: center; color: #86868b;" colspan="11">
                                        Nav remontu. <a href="{{ route('repairs.create') }}" style="color: #0071e3; text-decoration: none;">Pievienot pirmo</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
