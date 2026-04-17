<?php

namespace Tests\Feature\Smoke;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceModalRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_route_redirects_to_device_create_modal(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('devices.create'));

        $response->assertRedirect(route('devices.index', [
            'device_modal' => 'create',
        ]));
    }

    public function test_admin_edit_route_redirects_to_device_edit_modal(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $deviceTypeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Testa tips',
        ]);

        $device = Device::query()->create([
            'code' => 'TEST-REDIRECT-001',
            'name' => 'Testa ierīce',
            'device_type_id' => $deviceTypeId,
            'model' => 'Redirect Model',
            'status' => Device::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('devices.edit', $device));

        $response->assertRedirect(route('devices.index', [
            'device_modal' => 'edit',
            'modal_device' => $device->id,
        ]));
    }
}
