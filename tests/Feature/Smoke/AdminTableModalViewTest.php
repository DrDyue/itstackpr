<?php

namespace Tests\Feature\Smoke;

use App\Models\DeviceType;
use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableModalViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_contains_create_modal_when_requested(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('users.index', ['user_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('user-create-modal', false);
    }

    public function test_buildings_index_contains_create_modal_when_requested(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('buildings.index', ['building_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('building-create-modal', false);
    }

    public function test_rooms_index_contains_create_modal_when_requested(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('rooms.index', ['room_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('room-create-modal', false);
    }

    public function test_repairs_index_contains_create_modal_when_requested(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('repairs.index', ['repair_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('repair-create-modal', false);
    }

    public function test_repairs_index_contains_selected_edit_modal_when_requested(): void
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
            'code' => 'REPAIR-VIEW-001',
            'name' => 'Remonta skats',
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

        $response = $this->actingAs($admin)->get(route('repairs.index', [
            'repair_modal' => 'edit',
            'modal_repair' => $repair->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('repair-edit-modal-' . $repair->id, false);
    }

    public function test_device_types_index_contains_create_modal_when_requested(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('device-types.index', ['device_type_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('device-type-create-modal', false);
    }

    public function test_device_types_index_contains_selected_edit_modal_when_requested(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $deviceType = DeviceType::query()->create(['type_name' => 'Monitors']);

        $response = $this->actingAs($admin)->get(route('device-types.index', [
            'device_type_modal' => 'edit',
            'modal_device_type' => $deviceType->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('device-type-edit-modal-' . $deviceType->id, false);
    }
}
