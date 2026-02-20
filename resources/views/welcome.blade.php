<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Stack - Inventory Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="welcome-page-body">
    <div class="welcome-container">
        <header class="welcome-header">
            <h1 class="welcome-title">s-?l IT Stack</h1>
            <p class="welcome-subtitle">Inventory Management System</p>
            <div class="welcome-status">a" System Ready</div>
        </header>

        <div class="welcome-stats">
            <div class="welcome-stat">
                <div class="welcome-stat-value">{{ $deviceCount ?? 0 }}</div>
                <div class="welcome-stat-label">Devices</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-value">{{ $employeeCount ?? 0 }}</div>
                <div class="welcome-stat-label">Employees</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-value">{{ $buildingCount ?? 0 }}</div>
                <div class="welcome-stat-label">Buildings</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-value">{{ $repairCount ?? 0 }}</div>
                <div class="welcome-stat-label">Repairs</div>
            </div>
        </div>

        <div class="welcome-modules">
            <a href="{{ route('employees.index') }}" class="welcome-module-card">
                <h3>s'? Employees</h3>
                <p>Manage employees and staff information</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('employees.index') }}" class="welcome-btn">View</a>
                    <a href="{{ route('employees.create') }}" class="welcome-btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('devices.index') }}" class="welcome-module-card">
                <h3>s' Devices</h3>
                <p>Manage IT equipment and devices</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('devices.index') }}" class="welcome-btn">View</a>
                    <a href="{{ route('devices.create') }}" class="welcome-btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('buildings.index') }}" class="welcome-module-card">
                <h3>s Buildings</h3>
                <p>Manage office buildings and locations</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('buildings.index') }}" class="welcome-btn">View</a>
                    <a href="{{ route('buildings.create') }}" class="welcome-btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('rooms.index') }}" class="welcome-module-card">
                <h3>sR Rooms</h3>
                <p>Manage rooms and office spaces</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('rooms.index') }}" class="welcome-btn">View</a>
                    <a href="{{ route('rooms.create') }}" class="welcome-btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('device-types.index') }}" class="welcome-module-card">
                <h3>s" Device Types</h3>
                <p>Manage device categories and types</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('device-types.index') }}" class="welcome-btn">View</a>
                    <a href="{{ route('device-types.create') }}" class="welcome-btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('repairs.index') }}" class="welcome-module-card">
                <h3>s" Repairs</h3>
                <p>Track device repairs and maintenance</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('repairs.index') }}" class="welcome-btn">View</a>
                    <a href="{{ route('repairs.create') }}" class="welcome-btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('device-history.index') }}" class="welcome-module-card">
                <h3>s"< Device History</h3>
                <p>View device change history and audit trail</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('device-history.index') }}" class="welcome-btn">View</a>
                </div>
            </a>

            <a href="{{ route('audit-log.index') }}" class="welcome-module-card">
                <h3>s" Audit Log</h3>
                <p>System activity and user actions log</p>
                <div class="welcome-module-buttons">
                    <a href="{{ route('audit-log.index') }}" class="welcome-btn">View</a>
                </div>
            </a>
        </div>
    </div>
</body>
</html>


