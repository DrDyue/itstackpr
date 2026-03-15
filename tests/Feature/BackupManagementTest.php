<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Support\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_manual_backup(): void
    {
        Storage::fake('local');

        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('backups.store'));

        $response->assertRedirect(route('backups.index'));
        $backups = app(DatabaseBackupService::class)->allBackups();
        $backup = $backups->first();

        $this->assertNotNull($backup);
        $this->assertCount(1, $backups);
        $this->assertSame('manual', $backup->trigger_type);
        $this->assertSame('user', $backup->creator_type);
        $this->assertSame($user->id, $backup->created_by_user_id);
        Storage::disk('local')->assertExists($backup->file_path);
    }

    public function test_restoring_backup_marks_it_current_and_blocks_deletion(): void
    {
        Storage::fake('local');

        $user = $this->createAdminUser();
        $backup = app(DatabaseBackupService::class)->createBackup($user, 'manual');

        Employee::create([
            'full_name' => 'Temporary Employee',
            'email' => 'temp@example.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('employees', ['email' => 'temp@example.com']);

        $restoreResponse = $this->actingAs($user)->post(route('backups.restore', ['backup' => $backup->id]));

        $restoreResponse->assertRedirect(route('backups.index'));
        $this->assertDatabaseMissing('employees', ['email' => 'temp@example.com']);
        $storedBackup = app(DatabaseBackupService::class)->findBackup((string) $backup->id);

        $this->assertNotNull($storedBackup);
        $this->assertTrue($storedBackup->is_current);
        $this->assertSame(1, $storedBackup->restore_count);

        $deleteResponse = $this->actingAs(User::query()->findOrFail($user->id))
            ->delete(route('backups.destroy', ['backup' => $backup->id]));

        $deleteResponse->assertRedirect(route('backups.index'));
        $deleteResponse->assertSessionHas('error');
        $this->assertNotNull(app(DatabaseBackupService::class)->findBackup((string) $backup->id));
    }

    private function createAdminUser(): User
    {
        $employee = Employee::create([
            'full_name' => 'Backup Admin',
            'email' => 'backup-admin@example.com',
            'is_active' => true,
        ]);

        return User::create([
            'employee_id' => $employee->id,
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
