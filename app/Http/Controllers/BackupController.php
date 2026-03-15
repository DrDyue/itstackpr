<?php

namespace App\Http\Controllers;

use App\Support\AuditTrail;
use App\Support\DatabaseBackupService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
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
        $allBackups = $this->backupService->allBackups();
        $filters = [
            'q' => trim((string) request()->query('q', '')),
            'trigger' => trim((string) request()->query('trigger', '')),
            'creator_scope' => trim((string) request()->query('creator_scope', '')),
            'creator_name' => trim((string) request()->query('creator_name', '')),
            'date_from' => trim((string) request()->query('date_from', '')),
            'date_to' => trim((string) request()->query('date_to', '')),
        ];

        $filteredBackups = $allBackups
            ->filter(function ($backup) use ($filters) {
                if ($filters['q'] !== '') {
                    $haystack = mb_strtolower(trim($backup->name . ' ' . ($backup->database_name ?? '')));
                    if (! str_contains($haystack, mb_strtolower($filters['q']))) {
                        return false;
                    }
                }

                if ($filters['trigger'] !== '' && $backup->trigger_type !== $filters['trigger']) {
                    return false;
                }

                if ($filters['creator_scope'] === 'system' && $backup->creator_type !== 'system') {
                    return false;
                }

                if ($filters['creator_scope'] === 'user' && $backup->creator_type !== 'user') {
                    return false;
                }

                if ($filters['creator_name'] !== '') {
                    $creator = mb_strtolower((string) ($backup->created_by_name ?? ''));
                    if (! str_contains($creator, mb_strtolower($filters['creator_name']))) {
                        return false;
                    }
                }

                if ($filters['date_from'] !== '' && $backup->created_at?->lt(CarbonImmutable::parse($filters['date_from'])->startOfDay())) {
                    return false;
                }

                if ($filters['date_to'] !== '' && $backup->created_at?->gt(CarbonImmutable::parse($filters['date_to'])->endOfDay())) {
                    return false;
                }

                return true;
            })
            ->values();

        $backups = $this->paginateCollection($filteredBackups, 15);

        return view('backups.index', [
            'settings' => $settings,
            'backups' => $backups->appends(request()->query()),
            'filters' => $filters,
            'summary' => [
                'count' => $allBackups->count(),
                'latest' => $allBackups->first(),
                'current' => $allBackups->first(fn ($backup) => $backup->is_current),
                'total_size' => (int) $allBackups->sum('file_size_bytes'),
                'next_run_at' => $this->backupService->nextRunAt($settings, CarbonImmutable::now()),
                'scheduled_count' => $allBackups->where('trigger_type', 'scheduled')->count(),
                'manual_count' => $allBackups->where('trigger_type', 'manual')->count(),
                'uploaded_count' => $allBackups->where('trigger_type', 'uploaded')->count(),
                'filtered_count' => $filteredBackups->count(),
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

    private function paginateCollection($items, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage('page');

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
