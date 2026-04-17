<?php

namespace Tests\Feature\Smoke;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceModalValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_devices_index_opens_create_modal_from_query_parameter(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('devices.index', [
            'device_modal' => 'create',
        ]));

        $response->assertOk();
        $response->assertSee("detail: 'device-create-modal'", false);
        $response->assertSee('Jauna ierīce');
    }

    public function test_invalid_create_returns_to_create_modal_with_validation_summary(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('devices.index', ['device_modal' => 'create']))
            ->followingRedirects()
            ->post(route('devices.store'), [
                'modal_form' => 'device_create',
                'code' => '',
                'name' => '',
                'device_type_id' => '',
                'model' => '',
            ]);

        $response->assertOk();
        $response->assertSee('Neizdevās izveidot ierīci');
        $response->assertSee("detail: 'device-create-modal'", false);
        $response->assertSee('Kas jāizlabo');
    }

    public function test_invalid_edit_returns_to_correct_edit_modal_with_validation_summary(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $deviceTypeId = DB::table('device_types')->insertGetId([
            'type_name' => 'Testa tips',
        ]);

        $device = Device::query()->create([
            'code' => 'TEST-MODAL-VAL-001',
            'name' => 'Validācijas ierīce',
            'device_type_id' => $deviceTypeId,
            'model' => 'Validācijas modelis',
            'status' => Device::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('devices.index', [
                'device_modal' => 'edit',
                'modal_device' => $device->id,
            ]))
            ->followingRedirects()
            ->put(route('devices.update', $device), [
                'modal_form' => 'device_edit_'.$device->id,
                'code' => $device->code,
                'name' => $device->name,
                'device_type_id' => $device->device_type_id,
                'model' => $device->model,
                'status' => Device::STATUS_ACTIVE,
                'purchase_date' => '2026-04-17',
                'warranty_until' => '2026-04-01',
            ]);

        $response->assertOk();
        $response->assertSee('Neizdevās saglabāt ierīces izmaiņas');
        $response->assertSee("detail: 'device-edit-modal-{$device->id}'", false);
        $response->assertSee('Kas jāizlabo');
    }
}
