<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
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
            DB::table('device_transfers')->delete();
            DB::table('writeoff_requests')->delete();
            DB::table('repair_requests')->delete();
            DB::table('repairs')->delete();
            DB::table('devices')->delete();
            DB::table('rooms')->delete();
            DB::table('device_types')->delete();
            DB::table('buildings')->delete();
            DB::table('audit_log')->delete();
            DB::table('users')->delete();

            DB::table('buildings')->insert([
                [
                    'building_name' => 'Administrācijas ēka',
                    'address' => 'Brīvības iela 1',
                    'city' => 'Ludza',
                    'total_floors' => 3,
                    'notes' => 'Galvenais korpuss',
                    'created_at' => $now,
                ],
                [
                    'building_name' => 'Tehniskais korpuss',
                    'address' => 'Dārza iela 8',
                    'city' => 'Ludza',
                    'total_floors' => 2,
                    'notes' => 'IT un noliktavas telpas',
                    'created_at' => $now,
                ],
            ]);

            $buildingIds = DB::table('buildings')->pluck('id', 'building_name')->all();

            $users = [
                ['full_name' => 'Artis Bērziņš', 'email' => 'artis.berzins@ludzas.lv', 'phone' => '+37126000001', 'job_title' => 'Sistēmu administrators', 'role' => User::ROLE_ADMIN],
                ['full_name' => 'Linda Kalniņa', 'email' => 'linda.kalnina@ludzas.lv', 'phone' => '+37126000002', 'job_title' => 'IT speciāliste', 'role' => User::ROLE_ADMIN],
                ['full_name' => 'Jānis Ozols', 'email' => 'janis.ozols@ludzas.lv', 'phone' => '+37126000003', 'job_title' => 'IT atbalsta inženieris', 'role' => User::ROLE_ADMIN],
                ['full_name' => 'Ilze Strautiņa', 'email' => 'ilze.strautina@ludzas.lv', 'phone' => '+37126000004', 'job_title' => 'Projektu koordinatore', 'role' => User::ROLE_USER],
                ['full_name' => 'Māris Vītols', 'email' => 'maris.vitols@ludzas.lv', 'phone' => '+37126000005', 'job_title' => 'Tīkla administrators', 'role' => User::ROLE_USER],
                ['full_name' => 'Kristīne Daukste', 'email' => 'kristine.daukste@ludzas.lv', 'phone' => '+37126000006', 'job_title' => 'Finanšu analītiķe', 'role' => User::ROLE_USER],
                ['full_name' => 'Edgars Sviklis', 'email' => 'edgars.sviklis@ludzas.lv', 'phone' => '+37126000007', 'job_title' => 'Iepirkumu speciālists', 'role' => User::ROLE_USER],
                ['full_name' => 'Agnese Leite', 'email' => 'agnese.leite@ludzas.lv', 'phone' => '+37126000008', 'job_title' => 'Grāmatvede', 'role' => User::ROLE_USER],
                ['full_name' => 'Roberts Arbidāns', 'email' => 'roberts.arbidans@ludzas.lv', 'phone' => '+37126000009', 'job_title' => 'Jurists', 'role' => User::ROLE_USER],
                ['full_name' => 'Dace Rudzīte', 'email' => 'dace.rudzite@ludzas.lv', 'phone' => '+37126000010', 'job_title' => 'Personāla speciāliste', 'role' => User::ROLE_USER],
                ['full_name' => 'Marta Zvirbule', 'email' => 'marta.zvirbule@ludzas.lv', 'phone' => '+37126000011', 'job_title' => 'Sekretāre', 'role' => User::ROLE_USER],
                ['full_name' => 'Ruta Liepa', 'email' => 'ruta.liepa@ludzas.lv', 'phone' => '+37126000012', 'job_title' => 'Iestādes vadītāja', 'role' => User::ROLE_USER],
                ['full_name' => 'Līga Jansone', 'email' => 'liga.jansone@ludzas.lv', 'phone' => '+37126000013', 'job_title' => 'Lietvede', 'role' => User::ROLE_USER],
                ['full_name' => 'Andris Bērziņš', 'email' => 'andris.berzins@ludzas.lv', 'phone' => '+37126000014', 'job_title' => 'Saimniecības pārvaldnieks', 'role' => User::ROLE_USER],
                ['full_name' => 'Elīna Krūmiņa', 'email' => 'elina.krumina@ludzas.lv', 'phone' => '+37126000015', 'job_title' => 'Projektu asistente', 'role' => User::ROLE_USER],
                ['full_name' => 'Guntis Vilks', 'email' => 'guntis.vilks@ludzas.lv', 'phone' => '+37126000016', 'job_title' => 'Autovadītājs', 'role' => User::ROLE_USER],
                ['full_name' => 'Inese Priede', 'email' => 'inese.priede@ludzas.lv', 'phone' => '+37126000017', 'job_title' => 'Jurista palīdze', 'role' => User::ROLE_USER],
                ['full_name' => 'Oskars Baļevičs', 'email' => 'oskars.balevics@ludzas.lv', 'phone' => '+37126000018', 'job_title' => 'Iepirkumu koordinators', 'role' => User::ROLE_USER],
                ['full_name' => 'Sanita Miezīte', 'email' => 'sanita.miezite@ludzas.lv', 'phone' => '+37126000019', 'job_title' => 'Klientu apkalpošanas speciāliste', 'role' => User::ROLE_USER],
                ['full_name' => 'Kaspars Lieķis', 'email' => 'kaspars.liekis@ludzas.lv', 'phone' => '+37126000020', 'job_title' => 'Tehniskais darbinieks', 'role' => User::ROLE_USER],
                ['full_name' => 'Evija Ozoliņa', 'email' => 'evija.ozolina@ludzas.lv', 'phone' => '+37126000021', 'job_title' => 'Biroja vadītāja', 'role' => User::ROLE_USER],
                ['full_name' => 'Toms Kārkliņš', 'email' => 'toms.karklins@ludzas.lv', 'phone' => '+37126000022', 'job_title' => 'Komunikācijas speciālists', 'role' => User::ROLE_USER],
                ['full_name' => 'Laura Vasiļe', 'email' => 'laura.vasile@ludzas.lv', 'phone' => '+37126000023', 'job_title' => 'Datu ievades operatore', 'role' => User::ROLE_USER],
                ['full_name' => 'Rihards Sproģis', 'email' => 'rihards.sprogis@ludzas.lv', 'phone' => '+37126000024', 'job_title' => 'Arhīvārs', 'role' => User::ROLE_USER],
                ['full_name' => 'Zane Paberde', 'email' => 'zane.paberde@ludzas.lv', 'phone' => '+37126000025', 'job_title' => 'Vadītāja asistente', 'role' => User::ROLE_USER],
                ['full_name' => 'Normunds Keišs', 'email' => 'normunds.keiss@ludzas.lv', 'phone' => '+37126000026', 'job_title' => 'Saimniecības speciālists', 'role' => User::ROLE_USER],
                ['full_name' => 'Ieva Rubene', 'email' => 'ieva.rubene@ludzas.lv', 'phone' => '+37126000027', 'job_title' => 'Finanšu asistente', 'role' => User::ROLE_USER],
                ['full_name' => 'Miks Upītis', 'email' => 'miks.upitis@ludzas.lv', 'phone' => '+37126000028', 'job_title' => 'Dokumentu pārzinis', 'role' => User::ROLE_USER],
                ['full_name' => 'Una Eglīte', 'email' => 'una.eglite@ludzas.lv', 'phone' => '+37126000029', 'job_title' => 'Lietvede', 'role' => User::ROLE_USER],
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
                ['building' => 'Administrācijas ēka', 'floor_number' => 1, 'room_number' => '101', 'room_name' => 'IT mezgls', 'user_name' => 'Artis Bērziņš', 'department' => 'IT', 'notes' => 'Serveri un tīkla mezgli'],
                ['building' => 'Administrācijas ēka', 'floor_number' => 1, 'room_number' => '102', 'room_name' => 'Atbalsta kabinets', 'user_name' => 'Linda Kalniņa', 'department' => 'IT', 'notes' => 'Ikdienas atbalsta darbi'],
                ['building' => 'Administrācijas ēka', 'floor_number' => 2, 'room_number' => '201', 'room_name' => 'Vadības kabinets', 'user_name' => 'Ruta Liepa', 'department' => 'Vadība', 'notes' => 'Vadības darba vieta'],
                ['building' => 'Administrācijas ēka', 'floor_number' => 2, 'room_number' => '202', 'room_name' => 'Finanšu telpa', 'user_name' => 'Agnese Leite', 'department' => 'Finanses', 'notes' => 'Finanšu nodaļa'],
                ['building' => 'Administrācijas ēka', 'floor_number' => 3, 'room_number' => '301', 'room_name' => 'Personāla kabinets', 'user_name' => 'Dace Rudzīte', 'department' => 'Personāls', 'notes' => 'Personāla dokumenti'],
                ['building' => 'Tehniskais korpuss', 'floor_number' => 1, 'room_number' => 'T1', 'room_name' => 'Noliktava', 'user_name' => 'Māris Vītols', 'department' => 'IT', 'notes' => 'Rezerves tehnika'],
                ['building' => 'Tehniskais korpuss', 'floor_number' => 1, 'room_number' => 'T2', 'room_name' => 'Darbnīca', 'user_name' => 'Jānis Ozols', 'department' => 'IT', 'notes' => 'Diagnostika un remonti'],
                ['building' => 'Tehniskais korpuss', 'floor_number' => 2, 'room_number' => 'T3', 'room_name' => 'Projektu telpa', 'user_name' => 'Kristīne Daukste', 'department' => 'Attīstība', 'notes' => 'Projektu komanda'],
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
                ['type_name' => 'Klēpjdators'],
                ['type_name' => 'Stacionārais dators'],
                ['type_name' => 'Monitors'],
                ['type_name' => 'Printeris'],
                ['type_name' => 'Komutators'],
                ['type_name' => 'UPS'],
            ];

            DB::table('device_types')->insert($deviceTypes);

            $deviceTypeIds = DB::table('device_types')->pluck('id', 'type_name')->all();

            $deviceBlueprints = [
                ['code' => 'LDZ-0001', 'name' => 'Klēpjdators A1', 'type' => 'Klēpjdators', 'model' => 'Dell Latitude 5520', 'status' => Device::STATUS_REPAIR, 'room' => '102', 'assigned_to' => 'Ilze Strautiņa'],
                ['code' => 'LDZ-0002', 'name' => 'Monitors A1', 'type' => 'Monitors', 'model' => 'Dell P2422H', 'status' => Device::STATUS_ACTIVE, 'room' => '102', 'assigned_to' => 'Ilze Strautiņa'],
                ['code' => 'LDZ-0003', 'name' => 'Stacionārais dators B1', 'type' => 'Stacionārais dators', 'model' => 'Dell OptiPlex 7090', 'status' => Device::STATUS_ACTIVE, 'room' => '202', 'assigned_to' => 'Agnese Leite'],
                ['code' => 'LDZ-0004', 'name' => 'Printeris B1', 'type' => 'Printeris', 'model' => 'HP LaserJet Pro 400', 'status' => Device::STATUS_ACTIVE, 'room' => '202', 'assigned_to' => null],
                ['code' => 'LDZ-0005', 'name' => 'Klēpjdators C1', 'type' => 'Klēpjdators', 'model' => 'Lenovo ThinkPad T14', 'status' => Device::STATUS_ACTIVE, 'room' => '201', 'assigned_to' => 'Ruta Liepa'],
                ['code' => 'LDZ-0006', 'name' => 'Klēpjdators C2', 'type' => 'Klēpjdators', 'model' => 'HP EliteBook 840', 'status' => Device::STATUS_REPAIR, 'room' => '301', 'assigned_to' => 'Dace Rudzīte'],
                ['code' => 'LDZ-0007', 'name' => 'UPS D1', 'type' => 'UPS', 'model' => 'APC Smart-UPS 1000', 'status' => Device::STATUS_ACTIVE, 'room' => 'T2', 'assigned_to' => 'Jānis Ozols'],
                ['code' => 'LDZ-0008', 'name' => 'Komutators D1', 'type' => 'Komutators', 'model' => 'Cisco CBS250', 'status' => Device::STATUS_ACTIVE, 'room' => '101', 'assigned_to' => 'Māris Vītols'],
                ['code' => 'LDZ-0009', 'name' => 'Monitors E1', 'type' => 'Monitors', 'model' => 'LG 27UL500', 'status' => Device::STATUS_ACTIVE, 'room' => 'T3', 'assigned_to' => 'Kristīne Daukste'],
                ['code' => 'LDZ-0010', 'name' => 'Klēpjdators F1', 'type' => 'Klēpjdators', 'model' => 'Dell Latitude 7420', 'status' => Device::STATUS_WRITEOFF, 'room' => 'T1', 'assigned_to' => null],
                ['code' => 'LDZ-0011', 'name' => 'Stacionārais dators G1', 'type' => 'Stacionārais dators', 'model' => 'HP ProDesk 600', 'status' => Device::STATUS_ACTIVE, 'room' => 'T1', 'assigned_to' => null],
                ['code' => 'LDZ-0012', 'name' => 'Printeris H1', 'type' => 'Printeris', 'model' => 'Brother HL-L5100DN', 'status' => Device::STATUS_ACTIVE, 'room' => '301', 'assigned_to' => 'Marta Zvirbule'],
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
                    'serial_number' => 'SN-' . $device['code'],
                    'manufacturer' => str_contains($device['model'], 'Dell')
                        ? 'Dell'
                        : (str_contains($device['model'], 'HP')
                            ? 'HP'
                            : (str_contains($device['model'], 'Cisco') ? 'Cisco' : 'APC')),
                    'notes' => 'Demo ierīce jaunajai shēmai',
                    'device_image_url' => null,
                    'created_by' => $adminUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $deviceBlueprints));

            $deviceIds = DB::table('devices')->pluck('id', 'code')->all();

            DB::table('repairs')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0001'],
                    'issue_reported_by' => $userIdsByName['Ilze Strautiņa'],
                    'accepted_by' => $adminUserId,
                    'description' => 'Klēpjdators vairs neuzlādējas.',
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
                    'issue_reported_by' => $userIdsByName['Dace Rudzīte'],
                    'accepted_by' => $adminUserId,
                    'description' => 'Ierīce ieslēdzas, bet disks rāda kļūdas.',
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
                    'description' => 'Printeris iesprūdina papīru.',
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
            ]);

            $repairIds = DB::table('repairs')->pluck('id', 'device_id')->all();

            DB::table('repair_requests')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0001'],
                    'responsible_user_id' => $userIdsByName['Ilze Strautiņa'],
                    'description' => 'Uzlāde nestrādā un dators izslēdzas.',
                    'status' => RepairRequest::STATUS_APPROVED,
                    'reviewed_by_user_id' => $adminUserId,
                    'repair_id' => $repairIds[$deviceIds['LDZ-0001']] ?? null,
                    'review_notes' => 'Apstiprināts un nodots IT nodaļai.',
                    'created_at' => $now->copy()->subDays(1),
                    'updated_at' => $now,
                ],
                [
                    'device_id' => $deviceIds['LDZ-0005'],
                    'responsible_user_id' => $userIdsByName['Ruta Liepa'],
                    'description' => 'Dators sakarst un strādā lēni.',
                    'status' => RepairRequest::STATUS_SUBMITTED,
                    'reviewed_by_user_id' => null,
                    'repair_id' => null,
                    'review_notes' => null,
                    'created_at' => $now->copy()->subHours(6),
                    'updated_at' => $now->copy()->subHours(6),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0009'],
                    'responsible_user_id' => $userIdsByName['Kristīne Daukste'],
                    'description' => 'Monitors mirgo, bet pēc pārbaudes strādā korekti.',
                    'status' => RepairRequest::STATUS_REJECTED,
                    'reviewed_by_user_id' => $userIdsByName['Linda Kalniņa'],
                    'repair_id' => null,
                    'review_notes' => 'Atkārtotu problēmu neizdevās konstatēt.',
                    'created_at' => $now->copy()->subDays(3),
                    'updated_at' => $now->copy()->subDays(2),
                ],
            ]);

            DB::table('writeoff_requests')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0010'],
                    'responsible_user_id' => $userIdsByName['Edgars Sviklis'],
                    'reason' => 'Ierīce novecojusi un ekonomiski nelietderīga remontam.',
                    'status' => WriteoffRequest::STATUS_APPROVED,
                    'reviewed_by_user_id' => $adminUserId,
                    'review_notes' => 'Norakstīšana apstiprināta.',
                    'created_at' => $now->copy()->subDays(7),
                    'updated_at' => $now->copy()->subDays(6),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0003'],
                    'responsible_user_id' => $userIdsByName['Agnese Leite'],
                    'reason' => 'Nepietiekama veiktspēja ikdienas darbam.',
                    'status' => WriteoffRequest::STATUS_SUBMITTED,
                    'reviewed_by_user_id' => null,
                    'review_notes' => null,
                    'created_at' => $now->copy()->subHours(10),
                    'updated_at' => $now->copy()->subHours(10),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0008'],
                    'responsible_user_id' => $userIdsByName['Māris Vītols'],
                    'reason' => 'Pēc lietotāja domām lēns, bet tehniski darba kārtībā.',
                    'status' => WriteoffRequest::STATUS_REJECTED,
                    'reviewed_by_user_id' => $userIdsByName['Jānis Ozols'],
                    'review_notes' => 'Nomaiņa šobrīd nav pamatota.',
                    'created_at' => $now->copy()->subDays(5),
                    'updated_at' => $now->copy()->subDays(4),
                ],
            ]);

            DB::table('device_transfers')->insert([
                [
                    'device_id' => $deviceIds['LDZ-0002'],
                    'responsible_user_id' => $userIdsByName['Ilze Strautiņa'],
                    'transfered_to_id' => $userIdsByName['Marta Zvirbule'],
                    'transfer_reason' => 'Monitors nepieciešams sekretārei darba vietai.',
                    'status' => DeviceTransfer::STATUS_APPROVED,
                    'reviewed_by_user_id' => $userIdsByName['Marta Zvirbule'],
                    'review_notes' => 'Ierīce pārreģistrēta.',
                    'created_at' => $now->copy()->subDays(2),
                    'updated_at' => $now->copy()->subDay(),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0005'],
                    'responsible_user_id' => $userIdsByName['Ruta Liepa'],
                    'transfered_to_id' => $userIdsByName['Kristīne Daukste'],
                    'transfer_reason' => 'Pagaidu darba vajadzībām projektu komandai.',
                    'status' => DeviceTransfer::STATUS_SUBMITTED,
                    'reviewed_by_user_id' => null,
                    'review_notes' => null,
                    'created_at' => $now->copy()->subHours(5),
                    'updated_at' => $now->copy()->subHours(5),
                ],
                [
                    'device_id' => $deviceIds['LDZ-0007'],
                    'responsible_user_id' => $userIdsByName['Jānis Ozols'],
                    'transfered_to_id' => $userIdsByName['Roberts Arbidāns'],
                    'transfer_reason' => 'Lietotājs pieprasīja UPS juristu kabinetam.',
                    'status' => DeviceTransfer::STATUS_REJECTED,
                    'reviewed_by_user_id' => $userIdsByName['Roberts Arbidāns'],
                    'review_notes' => 'Saņēmējam šobrīd nav vajadzības pēc ierīces.',
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

            DB::table('audit_log')->insert([
                [
                    'timestamp' => $now->copy()->subDays(7),
                    'user_id' => $adminUserId,
                    'action' => 'CREATE',
                    'entity_type' => 'Device',
                    'entity_id' => $deviceIds['LDZ-0001'],
                    'description' => 'Ierīce izveidota: [LDZ-0001] Klēpjdators A1',
                    'severity' => 'info',
                ],
                [
                    'timestamp' => $now->copy()->subDays(6),
                    'user_id' => $adminUserId,
                    'action' => 'CREATE',
                    'entity_type' => 'RepairRequest',
                    'entity_id' => DB::table('repair_requests')->where('device_id', $deviceIds['LDZ-0001'])->value('id'),
                    'description' => 'Remonta pieteikums izveidots: Klēpjdators A1',
                    'severity' => 'info',
                ],
                [
                    'timestamp' => $now->copy()->subDays(6)->addHour(),
                    'user_id' => $adminUserId,
                    'action' => 'UPDATE',
                    'entity_type' => 'RepairRequest',
                    'entity_id' => DB::table('repair_requests')->where('device_id', $deviceIds['LDZ-0001'])->value('id'),
                    'description' => 'Remonta pieteikums atjaunināts: Klēpjdators A1 | detaļas: statuss: Iesniegts -> Apstiprināts',
                    'severity' => 'info',
                ],
                [
                    'timestamp' => $now->copy()->subDays(5),
                    'user_id' => $adminUserId,
                    'action' => 'CREATE',
                    'entity_type' => 'Repair',
                    'entity_id' => $repairIds[$deviceIds['LDZ-0001']] ?? null,
                    'description' => 'Remonts izveidots: Klēpjdators A1',
                    'severity' => 'warning',
                ],
                [
                    'timestamp' => $now->copy()->subDays(4),
                    'user_id' => $userIdsByName['Marta Zvirbule'],
                    'action' => 'LOGIN',
                    'entity_type' => 'User',
                    'entity_id' => $userIdsByName['Marta Zvirbule'],
                    'description' => 'Lietotājs pieslēdzas: Marta Zvirbule',
                    'severity' => 'info',
                ],
                [
                    'timestamp' => $now->copy()->subDays(3),
                    'user_id' => $userIdsByName['Marta Zvirbule'],
                    'action' => 'UPDATE',
                    'entity_type' => 'DeviceTransfer',
                    'entity_id' => DB::table('device_transfers')->where('device_id', $deviceIds['LDZ-0002'])->value('id'),
                    'description' => 'Ierīces pārsūtīšana atjaunināta: Monitors A1 | detaļas: statuss: Iesniegts -> Apstiprināts',
                    'severity' => 'info',
                ],
                [
                    'timestamp' => $now->copy()->subDays(2),
                    'user_id' => $adminUserId,
                    'action' => 'VIEW',
                    'entity_type' => 'AuditLog',
                    'entity_id' => null,
                    'description' => 'Apskatīts audita žurnāls',
                    'severity' => 'info',
                ],
                [
                    'timestamp' => $now->copy()->subDay(),
                    'user_id' => $adminUserId,
                    'action' => 'UPDATE',
                    'entity_type' => 'WriteoffRequest',
                    'entity_id' => DB::table('writeoff_requests')->where('device_id', $deviceIds['LDZ-0010'])->value('id'),
                    'description' => 'Norakstīšanas pieteikums atjaunināts: Klēpjdators F1 | detaļas: statuss: Iesniegts -> Apstiprināts',
                    'severity' => 'warning',
                ],
            ]);
        });
    }
}
