<?php

namespace App\Http\Controllers;

use App\Support\AuditTrail;
use App\Support\DatabaseBackupService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backupService
    ) {
    }

    public function index(): View
    {
        $this->ensureAdmin();

        $settings = $this->backupService->getSettings();
        $backups = $this->backupService->paginateBackups(15);
        $allBackups = $this->backupService->allBackups();

        return view('backups.index', [
            'settings' => $settings,
            'backups' => $backups,
            'summary' => [
                'count' => $allBackups->count(),
                'latest' => $allBackups->first(),
                'current' => $allBackups->first(fn ($backup) => $backup->is_current),
                'total_size' => (int) $allBackups->sum('file_size_bytes'),
                'next_run_at' => $this->backupService->nextRunAt($settings, CarbonImmutable::now()),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $backup = $this->backupService->createBackup($request->user(), 'manual');

        return redirect()
            ->route('backups.index')
            ->with('success', 'Rezerves kopija izveidota: ' . $backup->name);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'run_at' => ['required', 'date_format:H:i'],
            'weekly_day' => ['nullable', 'integer', 'min:1', 'max:7'],
            'monthly_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        $settings = $this->backupService->updateSettings([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $validated['frequency'],
            'run_at' => $validated['run_at'] . ':00',
            'weekly_day' => (int) ($validated['weekly_day'] ?? 1),
            'monthly_day' => (int) ($validated['monthly_day'] ?? 1),
        ]);

        AuditTrail::write(
            $request->user()?->id,
            'UPDATE',
            'BackupSetting',
            'filesystem',
            'Backup schedule updated: ' . $settings->frequency . ' at ' . substr((string) $settings->run_at, 0, 5),
            'info'
        );

        return redirect()
            ->route('backups.index')
            ->with('success', 'Automatiskas kopijas grafiks saglabats.');
    }

    public function download(string $backup): StreamedResponse
    {
        $this->ensureAdmin();

        $record = $this->findBackupOrAbort($backup);

        if (! Storage::disk((string) $record->disk)->exists((string) $record->file_path)) {
            abort(404);
        }

        return Storage::disk((string) $record->disk)->download(
            (string) $record->file_path,
            basename((string) $record->file_path)
        );
    }

    public function restore(Request $request, string $backup): RedirectResponse
    {
        $this->ensureAdmin();

        try {
            $record = $this->backupService->restoreBackup($backup, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('success', 'Datubaze atjaunota no kopijas: ' . $record->name);
    }

    public function uploadAndRestore(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'max:' . (int) config('backups.max_upload_kb', 102400)],
        ]);

        try {
            $record = $this->backupService->registerUploadedBackup($validated['backup_file'], $request->user());
            $this->backupService->restoreBackup((string) $record->id, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('success', 'Fails augshupladets un datubaze atjaunota no jaunas kopijas.');
    }

    public function destroy(Request $request, string $backup): RedirectResponse
    {
        $this->ensureAdmin();

        $record = $this->findBackupOrAbort($backup);

        try {
            $this->backupService->deleteBackup($backup);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        AuditTrail::write(
            $request->user()?->id,
            'DELETE',
            'DatabaseBackup',
            (string) $record->id,
            'Backup deleted: ' . $record->name,
            'warning'
        );

        return redirect()
            ->route('backups.index')
            ->with('success', 'Rezerves kopija izdzesta.');
    }

    private function ensureAdmin(): void
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }
    }

    private function findBackupOrAbort(string $backup)
    {
        $record = $this->backupService->findBackup($backup);

        if (! $record) {
            abort(404);
        }

        return $record;
    }
}
