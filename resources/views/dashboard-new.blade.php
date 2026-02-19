@extends('layouts.app')

@section('content')
<div class="main-container">
    <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <div style="padding: 16px; border-bottom: 1px solid #e5e5e7; margin-bottom: 8px;">
            <h3 style="font-size: 14px; font-weight: 700; color: #1d1d1f; margin: 0;">IT INVENTĀRA</h3>
        </div>

        <a href="#" class="sidebar-item active">
            <span>📊 Darbvirsma</span>
        </a>
        <a href="#" class="sidebar-item">
            <span>📋 Inventārs</span>
        </a>
        <a href="#" class="sidebar-item">
            <span>🏢 Ēkas & Kabineti</span>
        </a>
        <a href="#" class="sidebar-item">
            <span>🔧 Remonti</span>
        </a>
        <a href="#" class="sidebar-item">
            <span>💾 Rezerves Kopijas</span>
        </a>
        <a href="#" class="sidebar-item">
            <span>📋 Audita Žurnāls</span>
        </a>
        <a href="#" class="sidebar-item">
            <span>📦 Komplektācijas</span>
        </a>

        <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e5e7;">
            <a href="{{ route('profile.edit') }}" class="sidebar-item">
                <span>⚙️ Profils</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                @csrf
                <button type="submit" class="sidebar-item" style="width: 100%; text-align: left; color: #d70015;">
                    <span>🚪 Izloģēties</span>
                </button>
            </form>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-header-title">Laipni lūdzam!</h1>
                <p class="card-subtitle">Sveiki, {{ auth()->user()->employee->full_name }}</p>
            </div>
            <div class="dashboard-header-actions">
                <button style="padding: 10px 16px; background-color: #0071e3; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">+ Pievienot Ierīci</button>
            </div>
        </div>

        <!-- Dashboard Grid - 3 Columns -->
        <div class="dashboard-grid dashboard-grid-3col">
            <!-- Left Column: Buildings -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🏢 Ēkas</h3>
                    <span class="badge badge-info">3 ēkas</span>
                </div>
                <div class="card-content">
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 8px; background-color: #f9f9fa; cursor: pointer;">
                            <span style="width: 8px; height: 8px; background-color: #22c55e; border-radius: 50%; flex-shrink: 0;"></span>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;">Ēka A</div>
                                <div style="font-size: 12px; color: #86868b;">5 stāvi, 24 kabineti</div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 8px; background-color: #f9f9fa; cursor: pointer;">
                            <span style="width: 8px; height: 8px; background-color: #22c55e; border-radius: 50%; flex-shrink: 0;"></span>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;">Ēka B</div>
                                <div style="font-size: 12px; color: #86868b;">3 stāvi, 18 kabineti</div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 8px; background-color: #f9f9fa; cursor: pointer;">
                            <span style="width: 8px; height: 8px; background-color: #f97316; border-radius: 50%; flex-shrink: 0;"></span>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;">Ēka C</div>
                                <div style="font-size: 12px; color: #86868b;">2 stāvi, 12 kabineti</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">Kopā: 54 kabineti</div>
            </div>

            <!-- Center Column: Hot Points / Issues -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⚠️ Kritiskās Problēmas</h3>
                    <span class="badge badge-danger">5</span>
                </div>
                
                <div style="margin-bottom: 12px;">
                    <div class="tab-list">
                        <button class="tab-item active">Problēmas</button>
                        <button class="tab-item">Brīdinājumi</button>
                        <button class="tab-item">Gaida Daļas</button>
                    </div>
                </div>

                <div class="card-content">
                    <div class="device-item">
                        <div class="device-icon" style="background-color: #ef4444;">🖥️</div>
                        <div class="device-info">
                            <div class="device-name">LG 24MK600</div>
                            <div class="device-code">LDZ-MON-015 • Ēka A, 3.st</div>
                        </div>
                        <div class="device-status">
                            <span class="badge badge-danger">Bojāta</span>
                        </div>
                    </div>

                    <div class="device-item">
                        <div class="device-icon" style="background-color: #f97316;">⌨️</div>
                        <div class="device-info">
                            <div class="device-name">Dell KBD 123</div>
                            <div class="device-code">LDZ-KBD-008 • Ēka A, 1.st</div>
                        </div>
                        <div class="device-status">
                            <span class="badge badge-warning">Remontā</span>
                        </div>
                    </div>

                    <div class="device-item">
                        <div class="device-icon" style="background-color: #3b82f6;">🖱️</div>
                        <div class="device-info">
                            <div class="device-name">Logitech M90</div>
                            <div class="device-code">LDZ-MOU-022 • Ēka B, 2.st</div>
                        </div>
                        <div class="device-status">
                            <span class="badge badge-info">Komplektācijā</span>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="#" class="auth-link">Skatīt Visas →</a>
                </div>
            </div>

            <!-- Right Column: Statistics -->
            <div class="stat-card" style="grid-row: span 2;">
                <div class="stat-label">Kopā Ierīču</div>
                <p class="stat-value">234</p>
                <div class="stat-change">+12 šomēnes</div>

                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e5e7;">
                    <div class="stat-label">Aktīvās</div>
                    <p style="font-size: 24px; font-weight: 700; color: #22c55e; margin: 0;">198</p>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e7;">
                    <div class="stat-label">Remontā/Bojātas</div>
                    <p style="font-size: 24px; font-weight: 700; color: #ef4444; margin: 0;">15</p>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e7;">
                    <div class="stat-label">Pēdējā Aktivitāte</div>
                    <div style="font-size: 12px; color: #555; margin-top: 8px;">
                        <div style="margin-bottom: 6px;">• Ierīce pievienota (2h)</div>
                        <div style="margin-bottom: 6px;">• Statuss mainīts (5h)</div>
                        <div>• Pārvietota (1d)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Recent Devices -->
        <div style="margin-top: 24px;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📋 Pēdējās Ierīces</h3>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Kods</th>
                                <th>Modelis</th>
                                <th>Tips</th>
                                <th>Statuss</th>
                                <th>Atrašanās vieta</th>
                                <th>Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>LDZ-MON-001</strong></td>
                                <td>LG 24MK600</td>
                                <td>Monitors</td>
                                <td><span class="badge badge-success">Aktīvs</span></td>
                                <td>Ēka A, 3.st, Kab.314</td>
                                <td style="text-align: center;">
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">👁️</a>
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;;">✏️</a>
                                    <a href="#" style="color: #d70015; text-decoration: none; margin: 0 4px;">🗑️</a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>LDZ-COM-045</strong></td>
                                <td>Dell OptiPlex 7090</td>
                                <td>Dators</td>
                                <td><span class="badge badge-warning">Remontā</span></td>
                                <td>Ēka B, 1.st, Kab.105</td>
                                <td style="text-align: center;">
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">👁️</a>
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">✏️</a>
                                    <a href="#" style="color: #d70015; text-decoration: none; margin: 0 4px;">🗑️</a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>LDZ-PRI-123</strong></td>
                                <td>HP LaserJet Pro M404</td>
                                <td>Printeris</td>
                                <td><span class="badge badge-success">Aktīvs</span></td>
                                <td>Ēka A, 2.st, Kab.208</td>
                                <td style="text-align: center;">
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">👁️</a>
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">✏️</a>
                                    <a href="#" style="color: #d70015; text-decoration: none; margin: 0 4px;">🗑️</a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>LDZ-MON-015</strong></td>
                                <td>ASUS PA248QV</td>
                                <td>Monitors</td>
                                <td><span class="badge badge-danger">Bojāta</span></td>
                                <td>Ēka C, 1.st, Depo</td>
                                <td style="text-align: center;">
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">👁️</a>
                                    <a href="#" style="color: #0071e3; text-decoration: none; margin: 0 4px;">✏️</a>
                                    <a href="#" style="color: #d70015; text-decoration: none; margin: 0 4px;">🗑️</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
