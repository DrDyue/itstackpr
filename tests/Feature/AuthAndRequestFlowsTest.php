<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\AuditLog;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndRequestFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_bootstraps_demo_accounts(): void
    {
        $this->get(route('login'))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'email' => 'artis.berzins@ludzas.lv',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ilze.strautina@ludzas.lv',
        ]);
    }

    public function test_demo_login_creates_or_repairs_demo_admin_account(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'artis.berzins@ludzas.lv',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'artis.berzins@ludzas.lv',
            'role' => User::ROLE_ADMIN,
            'is_active' => 1,
        ]);
    }

    public function test_demo_regular_user_can_log_in_with_example_credentials(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'ilze.strautina@ludzas.lv',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ilze.strautina@ludzas.lv',
            'role' => User::ROLE_USER,
            'is_active' => 1,
        ]);
    }

    public function test_regular_user_cannot_open_admin_user_list(): void
    {
        $user = $this->createUser(role: User::ROLE_USER);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_open_manager_routes(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'manager-blocked@example.com');

        foreach ([
            route('buildings.index'),
            route('rooms.index'),
            route('device-types.index'),
            route('devices.create'),
            route('repairs.create'),
        ] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_regular_user_cannot_open_admin_only_routes(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'admin-blocked@example.com');

        foreach ([
            route('register'),
            route('audit-log.index'),
            route('users.create'),
        ] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_user_can_submit_repair_request_for_own_device(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'user1@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-001');

        $response = $this->actingAs($user)->post(route('repair-requests.store'), [
            'device_id' => $device->id,
            'description' => 'Dators vairs neiesledzas.',
        ]);

        $response->assertRedirect(route('repair-requests.index'));
        $this->assertDatabaseHas('repair_requests', [
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_admin_approving_repair_request_creates_repair_and_updates_device_status(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin1@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'user2@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-002');

        $repairRequestId = DB::table('repair_requests')->insertGetId([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Nepieciesams remonts.',
            'status' => RepairRequest::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('repair-requests.review', $repairRequestId), [
            'status' => RepairRequest::STATUS_APPROVED,
            'review_notes' => 'Apstiprinats.',
            'repair_type' => 'internal',
            'priority' => 'high',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequestId,
            'status' => RepairRequest::STATUS_APPROVED,
            'reviewed_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('repairs', [
            'device_id' => $device->id,
            'issue_reported_by' => $user->id,
            'accepted_by' => $admin->id,
            'request_id' => $repairRequestId,
        ]);
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => Device::STATUS_REPAIR,
        ]);
    }

    public function test_admin_approving_writeoff_request_marks_device_as_writeoff(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin2@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'user3@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-003');

        $writeoffRequestId = DB::table('writeoff_requests')->insertGetId([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Ierice ir novecojusi.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('writeoff-requests.review', $writeoffRequestId), [
            'status' => WriteoffRequest::STATUS_APPROVED,
            'review_notes' => 'Norakstisana apstiprinata.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => Device::STATUS_WRITEOFF,
            'assigned_to_id' => null,
        ]);
    }

    public function test_transfer_recipient_can_approve_and_receive_device(): void
    {
        $sender = $this->createUser(role: User::ROLE_USER, email: 'sender@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'recipient@example.com');
        $device = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-004');

        $transferId = DB::table('device_transfers')->insertGetId([
            'device_id' => $device->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Nododu ierici citam lietotajam.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($recipient)->post(route('device-transfers.review', $transferId), [
            'status' => DeviceTransfer::STATUS_APPROVED,
            'review_notes' => 'Piekritu sanemt ierici.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('device_transfers', [
            'id' => $transferId,
            'status' => DeviceTransfer::STATUS_APPROVED,
            'reviewed_by_user_id' => $recipient->id,
        ]);
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'assigned_to_id' => $recipient->id,
        ]);
    }

    public function test_admin_can_create_transfer_request_for_any_active_assigned_device(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-transfer-create@example.com');
        $sender = $this->createUser(role: User::ROLE_USER, email: 'transfer-owner@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'transfer-recipient@example.com');
        $device = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-ADMIN-TRANSFER');

        $response = $this->actingAs($admin)->post(route('device-transfers.store'), [
            'device_id' => $device->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Admins sakarto ierices parsutisanu.',
        ]);

        $response->assertRedirect(route('device-transfers.index'));
        $this->assertDatabaseHas('device_transfers', [
            'device_id' => $device->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);
    }

    public function test_admin_cannot_review_transfer_if_admin_is_not_recipient(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-transfer-review@example.com');
        $sender = $this->createUser(role: User::ROLE_USER, email: 'transfer-sender@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'transfer-target@example.com');
        $device = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-005');

        $transferId = DB::table('device_transfers')->insertGetId([
            'device_id' => $device->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Parsutit citam lietotajam.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('device-transfers.review', $transferId), [
                'status' => DeviceTransfer::STATUS_APPROVED,
                'review_notes' => 'Admins nevar apstiprinat svesu parsutisanu.',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_see_full_request_history_on_all_request_pages(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-history@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'history-user@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'history-recipient@example.com');

        $repairDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-006');
        $writeoffDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-007');
        $transferDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-008');

        DB::table('repair_requests')->insert([
            'device_id' => $repairDevice->id,
            'responsible_user_id' => $user->id,
            'description' => 'Remonta vesture adminam.',
            'status' => RepairRequest::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('writeoff_requests')->insert([
            'device_id' => $writeoffDevice->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Norakstisanas vesture adminam.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('device_transfers')->insert([
            'device_id' => $transferDevice->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Parsutisanas vesture adminam.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('repair-requests.index'))
            ->assertOk()
            ->assertSee('Remonta vesture adminam.');

        $this->actingAs($admin)
            ->get(route('writeoff-requests.index'))
            ->assertOk()
            ->assertSee('Norakstisanas vesture adminam.');

        $this->actingAs($admin)
            ->get(route('device-transfers.index'))
            ->assertOk()
            ->assertSee('Parsutisanas vesture adminam.');
    }

    public function test_missing_writeoff_requests_table_is_bootstrapped_on_demand(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-writeoff@example.com');

        Schema::dropIfExists('writeoff_requests');

        $this->actingAs($admin)
            ->get(route('writeoff-requests.index'))
            ->assertOk();

        $this->assertTrue(Schema::hasTable('writeoff_requests'));
    }

    public function test_missing_device_transfers_table_is_bootstrapped_on_demand(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-transfer@example.com');

        Schema::dropIfExists('device_transfers');

        $this->actingAs($admin)
            ->get(route('device-transfers.index'))
            ->assertOk();

        $this->assertTrue(Schema::hasTable('device_transfers'));
    }

    public function test_missing_repair_requests_table_is_bootstrapped_on_demand(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-repair-request@example.com');

        Schema::dropIfExists('repair_requests');

        $this->actingAs($admin)
            ->get(route('repair-requests.index'))
            ->assertOk();

        $this->assertTrue(Schema::hasTable('repair_requests'));
    }

    public function test_missing_audit_log_table_does_not_break_dashboard(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-dashboard@example.com');

        Schema::dropIfExists('audit_log');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertTrue(Schema::hasTable('audit_log'));
    }

    public function test_regular_user_cannot_view_foreign_device_asset(): void
    {
        Storage::fake('public');

        $owner = $this->createUser(role: User::ROLE_USER, email: 'asset-owner@example.com');
        $intruder = $this->createUser(role: User::ROLE_USER, email: 'asset-intruder@example.com');
        $device = $this->createDevice($owner->id, Device::STATUS_ACTIVE, 'DEV-ASSET');
        $path = 'devices/images/foreign-device-image.jpg';

        Storage::disk('public')->put($path, 'fake-image-contents');
        $device->update(['device_image_url' => $path]);

        $this->actingAs($intruder)
            ->get(route('device-assets.show', ['path' => $path]))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('device-assets.show', ['path' => $path]))
            ->assertOk();
    }

    public function test_regular_user_cannot_preview_remote_asset_for_foreign_device(): void
    {
        $owner = $this->createUser(role: User::ROLE_USER, email: 'remote-owner@example.com');
        $intruder = $this->createUser(role: User::ROLE_USER, email: 'remote-intruder@example.com');
        $device = $this->createDevice($owner->id, Device::STATUS_ACTIVE, 'DEV-REMOTE');

        $device->update([
            'device_image_url' => 'https://example.com/private-device-image.png',
        ]);

        $this->actingAs($intruder)
            ->get(route('device-assets.remote-preview', ['url' => 'https://example.com/private-device-image.png']))
            ->assertNotFound();
    }

    public function test_regular_user_dashboard_shows_only_own_activity_and_scope(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'dashboard-own@example.com');
        $otherUser = $this->createUser(role: User::ROLE_ADMIN, email: 'dashboard-other@example.com');

        $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-DASH-OWN');
        $this->createDevice($otherUser->id, Device::STATUS_ACTIVE, 'DEV-DASH-OTHER');

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => $user->id,
            'action' => 'VIEW',
            'entity_type' => 'device',
            'entity_id' => 1,
            'description' => 'Mana personiga darbiba',
            'severity' => 'info',
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => $otherUser->id,
            'action' => 'DELETE',
            'entity_type' => 'device',
            'entity_id' => 2,
            'description' => 'Svesa admina darbiba',
            'severity' => 'warning',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mana personiga darbiba')
            ->assertDontSee('Svesa admina darbiba')
            ->assertSee('No 1 telpam');
    }

    private function createUser(string $role, ?string $email = null): User
    {
        return User::create([
            'full_name' => $role === User::ROLE_ADMIN ? 'Admin User' : 'Regular User',
            'email' => $email ?? fake()->unique()->safeEmail(),
            'password' => Hash::make('secret123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createDevice(int $assignedToId, string $status, string $code): Device
    {
        $buildingId = DB::table('buildings')->insertGetId([
            'building_name' => 'Testa eka ' . $code,
            'address' => 'Adrese 1',
            'city' => 'Ludza',
            'total_floors' => 3,
            'notes' => null,
            'created_at' => now(),
        ]);

        $roomId = DB::table('rooms')->insertGetId([
            'building_id' => $buildingId,
            'floor_number' => 1,
            'room_number' => '101-' . $code,
            'room_name' => 'Testa telpa',
            'user_id' => $assignedToId,
            'department' => 'IT',
            'notes' => null,
            'created_at' => now(),
        ]);

        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Klepjdators ' . $code,
            'category' => 'Datori',
            'description' => 'Tests',
            'created_at' => now(),
        ]);

        return Device::create([
            'code' => $code,
            'name' => 'Testa ierice ' . $code,
            'device_type_id' => $typeId,
            'model' => 'Modelis ' . $code,
            'status' => $status,
            'building_id' => $buildingId,
            'room_id' => $roomId,
            'assigned_to_id' => $assignedToId,
            'created_by' => $assignedToId,
        ]);
    }
}
