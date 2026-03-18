<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LatvianDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {
            DB::table('device_set_items')->delete();
            DB::table('device_transfers')->delete();
            DB::table('writeoff_requests')->delete();
            DB::table('repair_requests')->delete();
            DB::table('repairs')->delete();
            DB::table('device_sets')->delete();
            DB::table('devices')->delete();
            DB::table('rooms')->delete();
            DB::table('device_types')->delete();
            DB::table('buildings')->delete();
            DB::table('audit_log')->delete();
            DB::table('users')->delete();

            DB::table('buildings')->insert([
                [
                    'building_name' => 'Administracijas eka',
                    'address' => 'Brivibas iela 1',
                    'city' => 'Ludza',
                    'total_floors' => 3,
                    'notes' => 'Galvenais korpuss',
                    'created_at' => $now,
                ],
                [
                    'building_name' => 'Tehniskais korpuss',
                    'address' => 'Darza iela 8',
                    'city' => 'Ludza',
                    'total_floors' => 2,
                    'notes' => 'IT un noliktavas telpas',
                    'created_at' => $now,
                ],
            ]);

            $buildingIds = DB::table('buildings')->pluck('id', 'building_name')->all();

            $users = [
                ['full_name' => 'Artis Berzins', 'email' => 'artis.berzins@ludzas.lv', 'phone' => '+37126000001', 'job_title' => 'Sistemas administrators', 'role' => User::ROLE_ADMIN],
                ['full_name' => 'Linda Kalnina', 'email' => 'linda.kalnina@ludzas.lv', 'phone' => '+37126000002', 'job_title' => 'IT specialiste', 'role' => User::ROLE_ADMIN],
                ['full_name' => 'Janis Ozols', 'email' => 'janis.ozols@ludzas.lv', 'phone' => '+37126000003', 'job_title' => 'IT atbalsta inzenieris', 'role' => User::ROLE_ADMIN],
                ['full_name' => 'Ilze Strautina', 'email' => 'ilze.strautina@ludzas.lv', 'phone' => '+37126000004', 'job_title' => 'Projektu koordinatore', 'role' => User::ROLE_USER],
                ['full_name' => 'Maris Vitols', 'email' => 'maris.vitols@ludzas.lv', 'phone' => '+37126000005', 'job_title' => 'Tikla administrators', 'role' => User::ROLE_USER],
                ['full_name' => 'Kristine Daukste', 'email' => 'kristine.daukste@ludzas.lv', 'phone' => '+37126000006', 'job_title' => 'Finansu analitike', 'role' => User::ROLE_USER],
                ['full_name' => 'Edgars Sviklis', 'email' => 'edgars.sviklis@ludzas.lv', 'phone' => '+37126000007', 'job_title' => 'Iepirkumu specialists', 'role' => User::ROLE_USER],
                ['full_name' => 'Agnese Leite', 'email' => 'agnese.leite@ludzas.lv', 'phone' => '+37126000008', 'job_title' => 'Gramatvede', 'role' => User::ROLE_USER],
                ['full_name' => 'Roberts Arbidans', 'email' => 'roberts.arbidans@ludzas.lv', 'phone' => '+37126000009', 'job_title' => 'Jurists', 'role' => User::ROLE_USER],
                ['full_name' => 'Dace Rudzite', 'email' => 'dace.rudzite@ludzas.lv', 'phone' => '+37126000010', 'job_title' => 'Personala specialiste', 'role' => User::ROLE_USER],
                ['full_name' => 'Marta Zvirbule', 'email' => 'marta.zvirbule@ludzas.lv', 'phone' => '+37126000011', 'job_title' => 'Sekretare', 'role' => User::ROLE_USER],
                ['full_name' => 'Ruta Liepa', 'email' => 'ruta.liepa@ludzas.lv', 'phone' => '+37126000012', 'job_title' => 'Iestades vaditaja', 'role' => User::ROLE_USER],
                ['full_name' => 'Liga Jansone', 'email' => 'liga.jansone@ludzas.lv', 'phone' => '+37126000013', 'job_title' => 'Lietvede', 'role' => User::ROLE_USER],
                ['full_name' => 'Andris Berzins', 'email' => 'andris.berzins@ludzas.lv', 'phone' => '+37126000014', 'job_title' => 'Saimniecibas parsvaldnieks', 'role' => User::ROLE_USER],
                ['full_name' => 'Elina Krumina', 'email' => 'elina.krumina@ludzas.lv', 'phone' => '+37126000015', 'job_title' => 'Projektu asistente', 'role' => User::ROLE_USER],
                ['full_name' => 'Guntis Vilks', 'email' => 'guntis.vilks@ludzas.lv', 'phone' => '+37126000016', 'job_title' => 'Autovaditajs', 'role' => User::ROLE_USER],
                ['full_name' => 'Inese Priede', 'email' => 'inese.priede@ludzas.lv', 'phone' => '+37126000017', 'job_title' => 'Jurista palidze', 'role' => User::ROLE_USER],
                ['full_name' => 'Oskars Balevics', 'email' => 'oskars.balevics@ludzas.lv', 'phone' => '+37126000018', 'job_title' => 'Iepirkumu koordinators', 'role' => User::ROLE_USER],
                ['full_name' => 'Sanita Miezite', 'email' => 'sanita.miezite@ludzas.lv', 'phone' => '+37126000019', 'job_title' => 'Klientu apkalposanas specialiste', 'role' => User::ROLE_USER],
                ['full_name' => 'Kaspars Liekis', 'email' => 'kaspars.liekis@ludzas.lv', 'phone' => '+37126000020', 'job_title' => 'Tehniskais darbinieks', 'role' => User::ROLE_USER],
                ['full_name' => 'Evija Ozolina', 'email' => 'evija.ozolina@ludzas.lv', 'phone' => '+37126000021', 'job_title' => 'Biroja vaditaja', 'role' => User::ROLE_USER],
                ['full_name' => 'Toms Karklins', 'email' => 'toms.karklins@ludzas.lv', 'phone' => '+37126000022', 'job_title' => 'Komunikacijas specialists', 'role' => User::ROLE_USER],
                ['full_name' => 'Laura Vasile', 'email' => 'laura.vasile@ludzas.lv', 'phone' => '+37126000023', 'job_title' => 'Datu ievades operatore', 'role' => User::ROLE_USER],
                ['full_name' => 'Rihards Sprogis', 'email' => 'rihards.sprogis@ludzas.lv', 'phone' => '+37126000024', 'job_title' => 'Arhivars', 'role' => User::ROLE_USER],
                ['full_name' => 'Zane Paberde', 'email' => 'zane.paberde@ludzas.lv', 'phone' => '+37126000025', 'job_title' => 'Vaditaja asistente', 'role' => User::ROLE_USER],
                ['full_name' => 'Normunds Keiss', 'email' => 'normunds.keiss@ludzas.lv', 'phone' => '+37126000026', 'job_title' => 'Saimniecibas specialists', 'role' => User::ROLE_USER],
                ['full_name' => 'Ieva Rubene', 'email' => 'ieva.rubene@ludzas.lv', 'phone' => '+37126000027', 'job_title' => 'Finansu asistente', 'role' => User::ROLE_USER],
                ['full_name' => 'Miks Upitis', 'email' => 'miks.upitis@ludzas.lv', 'phone' => '+37126000028', 'job_title' => 'Dokumentu parzinis', 'role' => User::ROLE_USER],
                ['full_name' => 'Una Eglite', 'email' => 'una.eglite@ludzas.lv', 'phone' => '+37126000029', 'job_title' => 'Lietvede', 'role' => User::ROLE_USER],
                ['full_name' => 'Viktorija Zeile', 'email' => 'viktorija.zeile@ludzas.lv', 'phone' => '+37126000030', 'job_title' => 'Administratore', 'role' => User::ROLE_USER],
            ];

            DB::table('users')->insert(array_map(function (array $user) use ($now) {
                return array_merge($user, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'remember_token' => null,
                    'last_login' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $users));

            $userIdsByEmail = DB::table('users')->pluck('id', 'email')->all();
            $userIdsByName = DB::table('users')->pluck('id', 'full_name')->all();
            $adminUserId = $userIdsByEmail['artis.berzins@ludzas.lv'];

            $rooms = [
                ['building' => 'Administracijas eka', 'floor_number' => 1, 'room_number' => '101', 'room_name' => 'IT mezgls', 'user_name' => 'Artis Berzins', 'department' => 'IT', 'notes' => 'Serveri un tikla mezgli'],
                ['building' => 'Administracijas eka', 'floor_number' => 1, 'room_number' => '102', 'room_name' => 'Atbalsta kabinets', 'user_name' => 'Linda Kalnina', 'department' => 'IT', 'notes' => 'Ikdienas atbalsta darbi'],
                ['building' => 'Administracijas eka', 'floor_number' => 2, 'room_number' => '201', 'room_name' => 'Vadibas kabinets', 'user_name' => 'Ruta Liepa', 'department' => 'Vadiba', 'notes' => 'Vadibas darba vieta'],
                ['building' => 'Administracijas eka', 'floor_number' => 2, 'room_number' => '202', 'room_name' => 'Finansu telpa', 'user_name' => 'Agnese Leite', 'department' => 'Finanses', 'notes' => 'Finansu nodala'],
                ['building' => 'Administracijas eka', 'floor_number' => 3, 'room_number' => '301', 'room_name' => 'Personala kabinets', 'user_name' => 'Dace Rudzite', 'department' => 'Personals', 'notes' => 'Personala dokumenti'],
                ['building' => 'Tehniskais korpuss', 'floor_number' => 1, 'room_number' => 'T1', 'room_name' => 'Noliktava', 'user_name' => 'Maris Vitols', 'department' => 'IT', 'notes' => 'Rezerves tehnika'],
                ['building' => 'Tehniskais korpuss', 'floor_number' => 1, 'room_number' => 'T2', 'room_name' => 'Darbnica', 'user_name' => 'Janis Ozols', 'department' => 'IT', 'notes' => 'Diagnostika un remonti'],
                ['building' => 'Tehniskais korpuss', 'floor_number' => 2, 'room_number' => 'T3', 'room_name' => 'Projektu telpa', 'user_name' => 'Kristine Daukste', 'department' => 'Attistiba', 'notes' => 'Projektu komanda'],
            ];

            DB::table('rooms')->insert(array_map(function (array $room) use ($buildingIds, $userIdsByName, $now) {
                return [
                    'building_id' => $buildingIds[$room['building']],
                    'floor_number' => $room['floor_number'],
                    'room_number' => $room['room_number'],
                    'room_name' => $room['room_name'],
                    'user_id' => $userIdsByName[$room['user_name']] ?? null,
                    'department' => $room['department'],
                    'notes' => $room['notes'],
                    'created_at' => $now,
                ];
            }, $rooms));

            $roomIds = DB::table('rooms')->pluck('id', 'room_number')->all();

            $deviceTypes = [
                ['type_name' => 'Klepjdators', 'category' => 'Datori', 'description' => 'Parnesajami datori', 'expected_lifetime_years' => 4],
                ['type_name' => 'Stacionarais dators', 'category' => 'Datori', 'description' => 'Darba stacijas', 'expected_lifetime_years' => 5],
                ['type_name' => 'Monitors', 'category' => 'Periferija', 'description' => 'Attela monitori', 'expected_lifetime_years' => 6],
                ['type_name' => 'Printeris', 'category' => 'Periferija', 'description' => 'Drukas iekartas', 'expected_lifetime_years' => 5],
                ['type_name' => 'Komutators', 'category' => 'Tikls', 'description' => 'Tikla komutatori', 'expected_lifetime_years' => 7],
                ['type_name' => 'UPS', 'category' => 'Elektroapgade', 'description' => 'Barosanas rezerve', 'expected_lifetime_years' => 6],
            ];

            DB::table('device_types')->insert(array_map(fn (array $type) => array_merge($type, ['created_at' => $now]), $deviceTypes));
            $deviceTypeIds = DB::table('device_types')->pluck('id', 'type_name')->all();

            $deviceBlueprints = [
                ['code' => 'LDZ-0001', 'name' => 'Klepjdators A1', 'type' => 'Klepjdators', 'model' => 'Dell Latitude 5520', 'status' => 'repair', 'room' => '102', 'assigned_to' => 'Ilze Strautina'],
                ['code' => 'LDZ-0002', 'name' => 'Monitors A1', 'type' => 'Monitors', 'model' => 'Dell P2422H', 'status' => 'active', 'room' => '102', 'assigned_to' => 'Ilze Strautina'],
                ['code' => 'LDZ-0003', 'name' => 'Stacionarais dators B1', 'type' => 'Stacionarais dators', 'model' => 'Dell OptiPlex 7090', 'status' => 'active', 'room' => '202', 'assigned_to' => 'Agnese Leite'],
                ['code' => 'LDZ-0004', 'name' => 'Printeris B1', 'type' => 'Printeris', 'model' => 'HP LaserJet Pro 400', 'status' => 'active', 'room' => '202', 'assigned_to' => null],
                ['code' => 'LDZ-0005', 'name' => 'Klepjdators C1', 'type' => 'Klepjdators', 'model' => 'Lenovo ThinkPad T14', 'status' => 'active', 'room' => '201', 'assigned_to' => 'Ruta Liepa'],
                ['code' => 'LDZ-0006', 'name' => 'Klepjdators C2', 'type' => 'Klepjdators', 'model' => 'HP EliteBook 840', 'status' => 'repair', 'room' => '301', 'assigned_to' => 'Dace Rudzite'],
                ['code' => 'LDZ-0007', 'name' => 'UPS D1', 'type' => 'UPS', 'model' => 'APC Smart-UPS 1000', 'status' => 'active', 'room' => 'T2', 'assigned_to' => 'Janis Ozols'],
                ['code' => 'LDZ-0008', 'name' => 'Komutators D1', 'type' => 'Komutators', 'model' => 'Cisco CBS250', 'status' => 'active', 'room' => '101', 'assigned_to' => 'Maris Vitols'],
                ['code' => 'LDZ-0009', 'name' => 'Monitors E1', 'type' => 'Monitors', 'model' => 'LG 27UL500', 'status' => 'active', 'room' => 'T3', 'assigned_to' => 'Kristine Daukste'],
                ['code' => 'LDZ-0010', 'name' => 'Klepjdators F1', 'type' => 'Klepjdators', 'model' => 'Dell Latitude 7420', 'status' => 'writeoff', 'room' => 'T1', 'assigned_to' => null],
                ['code' => 'LDZ-0011', 'name' => 'Stacionarais dators G1', 'type' => 'Stacionarais dators', 'model' => 'HP ProDesk 600', 'status' => 'active', 'room' => 'T1', 'assigned_to' => null],
                ['code' => 'LDZ-0012', 'name' => 'Printeris H1', 'type' => 'Printeris', 'model' => 'Brother HL-L5100DN', 'status' => 'active', 'room' => '301', 'assigned_to' => 'Marta Zvirbule'],
            ];

            DB::table('devices')->insert(array_map(function (array $device) use ($deviceTypeIds, $roomIds, $userIdsByName, $adminUserId, $now) {
                $roomId = $roomIds[$device['room']] ?? null;
                $room = DB::table('rooms')->where('id', $roomId)->first();

                return [
                    'code' => $device['code'],
                    'name' => $device['name'],
                    'device_type_id' => $deviceTypeIds[$device['type']],
                    'model' => $device['model'],
                    'status' => $device['status'],
                    'building_id' => $room->building_id ?? null,
                    'room_id' => $roomId,
                    'assigned_to_id' => $device['assigned_to'] ? ($userIdsByName[$device['assigned_to']] ?? null) : null,
                    'purchase_date' => now()->subDays(rand(180, 1600))->toDateString(),
                    'purchase_price' => rand(180, 2200) + 0.99,
                    'warranty_until' => now()->addDays(rand(30, 720))->toDateString(),
                    'warranty_photo_name' => null,
                    'serial_number' => 'SN-' . $device['code'],
                    'manufacturer' => str_contains($device['model'], 'Dell') ? 'Dell' : (str_contains($device['model'], 'HP') ? 'HP' : 'Cisco'),
                    'notes' => 'Demo ierice jaunajai schemai',
                    'device_image_url' => null,
                    'created_by' => $adminUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $deviceBlueprints));

            $deviceIds = DB::table('devices')->pluck('id', 'code')->all();

            $repairs = [
                [
                    'device_id' => $deviceIds['LDZ-0001'],
                    'issue_reported_by' => $userIdsByName['Ilze Strautina'],
                    'accepted_by' => $adminUserId,
                    'description' => 'Klepjdators vairs neuzladejas.',
                    'status' => 'waiting',
                    'repair_type' => 'internal',
                    'priority' => 'high',
                    'start_date' => now()->subDays(1)->toDateString(),
                    'end_date' => null,
                    'cost' => null,
                    'vendor_name' => null,
                    'vendor_contact' => null,
                    'invoice_number' => null,
                    'request_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'device_id' => $deviceIds['LDZ-0006'],
                    'issue_reported_by' => $userIdsByName['Dace Rudzite'],
                    'accepted_by' => $adminUserId,
                    'description' => 'Ierice iesledzas, bet disks rada kludas.',
                    'status' => 'in-progress',
                    'repair_type' => 'external',
                    'priority' => 'critical',
                    'start_date' => now()->subDays(4)->toDateString(),
                    'end_date' => null,
                    'cost' => 180.00,
                    'vendor_name' => 'SIA IT Serviss',
                    'vendor_contact' => '+37126660001',
                    'invoice_number' => 'INV-2026-1001',
                    'request_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'device_id' => $deviceIds['LDZ-0012'],
                    'issue_reported_by' => $userIdsByName['Marta Zvirbule'],
                    'accepted_by' => $adminUserId,
                    'description' => 'Printeris iesprudina papiru.',
                    'status' => 'completed',
                    'repair_type' => 'internal',
                    'priority' => 'medium',
                    'start_date' => now()->subDays(10)->toDateString(),
                    'end_date' => now()->subDays(8)->toDateString(),
                    'cost' => 35.00,
                    'vendor_name' => null,
                    'vendor_contact' => null,
                    'invoice_number' => null,
                    'request_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            DB::table('repairs')->insert($repairs);
            $repairIds = DB::table('repairs')->pluck('id', 'device_id')->all();

            DB::table('repair_requests')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0001'],
                    'responsible_user_id' => $userIdsByName['Ilze Strautina'],
                    'description' => 'Uzlade nestrada un dators izsledzas.',
                    'status' => 'approved',
                    'reviewed_by_user_id' => $adminUserId,
                    'repair_id' => $repairIds[$deviceIds['LDZ-0001']] ?? null,
                    'review_notes' => 'Apstiprinats un nodots IT nodalai.',
                    'created_at' => $now->copy()->subDays(1),
                    'updated_at' => $now,
                ],
                [
                    'device_id' => $deviceIds['LDZ-0005'],
                    'responsible_user_id' => $userIdsByName['Ruta Liepa'],
                    'description' => 'Dators sakarst un strada leni.',
                    'status' => 'submitted',
                    'reviewed_by_user_id' => null,
                    'repair_id' => null,
                    'review_notes' => null,
                    'created_at' => $now->copy()->subHours(6),
                    'updated_at' => $now->copy()->subHours(6),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0009'],
                    'responsible_user_id' => $userIdsByName['Kristine Daukste'],
                    'description' => 'Monitors mirgo, bet pec parbaudes strada korekti.',
                    'status' => 'rejected',
                    'reviewed_by_user_id' => $userIdsByName['Linda Kalnina'],
                    'repair_id' => null,
                    'review_notes' => 'Atkārtotu problemu neizdevas konstatet.',
                    'created_at' => $now->copy()->subDays(3),
                    'updated_at' => $now->copy()->subDays(2),
                ],
            ]);

            DB::table('writeoff_requests')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0010'],
                    'responsible_user_id' => $userIdsByName['Edgars Sviklis'],
                    'reason' => 'Ierice novecojusi un ekonomiski nelietderiga remontam.',
                    'status' => 'approved',
                    'reviewed_by_user_id' => $adminUserId,
                    'review_notes' => 'Norakstisana apstiprinata.',
                    'created_at' => $now->copy()->subDays(7),
                    'updated_at' => $now->copy()->subDays(6),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0003'],
                    'responsible_user_id' => $userIdsByName['Agnese Leite'],
                    'reason' => 'Nepietiekama veiktspēja ikdienas darbam.',
                    'status' => 'submitted',
                    'reviewed_by_user_id' => null,
                    'review_notes' => null,
                    'created_at' => $now->copy()->subHours(10),
                    'updated_at' => $now->copy()->subHours(10),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0008'],
                    'responsible_user_id' => $userIdsByName['Maris Vitols'],
                    'reason' => 'Pec lietotaja domam lens, bet tehniski darba kartiba.',
                    'status' => 'rejected',
                    'reviewed_by_user_id' => $userIdsByName['Janis Ozols'],
                    'review_notes' => 'Nomaina sobrid nav pamatota.',
                    'created_at' => $now->copy()->subDays(5),
                    'updated_at' => $now->copy()->subDays(4),
                ],
            ]);

            DB::table('device_transfers')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0002'],
                    'responsible_user_id' => $userIdsByName['Ilze Strautina'],
                    'transfered_to_id' => $userIdsByName['Marta Zvirbule'],
                    'transfer_reason' => 'Monitors nepieciešams sekretāres darba vietai.',
                    'status' => 'approved',
                    'reviewed_by_user_id' => $userIdsByName['Marta Zvirbule'],
                    'review_notes' => 'Ierice parregistreta.',
                    'created_at' => $now->copy()->subDays(2),
                    'updated_at' => $now->copy()->subDay(),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0005'],
                    'responsible_user_id' => $userIdsByName['Ruta Liepa'],
                    'transfered_to_id' => $userIdsByName['Kristine Daukste'],
                    'transfer_reason' => 'Pagaidu darba vajadzibam projektu komandai.',
                    'status' => 'submitted',
                    'reviewed_by_user_id' => null,
                    'review_notes' => null,
                    'created_at' => $now->copy()->subHours(5),
                    'updated_at' => $now->copy()->subHours(5),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0007'],
                    'responsible_user_id' => $userIdsByName['Janis Ozols'],
                    'transfered_to_id' => $userIdsByName['Roberts Arbidans'],
                    'transfer_reason' => 'Lietotajs pieprasija UPS juristu kabinetam.',
                    'status' => 'rejected',
                    'reviewed_by_user_id' => $userIdsByName['Roberts Arbidans'],
                    'review_notes' => 'Sanemejam sobrid nav vajadzibas pec ierices.',
                    'created_at' => $now->copy()->subDays(4),
                    'updated_at' => $now->copy()->subDays(3),
                ],
            ]);

            DB::table('devices')
                ->where('id', $deviceIds['LDZ-0002'])
                ->update(['assigned_to_id' => $userIdsByName['Marta Zvirbule']]);

            if (Schema::hasColumn('repairs', 'request_id')) {
                $approvedRequestId = DB::table('repair_requests')
                    ->where('device_id', $deviceIds['LDZ-0001'])
                    ->value('id');

                if ($approvedRequestId) {
                    DB::table('repairs')
                        ->where('id', $repairIds[$deviceIds['LDZ-0001']] ?? null)
                        ->update(['request_id' => $approvedRequestId]);
                }
            }

            DB::table('device_sets')->insert([
                [
                    'name' => 'Darba vieta sekretarei',
                    'description' => 'Pilns komplekts sekretarei',
                    'set_name' => 'Sekretares komplekts',
                    'set_code' => 'KIT-SEC-01',
                    'status' => 'active',
                    'room_id' => $roomIds['301'],
                    'assigned_to' => 'Marta Zvirbule',
                    'notes' => 'Darba vietas pamata komplekts',
                    'created_by' => $adminUserId,
                    'created_at' => $now,
                ],
                [
                    'name' => 'Rezerves komplekts',
                    'description' => 'Rezerves tehnikai noliktava',
                    'set_name' => 'Rezerves komplekts',
                    'set_code' => 'KIT-RES-01',
                    'status' => 'draft',
                    'room_id' => $roomIds['T1'],
                    'assigned_to' => 'Maris Vitols',
                    'notes' => 'Noliktavas komplekts',
                    'created_by' => $adminUserId,
                    'created_at' => $now,
                ],
            ]);

            $setIds = DB::table('device_sets')->pluck('id', 'set_code')->all();

            DB::table('device_set_items')->insert([
                [
                    'device_set_id' => $setIds['KIT-SEC-01'],
                    'device_id' => $deviceIds['LDZ-0002'],
                    'quantity' => 1,
                    'role' => 'Monitors',
                    'description' => 'Sekretares monitors',
                    'created_at' => $now,
                ],
                [
                    'device_set_id' => $setIds['KIT-SEC-01'],
                    'device_id' => $deviceIds['LDZ-0012'],
                    'quantity' => 1,
                    'role' => 'Printeris',
                    'description' => 'Drukas ierice sekretarei',
                    'created_at' => $now,
                ],
                [
                    'device_set_id' => $setIds['KIT-RES-01'],
                    'device_id' => $deviceIds['LDZ-0011'],
                    'quantity' => 1,
                    'role' => 'Rezerves dators',
                    'description' => 'Rezerves darba vietas dators',
                    'created_at' => $now,
                ],
            ]);
        });
    }
}
