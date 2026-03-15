<?php

namespace App\Console\Commands;

use App\Models\BackupSetting;
use App\Support\DatabaseBackupService;
use Illuminate\Console\Command;

class RunScheduledBackups extends Command
{
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Create database backups using the configured backup schedule';

    public function handle(DatabaseBackupService $backupService): int
    {
        $settings = BackupSetting::singleton();
        $scheduledAt = $backupService->dueScheduledRun($settings);

        if (! $scheduledAt) {
            $this->line('No scheduled backup is due right now.');

            return self::SUCCESS;
        }

        $backup = $backupService->createBackup(null, 'scheduled');

        $settings->forceFill([
            'last_scheduled_backup_at' => now(),
        ])->save();

        $this->info('Scheduled backup created: #' . $backup->id);

        return self::SUCCESS;
    }
}
