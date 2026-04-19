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

    public function test_repair_show_route_redirects_to_repair_edit_modal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $device = $this->createRepairDeviceFor($admin, 'REPAIR-SHOW-001', Device::STATUS_REPAIR);
        $repair = Repair::query()->create([
            'device_id' => $device->id,
            'issue_reported_by' => $admin->id,
            'accepted_by' => $admin->id,
            'description' => 'Tests',
            'status' => 'waiting',
            'repair_type' => 'internal',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($admin)->get(route('repairs.show', $repair));

        $response->assertRedirect(route('repairs.index', [
            'repair_modal' => 'edit',
            'modal_repair' => $repair->id,
        ]));
    }

    public function test_device_quick_update_repair_redirects_to_repair_modal_flow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $device = $this->createRepairDeviceFor($admin, 'DEVICE-QUICK-REPAIR-001', Device::STATUS_ACTIVE);

        $response = $this->actingAs($admin)->post(route('devices.quick-update', $device), [
            'action' => 'status',
            'target_status' => Device::STATUS_REPAIR,
        ]);

        $response->assertRedirect(route('repairs.index', [
            'repair_modal' => 'create',
            'device_id' => $device->id,
        ]));
    }

    private function createRepairDeviceFor(User $admin, string $code, string $status): Device
    {
        $deviceTypeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Remonta tips '.uniqid(),
        ]);
        $buildingId = DB::table('buildings')->insertGetId([
            'building_name' => 'Administrācijas ēka '.uniqid(),
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

        return Device::query()->create([
            'code' => $code,
            'name' => 'Remonta tests',
            'device_type_id' => $deviceTypeId,
            'model' => 'Test Model',
            'status' => $status,
            'building_id' => $buildingId,
            'room_id' => $roomId,
            'assigned_to_id' => $admin->id,
            'created_by' => $admin->id,
        ]);
    }
}
