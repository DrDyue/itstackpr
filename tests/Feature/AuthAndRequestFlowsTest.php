<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRequestFlowsTest extends TestCase
{
    use RefreshDatabase;

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
