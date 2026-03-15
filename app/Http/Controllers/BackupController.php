<?php

namespace App\Http\Controllers;

use App\Models\BackupSetting;
use App\Models\DatabaseBackup;
use App\Support\AuditTrail;
use App\Support\DatabaseBackupService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        if (! $this->moduleReady()) {
            return view('backups.index', [
                'moduleReady' => false,
                'settings' => BackupSetting::defaultInstance(),
                'backups' => new LengthAwarePaginator([], 0, 15, 1),
                'summary' => [
                    'count' => 0,
                    'latest' => null,
                    'current' => null,
                    'total_size' => 0,
                    'next_run_at' => null,
                ],
            ]);
        }

        $settings = BackupSetting::singleton();
        $backups = DatabaseBackup::query()
            ->with('createdBy.employee')
            ->latest('created_at')
            ->paginate(15);

        $latestBackup = DatabaseBackup::query()->latest('created_at')->first();
        $currentBackup = DatabaseBackup::query()->where('is_current', true)->latest('last_restored_at')->first();
        $nextRunAt = $this->backupService->nextRunAt($settings, CarbonImmutable::now());

        return view('backups.index', [
            'moduleReady' => true,
            'settings' => $settings,
            'backups' => $backups,
            'summary' => [
                'count' => DatabaseBackup::query()->count(),
                'latest' => $latestBackup,
                'current' => $currentBackup,
                'total_size' => (int) DatabaseBackup::query()->sum('file_size_bytes'),
                'next_run_at' => $nextRunAt,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $notReady = $this->redirectIfModuleNotReady();

        if ($notReady) {
            return $notReady;
        }

        $backup = $this->backupService->createBackup($request->user(), 'manual');

        return redirect()
            ->route('backups.index')
            ->with('success', 'Rezerves kopija izveidota: ' . $backup->name);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $notReady = $this->redirectIfModuleNotReady();

        if ($notReady) {
            return $notReady;
        }

        $validated = $request->validate([
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'run_at' => ['required', 'date_format:H:i'],
            'weekly_day' => ['nullable', 'integer', 'min:1', 'max:7'],
            'monthly_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        $settings = BackupSetting::singleton();
        $settings->forceFill([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $validated['frequency'],
            'run_at' => $validated['run_at'] . ':00',
            'weekly_day' => (int) ($validated['weekly_day'] ?? $settings->weekly_day ?: 1),
            'monthly_day' => (int) ($validated['monthly_day'] ?? $settings->monthly_day ?: 1),
        ])->save();

        AuditTrail::write(
            $request->user()?->id,
            'UPDATE',
            'BackupSetting',
            (string) $settings->id,
            'Backup schedule updated: ' . $settings->frequency . ' at ' . substr((string) $settings->run_at, 0, 5),
            'info'
        );

        return redirect()
            ->route('backups.index')
            ->with('success', 'Automatiskas kopijas grafiks saglabats.');
    }

    public function download(DatabaseBackup $backup): StreamedResponse
    {
        $this->ensureAdmin();

        if (! Storage::disk($backup->disk)->exists($backup->file_path)) {
            abort(404);
        }

        return Storage::disk($backup->disk)->download(
            $backup->file_path,
            basename($backup->file_path)
        );
    }

    public function restore(Request $request, DatabaseBackup $backup): RedirectResponse
    {
        $this->ensureAdmin();
        $notReady = $this->redirectIfModuleNotReady();

        if ($notReady) {
            return $notReady;
        }

        try {
            $this->backupService->restoreBackup($backup, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('success', 'Datubaze atjaunota no kopijas: ' . $backup->name);
    }

    public function uploadAndRestore(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $notReady = $this->redirectIfModuleNotReady();

        if ($notReady) {
            return $notReady;
        }

        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'max:' . (int) config('backups.max_upload_kb', 102400)],
        ]);

        try {
            $backup = $this->backupService->registerUploadedBackup($validated['backup_file'], $request->user());
            $this->backupService->restoreBackup($backup, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('success', 'Fails augshupladets un datubaze atjaunota no jaunas kopijas.');
    }

    public function destroy(Request $request, DatabaseBackup $backup): RedirectResponse
    {
        $this->ensureAdmin();
        $notReady = $this->redirectIfModuleNotReady();

        if ($notReady) {
            return $notReady;
        }

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
            (string) $backup->id,
            'Backup deleted: ' . $backup->name,
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

    private function moduleReady(): bool
    {
        return BackupSetting::tableExists() && DatabaseBackup::tableExists();
    }

    private function redirectIfModuleNotReady(): ?RedirectResponse
    {
        if ($this->moduleReady()) {
            return null;
        }

        return redirect()
            ->route('backups.index')
            ->with('error', 'Backup modulis vel nav aktivets. Palaidiet jaunakas migracijas produkcijas datubaze.');
    }
}
