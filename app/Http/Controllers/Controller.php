<?php

namespace App\Http\Controllers;

use App\Models\User;

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
}
