<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

abstract class Controller
{
    protected function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected function requireAdmin(): User
    {
        $user = $this->user();

        abort_unless($user?->isAdmin(), 403);

        return $user;
    }

    protected function requireManager(): User
    {
        $user = $this->user();

        abort_unless($user?->canManageRequests(), 403);

        return $user;
    }

    protected function featureTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function emptyPaginator(int $perPage = 20): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            Paginator::resolveCurrentPage('page'),
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
