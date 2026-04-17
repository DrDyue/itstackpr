<?php

namespace Tests\Feature\Smoke;

use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTableModalRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_create_route_redirects_to_user_create_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertRedirect(route('users.index', ['user_modal' => 'create']));
    }

    public function test_user_edit_route_redirects_to_user_edit_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $managedUser = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($admin)->get(route('users.edit', $managedUser));

        $response->assertRedirect(route('users.index', [
            'user_modal' => 'edit',
            'modal_user' => $managedUser->id,
        ]));
    }

    public function test_building_create_route_redirects_to_building_create_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('buildings.create'));

        $response->assertRedirect(route('buildings.index', ['building_modal' => 'create']));
    }

    public function test_device_type_create_route_redirects_to_device_type_create_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('device-types.create'));

        $response->assertRedirect(route('device-types.index', ['device_type_modal' => 'create']));
    }

    public function test_device_type_edit_route_redirects_to_device_type_edit_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $deviceTypeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Editējams tips',
        ]);

        $response = $this->actingAs($admin)->get(route('device-types.edit', $deviceTypeId));

        $response->assertRedirect(route('device-types.index', [
            'device_type_modal' => 'edit',
            'modal_device_type' => $deviceTypeId,
        ]));
    }

    public function test_room_create_route_redirects_to_room_create_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('rooms.create'));

        $response->assertRedirect(route('rooms.index', ['room_modal' => 'create']));
    }

    public function test_repair_create_route_redirects_to_repair_create_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('repairs.create'));

        $response->assertRedirect(route('repairs.index', ['repair_modal' => 'create']));
    }

    public function test_repair_edit_route_redirects_to_repair_edit_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $deviceTypeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Remonta tips',
        ]);
        $buildingId = DB::table('buildings')->insertGetId([
            'building_name' => 'Administrācijas ēka',
            'address' => null,
            'city' => 'Ludza',
            'total_floors' => 3,
            'notes' => '',
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'building_id' => $buildingId,
            'floor_number' => 1,
            'room_number' => '101',
            'room_name' => 'IT',
            'user_id' => $admin->id,
            'department' => 'IT',
            'notes' => null,
            'created_at' => now(),
        ]);

        $device = Device::query()->create([
            'code' => 'REPAIR-MODAL-001',
            'name' => 'Remonta tests',
            'device_type_id' => $deviceTypeId,
            'model' => 'Test Model',
            'status' => Device::STATUS_REPAIR,
            'building_id' => $buildingId,
            'room_id' => $roomId,
            'assigned_to_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $repair = Repair::query()->create([
            'device_id' => $device->id,
            'issue_reported_by' => $admin->id,
            'accepted_by' => $admin->id,
            'description' => 'Tests',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($admin)->get(route('repairs.edit', $repair));

        $response->assertRedirect(route('repairs.index', [
            'repair_modal' => 'edit',
            'modal_repair' => $repair->id,
        ]));
    }
}
