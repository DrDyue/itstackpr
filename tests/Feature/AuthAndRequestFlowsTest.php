<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\Repair;
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

        $response->assertRedirect(route('devices.index', absolute: false));
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

    public function test_admin_can_filter_users_by_multiple_roles(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'multi-role-admin@example.com');
        $otherAdmin = $this->createUser(role: User::ROLE_ADMIN, email: 'second-admin@example.com');
        $employee = $this->createUser(role: User::ROLE_USER, email: 'employee-filter@example.com');

        $this->actingAs($admin)
            ->get(route('users.index', ['role' => [User::ROLE_ADMIN]]))
            ->assertOk()
            ->assertSee($otherAdmin->email)
            ->assertDontSee($employee->email);
    }

    public function test_admin_can_filter_devices_by_assigned_user_from_user_list_shortcut(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'user-device-shortcut-admin@example.com');
        $firstUser = $this->createUser(role: User::ROLE_USER, email: 'shortcut-first@example.com');
        $secondUser = $this->createUser(role: User::ROLE_USER, email: 'shortcut-second@example.com');

        $firstDevice = $this->createDevice($firstUser->id, Device::STATUS_ACTIVE, 'DEV-USER-SHORTCUT-1');
        $secondDevice = $this->createDevice($secondUser->id, Device::STATUS_ACTIVE, 'DEV-USER-SHORTCUT-2');

        $this->actingAs($admin)
            ->get(route('devices.index', [
                'assigned_to_id' => $firstUser->id,
                'assigned_to_query' => $firstUser->full_name,
            ]))
            ->assertOk()
            ->assertSee($firstDevice->name)
            ->assertDontSee($secondDevice->name);
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

    public function test_regular_user_cannot_switch_view_mode(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'view-mode-regular@example.com');

        $this->actingAs($user)
            ->post(route('view-mode.update'), [
                'mode' => User::VIEW_MODE_ADMIN,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_switch_to_user_view_and_back(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'view-mode-admin@example.com');
        $otherUser = $this->createUser(role: User::ROLE_USER, email: 'view-mode-other@example.com');
        $ownDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-VIEW-OWN');
        $foreignDevice = $this->createDevice($otherUser->id, Device::STATUS_ACTIVE, 'DEV-VIEW-FOREIGN');

        $this->actingAs($admin)
            ->post(route('view-mode.update'), [
                'mode' => User::VIEW_MODE_USER,
            ])
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas(User::VIEW_MODE_SESSION_KEY, User::VIEW_MODE_USER);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('devices.index'));

        $this->actingAs($admin)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('Ierices')
            ->assertDontSee('Darbvirsma')
            ->assertSee($ownDevice->name)
            ->assertDontSee($foreignDevice->name);

        $this->actingAs($admin)
            ->get(route('my-requests.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('view-mode.update'), [
                'mode' => User::VIEW_MODE_ADMIN,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(User::VIEW_MODE_SESSION_KEY, User::VIEW_MODE_ADMIN);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_admin_cannot_open_or_submit_user_only_request_forms(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'admin-user-only-requests@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-ADMIN-USER-ONLY');

        foreach ([
            route('repair-requests.create'),
            route('writeoff-requests.create'),
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertForbidden();
        }

        $this->actingAs($admin)
            ->post(route('repair-requests.store'), [
                'device_id' => $device->id,
                'description' => 'Adminam nevajadzetu pieteikt remontu caur lietotaja formu.',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('writeoff-requests.store'), [
                'device_id' => $device->id,
                'reason' => 'Adminam nevajadzetu pieteikt norakstisanu caur lietotaja formu.',
            ])
            ->assertForbidden();
    }

    public function test_device_create_form_preselects_current_admin_and_default_warehouse_room(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-create-defaults@example.com');

        $response = $this->actingAs($admin)
            ->get(route('devices.create'))
            ->assertOk();

        $warehouseRoomId = DB::table('rooms')
            ->where('room_name', 'Noliktava')
            ->value('id');

        $this->assertNotNull($warehouseRoomId);
        $this->assertDatabaseHas('buildings', [
            'id' => DB::table('rooms')->where('id', $warehouseRoomId)->value('building_id'),
        ]);

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('device-type-form-select', $content);
        $this->assertStringContainsString('name="status" value="active"', $content);
        $this->assertMatchesRegularExpression('/<option value="'.preg_quote((string) $admin->id, '/').'"[^>]*selected/', $content);
        $this->assertMatchesRegularExpression('/<option value="'.preg_quote((string) $warehouseRoomId, '/').'"[^>]*selected/', $content);
    }

    public function test_device_create_form_restores_missing_room_and_building_updated_at_columns(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-create-missing-updated-at@example.com');

        if (Schema::hasColumn('rooms', 'updated_at')) {
            Schema::table('rooms', function ($table) {
                $table->dropColumn('updated_at');
            });
        }

        if (Schema::hasColumn('buildings', 'updated_at')) {
            Schema::table('buildings', function ($table) {
                $table->dropColumn('updated_at');
            });
        }

        if (Schema::hasColumn('device_types', 'updated_at')) {
            Schema::table('device_types', function ($table) {
                $table->dropColumn('updated_at');
            });
        }

        $this->actingAs($admin)
            ->get(route('devices.create'))
            ->assertOk();

        $this->assertTrue(Schema::hasColumn('rooms', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('buildings', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('device_types', 'updated_at'));
        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Noliktava',
        ]);
    }

    public function test_admin_can_create_device_without_explicit_assignee_or_room_and_defaults_are_applied(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-store-defaults@example.com');
        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Dators',
            'category' => 'Darba vieta',
            'description' => 'Tests',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('devices.store'), [
                'code' => 'DEV-STORE-DEFAULTS',
                'name' => 'Noklusetais dators',
                'device_type_id' => $typeId,
                'model' => 'OptiPlex 7000',
                'status' => Device::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('devices.index'));

        $device = Device::query()->where('code', 'DEV-STORE-DEFAULTS')->first();

        $this->assertNotNull($device);
        $this->assertSame($admin->id, $device->assigned_to_id);
        $this->assertNotNull($device->room_id);
        $this->assertNotNull($device->building_id);
        $this->assertSame(
            'Noliktava',
            DB::table('rooms')->where('id', $device->room_id)->value('room_name')
        );
    }

    public function test_new_device_creation_always_forces_active_status(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-store-force-active@example.com');
        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Plansete',
            'category' => 'Mobilas ierices',
            'description' => 'Tests',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('devices.store'), [
                'code' => 'DEV-FORCE-ACT',
                'name' => 'Vienmer aktiva ierice',
                'device_type_id' => $typeId,
                'model' => 'Tab 11',
                'status' => Device::STATUS_REPAIR,
            ])
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', [
            'code' => 'DEV-FORCE-ACT',
            'status' => Device::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_create_with_blank_assignee_and_room_creates_warehouse_and_assigns_creator(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-store-empty-defaults@example.com');
        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Monitors',
            'category' => 'Darba vieta',
            'description' => 'Tests',
            'created_at' => now(),
        ]);

        $this->assertDatabaseMissing('rooms', [
            'room_name' => 'Noliktava',
        ]);

        $this->assertDatabaseMissing('buildings', [
            'building_name' => 'Ludzes novada pasvaldiba',
        ]);

        $this->actingAs($admin)
            ->post(route('devices.store'), [
                'code' => 'DEV-BLNK-DFLT',
                'name' => 'Automatiski pieskirtais monitors',
                'device_type_id' => $typeId,
                'model' => 'Dell P2723',
                'status' => Device::STATUS_ACTIVE,
                'assigned_to_id' => '',
                'building_id' => '',
                'room_id' => '',
            ])
            ->assertRedirect(route('devices.index'));

        $device = Device::query()->where('code', 'DEV-BLNK-DFLT')->first();

        $this->assertNotNull($device);
        $this->assertSame($admin->id, $device->assigned_to_id);
        $this->assertNotNull($device->room_id);
        $this->assertNotNull($device->building_id);

        $this->assertDatabaseHas('rooms', [
            'id' => $device->room_id,
            'room_name' => 'Noliktava',
        ]);

        $this->assertDatabaseHas('buildings', [
            'id' => $device->building_id,
            'building_name' => 'Ludzes novada pasvaldiba',
        ]);
    }

    public function test_runtime_bootstrap_backfills_legacy_active_device_without_room(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'legacy-device-backfill-admin@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-LEGACY-RM');

        DB::table('devices')
            ->where('id', $device->id)
            ->update([
                'assigned_to_id' => null,
                'building_id' => null,
                'room_id' => null,
            ]);

        $this->assertDatabaseMissing('rooms', [
            'room_name' => 'Noliktava',
        ]);

        $this->actingAs($admin)
            ->get(route('devices.index'))
            ->assertOk();

        $device->refresh();

        $this->assertSame($admin->id, $device->assigned_to_id);
        $this->assertNotNull($device->room_id);
        $this->assertNotNull($device->building_id);
        $this->assertSame(
            'Noliktava',
            DB::table('rooms')->where('id', $device->room_id)->value('room_name')
        );
    }

    public function test_runtime_bootstrap_backfills_legacy_active_device_without_assigned_person(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'legacy-device-owner-backfill-admin@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-LEGACY-OWN');
        $originalRoomId = $device->room_id;
        $originalBuildingId = $device->building_id;

        DB::table('devices')
            ->where('id', $device->id)
            ->update([
                'assigned_to_id' => null,
            ]);

        $this->actingAs($admin)
            ->get(route('devices.index'))
            ->assertOk();

        $device->refresh();

        $this->assertSame($admin->id, $device->assigned_to_id);
        $this->assertSame($originalRoomId, $device->room_id);
        $this->assertSame($originalBuildingId, $device->building_id);
    }

    public function test_manager_can_send_device_to_repair_from_devices_table_action(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-repair-action@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-QUICK-REPAIR');

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'status',
                'target_status' => Device::STATUS_REPAIR,
            ])
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => Device::STATUS_REPAIR,
        ]);
        $this->assertDatabaseHas('repairs', [
            'device_id' => $device->id,
            'status' => 'waiting',
            'accepted_by' => $admin->id,
            'request_id' => null,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function test_manager_can_writeoff_device_from_devices_table_action(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-writeoff-action@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-QUICK-WRITEOFF');

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'status',
                'target_status' => Device::STATUS_WRITEOFF,
            ])
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => Device::STATUS_WRITEOFF,
            'assigned_to_id' => null,
        ]);
        $this->assertSame('Noliktava', DB::table('rooms')->where('id', $device->fresh()->room_id)->value('room_name'));
    }

    public function test_get_quick_update_route_redirects_instead_of_throwing_method_not_allowed(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'quick-update-get@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-QUICK-GET');

        $this->actingAs($admin)
            ->get(route('devices.quick-update.redirect', $device))
            ->assertRedirect(route('devices.show', $device));
    }

    public function test_written_off_device_does_not_show_in_regular_user_inventory_even_if_legacy_assignment_remains(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'writtenoff-hidden@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_WRITEOFF, 'DEV-HIDDEN-WRITEOFF');

        DB::table('devices')
            ->where('id', $device->id)
            ->update([
                'assigned_to_id' => $user->id,
            ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('Ierices nav atrastas.')
            ->assertDontSee(route('devices.show', $device), false);
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
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'start_date' => null,
            'end_date' => null,
        ]);
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => Device::STATUS_REPAIR,
        ]);
    }

    public function test_repair_transition_to_in_progress_sets_start_date_automatically(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-start-date@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-REPAIR-START');

        $repair = Repair::create([
            'device_id' => $device->id,
            'description' => 'Gaida remonta uzsaksanu.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('repairs.transition', $repair), [
                'target_status' => 'in-progress',
            ])
            ->assertRedirect();

        $repair->refresh();
        $this->assertSame('in-progress', $repair->status);
        $this->assertSame(now()->toDateString(), $repair->start_date?->toDateString());
        $this->assertNull($repair->end_date);
    }

    public function test_repair_transition_back_to_waiting_clears_dates(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-back-waiting@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-REPAIR-BACK');

        $repair = Repair::create([
            'device_id' => $device->id,
            'description' => 'Atgriezt uz gaidisanu.',
            'status' => 'in-progress',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->post(route('repairs.transition', $repair), [
                'target_status' => 'waiting',
            ])
            ->assertRedirect();

        $repair->refresh();
        $this->assertSame('waiting', $repair->status);
        $this->assertNull($repair->start_date);
        $this->assertNull($repair->end_date);
    }

    public function test_completed_repair_can_be_returned_to_in_progress_and_end_date_is_cleared(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-reopen@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-REPAIR-REOPEN');

        $repair = Repair::create([
            'device_id' => $device->id,
            'description' => 'Atgriezt atpakal procesa.',
            'status' => 'completed',
            'repair_type' => 'external',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->post(route('repairs.transition', $repair), [
                'target_status' => 'in-progress',
            ])
            ->assertRedirect();

        $repair->refresh();
        $this->assertSame('in-progress', $repair->status);
        $this->assertNull($repair->end_date);
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
        $this->assertSame('Noliktava', DB::table('rooms')->where('id', $device->fresh()->room_id)->value('room_name'));
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

    public function test_dashboard_device_table_shows_serial_number_job_title_and_device_metadata(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'dashboard-metadata-admin@example.com');
        $assignee = $this->createUser(role: User::ROLE_USER, email: 'dashboard-metadata-user@example.com');
        $device = $this->createDevice($assignee->id, Device::STATUS_ACTIVE, 'DEV-DASH-META');

        DB::table('users')->where('id', $assignee->id)->update([
            'full_name' => 'Maris Vitols',
            'job_title' => null,
        ]);

        DB::table('device_types')->where('id', $device->device_type_id)->update([
            'type_name' => 'Dators',
        ]);

        $device->update([
            'name' => 'Darba stacija',
            'manufacturer' => 'HP',
            'model' => 'EliteDesk 800',
            'serial_number' => 'SN-HP-800',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression('/DEV-DASH-META.*SN-HP-800/s', $content);
        $this->assertMatchesRegularExpression('/Darba stacija.*Dators.*HP EliteDesk 800/s', $content);
        $this->assertMatchesRegularExpression('/Maris Vitols.*Nav amata/s', $content);
        $this->assertStringNotContainsString('Aktivie remonti', $content);
        $this->assertStringNotContainsString('Jaunakas darbibas', $content);
    }

    public function test_dashboard_uses_localized_device_pagination(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'dashboard-pagination-admin@example.com');

        foreach (range(1, 13) as $index) {
            $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-PAG-'.$index);
        }

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Lapa 1 no 2', $content);
        $this->assertStringContainsString('Nakama', $content);
        $this->assertStringNotContainsString('Showing', $content);
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

    public function test_regular_user_dashboard_redirects_to_own_devices_scope(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'dashboard-own@example.com');
        $otherUser = $this->createUser(role: User::ROLE_ADMIN, email: 'dashboard-other@example.com');

        $ownDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-DASH-OWN');
        $foreignDevice = $this->createDevice($otherUser->id, Device::STATUS_ACTIVE, 'DEV-DASH-OTHER');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('devices.index'));

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee($ownDevice->name)
            ->assertDontSee($foreignDevice->name)
            ->assertDontSee('Darbvirsma');
    }

    public function test_regular_user_can_open_unified_request_center_and_see_related_request_types(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-center-user@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'request-center-recipient@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQUEST-CENTER');

        RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Jaiet uz vienoto centru.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Ar norakstisanu saistits ieraksts.',
            'status' => WriteoffRequest::STATUS_REJECTED,
        ]);

        DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Nodosanas ieraksts vienotajam sarakstam.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->get(route('my-requests.index'))
            ->assertOk()
            ->assertSee('Mani pieteikumi')
            ->assertSee('Jaiet uz vienoto centru.')
            ->assertSee('Ar norakstisanu saistits ieraksts.')
            ->assertSee('Nodosanas ieraksts vienotajam sarakstam.');
    }

    public function test_regular_user_can_create_transfer_request_from_unified_form(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'unified-create-user@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'unified-create-recipient@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-UNIFIED-CREATE');

        $this->actingAs($user)
            ->post(route('my-requests.store'), [
                'request_type' => 'transfer',
                'device_id' => $device->id,
                'transfered_to_id' => $recipient->id,
                'transfer_reason' => 'Vienota forma nodosanai.',
            ])
            ->assertRedirect(route('my-requests.index'));

        $this->assertDatabaseHas('device_transfers', [
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Vienota forma nodosanai.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);
    }

    public function test_admin_request_indexes_show_all_statuses_by_default(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'request-defaults-admin@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-defaults-user@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'request-defaults-recipient@example.com');
        $repairDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-DEFAULT-REPAIR');
        $writeoffDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-DEFAULT-WRITEOFF');
        $transferDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-DEFAULT-TRANSFER');

        RepairRequest::create([
            'device_id' => $repairDevice->id,
            'responsible_user_id' => $user->id,
            'description' => 'Remonts iesniegts pec noklusejuma.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);
        RepairRequest::create([
            'device_id' => $repairDevice->id,
            'responsible_user_id' => $user->id,
            'description' => 'Remonts apstiprinats pec noklusejuma.',
            'status' => RepairRequest::STATUS_APPROVED,
        ]);
        RepairRequest::create([
            'device_id' => $repairDevice->id,
            'responsible_user_id' => $user->id,
            'description' => 'Remonts noraidits pec noklusejuma.',
            'status' => RepairRequest::STATUS_REJECTED,
        ]);

        WriteoffRequest::create([
            'device_id' => $writeoffDevice->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Norakstisana iesniegta pec noklusejuma.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);
        WriteoffRequest::create([
            'device_id' => $writeoffDevice->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Norakstisana apstiprinata pec noklusejuma.',
            'status' => WriteoffRequest::STATUS_APPROVED,
        ]);
        WriteoffRequest::create([
            'device_id' => $writeoffDevice->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Norakstisana noraidita pec noklusejuma.',
            'status' => WriteoffRequest::STATUS_REJECTED,
        ]);

        DeviceTransfer::create([
            'device_id' => $transferDevice->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Nodosana iesniegta pec noklusejuma.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);
        DeviceTransfer::create([
            'device_id' => $transferDevice->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Nodosana apstiprinata pec noklusejuma.',
            'status' => DeviceTransfer::STATUS_APPROVED,
        ]);
        DeviceTransfer::create([
            'device_id' => $transferDevice->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Nodosana noraidita pec noklusejuma.',
            'status' => DeviceTransfer::STATUS_REJECTED,
        ]);

        $this->actingAs($admin)
            ->get(route('repair-requests.index'))
            ->assertOk()
            ->assertSee('Remonts iesniegts pec noklusejuma.')
            ->assertSee('Remonts apstiprinats pec noklusejuma.')
            ->assertSee('Remonts noraidits pec noklusejuma.');

        $this->actingAs($admin)
            ->get(route('writeoff-requests.index'))
            ->assertOk()
            ->assertSee('Norakstisana iesniegta pec noklusejuma.')
            ->assertSee('Norakstisana apstiprinata pec noklusejuma.')
            ->assertSee('Norakstisana noraidita pec noklusejuma.');

        $this->actingAs($admin)
            ->get(route('device-transfers.index'))
            ->assertOk()
            ->assertSee('Nodosana iesniegta pec noklusejuma.')
            ->assertSee('Nodosana apstiprinata pec noklusejuma.')
            ->assertSee('Nodosana noraidita pec noklusejuma.');
    }

    public function test_regular_user_can_edit_submitted_repair_request_text_from_unified_center(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-edit-repair-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-EDIT-REPAIR');
        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Sakotnejs remonta apraksts.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->get(route('my-requests.edit', ['requestType' => 'repair', 'requestId' => $repairRequest->id]))
            ->assertOk()
            ->assertSee('Labot remonta pieteikumu');

        $this->actingAs($user)
            ->patch(route('my-requests.update', ['requestType' => 'repair', 'requestId' => $repairRequest->id]), [
                'description' => 'Atjaunots remonta apraksts.',
                'device_id' => 999999,
            ])
            ->assertRedirect(route('my-requests.index'));

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'device_id' => $device->id,
            'description' => 'Atjaunots remonta apraksts.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_regular_user_can_edit_submitted_transfer_reason_without_changing_recipient(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-edit-transfer-user@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'request-edit-transfer-recipient@example.com');
        $otherRecipient = $this->createUser(role: User::ROLE_USER, email: 'request-edit-transfer-other@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-EDIT-TRANSFER');
        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Sakotnejs nodosanas iemesls.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->patch(route('my-requests.update', ['requestType' => 'transfer', 'requestId' => $transfer->id]), [
                'transfer_reason' => 'Atjaunots nodosanas iemesls.',
                'transfered_to_id' => $otherRecipient->id,
            ])
            ->assertRedirect(route('my-requests.index'));

        $this->assertDatabaseHas('device_transfers', [
            'id' => $transfer->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Atjaunots nodosanas iemesls.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);
    }

    public function test_regular_user_can_cancel_submitted_writeoff_request_from_unified_center(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-delete-writeoff-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-DELETE-WRITEOFF');
        $writeoffRequest = WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Atcelams norakstisanas pieteikums.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->delete(route('my-requests.destroy', ['requestType' => 'writeoff', 'requestId' => $writeoffRequest->id]))
            ->assertRedirect(route('my-requests.index'));

        $this->assertDatabaseMissing('writeoff_requests', [
            'id' => $writeoffRequest->id,
        ]);
    }

    public function test_regular_user_cannot_edit_or_cancel_reviewed_request_from_unified_center(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-edit-reviewed-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-EDIT-REVIEWED');
        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Jau izskatits remonta pieteikums.',
            'status' => RepairRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($user)
            ->get(route('my-requests.edit', ['requestType' => 'repair', 'requestId' => $repairRequest->id]))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('my-requests.update', ['requestType' => 'repair', 'requestId' => $repairRequest->id]), [
                'description' => 'Nevajadzetu saglabaties.',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('my-requests.destroy', ['requestType' => 'repair', 'requestId' => $repairRequest->id]))
            ->assertForbidden();
    }

    public function test_transfer_recipient_can_approve_device_and_change_room(): void
    {
        $sender = $this->createUser(role: User::ROLE_USER, email: 'transfer-room-sender@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'transfer-room-recipient@example.com');
        $device = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-TRANSFER-ROOM');

        $newBuildingId = DB::table('buildings')->insertGetId([
            'building_name' => 'Jauna eka',
            'address' => 'Adrese 2',
            'city' => 'Ludza',
            'total_floors' => 2,
            'notes' => null,
            'created_at' => now(),
        ]);

        $newRoomId = DB::table('rooms')->insertGetId([
            'building_id' => $newBuildingId,
            'floor_number' => 2,
            'room_number' => '205-NEW',
            'room_name' => 'Jauna telpa',
            'user_id' => $recipient->id,
            'department' => 'IT',
            'notes' => null,
            'created_at' => now(),
        ]);

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Pec apstiprinasanas mainit telpu.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($recipient)
            ->post(route('device-transfers.review', $transfer), [
                'status' => DeviceTransfer::STATUS_APPROVED,
                'room_id' => $newRoomId,
                'keep_current_room' => 0,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'assigned_to_id' => $recipient->id,
            'room_id' => $newRoomId,
            'building_id' => $newBuildingId,
        ]);
    }

    public function test_regular_user_can_update_room_for_owned_device(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-room-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-USER-ROOM');

        $newBuildingId = DB::table('buildings')->insertGetId([
            'building_name' => 'Papildus eka',
            'address' => 'Adrese 3',
            'city' => 'Ludza',
            'total_floors' => 1,
            'notes' => null,
            'created_at' => now(),
        ]);

        $newRoomId = DB::table('rooms')->insertGetId([
            'building_id' => $newBuildingId,
            'floor_number' => 1,
            'room_number' => '110-USER',
            'room_name' => 'Lietotaja telpa',
            'user_id' => $user->id,
            'department' => 'IT',
            'notes' => null,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('devices.user-room.update', $device), [
                'room_id' => $newRoomId,
            ])
            ->assertRedirect(route('devices.show', $device));

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'room_id' => $newRoomId,
            'building_id' => $newBuildingId,
        ]);
    }

    public function test_user_cannot_create_writeoff_request_when_pending_repair_request_exists(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'blocked-writeoff@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-BLOCK-WRITEOFF');

        RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Gaida remonta izskatisanu.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->from(route('my-requests.create'))
            ->post(route('my-requests.store'), [
                'request_type' => 'writeoff',
                'device_id' => $device->id,
                'reason' => 'Nevajadzetu laut izveidot.',
            ])
            ->assertSessionHasErrors('device_id');
    }

    public function test_user_cannot_create_transfer_request_when_pending_writeoff_request_exists(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'blocked-transfer@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'blocked-transfer-recipient@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-BLOCK-TRANSFER');

        WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Gaida norakstisanas izskatisanu.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->from(route('my-requests.create'))
            ->post(route('my-requests.store'), [
                'request_type' => 'transfer',
                'device_id' => $device->id,
                'transfered_to_id' => $recipient->id,
                'transfer_reason' => 'Nevajadzetu laut nodot.',
            ])
            ->assertSessionHasErrors('device_id');
    }

    public function test_user_cannot_create_repair_request_when_pending_transfer_request_exists(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'blocked-repair@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'blocked-repair-recipient@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-BLOCK-REPAIR');

        DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Gaida nodosanas izskatisanu.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->post(route('repair-requests.store'), [
                'device_id' => $device->id,
                'description' => 'Nevajadzetu laut pieteikt remontu.',
            ])
            ->assertSessionHasErrors('device_id');
    }

    public function test_user_cannot_create_any_request_for_device_in_repair_status(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'repair-state-blocked@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'repair-state-target@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_REPAIR, 'DEV-IN-REPAIR');

        Repair::create([
            'device_id' => $device->id,
            'description' => 'Ierice jau ir remonta.',
            'status' => 'in-progress',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('my-requests.store'), [
                'request_type' => 'repair',
                'device_id' => $device->id,
                'description' => 'Vel viens remonts.',
            ])
            ->assertSessionHasErrors('device_id');

        $this->actingAs($user)
            ->post(route('my-requests.store'), [
                'request_type' => 'writeoff',
                'device_id' => $device->id,
                'reason' => 'Nevajadzetu laut norakstit.',
            ])
            ->assertSessionHasErrors('device_id');

        $this->actingAs($user)
            ->post(route('my-requests.store'), [
                'request_type' => 'transfer',
                'device_id' => $device->id,
                'transfered_to_id' => $recipient->id,
                'transfer_reason' => 'Nevajadzetu laut nodot.',
            ])
            ->assertSessionHasErrors('device_id');
    }

    public function test_dashboard_accepts_floor_and_room_filters_without_leaving_page(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-filter-admin@example.com');
        $firstDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-FLOOR-ONE');
        $secondDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-FLOOR-TWO');

        DB::table('rooms')->where('id', $secondDevice->room_id)->update([
            'floor_number' => 2,
            'room_number' => '201-DEV-FLOOR-TWO',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['floor' => 1, 'room_id' => $firstDevice->room_id]))
            ->assertOk()
            ->assertSee('DEV-FLOOR-ONE')
            ->assertSee('Testa ierice DEV-FLOOR-ONE')
            ->assertDontSee('Testa ierice DEV-FLOOR-TWO')
            ->assertSee('Telpas filtrs ieslegts');
    }

    public function test_devices_index_accepts_type_text_filter_without_exact_selection(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-type-filter-admin@example.com');
        $firstDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-TYPE-ONE');
        $secondDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-TYPE-TWO');

        DB::table('device_types')->where('id', $firstDevice->device_type_id)->update([
            'type_name' => 'Komutators Alpha',
        ]);

        DB::table('device_types')->where('id', $secondDevice->device_type_id)->update([
            'type_name' => 'Marsrutetajs Beta',
        ]);

        $this->actingAs($admin)
            ->get(route('devices.index', ['type_query' => 'Komut']))
            ->assertOk()
            ->assertSee('Testa ierice DEV-TYPE-ONE')
            ->assertDontSee('Testa ierice DEV-TYPE-TWO');
    }

    public function test_active_device_update_requires_assignee_and_room(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-update-required@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-UPDATE-REQ');

        $this->actingAs($admin)
            ->from(route('devices.edit', $device))
            ->put(route('devices.update', $device), [
                'code' => $device->code,
                'name' => $device->name,
                'device_type_id' => $device->device_type_id,
                'model' => $device->model,
                'status' => Device::STATUS_ACTIVE,
                'building_id' => $device->building_id,
                'room_id' => '',
                'assigned_to_id' => '',
            ])
            ->assertRedirect(route('devices.edit', $device))
            ->assertSessionHasErrors(['assigned_to_id', 'room_id']);
    }

    public function test_devices_index_can_filter_by_multiple_statuses(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-status-filter-admin@example.com');
        $activeDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-STATUS-ACTIVE');
        $repairDevice = $this->createDevice($admin->id, Device::STATUS_REPAIR, 'DEV-STATUS-REPAIR');
        $writeoffDevice = $this->createDevice($admin->id, Device::STATUS_WRITEOFF, 'DEV-STATUS-WRITEOFF');

        $this->actingAs($admin)
            ->get(route('devices.index', ['status' => [Device::STATUS_ACTIVE, Device::STATUS_REPAIR]]))
            ->assertOk()
            ->assertSee($activeDevice->name)
            ->assertSee($repairDevice->name)
            ->assertDontSee($writeoffDevice->name);
    }

    public function test_repairs_index_shows_related_request_author_problem_and_approver(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-board-admin@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'repair-board-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_REPAIR, 'DEV-REPAIR-BOARD');

        $requestId = DB::table('repair_requests')->insertGetId([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Ekrans mirgo un dators izsledzas.',
            'status' => RepairRequest::STATUS_APPROVED,
            'reviewed_by_user_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Repair::create([
            'device_id' => $device->id,
            'description' => 'Veikt diagnostiku un remontu.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'issue_reported_by' => $user->id,
            'accepted_by' => $admin->id,
            'request_id' => $requestId,
        ]);

        $this->actingAs($admin)
            ->get(route('repairs.index'))
            ->assertOk()
            ->assertSee('Saistitais remonta pieteikums #'.$requestId)
            ->assertSee($user->full_name)
            ->assertSee('Ekrans mirgo un dators izsledzas.')
            ->assertSee($admin->full_name);
    }

    public function test_repairs_index_can_filter_only_repairs_assigned_to_current_admin(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-filter-admin-a@example.com');
        $otherAdmin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-filter-admin-b@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'repair-filter-user@example.com');

        $myDevice = $this->createDevice($user->id, Device::STATUS_REPAIR, 'DEV-MY-REPAIR');
        $otherDevice = $this->createDevice($user->id, Device::STATUS_REPAIR, 'DEV-OTHER-REPAIR');

        Repair::create([
            'device_id' => $myDevice->id,
            'description' => 'Remonts pieskirts aktivajam adminam.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
        ]);

        Repair::create([
            'device_id' => $otherDevice->id,
            'description' => 'Remonts pieskirts citam adminam.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $otherAdmin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('repairs.index', ['mine' => 1]))
            ->assertOk()
            ->assertSee('DEV-MY-REPAIR')
            ->assertDontSee('DEV-OTHER-REPAIR');
    }

    public function test_room_cannot_be_deleted_while_devices_are_assigned(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'room-delete-admin@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-ROOM-BLOCK');

        $this->actingAs($admin)
            ->delete(route('rooms.destroy', $device->room_id))
            ->assertRedirect(route('rooms.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('rooms', [
            'id' => $device->room_id,
        ]);
    }

    public function test_building_cannot_be_deleted_while_rooms_or_devices_are_assigned(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'building-delete-admin@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-BUILDING-BLOCK');

        $this->actingAs($admin)
            ->delete(route('buildings.destroy', $device->building_id))
            ->assertRedirect(route('buildings.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('buildings', [
            'id' => $device->building_id,
        ]);
    }

    public function test_user_cannot_be_deleted_while_related_records_are_still_assigned(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'user-delete-admin@example.com');
        $employee = $this->createUser(role: User::ROLE_USER, email: 'user-delete-employee@example.com');
        $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-USER-DELETE-BLOCK');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $employee))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
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
            'building_name' => 'Testa eka '.$code,
            'address' => 'Adrese 1',
            'city' => 'Ludza',
            'total_floors' => 3,
            'notes' => null,
            'created_at' => now(),
        ]);

        $roomId = DB::table('rooms')->insertGetId([
            'building_id' => $buildingId,
            'floor_number' => 1,
            'room_number' => '101-'.$code,
            'room_name' => 'Testa telpa',
            'user_id' => $assignedToId,
            'department' => 'IT',
            'notes' => null,
            'created_at' => now(),
        ]);

        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Klepjdators '.$code,
            'category' => 'Datori',
            'description' => 'Tests',
            'created_at' => now(),
        ]);

        return Device::create([
            'code' => $code,
            'name' => 'Testa ierice '.$code,
            'device_type_id' => $typeId,
            'model' => 'Modelis '.$code,
            'status' => $status,
            'building_id' => $buildingId,
            'room_id' => $roomId,
            'assigned_to_id' => $assignedToId,
            'created_by' => $assignedToId,
        ]);
    }
}
