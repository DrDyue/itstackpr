<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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

        User::create([
            'full_name' => 'Temporary User',
            'email' => 'temp@example.com',
            'password' => Hash::make('secret123'),
            'role' => User::ROLE_USER,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', ['email' => 'temp@example.com']);

        app(DatabaseBackupService::class)->restoreBackup((string) $backup->id, $user);
        DB::purge();
        DB::reconnect();
        $this->assertDatabaseMissing('users', ['email' => 'temp@example.com']);
        $storedBackup = app(DatabaseBackupService::class)->findBackup((string) $backup->id);

        $this->assertNotNull($storedBackup);
        $this->assertTrue($storedBackup->is_current);
        $this->assertSame(1, $storedBackup->restore_count);

        try {
            app(DatabaseBackupService::class)->deleteBackup((string) $backup->id);
            $this->fail('Active restored backup should not be deletable.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Aktivo atjaunoto kopiju dzest nedrikst.', $exception->getMessage());
        }

        $this->assertNotNull(app(DatabaseBackupService::class)->findBackup((string) $backup->id));
    }

    private function createAdminUser(): User
    {
        return User::create([
            'full_name' => 'Backup Admin',
            'email' => 'backup-admin@example.com',
            'password' => Hash::make('secret123'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }
}
