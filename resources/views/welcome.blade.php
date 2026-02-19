<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Stack - Inventory Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 1000px;
            width: 100%;
            padding: 40px;
        }

        header {
            text-align: center;
            margin-bottom: 50px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .subtitle {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 15px;
        }

        .status {
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .modules {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .module-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .module-card h3 {
            font-size: 1.4em;
            margin-bottom: 10px;
        }

        .module-card p {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .module-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 15px;
            background: rgba(255,255,255,0.3);
            color: white;
            border: 1px solid white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85em;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn:hover {
            background: rgba(255,255,255,0.5);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 40px 0;
            padding: 20px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🖥️ IT Stack</h1>
            <p class="subtitle">Inventory Management System</p>
            <div class="status">✓ System Ready</div>
        </header>

        <div class="stats">
            <div class="stat">
                <div class="stat-value">{{ $deviceCount ?? 0 }}</div>
                <div class="stat-label">Devices</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $employeeCount ?? 0 }}</div>
                <div class="stat-label">Employees</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $buildingCount ?? 0 }}</div>
                <div class="stat-label">Buildings</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $repairCount ?? 0 }}</div>
                <div class="stat-label">Repairs</div>
            </div>
        </div>

        <div class="modules">
            <a href="{{ route('employees.index') }}" class="module-card">
                <h3>👥 Employees</h3>
                <p>Manage employees and staff information</p>
                <div class="module-buttons">
                    <a href="{{ route('employees.index') }}" class="btn">View</a>
                    <a href="{{ route('employees.create') }}" class="btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('devices.index') }}" class="module-card">
                <h3>💻 Devices</h3>
                <p>Manage IT equipment and devices</p>
                <div class="module-buttons">
                    <a href="{{ route('devices.index') }}" class="btn">View</a>
                    <a href="{{ route('devices.create') }}" class="btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('buildings.index') }}" class="module-card">
                <h3>🏢 Buildings</h3>
                <p>Manage office buildings and locations</p>
                <div class="module-buttons">
                    <a href="{{ route('buildings.index') }}" class="btn">View</a>
                    <a href="{{ route('buildings.create') }}" class="btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('rooms.index') }}" class="module-card">
                <h3>🚪 Rooms</h3>
                <p>Manage rooms and office spaces</p>
                <div class="module-buttons">
                    <a href="{{ route('rooms.index') }}" class="btn">View</a>
                    <a href="{{ route('rooms.create') }}" class="btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('device-types.index') }}" class="module-card">
                <h3>📱 Device Types</h3>
                <p>Manage device categories and types</p>
                <div class="module-buttons">
                    <a href="{{ route('device-types.index') }}" class="btn">View</a>
                    <a href="{{ route('device-types.create') }}" class="btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('repairs.index') }}" class="module-card">
                <h3>🔧 Repairs</h3>
                <p>Track device repairs and maintenance</p>
                <div class="module-buttons">
                    <a href="{{ route('repairs.index') }}" class="btn">View</a>
                    <a href="{{ route('repairs.create') }}" class="btn">Add New</a>
                </div>
            </a>

            <a href="{{ route('device-history.index') }}" class="module-card">
                <h3>📋 Device History</h3>
                <p>View device change history and audit trail</p>
                <div class="module-buttons">
                    <a href="{{ route('device-history.index') }}" class="btn">View</a>
                </div>
            </a>

            <a href="{{ route('audit-log.index') }}" class="module-card">
                <h3>📊 Audit Log</h3>
                <p>System activity and user actions log</p>
                <div class="module-buttons">
                    <a href="{{ route('audit-log.index') }}" class="btn">View</a>
                </div>
            </a>
        </div>
    </div>
</body>
</html>
