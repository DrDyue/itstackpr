<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupService;
use Illuminate\Console\Command;

class RunScheduledBackups extends Command
{
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Create database backups using the configured backup schedule';

    public function handle(DatabaseBackupService $backupService): int
    {
        $settings = $backupService->getSettings();
        $scheduledAt = $backupService->dueScheduledRun($settings);

        if (! $scheduledAt) {
            $this->line('No scheduled backup is due right now.');

            return self::SUCCESS;
        }

        $backup = $backupService->createBackup(null, 'scheduled');
        $backupService->markScheduledRun($scheduledAt);

        $this->info('Scheduled backup created: #' . $backup->id);

        return self::SUCCESS;
    }
}
