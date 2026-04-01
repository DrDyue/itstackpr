<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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

    public function test_device_type_table_is_simplified_to_single_business_field(): void
    {
        $this->assertTrue(Schema::hasColumn('device_types', 'type_name'));
        $this->assertFalse(Schema::hasColumn('device_types', 'category'));
        $this->assertFalse(Schema::hasColumn('device_types', 'description'));
        $this->assertFalse(Schema::hasColumn('device_types', 'created_at'));
        $this->assertFalse(Schema::hasColumn('device_types', 'updated_at'));
    }

    public function test_admin_cannot_delete_device_type_when_devices_are_linked(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-type-delete-admin@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-TYPE-LOCKED');
        $typeId = $device->device_type_id;

        $this->actingAs($admin)
            ->delete(route('device-types.destroy', $typeId))
            ->assertRedirect(route('device-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('device_types', [
            'id' => $typeId,
        ]);
    }

    public function test_admin_can_sort_device_types_by_linked_device_count(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-type-sort-admin@example.com');
        $firstType = DB::table('device_types')->insertGetId(['type_name' => 'Printeru tips']);
        $secondType = DB::table('device_types')->insertGetId(['type_name' => 'Monitoru tips']);

        $firstDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-TYPE-SORT-A');
        $secondDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-TYPE-SORT-B');
        $thirdDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-TYPE-SORT-C');

        DB::table('devices')->whereIn('id', [$firstDevice->id, $secondDevice->id])->update(['device_type_id' => $firstType]);
        DB::table('devices')->where('id', $thirdDevice->id)->update(['device_type_id' => $secondType]);

        $this->actingAs($admin)
            ->get(route('device-types.index', ['sort' => 'devices_count', 'direction' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Printeru tips', 'Monitoru tips']);

        $this->actingAs($admin)
            ->get(route('device-types.index', ['sort' => 'devices_count', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['Monitoru tips', 'Printeru tips']);
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
            ->assertSee('Ierīces')
            ->assertDontSee('Darbvirsma')
            ->assertSee($ownDevice->name)
            ->assertDontSee($foreignDevice->name);

        $this->actingAs($admin)
            ->get(route('my-requests.create'))
            ->assertRedirect(route('repair-requests.create'));

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

    public function test_switching_view_mode_creates_audit_entry(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'view-mode-audit@example.com');

        $this->actingAs($admin)
            ->post(route('view-mode.update'), [
                'mode' => User::VIEW_MODE_USER,
            ])
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $admin->id,
            'action' => 'SWITCH_VIEW',
            'entity_type' => 'ViewMode',
            'entity_id' => (string) $admin->id,
        ]);
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
        $this->assertStringContainsString('device-assigned-user-form-select', $content);
        $this->assertStringContainsString('device-room-form-select', $content);
        $this->assertMatchesRegularExpression("/selected:\\s*'".preg_quote((string) $admin->id, '/')."'/", $content);
        $this->assertMatchesRegularExpression("/selected:\\s*'".preg_quote((string) $warehouseRoomId, '/')."'/", $content);
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

        $this->actingAs($admin)
            ->get(route('devices.create'))
            ->assertOk();

        $this->assertTrue(Schema::hasColumn('rooms', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('buildings', 'updated_at'));
        $this->assertFalse(Schema::hasColumn('device_types', 'updated_at'));
        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Noliktava',
        ]);
    }

    public function test_admin_can_create_device_without_explicit_assignee_or_room_and_defaults_are_applied(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-store-defaults@example.com');
        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Dators',
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
        ]);

        $this->assertDatabaseMissing('rooms', [
            'room_name' => 'Noliktava',
        ]);

        $this->assertDatabaseMissing('buildings', [
            'building_name' => 'Ludzas novada pašvaldība',
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
            'building_name' => 'Ludzas novada pašvaldība',
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

    public function test_manager_can_move_active_device_to_another_room_from_devices_table_action(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-room-action@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-QUICK-ROOM');

        $targetRoomId = DB::table('rooms')->insertGetId([
            'building_id' => $device->building_id,
            'floor_number' => 2,
            'room_number' => '202-DEV-QUICK-ROOM',
            'room_name' => 'Meraparvietosanas telpa',
            'user_id' => $admin->id,
            'department' => 'Administracija',
            'notes' => null,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'room',
                'target_room_id' => $targetRoomId,
            ])
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'room_id' => $targetRoomId,
            'building_id' => $device->building_id,
        ]);
    }

    public function test_manager_can_reassign_active_device_from_devices_table_action(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-assignee-admin@example.com');
        $newAssignee = $this->createUser(role: User::ROLE_USER, email: 'device-assignee-target@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-QUICK-ASSIGNEE');

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'assignee',
                'target_assigned_to_id' => $newAssignee->id,
            ])
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'assigned_to_id' => $newAssignee->id,
        ]);
    }

    public function test_manager_cannot_move_repair_device_to_another_room_from_devices_table_action(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-room-repair-block@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-ROOM-REPAIR-BLOCK');
        $originalRoomId = $device->room_id;

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'status',
                'target_status' => Device::STATUS_REPAIR,
            ])
            ->assertRedirect(route('devices.index'));

        $targetRoomId = DB::table('rooms')->insertGetId([
            'building_id' => $device->building_id,
            'floor_number' => 3,
            'room_number' => '303-DEV-ROOM-REPAIR-BLOCK',
            'room_name' => 'Bloketa telpa',
            'user_id' => $admin->id,
            'department' => 'IT',
            'notes' => null,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'room',
                'target_room_id' => $targetRoomId,
            ])
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'room_id' => $originalRoomId,
        ]);
    }

    public function test_manager_cannot_reassign_written_off_device_from_devices_table_action(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-assignee-writeoff-block@example.com');
        $newAssignee = $this->createUser(role: User::ROLE_USER, email: 'device-assignee-writeoff-target@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_WRITEOFF, 'DEV-ASSIGNEE-WRITEOFF-BLOCK');
        $originalAssigneeId = $device->assigned_to_id;

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $device), [
                'action' => 'assignee',
                'target_assigned_to_id' => $newAssignee->id,
            ])
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'assigned_to_id' => $originalAssigneeId,
        ]);
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
            ->assertSee('Ierīces nav atrastas.')
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

    public function test_transfer_recipient_sees_pending_review_indicator_in_navigation_and_index(): void
    {
        $sender = $this->createUser(role: User::ROLE_USER, email: 'transfer-indicator-sender@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'transfer-indicator-recipient@example.com');
        $device = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-TRANSFER-ALERT');

        DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Jaskata un japienem lemums.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($recipient)
            ->get(route('device-transfers.index'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Tev ir 1 ienākošs pārsūtīšanas pieteikums', $content);
        $this->assertStringContainsString('Jāizskata: 1', $content);
        $this->assertStringContainsString('Ienākošs piedāvājums', $content);
    }

    public function test_admin_live_notifications_include_new_pending_requests(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'live-notify-admin@example.com');
        $employee = $this->createUser(role: User::ROLE_USER, email: 'live-notify-employee@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'live-notify-recipient@example.com');

        $repairDevice = $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-LIVE-REPAIR');
        $writeoffDevice = $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-LIVE-WRITEOFF');
        $transferDevice = $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-LIVE-TRANSFER');

        $repairRequest = RepairRequest::create([
            'device_id' => $repairDevice->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Nepieciesams jauns remonta pieteikums.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $writeoffRequest = WriteoffRequest::create([
            'device_id' => $writeoffDevice->id,
            'responsible_user_id' => $employee->id,
            'reason' => 'Ierice ir nolietota.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        $transfer = DeviceTransfer::create([
            'device_id' => $transferDevice->id,
            'responsible_user_id' => $employee->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Japarvieto pie cita lietotaja.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($admin)->getJson(route('live-notifications.index'));

        $response->assertOk();

        $notifications = collect($response->json('notifications'));

        $this->assertTrue($notifications->contains(fn (array $notification) => $notification['id'] === 'repair-request:'.$repairRequest->id));
        $this->assertTrue($notifications->contains(fn (array $notification) => $notification['id'] === 'writeoff-request:'.$writeoffRequest->id));
        $this->assertTrue($notifications->contains(fn (array $notification) => $notification['id'] === 'device-transfer:'.$transfer->id));
        $this->assertSame(2, count($notifications->firstWhere('id', 'repair-request:'.$repairRequest->id)['actions'] ?? []));
        $this->assertSame(2, count($notifications->firstWhere('id', 'writeoff-request:'.$writeoffRequest->id)['actions'] ?? []));
    }

    public function test_regular_user_live_notifications_show_only_incoming_transfer_requests(): void
    {
        $sender = $this->createUser(role: User::ROLE_USER, email: 'live-notify-sender@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'live-notify-target@example.com');
        $otherUser = $this->createUser(role: User::ROLE_USER, email: 'live-notify-other@example.com');

        $incomingDevice = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-LIVE-INCOMING');
        $outgoingDevice = $this->createDevice($recipient->id, Device::STATUS_ACTIVE, 'DEV-LIVE-OUTGOING');

        $incomingTransfer = DeviceTransfer::create([
            'device_id' => $incomingDevice->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Ienakoss nodosanas pieprasijums.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        DeviceTransfer::create([
            'device_id' => $outgoingDevice->id,
            'responsible_user_id' => $recipient->id,
            'transfered_to_id' => $otherUser->id,
            'transfer_reason' => 'Sis ir mana izejosa nodosana.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($recipient)->getJson(route('live-notifications.index'));

        $response->assertOk();

        $notifications = collect($response->json('notifications'));

        $this->assertCount(1, $notifications);
        $this->assertSame('incoming-transfer:'.$incomingTransfer->id, $notifications->first()['id']);
        $this->assertSame(2, count($notifications->first()['actions'] ?? []));
    }

    public function test_marking_notifications_as_read_creates_audit_entry(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'live-notify-audit-admin@example.com');
        $employee = $this->createUser(role: User::ROLE_USER, email: 'live-notify-audit-user@example.com');
        $device = $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-LIVE-AUDIT');

        RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Jāatzīmē kā lasīts.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->postJson(route('notifications.mark-all-read'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $admin->id,
            'action' => 'MARK_READ',
            'entity_type' => 'NotificationCenter',
            'entity_id' => (string) $admin->id,
        ]);
    }

    public function test_audit_log_can_filter_by_multiple_severities(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'audit-filter-admin@example.com');

        AuditLog::create([
            'timestamp' => now()->subMinutes(3),
            'user_id' => $admin->id,
            'action' => 'UPDATE',
            'entity_type' => 'Device',
            'entity_id' => 1001,
            'description' => 'Brīdinājuma ieraksts.',
            'severity' => 'warning',
        ]);

        AuditLog::create([
            'timestamp' => now()->subMinutes(2),
            'user_id' => $admin->id,
            'action' => 'DELETE',
            'entity_type' => 'Device',
            'entity_id' => 1002,
            'description' => 'Kritisks ieraksts.',
            'severity' => 'critical',
        ]);

        AuditLog::create([
            'timestamp' => now()->subMinute(),
            'user_id' => $admin->id,
            'action' => 'VIEW',
            'entity_type' => 'Device',
            'entity_id' => 1003,
            'description' => 'Informācijas ieraksts.',
            'severity' => 'info',
        ]);

        $this->actingAs($admin)
            ->get(route('audit-log.index', ['severity' => ['warning', 'critical']]))
            ->assertOk()
            ->assertSee('Brīdinājuma ieraksts.')
            ->assertSee('Kritisks ieraksts.')
            ->assertDontSee('Informācijas ieraksts.');
    }

    public function test_audit_log_find_entry_returns_second_page_result(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'audit-search-admin@example.com');

        for ($i = 1; $i <= 55; $i++) {
            AuditLog::create([
                'timestamp' => now()->subMinutes(60 - $i),
                'user_id' => $admin->id,
                'action' => 'UPDATE',
                'entity_type' => 'Device',
                'entity_id' => $i,
                'description' => $i === 1 ? 'Īpašais audita meklēšanas ieraksts' : 'Parasts audita ieraksts '.$i,
                'severity' => 'info',
            ]);
        }

        $targetId = AuditLog::query()
            ->where('description', 'Īpašais audita meklēšanas ieraksts')
            ->value('id');

        $this->actingAs($admin)
            ->getJson(route('audit-log.find-entry', [
                'lookup' => 'Īpašais audita meklēšanas ieraksts',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'page' => 2,
                'highlight_id' => 'audit-log-'.$targetId,
            ]);
    }

    public function test_audit_log_hides_unknown_entity_types_from_filters(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'audit-entity-filter-admin@example.com');

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => $admin->id,
            'action' => 'VIEW',
            'entity_type' => 'Session',
            'entity_id' => 55,
            'description' => 'Tehnisks sesijas ieraksts.',
            'severity' => 'info',
        ]);

        $this->actingAs($admin)
            ->get(route('audit-log.index'))
            ->assertOk()
            ->assertDontSee('placeholder="Visi objekti" value="Session"', false);
    }

    public function test_audit_log_shows_device_type_name_without_id_reference(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'audit-device-type-admin@example.com');
        $typeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Dokumentu skeneris',
        ]);

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => $admin->id,
            'action' => 'CREATE',
            'entity_type' => 'DeviceType',
            'entity_id' => $typeId,
            'description' => 'Ierīces tips izveidots: Dokumentu skeneris',
            'severity' => 'info',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('audit-log.index'));

        $response->assertOk()
            ->assertSee('Dokumentu skeneris')
            ->assertDontSee('Ierīces tips #'.$typeId);
    }

    public function test_admin_can_review_repair_request_via_json_endpoint(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'json-review-admin@example.com');
        $employee = $this->createUser(role: User::ROLE_USER, email: 'json-review-user@example.com');
        $device = $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-JSON-REPAIR');

        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Jaskata no toast paziņojuma.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->postJson(route('repair-requests.review', $repairRequest), [
                'status' => RepairRequest::STATUS_APPROVED,
            ])
            ->assertOk()
            ->assertJson([
                'status' => RepairRequest::STATUS_APPROVED,
                'request_id' => $repairRequest->id,
            ]);

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'status' => RepairRequest::STATUS_APPROVED,
        ]);
    }

    public function test_transfer_recipient_can_review_transfer_via_json_endpoint(): void
    {
        $sender = $this->createUser(role: User::ROLE_USER, email: 'json-transfer-sender@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'json-transfer-recipient@example.com');
        $device = $this->createDevice($sender->id, Device::STATUS_ACTIVE, 'DEV-JSON-TRANSFER');

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $sender->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Jaskata no toast paziņojuma.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($recipient)
            ->postJson(route('device-transfers.review', $transfer), [
                'status' => DeviceTransfer::STATUS_REJECTED,
            ])
            ->assertOk()
            ->assertJson([
                'status' => DeviceTransfer::STATUS_REJECTED,
                'request_id' => $transfer->id,
            ]);

        $this->assertDatabaseHas('device_transfers', [
            'id' => $transfer->id,
            'status' => DeviceTransfer::STATUS_REJECTED,
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
        $this->assertStringContainsString('Nākamā', $content);
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

    public function test_devices_index_uses_localized_pagination_labels(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'devices-pagination-user@example.com');

        foreach (range(1, 21) as $index) {
            $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-PAG-USER-'.$index);
        }

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('app-pagination', false)
            ->assertSee('Iepriekšējā')
            ->assertSee('Nākamā')
            ->assertDontSee('Showing');
    }

    public function test_my_requests_index_redirects_regular_user_to_repair_requests(): void
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
            ->assertRedirect(route('repair-requests.index'));
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
            ->assertRedirect(route('device-transfers.index'));

        $this->assertDatabaseHas('device_transfers', [
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Vienota forma nodosanai.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);
    }

    public function test_request_create_forms_show_searchable_device_details(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'request-create-search-user@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'request-create-search-recipient@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-SEARCHABLE-FORM');

        $device->update([
            'manufacturer' => 'HP',
            'model' => 'EliteDesk 800',
        ]);

        $this->actingAs($user)
            ->get(route('device-transfers.create'))
            ->assertOk()
            ->assertSee('searchable-select', false)
            ->assertSee('HP EliteDesk 800')
            ->assertSee('telpa 101-DEV-SEARCHABLE-FORM')
            ->assertSee($recipient->full_name);
    }

    public function test_admin_repair_create_form_uses_searchable_select_and_only_shows_eligible_devices(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-create-admin@example.com');
        $eligibleDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-REPAIR-ELIGIBLE');
        $writtenOffDevice = $this->createDevice($admin->id, Device::STATUS_WRITEOFF, 'DEV-REPAIR-WRITEOFF');
        $pendingRepairDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-REPAIR-PENDING');

        DB::table('repair_requests')->insert([
            'device_id' => $pendingRepairDevice->id,
            'responsible_user_id' => $admin->id,
            'description' => 'Gaida izskatisanu.',
            'status' => RepairRequest::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('repairs.create'))
            ->assertOk()
            ->assertSee('searchable-select', false)
            ->assertSee($eligibleDevice->code)
            ->assertDontSee($writtenOffDevice->code)
            ->assertDontSee($pendingRepairDevice->code);
    }

    public function test_regular_user_repair_request_create_form_only_shows_eligible_owned_devices(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-request-filter-admin@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'repair-request-filter-user@example.com');
        $otherUser = $this->createUser(role: User::ROLE_USER, email: 'repair-request-filter-other@example.com');
        $eligibleDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-FILTER-OK');
        $pendingWriteoffDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-FILTER-PENDING');
        $repairDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-FILTER-REPAIR');
        $foreignDevice = $this->createDevice($otherUser->id, Device::STATUS_ACTIVE, 'DEV-REQ-FILTER-FOREIGN');

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $repairDevice), [
                'action' => 'status',
                'target_status' => Device::STATUS_REPAIR,
            ])
            ->assertRedirect(route('devices.index'));

        DB::table('writeoff_requests')->insert([
            'device_id' => $pendingWriteoffDevice->id,
            'responsible_user_id' => $user->id,
            'reason' => 'Gaida norakstisanu.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('repair-requests.create'))
            ->assertOk()
            ->assertSee($eligibleDevice->code)
            ->assertDontSee($pendingWriteoffDevice->code)
            ->assertDontSee($repairDevice->code)
            ->assertDontSee($foreignDevice->code);
    }

    public function test_regular_user_writeoff_request_create_form_only_shows_eligible_owned_devices(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'writeoff-request-filter-admin@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'writeoff-request-filter-user@example.com');
        $otherUser = $this->createUser(role: User::ROLE_USER, email: 'writeoff-request-filter-other@example.com');
        $eligibleDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-WRITEOFF-FILTER-OK');
        $pendingTransferDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-WRITEOFF-FILTER-PENDING');
        $repairDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-WRITEOFF-FILTER-REPAIR');
        $foreignDevice = $this->createDevice($otherUser->id, Device::STATUS_ACTIVE, 'DEV-WRITEOFF-FILTER-FOREIGN');

        $this->actingAs($admin)
            ->from(route('devices.index'))
            ->post(route('devices.quick-update', $repairDevice), [
                'action' => 'status',
                'target_status' => Device::STATUS_REPAIR,
            ])
            ->assertRedirect(route('devices.index'));

        DB::table('device_transfers')->insert([
            'device_id' => $pendingTransferDevice->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $otherUser->id,
            'transfer_reason' => 'Gaida apstiprinasanu.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('writeoff-requests.create'))
            ->assertOk()
            ->assertSee($eligibleDevice->code)
            ->assertDontSee($pendingTransferDevice->code)
            ->assertDontSee($repairDevice->code)
            ->assertDontSee($foreignDevice->code);
    }

    public function test_admin_request_indexes_default_to_submitted_for_repair_and_writeoff_but_show_all_transfers(): void
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
            ->assertDontSee('Remonts apstiprinats pec noklusejuma.')
            ->assertDontSee('Remonts noraidits pec noklusejuma.');

        $this->actingAs($admin)
            ->get(route('writeoff-requests.index'))
            ->assertOk()
            ->assertSee('Norakstisana iesniegta pec noklusejuma.')
            ->assertDontSee('Norakstisana apstiprinata pec noklusejuma.')
            ->assertDontSee('Norakstisana noraidita pec noklusejuma.');

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
            ->assertRedirect(route('repair-requests.index'));

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'device_id' => $device->id,
            'description' => 'Atjaunots remonta apraksts.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_submitted_request_pages_show_edit_and_cancel_actions_for_user_view(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'request-page-actions-admin@example.com');
        $recipient = $this->createUser(role: User::ROLE_USER, email: 'request-page-actions-recipient@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-REQUEST-PAGE-ACTIONS');

        $repairRequest = RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $admin->id,
            'description' => 'Iesniegts remonta pieteikums pogu parbaudei.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $writeoffRequest = WriteoffRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $admin->id,
            'reason' => 'Iesniegts norakstisanas pieteikums pogu parbaudei.',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        $transfer = DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $admin->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Iesniegts nodosanas pieteikums pogu parbaudei.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->post(route('view-mode.update'), [
                'mode' => User::VIEW_MODE_USER,
            ])
            ->assertRedirect(route('devices.index'));

        $this->actingAs($admin)
            ->get(route('repair-requests.index'))
            ->assertOk()
            ->assertSee(route('my-requests.edit', ['requestType' => 'repair', 'requestId' => $repairRequest->id]), false)
            ->assertSee(route('my-requests.destroy', ['requestType' => 'repair', 'requestId' => $repairRequest->id]), false);

        $this->actingAs($admin)
            ->get(route('writeoff-requests.index'))
            ->assertOk()
            ->assertSee(route('my-requests.edit', ['requestType' => 'writeoff', 'requestId' => $writeoffRequest->id]), false)
            ->assertSee(route('my-requests.destroy', ['requestType' => 'writeoff', 'requestId' => $writeoffRequest->id]), false);

        $this->actingAs($admin)
            ->get(route('device-transfers.index'))
            ->assertOk()
            ->assertDontSee(route('my-requests.edit', ['requestType' => 'transfer', 'requestId' => $transfer->id]), false)
            ->assertDontSee(route('my-requests.destroy', ['requestType' => 'transfer', 'requestId' => $transfer->id]), false);
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
            ->assertRedirect(route('device-transfers.index'));

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
            ->assertRedirect(route('writeoff-requests.index'));

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
            ->assertSee('Telpas filtrs ieslēgts');
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

    public function test_devices_index_shows_all_devices_when_all_status_filters_are_selected(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-status-all-admin@example.com');
        $activeDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-STATUS-ALL-ACTIVE');
        $repairDevice = $this->createDevice($admin->id, Device::STATUS_REPAIR, 'DEV-STATUS-ALL-REPAIR');
        $writeoffDevice = $this->createDevice($admin->id, Device::STATUS_WRITEOFF, 'DEV-STATUS-ALL-WRITEOFF');

        $this->actingAs($admin)
            ->get(route('devices.index', ['status' => [
                Device::STATUS_ACTIVE,
                Device::STATUS_REPAIR,
                Device::STATUS_WRITEOFF,
            ]]))
            ->assertOk()
            ->assertSee($activeDevice->name)
            ->assertSee($repairDevice->name)
            ->assertSee($writeoffDevice->name);
    }

    public function test_devices_index_can_sort_by_code_ascending(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-sort-code-admin@example.com');
        $firstDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'LDZ-010');
        $secondDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'LDZ-002');

        $response = $this->actingAs($admin)
            ->get(route('devices.index', [
                'sort' => 'code',
                'direction' => 'asc',
            ]))
            ->assertOk();

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertLessThan(
            strpos($content, $firstDevice->code),
            strpos($content, $secondDevice->code)
        );
    }

    public function test_devices_index_code_search_matches_exact_device_code(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-code-search-admin@example.com');
        $matchingDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'LDZ-001');
        $otherDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'LDZ-001-EXTRA');

        $this->actingAs($admin)
            ->get(route('devices.index', ['code' => 'ldz-001']))
            ->assertOk()
            ->assertSee($matchingDevice->code)
            ->assertSee($otherDevice->code);
    }

    public function test_devices_index_renders_pending_request_preview_information(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-request-preview-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-PREVIEW');

        DeviceTransfer::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'transfered_to_id' => $this->createUser(role: User::ROLE_USER, email: 'device-request-preview-recipient@example.com')->id,
            'transfer_reason' => 'Japarvieto uz citu darba vietu.',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('Nodošanas pieprasījums')
            ->assertSee('Japarvieto uz citu darba vietu.')
            ->assertSee('Atvērt pieprasījumu');
    }

    public function test_devices_index_uses_amber_pending_repair_request_badge(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-pending-repair-badge-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-AMBER');

        RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Nepieciesams remonts.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('border-amber-200 bg-amber-50 text-amber-800', false);
    }

    public function test_devices_index_renders_repair_status_preview_information(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-repair-preview-admin@example.com');
        $device = $this->createDevice($admin->id, Device::STATUS_REPAIR, 'DEV-REPAIR-PREVIEW');

        Repair::create([
            'device_id' => $device->id,
            'description' => 'Maina detaļas un veic diagnostiku.',
            'status' => 'in-progress',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('Remonta ieraksts')
            ->assertSee('Maina detaļas un veic diagnostiku.')
            ->assertSee($admin->full_name)
            ->assertSee('Iekšējais');
    }

    public function test_regular_user_devices_index_shows_request_actions_for_active_device(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-request-actions-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-ACTIVE');

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee($device->name)
            ->assertSee('Pieteikt remontu')
            ->assertSee('Pieteikt norakstīšanu')
            ->assertSee('Nodot citam');
    }

    public function test_regular_user_devices_index_blocks_new_request_actions_when_pending_request_exists(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-request-pending-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-PENDING');

        RepairRequest::create([
            'device_id' => $device->id,
            'responsible_user_id' => $user->id,
            'description' => 'Gaida remontu.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee($device->name)
            ->assertSee('Pieprasījums')
            ->assertDontSee('Pieteikt remontu')
            ->assertDontSee('Pieteikt norakstīšanu')
            ->assertDontSee('Nodot citam');
    }

    public function test_regular_user_devices_index_blocks_new_request_actions_for_device_in_repair(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-request-repair-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_REPAIR, 'DEV-REQ-REPAIR');

        Repair::create([
            'device_id' => $device->id,
            'description' => 'Ierice jau ir remonta.',
            'status' => 'in-progress',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee($device->name)
            ->assertSee('Remonts')
            ->assertSee('Procesā')
            ->assertDontSee('Pieteikt remontu')
            ->assertDontSee('Pieteikt norakstīšanu')
            ->assertDontSee('Nodot citam');
    }

    public function test_dashboard_shows_request_review_link_and_repair_substatus(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'dashboard-status-admin@example.com');
        $employee = $this->createUser(role: User::ROLE_USER, email: 'dashboard-status-user@example.com');

        $pendingDevice = $this->createDevice($employee->id, Device::STATUS_ACTIVE, 'DEV-DASH-PENDING');
        $repairDevice = $this->createDevice($employee->id, Device::STATUS_REPAIR, 'DEV-DASH-REPAIR');

        RepairRequest::create([
            'device_id' => $pendingDevice->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Jaskata adminam.',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        Repair::create([
            'device_id' => $repairDevice->id,
            'description' => 'Remonts procesa.',
            'status' => 'in-progress',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression('/DEV-DASH-PENDING.*Apskatīt.*Remonts/s', $content);
        $this->assertMatchesRegularExpression('/DEV-DASH-REPAIR.*Remonts.*Procesā/s', $content);
        $this->assertStringContainsString($admin->full_name, $content);
        $this->assertStringNotContainsString('Bez gaidosa remonta', $content);
    }

    public function test_runtime_schema_syncs_device_repair_statuses_with_active_repairs(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'runtime-repair-sync-admin@example.com');

        $staleRepairDevice = $this->createDevice($admin->id, Device::STATUS_REPAIR, 'DEV-STALE-REPAIR');
        $activeRepairDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-ACTIVE-REPAIR');

        Repair::create([
            'device_id' => $activeRepairDevice->id,
            'description' => 'Aktivs remonts.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame(Device::STATUS_ACTIVE, $staleRepairDevice->fresh()->status);
        $this->assertSame(Device::STATUS_REPAIR, $activeRepairDevice->fresh()->status);
    }

    public function test_active_device_does_not_show_repair_substatus_when_device_status_is_active(): void
    {
        $user = $this->createUser(role: User::ROLE_USER, email: 'device-stale-repair-user@example.com');
        $device = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REQ-STABLE');

        Repair::create([
            'device_id' => $device->id,
            'description' => 'Vests remonta ieraksts bez statusa sinonizacijas.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee($device->name)
            ->assertDontSee('Remonta statuss:');
    }

    public function test_repairs_index_shows_related_request_link_and_requester(): void
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
            ->assertSee('Skatīt saistīto pieprasījumu')
            ->assertSee('Pieprasītājs: '.$user->full_name)
            ->assertSee('request_id='.$requestId, false);
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

    public function test_repairs_index_clear_filters_shows_all_statuses(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-clear-admin@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'repair-clear-user@example.com');

        $waitingDevice = $this->createDevice($user->id, Device::STATUS_REPAIR, 'DEV-REPAIR-WAIT');
        $completedDevice = $this->createDevice($user->id, Device::STATUS_ACTIVE, 'DEV-REPAIR-DONE');

        Repair::create([
            'device_id' => $waitingDevice->id,
            'description' => 'Gaidoss remonts notirisanas testam.',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
            'accepted_by' => $admin->id,
        ]);

        Repair::create([
            'device_id' => $completedDevice->id,
            'description' => 'Pabeigts remonts notirisanas testam.',
            'status' => 'completed',
            'repair_type' => 'internal',
            'priority' => 'high',
            'accepted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('repairs.index', ['statuses_filter' => 1]))
            ->assertOk()
            ->assertSee('DEV-REPAIR-WAIT')
            ->assertSee('DEV-REPAIR-DONE');
    }

    public function test_devices_find_by_code_returns_second_page_result(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'device-search-admin@example.com');

        for ($i = 1; $i <= 22; $i++) {
            $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-SEARCH-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($admin)
            ->withSession([User::VIEW_MODE_SESSION_KEY => User::VIEW_MODE_ADMIN])
            ->getJson(route('devices.find-by-code', [
            'code' => 'DEV-SEARCH-021',
            'sort' => 'code',
            'direction' => 'asc',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'found' => true,
                'page' => 2,
                'highlight_id' => 'device-'.Device::query()->where('code', 'DEV-SEARCH-021')->value('id'),
            ]);
    }

    public function test_repairs_find_by_code_returns_second_page_result(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'repair-search-admin@example.com');
        $user = $this->createUser(role: User::ROLE_USER, email: 'repair-search-user@example.com');

        for ($i = 1; $i <= 22; $i++) {
            $device = $this->createDevice($user->id, Device::STATUS_REPAIR, 'REP-SEARCH-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));

            Repair::create([
                'device_id' => $device->id,
                'description' => 'Meklēšanas tests '.$i,
                'status' => 'waiting',
                'repair_type' => 'internal',
                'priority' => 'medium',
                'accepted_by' => $admin->id,
            ]);
        }

        $response = $this->actingAs($admin)
            ->withSession([User::VIEW_MODE_SESSION_KEY => User::VIEW_MODE_ADMIN])
            ->getJson(route('repairs.find-by-code', [
            'code' => 'REP-SEARCH-021',
            'statuses_filter' => 1,
            'status' => ['waiting', 'in-progress'],
            'sort' => 'code',
            'direction' => 'asc',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'found' => true,
                'page' => 2,
                'highlight_id' => 'repair-'.Repair::query()
                    ->whereHas('device', fn ($query) => $query->where('code', 'REP-SEARCH-021'))
                    ->value('id'),
            ]);
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

    public function test_rooms_index_can_filter_by_floor_and_link_to_devices_in_room(): void
    {
        $admin = $this->createUser(role: User::ROLE_ADMIN, email: 'rooms-filter-admin@example.com');
        $firstDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-ROOM-LINK-ONE');
        $secondDevice = $this->createDevice($admin->id, Device::STATUS_ACTIVE, 'DEV-ROOM-LINK-TWO');

        DB::table('rooms')->where('id', $firstDevice->room_id)->update([
            'floor_number' => 3,
            'room_number' => '302',
            'room_name' => 'Rezerves telpa',
        ]);

        DB::table('rooms')->where('id', $secondDevice->room_id)->update([
            'floor_number' => 1,
            'room_number' => '105',
            'room_name' => 'Sanāksmju telpa',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('rooms.index', ['floor' => 3]));

        $response->assertOk()
            ->assertSee('Rezerves telpa')
            ->assertDontSee('Sanāksmju telpa')
            ->assertSee(route('devices.index', [
                'room_id' => $firstDevice->room_id,
                'room_query' => 'Rezerves telpa 302',
            ], false));
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
