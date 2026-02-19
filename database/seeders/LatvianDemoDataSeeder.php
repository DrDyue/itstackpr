<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LatvianDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $building = DB::table('buildings')->orderBy('id')->first();

        if (! $building) {
            $this->command?->error('Tabula buildings ir tuksa. Vispirms izveido vismaz vienu eku.');
            return;
        }

        DB::transaction(function () use ($building, $now) {
            DB::table('device_set_items')->delete();
            DB::table('repairs')->delete();
            DB::table('device_sets')->delete();
            DB::table('devices')->delete();
            DB::table('users')->delete();
            DB::table('rooms')->delete();
            DB::table('device_types')->delete();
            DB::table('employees')->delete();

            $employees = [
                ['full_name' => 'Artis Berzins', 'email' => 'artis.berzins@ludzas.lv', 'phone' => '+371 26000001', 'job_title' => 'IT administrators', 'is_active' => 1],
                ['full_name' => 'Linda Kalnina', 'email' => 'linda.kalnina@ludzas.lv', 'phone' => '+371 26000002', 'job_title' => 'IT specialists', 'is_active' => 1],
                ['full_name' => 'Janis Ozols', 'email' => 'janis.ozols@ludzas.lv', 'phone' => '+371 26000003', 'job_title' => 'Sistemas inzenieris', 'is_active' => 1],
                ['full_name' => 'Ilze Strautina', 'email' => 'ilze.strautina@ludzas.lv', 'phone' => '+371 26000004', 'job_title' => 'Lietotaju atbalsts', 'is_active' => 1],
                ['full_name' => 'Maris Vitols', 'email' => 'maris.vitols@ludzas.lv', 'phone' => '+371 26000005', 'job_title' => 'Tikla administrators', 'is_active' => 1],
                ['full_name' => 'Kristine Daukste', 'email' => 'kristine.daukste@ludzas.lv', 'phone' => '+371 26000006', 'job_title' => 'Projektu koordinatore', 'is_active' => 1],
                ['full_name' => 'Edgars Sviklis', 'email' => 'edgars.sviklis@ludzas.lv', 'phone' => '+371 26000007', 'job_title' => 'Iepirkumu specialists', 'is_active' => 1],
                ['full_name' => 'Agnese Leite', 'email' => 'agnese.leite@ludzas.lv', 'phone' => '+371 26000008', 'job_title' => 'Gramatvede', 'is_active' => 1],
                ['full_name' => 'Roberts Arbidans', 'email' => 'roberts.arbidans@ludzas.lv', 'phone' => '+371 26000009', 'job_title' => 'Jurists', 'is_active' => 1],
                ['full_name' => 'Dace Rudzite', 'email' => 'dace.rudzite@ludzas.lv', 'phone' => '+371 26000010', 'job_title' => 'Personala specialists', 'is_active' => 1],
                ['full_name' => 'Anrijs Krumins', 'email' => 'anrijs.krumins@ludzas.lv', 'phone' => '+371 26000011', 'job_title' => 'Datu analitikis', 'is_active' => 1],
                ['full_name' => 'Marta Zvirbule', 'email' => 'marta.zvirbule@ludzas.lv', 'phone' => '+371 26000012', 'job_title' => 'Sekretare', 'is_active' => 1],
                ['full_name' => 'Olafs Gailitis', 'email' => 'olafs.gailitis@ludzas.lv', 'phone' => '+371 26000013', 'job_title' => 'Saimniecibas vaditajs', 'is_active' => 1],
                ['full_name' => 'Ruta Liepa', 'email' => 'ruta.liepa@ludzas.lv', 'phone' => '+371 26000014', 'job_title' => 'Iestades vaditaja', 'is_active' => 1],
                ['full_name' => 'Toms Severs', 'email' => 'toms.severs@ludzas.lv', 'phone' => '+371 26000015', 'job_title' => 'Datu ievades operators', 'is_active' => 1],
                ['full_name' => 'Sintija Cevere', 'email' => 'sintija.cevere@ludzas.lv', 'phone' => '+371 26000016', 'job_title' => 'Arhivare', 'is_active' => 1],
                ['full_name' => 'Nauris Pauksts', 'email' => 'nauris.pauksts@ludzas.lv', 'phone' => '+371 26000017', 'job_title' => 'Darba aizsardzibas specialists', 'is_active' => 1],
                ['full_name' => 'Elina Grinberga', 'email' => 'elina.grinberga@ludzas.lv', 'phone' => '+371 26000018', 'job_title' => 'Dokumentu parvalde', 'is_active' => 1],
                ['full_name' => 'Kaspars Miezis', 'email' => 'kaspars.miezis@ludzas.lv', 'phone' => '+371 26000019', 'job_title' => 'Sistemu operators', 'is_active' => 1],
                ['full_name' => 'Ieva Spunde', 'email' => 'ieva.spunde@ludzas.lv', 'phone' => '+371 26000020', 'job_title' => 'Lietvedis', 'is_active' => 1],
                ['full_name' => 'Aivars Pelsis', 'email' => 'aivars.pelsis@ludzas.lv', 'phone' => '+371 26000021', 'job_title' => 'Elektroinzenieris', 'is_active' => 1],
                ['full_name' => 'Evita Romanova', 'email' => 'evita.romanova@ludzas.lv', 'phone' => '+371 26000022', 'job_title' => 'Komunikaciju specialists', 'is_active' => 1],
                ['full_name' => 'Rihards Goba', 'email' => 'rihards.goba@ludzas.lv', 'phone' => '+371 26000023', 'job_title' => 'Projekta asistents', 'is_active' => 1],
                ['full_name' => 'Liga Kresla', 'email' => 'liga.kresla@ludzas.lv', 'phone' => '+371 26000024', 'job_title' => 'Administratore', 'is_active' => 1],
            ];

            foreach ($employees as &$employee) {
                $employee['created_at'] = $now;
            }
            unset($employee);
            DB::table('employees')->insert($employees);

            $employeeIds = DB::table('employees')
                ->pluck('id', 'email')
                ->all();

            $users = [
                ['employee_email' => 'artis.berzins@ludzas.lv', 'role' => 'admin', 'is_active' => 1],
                ['employee_email' => 'linda.kalnina@ludzas.lv', 'role' => 'user', 'is_active' => 1],
                ['employee_email' => 'janis.ozols@ludzas.lv', 'role' => 'user', 'is_active' => 1],
                ['employee_email' => 'ilze.strautina@ludzas.lv', 'role' => 'user', 'is_active' => 1],
                ['employee_email' => 'maris.vitols@ludzas.lv', 'role' => 'user', 'is_active' => 1],
            ];

            $userRows = [];
            foreach ($users as $user) {
                $userRows[] = [
                    'employee_id' => $employeeIds[$user['employee_email']],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'is_active' => $user['is_active'],
                    'remember_token' => null,
                    'last_login' => null,
                    'created_at' => $now,
                ];
            }
            DB::table('users')->insert($userRows);

            $userIds = DB::table('users')
                ->pluck('id', 'employee_id')
                ->all();
            $adminUserId = $userIds[$employeeIds['artis.berzins@ludzas.lv']] ?? null;

            $rooms = [
                ['floor_number' => 1, 'room_number' => '101', 'room_name' => 'IT mezgls', 'employee_email' => 'artis.berzins@ludzas.lv', 'department' => 'Informacijas tehnologijas', 'notes' => 'Serveru skapji un tikla mezgli'],
                ['floor_number' => 1, 'room_number' => '102', 'room_name' => 'Atbalsta kabinets', 'employee_email' => 'linda.kalnina@ludzas.lv', 'department' => 'Informacijas tehnologijas', 'notes' => 'Lietotaju atbalsta darba vietas'],
                ['floor_number' => 1, 'room_number' => '103', 'room_name' => 'Tikla telpa', 'employee_email' => 'maris.vitols@ludzas.lv', 'department' => 'Informacijas tehnologijas', 'notes' => 'Maršrutetaji un komutatori'],
                ['floor_number' => 1, 'room_number' => '104', 'room_name' => 'Uzskaites kabinets', 'employee_email' => 'agnese.leite@ludzas.lv', 'department' => 'Finansu nodala', 'notes' => 'Darba vietas gramatvedibai'],
                ['floor_number' => 1, 'room_number' => '105', 'room_name' => 'Klientu pieņemšana', 'employee_email' => 'marta.zvirbule@ludzas.lv', 'department' => 'Administracija', 'notes' => 'Apmekletaju apkalposana'],
                ['floor_number' => 2, 'room_number' => '201', 'room_name' => 'Vadibas kabinets', 'employee_email' => 'ruta.liepa@ludzas.lv', 'department' => 'Vadiba', 'notes' => 'Iestades vaditajas darba vieta'],
                ['floor_number' => 2, 'room_number' => '202', 'room_name' => 'Projektu telpa', 'employee_email' => 'kristine.daukste@ludzas.lv', 'department' => 'Attistibas projekti', 'notes' => 'Projektu sanaksmes'],
                ['floor_number' => 2, 'room_number' => '203', 'room_name' => 'Personala kabinets', 'employee_email' => 'dace.rudzite@ludzas.lv', 'department' => 'Personala nodala', 'notes' => 'Personala dokumenti'],
                ['floor_number' => 2, 'room_number' => '204', 'room_name' => 'Datu analitika', 'employee_email' => 'anrijs.krumins@ludzas.lv', 'department' => 'Analitikas nodala', 'notes' => 'Atskaites un paneli'],
                ['floor_number' => 2, 'room_number' => '205', 'room_name' => 'Arhivs', 'employee_email' => 'sintija.cevere@ludzas.lv', 'department' => 'Dokumentu parvalde', 'notes' => 'Arhiva materiali'],
                ['floor_number' => 3, 'room_number' => '301', 'room_name' => 'Sanaksmju zale', 'employee_email' => 'roberts.arbidans@ludzas.lv', 'department' => 'Juridiska nodala', 'notes' => 'Kopejas sanaksmes'],
                ['floor_number' => 3, 'room_number' => '302', 'room_name' => 'Rezerves telpa', 'employee_email' => 'olafs.gailitis@ludzas.lv', 'department' => 'Saimniecibas nodala', 'notes' => 'Rezerves aprikojums'],
            ];

            $roomRows = [];
            foreach ($rooms as $room) {
                $roomRows[] = [
                    'building_id' => $building->id,
                    'floor_number' => $room['floor_number'],
                    'room_number' => $room['room_number'],
                    'room_name' => $room['room_name'],
                    'employee_id' => $employeeIds[$room['employee_email']] ?? null,
                    'department' => $room['department'],
                    'notes' => $room['notes'],
                    'created_at' => $now,
                ];
            }
            DB::table('rooms')->insert($roomRows);

            $roomIds = DB::table('rooms')
                ->where('building_id', $building->id)
                ->pluck('id', 'room_number')
                ->all();

            $deviceTypes = [
                ['type_name' => 'Klepjdators', 'category' => 'Datori', 'icon_name' => 'laptop', 'description' => 'Parnesajamie datori', 'expected_lifetime_years' => 4],
                ['type_name' => 'Stacionarais dators', 'category' => 'Datori', 'icon_name' => 'desktop', 'description' => 'Biroja darba stacijas', 'expected_lifetime_years' => 5],
                ['type_name' => 'Monitors', 'category' => 'Periferija', 'icon_name' => 'monitor', 'description' => 'Attela monitori', 'expected_lifetime_years' => 6],
                ['type_name' => 'Printeris', 'category' => 'Periferija', 'icon_name' => 'printer', 'description' => 'Drukas iekartas', 'expected_lifetime_years' => 5],
                ['type_name' => 'Komutators', 'category' => 'Tikls', 'icon_name' => 'switch', 'description' => 'Tikla komutators', 'expected_lifetime_years' => 7],
                ['type_name' => 'Marsrutetajs', 'category' => 'Tikls', 'icon_name' => 'router', 'description' => 'Tikla marsrutetajs', 'expected_lifetime_years' => 7],
                ['type_name' => 'UPS', 'category' => 'Elektroapgade', 'icon_name' => 'ups', 'description' => 'Nepartauktas barosanas avots', 'expected_lifetime_years' => 6],
                ['type_name' => 'Skeneris', 'category' => 'Periferija', 'icon_name' => 'scanner', 'description' => 'Dokumentu skeneri', 'expected_lifetime_years' => 5],
            ];
            foreach ($deviceTypes as &$type) {
                $type['created_at'] = $now;
            }
            unset($type);
            DB::table('device_types')->insert($deviceTypes);

            $typeIds = DB::table('device_types')->pluck('id', 'type_name')->all();

            $deviceTemplates = [
                ['prefix' => 'KL', 'name' => 'Klepjdators', 'type' => 'Klepjdators', 'model' => 'Latitude 5520', 'manufacturer' => 'Dell', 'status' => 'active'],
                ['prefix' => 'ST', 'name' => 'Stacionarais dators', 'type' => 'Stacionarais dators', 'model' => 'OptiPlex 7090', 'manufacturer' => 'Dell', 'status' => 'active'],
                ['prefix' => 'MN', 'name' => 'Monitors', 'type' => 'Monitors', 'model' => 'P2422H', 'manufacturer' => 'Dell', 'status' => 'active'],
                ['prefix' => 'PR', 'name' => 'Printeris', 'type' => 'Printeris', 'model' => 'LaserJet Pro 400', 'manufacturer' => 'HP', 'status' => 'reserve'],
                ['prefix' => 'SW', 'name' => 'Komutators', 'type' => 'Komutators', 'model' => 'CBS250-24T', 'manufacturer' => 'Cisco', 'status' => 'active'],
                ['prefix' => 'MR', 'name' => 'Marsrutetajs', 'type' => 'Marsrutetajs', 'model' => 'RB4011', 'manufacturer' => 'MikroTik', 'status' => 'active'],
                ['prefix' => 'UP', 'name' => 'UPS', 'type' => 'UPS', 'model' => 'Smart-UPS 1000', 'manufacturer' => 'APC', 'status' => 'reserve'],
                ['prefix' => 'SK', 'name' => 'Skeneris', 'type' => 'Skeneris', 'model' => 'ScanJet Pro 3000', 'manufacturer' => 'HP', 'status' => 'active'],
            ];

            $roomSequence = ['101', '102', '103', '104', '105', '201', '202', '203', '204', '205', '301', '302'];
            $assignees = [
                'Artis Berzins', 'Linda Kalnina', 'Janis Ozols', 'Ilze Strautina', 'Maris Vitols',
                'Kristine Daukste', 'Edgars Sviklis', 'Agnese Leite', 'Roberts Arbidans', 'Dace Rudzite',
                'Anrijs Krumins', 'Marta Zvirbule', 'Olafs Gailitis', 'Ruta Liepa', 'Toms Severs',
            ];

            $devices = [];
            $index = 1;
            for ($i = 0; $i < 40; $i++) {
                $template = $deviceTemplates[$i % count($deviceTemplates)];
                $roomNumber = $roomSequence[$i % count($roomSequence)];
                $status = $template['status'];
                if ($i % 11 === 0) {
                    $status = 'repair';
                } elseif ($i % 13 === 0) {
                    $status = 'broken';
                } elseif ($i % 17 === 0) {
                    $status = 'kitting';
                }

                $purchaseDate = now()->subDays(rand(200, 1800))->toDateString();
                $warrantyUntil = now()->addDays(rand(50, 700))->toDateString();

                $devices[] = [
                    'code' => 'LDZ-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'name' => $template['name'] . ' ' . $index,
                    'device_type_id' => $typeIds[$template['type']],
                    'model' => $template['model'],
                    'status' => $status,
                    'building_id' => $building->id,
                    'room_id' => $roomIds[$roomNumber] ?? null,
                    'assigned_to' => $assignees[$i % count($assignees)],
                    'purchase_date' => $purchaseDate,
                    'purchase_price' => rand(120, 2200) + 0.99,
                    'warranty_until' => $warrantyUntil,
                    'warranty_photo_name' => null,
                    'serial_number' => 'SN-LDZ-' . str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                    'manufacturer' => $template['manufacturer'],
                    'notes' => 'Inventarizacijas ierice darba videi',
                    'device_image_url' => null,
                    'created_by' => $adminUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $index++;
            }
            DB::table('devices')->insert($devices);

            $deviceIdsByCode = DB::table('devices')->pluck('id', 'code')->all();
            $allUserIds = array_values(DB::table('users')->pluck('id')->all());

            $repairs = [
                ['code' => 'LDZ-0001', 'description' => 'Neiesledzas pec stravas partraukuma', 'status' => 'in-progress', 'repair_type' => 'internal', 'priority' => 'high', 'start' => now()->subDays(8), 'eta' => now()->addDays(3), 'actual' => null, 'cost' => 45.50, 'vendor_name' => null, 'vendor_contact' => null, 'invoice' => null],
                ['code' => 'LDZ-0008', 'description' => 'Papira padeves kluda', 'status' => 'waiting', 'repair_type' => 'external', 'priority' => 'medium', 'start' => now()->subDays(4), 'eta' => now()->addDays(5), 'actual' => null, 'cost' => 120.00, 'vendor_name' => 'SIA Druka Serviss', 'vendor_contact' => '+371 26660001', 'invoice' => 'INV-2026-041'],
                ['code' => 'LDZ-0012', 'description' => 'Diska kluda un lena darbiba', 'status' => 'completed', 'repair_type' => 'internal', 'priority' => 'high', 'start' => now()->subDays(15), 'eta' => now()->subDays(10), 'actual' => now()->subDays(11), 'cost' => 80.00, 'vendor_name' => null, 'vendor_contact' => null, 'invoice' => null],
                ['code' => 'LDZ-0017', 'description' => 'Portu nestabila darbiba', 'status' => 'in-progress', 'repair_type' => 'external', 'priority' => 'critical', 'start' => now()->subDays(3), 'eta' => now()->addDays(4), 'actual' => null, 'cost' => 210.00, 'vendor_name' => 'SIA Tikla Meistars', 'vendor_contact' => '+371 26660002', 'invoice' => 'INV-2026-052'],
                ['code' => 'LDZ-0022', 'description' => 'Barosanas bloka nomaina', 'status' => 'completed', 'repair_type' => 'internal', 'priority' => 'low', 'start' => now()->subDays(22), 'eta' => now()->subDays(18), 'actual' => now()->subDays(19), 'cost' => 35.00, 'vendor_name' => null, 'vendor_contact' => null, 'invoice' => null],
                ['code' => 'LDZ-0026', 'description' => 'Bojats ekrans', 'status' => 'waiting', 'repair_type' => 'external', 'priority' => 'high', 'start' => now()->subDays(2), 'eta' => now()->addDays(9), 'actual' => null, 'cost' => 160.00, 'vendor_name' => 'SIA Display Serviss', 'vendor_contact' => '+371 26660003', 'invoice' => 'INV-2026-059'],
                ['code' => 'LDZ-0031', 'description' => 'Nestabils interneta savienojums', 'status' => 'cancelled', 'repair_type' => 'internal', 'priority' => 'medium', 'start' => now()->subDays(9), 'eta' => null, 'actual' => null, 'cost' => null, 'vendor_name' => null, 'vendor_contact' => null, 'invoice' => null],
                ['code' => 'LDZ-0036', 'description' => 'Ventilatora troksnis', 'status' => 'in-progress', 'repair_type' => 'internal', 'priority' => 'low', 'start' => now()->subDays(5), 'eta' => now()->addDays(2), 'actual' => null, 'cost' => 25.00, 'vendor_name' => null, 'vendor_contact' => null, 'invoice' => null],
            ];

            $repairRows = [];
            foreach ($repairs as $i => $repair) {
                if (! isset($deviceIdsByCode[$repair['code']])) {
                    continue;
                }
                $reporterId = $allUserIds[$i % count($allUserIds)] ?? null;
                $assigneeId = $allUserIds[($i + 1) % count($allUserIds)] ?? null;

                $repairRows[] = [
                    'device_id' => $deviceIdsByCode[$repair['code']],
                    'description' => $repair['description'],
                    'status' => $repair['status'],
                    'repair_type' => $repair['repair_type'],
                    'priority' => $repair['priority'],
                    'start_date' => $repair['start']->toDateString(),
                    'estimated_completion' => $repair['eta'] ? $repair['eta']->toDateString() : null,
                    'actual_completion' => $repair['actual'] ? $repair['actual']->toDateString() : null,
                    'cost' => $repair['cost'],
                    'vendor_name' => $repair['vendor_name'],
                    'vendor_contact' => $repair['vendor_contact'],
                    'invoice_number' => $repair['invoice'],
                    'issue_reported_by' => $reporterId,
                    'assigned_to' => $assigneeId,
                    'created_at' => $now,
                ];
            }
            DB::table('repairs')->insert($repairRows);

            $sets = [
                ['name' => 'Komplekts 1', 'description' => 'Darba vieta sekretarei', 'set_name' => 'Sekretares komplekts', 'set_code' => 'KIT-SEC-01', 'status' => 'active', 'room' => '105', 'assigned_to' => 'Marta Zvirbule', 'notes' => 'Pilns darba komplekts'],
                ['name' => 'Komplekts 2', 'description' => 'Attalinata darba komplekts', 'set_name' => 'Attalinata darba komplekts', 'set_code' => 'KIT-REM-01', 'status' => 'draft', 'room' => '202', 'assigned_to' => 'Kristine Daukste', 'notes' => 'Sagaida apstiprinajumu'],
                ['name' => 'Komplekts 3', 'description' => 'Tikla apkalpes komplekts', 'set_name' => 'Tikla servisa komplekts', 'set_code' => 'KIT-NET-01', 'status' => 'active', 'room' => '103', 'assigned_to' => 'Maris Vitols', 'notes' => 'Tikla diagnostikai'],
                ['name' => 'Komplekts 4', 'description' => 'Rezerves komplekts', 'set_name' => 'Rezerves biroja komplekts', 'set_code' => 'KIT-RES-01', 'status' => 'archived', 'room' => '302', 'assigned_to' => 'Olafs Gailitis', 'notes' => 'Glabasana noliktava'],
            ];

            $setRows = [];
            foreach ($sets as $set) {
                $setRows[] = [
                    'name' => $set['name'],
                    'description' => $set['description'],
                    'set_name' => $set['set_name'],
                    'set_code' => $set['set_code'],
                    'status' => $set['status'],
                    'room_id' => $roomIds[$set['room']] ?? null,
                    'assigned_to' => $set['assigned_to'],
                    'notes' => $set['notes'],
                    'created_by' => $adminUserId,
                    'created_at' => $now,
                ];
            }
            DB::table('device_sets')->insert($setRows);

            $setIds = DB::table('device_sets')->pluck('id', 'set_code')->all();
            $setItems = [
                ['set_code' => 'KIT-SEC-01', 'device_code' => 'LDZ-0002', 'quantity' => 1, 'role' => 'Pamata dators', 'description' => 'Sekretares darba dators'],
                ['set_code' => 'KIT-SEC-01', 'device_code' => 'LDZ-0003', 'quantity' => 1, 'role' => 'Displejs', 'description' => 'Darba monitors'],
                ['set_code' => 'KIT-SEC-01', 'device_code' => 'LDZ-0004', 'quantity' => 1, 'role' => 'Druka', 'description' => 'Biroja printeris'],
                ['set_code' => 'KIT-REM-01', 'device_code' => 'LDZ-0009', 'quantity' => 1, 'role' => 'Klepjdators', 'description' => 'Attalinatam darbam'],
                ['set_code' => 'KIT-REM-01', 'device_code' => 'LDZ-0015', 'quantity' => 1, 'role' => 'UPS', 'description' => 'Stabilai barosanai'],
                ['set_code' => 'KIT-NET-01', 'device_code' => 'LDZ-0013', 'quantity' => 1, 'role' => 'Komutators', 'description' => 'Servisa nomaina'],
                ['set_code' => 'KIT-NET-01', 'device_code' => 'LDZ-0014', 'quantity' => 1, 'role' => 'Marsrutetajs', 'description' => 'Tikla mezgliem'],
                ['set_code' => 'KIT-RES-01', 'device_code' => 'LDZ-0028', 'quantity' => 1, 'role' => 'Rezerves dators', 'description' => 'Noliktavas rezerve'],
            ];

            $itemRows = [];
            foreach ($setItems as $item) {
                if (! isset($setIds[$item['set_code']]) || ! isset($deviceIdsByCode[$item['device_code']])) {
                    continue;
                }
                $itemRows[] = [
                    'device_set_id' => $setIds[$item['set_code']],
                    'device_id' => $deviceIdsByCode[$item['device_code']],
                    'quantity' => $item['quantity'],
                    'role' => $item['role'],
                    'description' => $item['description'],
                    'created_at' => $now,
                ];
            }
            DB::table('device_set_items')->insert($itemRows);
        });
    }
}
