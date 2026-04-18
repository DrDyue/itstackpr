<?php

namespace Tests\Feature\Smoke;

use App\Models\Device;
use App\Models\DeviceTransfer;
use App\Models\DeviceType;
use App\Models\Repair;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WriteoffRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTableModalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_request_create_route_redirects_to_index_modal(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($employee)->get(route('repair-requests.create'));

        $response->assertRedirect(route('repair-requests.index', ['repair_request_modal' => 'create']));
    }

    public function test_writeoff_request_create_route_redirects_to_index_modal(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($employee)->get(route('writeoff-requests.create'));

        $response->assertRedirect(route('writeoff-requests.index', ['writeoff_request_modal' => 'create']));
    }

    public function test_device_transfer_create_route_redirects_to_index_modal(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($employee)->get(route('device-transfers.create'));

        $response->assertRedirect(route('device-transfers.index', ['device_transfer_modal' => 'create']));
    }

    public function test_repair_requests_index_contains_modal_when_requested(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($employee)->get(route('repair-requests.index', ['repair_request_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('request-form-repair', false);
    }

    public function test_writeoff_requests_index_contains_modal_when_requested(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($employee)->get(route('writeoff-requests.index', ['writeoff_request_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('request-form-writeoff', false);
    }

    public function test_device_transfers_index_contains_modal_when_requested(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($employee)->get(route('device-transfers.index', ['device_transfer_modal' => 'create']));

        $response
            ->assertOk()
            ->assertSee('request-form-transfer', false);
    }

    public function test_my_requests_edit_redirects_repair_requests_to_edit_modal(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $repairRequest = RepairRequest::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Nedarbojas ekrāns',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($employee)->get(route('my-requests.edit', [
            'requestType' => 'repair',
            'requestId' => $repairRequest->id,
        ]));

        $response->assertRedirect(route('repair-requests.index', [
            'repair_request_modal' => 'edit',
            'modal_request' => $repairRequest->id,
        ]));
    }

    public function test_my_requests_edit_redirects_writeoff_requests_to_edit_modal(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $writeoffRequest = WriteoffRequest::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'reason' => 'Ierīce vairs nav lietojama',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($employee)->get(route('my-requests.edit', [
            'requestType' => 'writeoff',
            'requestId' => $writeoffRequest->id,
        ]));

        $response->assertRedirect(route('writeoff-requests.index', [
            'writeoff_request_modal' => 'edit',
            'modal_request' => $writeoffRequest->id,
        ]));
    }

    public function test_my_requests_edit_redirects_transfer_requests_to_edit_modal(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $recipient = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $transfer = DeviceTransfer::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Jānodod kolēģim',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($employee)->get(route('my-requests.edit', [
            'requestType' => 'transfer',
            'requestId' => $transfer->id,
        ]));

        $response->assertRedirect(route('device-transfers.index', [
            'device_transfer_modal' => 'edit',
            'modal_request' => $transfer->id,
        ]));
    }

    public function test_repair_requests_index_contains_edit_modal_when_requested(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $repairRequest = RepairRequest::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Skaņa nestrādā',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($employee)->get(route('repair-requests.index', [
            'repair_request_modal' => 'edit',
            'modal_request' => $repairRequest->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('repair-request-edit-'.$repairRequest->id, false);
    }

    public function test_writeoff_requests_index_contains_edit_modal_when_requested(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $writeoffRequest = WriteoffRequest::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'reason' => 'Fiziski bojāta',
            'status' => WriteoffRequest::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($employee)->get(route('writeoff-requests.index', [
            'writeoff_request_modal' => 'edit',
            'modal_request' => $writeoffRequest->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('writeoff-request-edit-'.$writeoffRequest->id, false);
    }

    public function test_device_transfers_index_contains_edit_modal_when_requested(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $recipient = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $transfer = DeviceTransfer::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'transfered_to_id' => $recipient->id,
            'transfer_reason' => 'Nodošana projektam',
            'status' => DeviceTransfer::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($employee)->get(route('device-transfers.index', [
            'device_transfer_modal' => 'edit',
            'modal_request' => $transfer->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('transfer-request-edit-'.$transfer->id, false);
    }

    public function test_approving_repair_request_redirects_to_repair_edit_modal(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_USER]);
        $device = $this->createDeviceFor($employee);
        $repairRequest = RepairRequest::query()->create([
            'device_id' => $device->id,
            'responsible_user_id' => $employee->id,
            'description' => 'Nepieciešams remonts',
            'status' => RepairRequest::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($manager)->post(route('repair-requests.review', $repairRequest), [
            'status' => RepairRequest::STATUS_APPROVED,
        ]);

        $createdRepair = Repair::query()->where('request_id', $repairRequest->id)->first();

        $this->assertNotNull($createdRepair);
        $response->assertRedirect(route('repairs.index', [
            'repair_modal' => 'edit',
            'modal_repair' => $createdRepair->id,
        ]));
    }

    private function createDeviceFor(User $owner): Device
    {
        $deviceType = DeviceType::query()->create([
            'type_name' => 'Tests-'.$owner->id.'-'.uniqid(),
        ]);

        return Device::query()->create([
            'code' => 'DEV-'.$owner->id.'-'.uniqid(),
            'name' => 'Testa ierīce',
            'device_type_id' => $deviceType->id,
            'model' => 'Model X',
            'status' => Device::STATUS_ACTIVE,
            'assigned_to_id' => $owner->id,
        ]);
    }
}
