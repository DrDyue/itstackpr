<?php

namespace Tests\Feature\Smoke;

use App\Models\DeviceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTypeModalValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_create_redirect_keeps_create_modal_context(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this
            ->from(route('device-types.index', ['device_type_modal' => 'create']))
            ->actingAs($admin)
            ->post(route('device-types.store'), [
                'type_name' => '',
                'modal_form' => 'device_type_create',
            ]);

        $response->assertRedirect(route('device-types.index', ['device_type_modal' => 'create']));
        $response->assertSessionHasErrors(['type_name']);
    }

    public function test_duplicate_edit_redirect_keeps_edit_modal_context(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $existing = DeviceType::query()->create(['type_name' => 'Dators']);
        $editable = DeviceType::query()->create(['type_name' => 'Monitors']);

        $response = $this
            ->from(route('device-types.index', [
                'device_type_modal' => 'edit',
                'modal_device_type' => $editable->id,
            ]))
            ->actingAs($admin)
            ->put(route('device-types.update', $editable), [
                'type_name' => $existing->type_name,
                'modal_form' => 'device_type_edit_' . $editable->id,
            ]);

        $response->assertRedirect(route('device-types.index', [
            'device_type_modal' => 'edit',
            'modal_device_type' => $editable->id,
        ]));
        $response->assertSessionHasErrors(['type_name']);
    }
}
